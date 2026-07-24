<?php

/**
 * helper для локального master json кеша
 * обертка над GameQ helper чтобы в cron не было прямого require_once
 *
 * @version 1.0.10
 * @author bySanya@
 */
class master_cache_helper extends GlobalLoader
{
	/**
	 * Локальный singleton для доступа к GlobalLoader в static контексте
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Возвращает singleton helper
	 *
	 * @return self
	 */
	private static function get_instance(): self
	{
		if( self::$instance === null )
			self::$instance = new self( );

		return self::$instance;
	}

	private static function get_master_sources( string $game ) :array
	{
		return GAMES_CONFIG[$game]['master']['master_json'] ?? [];
	}

	private static function get_preload_config( string $game ) :array
	{
		return GAMES_CONFIG[$game]['master']['preload'] ?? [];
	}

	private static function get_preload_ttl( string $game, int $default = 180 ) :int
	{
		return (int)(self::get_preload_config($game)['ttl'] ?? $default);
	}

	private static function get_cache_file_key( string $game, string $default = '' ) :string
	{
		return GAMES_CONFIG[$game]['master']['cache_file_key'] ?? $default;
	}

	private static function is_cache_required( string $game ) :bool
	{
		return !empty(GAMES_CONFIG[$game]['master']['cache_required']);
	}

	private static function is_debug_log_enabled( string $game ) :bool
	{
		return !empty(GAMES_CONFIG[$game]['master']['debug_log']);
	}

	/**
	 * Флаг предзагрузки preload по играм в рамках одного запуска
	 *
	 * @var array<string, bool>
	 */
	private static $preload_primed_games = [];

	/**
	 * Состояние preload по играм в рамках одного запуска
	 *
	 * @var array<string, array>
	 */
	private static $preload_ready_status = [];

	/**
	 * Кеш индексов preload файлов по сигнатуре
	 *
	 * @var array<string, array>
	 */
	private static $preload_index_cache = [];

	/**
	 * Активные lock-хендлы по cache_key
	 *
	 * @var array<string, mixed>
	 */
	private static $refresh_locks = [];

	/**
	 * Безопасно декодирует JSON в массив
	 *
	 * @param string $raw
	 * @return array|null
	 */
	private static function decode_json_array( string $raw )
	{
		$helper = self::get_instance()->jsnh;
		if( !is_object($helper) || !method_exists($helper, 'decode') )
			return null;

		$data = $helper->decode($raw, true);

		return (is_array($data) ? $data : null);
	}

	/**
	 * Единая нормализация сырого master/server ряда
	 *
	 * Правило по addr:
	 * - SCUM: addr = ip:query_port
	 * - остальные: addr = ip:game_port
	 *
	 * @param array $raw
	 * @param string $force_game
	 * @return array|null
	 */
	public static function normalize_master_server_row( array $raw, string $force_game = '' )
	{
		$preserve_provided_info = !empty($raw['_preserve_provided_info']);
		$provided_info = (is_array($raw['info'] ?? null) ? $raw['info'] : []);
		$provided_info_raw = (is_array($raw['info_raw'] ?? null) ? $raw['info_raw'] : []);
		$passthrough = [];

		unset($raw['_preserve_provided_info']);

		foreach( ['password', 'version', 'description'] as $field )
		{
			if( $preserve_provided_info && array_key_exists($field, $raw) )
				$passthrough[$field] = $raw[$field];
		}

		# если сюда прилетел уже result с info_raw, сперва возвращаем его в raw-вид
		if( !empty($raw['info_raw']) && is_array($raw['info_raw']) )
		{
			$raw = array_replace($raw['info_raw'], $raw);
			unset($raw['info'], $raw['info_raw'], $raw['players_count_arr'], $raw['changed_addr']);
		}

		$game = strtolower(trim((string)($force_game !== '' ? $force_game : ($raw['game'] ?? $raw['gamedir'] ?? ''))));

		$addr = (string)($raw['addr'] ?? '');
		[ $addr_ip, $addr_port ] = explode(':', $addr, 2) + [ '', 0 ];

		$ip = strtolower(trim((string)($raw['ip'] ?? $raw['gq_address'] ?? $addr_ip)));
		if( $ip === '' )
			return null;

		$query_port = (int)($raw['query_port'] ?? $raw['gq_port_query'] ?? 0);
		$game_port = (int)($raw['port'] ?? $raw['gameport'] ?? $raw['clientport'] ?? $raw['gq_port_client'] ?? 0);

		if( $query_port < 1 && (int)$addr_port > 0 )
			$query_port = (int)$addr_port;

		if( $game === 'scum' )
		{
			if( $query_port < 1 && $game_port > 1 )
				$query_port = $game_port + 2;

			if( $game_port < 1 && $query_port > 1 )
				$game_port = $query_port - 2;

			if( $query_port < 1 )
				return null;
		}
		else
		{
			if( $game_port < 1 && $query_port > 0 )
				$game_port = $query_port;

			if( $query_port < 1 && $game_port > 0 )
				$query_port = $game_port;
		}

		if( $game_port < 1 )
			return null;

		$addr_port_out = ($game === 'scum' ? $query_port : $game_port);
		$addr_out = $ip . ':' . $addr_port_out;

		$raw['addr'] = $addr_out;
		$raw['ip'] = $ip;
		$raw['gameport'] = $game_port;
		$raw['port'] = $game_port;
		$raw['clientport'] = $game_port;
		$raw['gq_port_query'] = $query_port;
		$raw['query_port'] = $query_port;

		# Для Steam-style рядов подмешиваем ключи которые ожидает query_helper
		if( !isset($raw['gq_hostname']) && !empty($raw['name']) )
			$raw['gq_hostname'] = (string)$raw['name'];

		if( !isset($raw['hostname']) && !empty($raw['name']) )
			$raw['hostname'] = (string)$raw['name'];

		if( !isset($raw['gq_numplayers']) && isset($raw['players']) && is_scalar($raw['players']) )
			$raw['gq_numplayers'] = (int)$raw['players'];

		if( !isset($raw['numplayers']) && isset($raw['players']) && is_scalar($raw['players']) )
			$raw['numplayers'] = (int)$raw['players'];

		if( !isset($raw['gq_maxplayers']) && isset($raw['max_players']) && is_scalar($raw['max_players']) )
			$raw['gq_maxplayers'] = (int)$raw['max_players'];

		if( !isset($raw['maxplayers']) && isset($raw['max_players']) && is_scalar($raw['max_players']) )
			$raw['maxplayers'] = (int)$raw['max_players'];

		if( !isset($raw['keywords']) && !empty($raw['gametype']) )
			$raw['keywords'] = (string)$raw['gametype'];

		if( $game !== '' && empty($raw['game']) )
			$raw['game'] = $game;

		static $gqh = null;

		if( !$gqh instanceof game_query_helper )
			$gqh = new game_query_helper( );

		$normalized = $gqh->get_normalized_server([$addr_out => $raw]);

		if( !is_array($normalized) || empty($normalized['addr']) )
			return null;

		if( $preserve_provided_info && !empty($provided_info) )
			$normalized['info'] = array_replace((array)($normalized['info'] ?? []), $provided_info);

		if( $preserve_provided_info && !empty($provided_info_raw) )
			$normalized['info_raw'] = $provided_info_raw;

		foreach( $passthrough as $field => $value )
			$normalized[$field] = $value;

		return $normalized;
	}

	/**
	 * Собирает preload-конфиг игр из общего реестра
	 *
	 * @return array<string, array>
	 */
	private static function preload_resolvers(): array
	{
		$resolvers = [];

		foreach( array_keys(GAMES_CONFIG) as $game )
		{
			$preload = self::get_preload_config($game);

			if( !empty($preload) )
				$resolvers[$game] = $preload;
		}

		return $resolvers;
	}

	/**
	 * Instance proxy: проверяет preload-резолвер
	 *
	 * @param string $game
	 * @return bool
	 */
	public function has_preload( string $game ): bool
	{
		return self::has_preload_resolver($game);
	}

	/**
	 * Instance proxy: готовит cache/preload для игр
	 *
	 * @param array $games
	 * @return array<string, array>
	 */
	public function prepare_preload( array $games ): array
	{
		return self::prepare_preload_for_games($games);
	}

	/**
	 * Instance proxy: готов ли preload игры
	 *
	 * @param string $game
	 * @return bool
	 */
	public function preload_ready( string $game ): bool
	{
		return self::is_preload_ready($game);
	}

	/**
	 * Instance proxy: cache для игры обязателен
	 *
	 * @param string $game
	 * @return bool
	 */
	public function preload_required( string $game ): bool
	{
		return self::is_preload_required($game);
	}

	/**
	 * Instance proxy: запускает preload-резолвер
	 *
	 * @param string $game
	 * @param array $rows
	 * @return array|null
	 */
	public function resolve_preload( string $game, array $rows )
	{
		return self::resolve_preload_servers($game, $rows);
	}

	/**
	 * Instance proxy: можно ли отдать preload результат как есть
	 *
	 * @param string $game
	 * @return bool
	 */
	public function preload_passthrough( string $game ): bool
	{
		return self::is_preload_passthrough($game);
	}

	/**
	 * Instance proxy: можно ли добивать cache miss через query_batch
	 *
	 * @param string $game
	 * @return bool
	 */
	public function preload_fallback_query_missing( string $game ): bool
	{
		return self::should_preload_fallback_query_missing($game);
	}

	/**
	 * Возвращает master socket endpoints из единого конфига
	 *
	 * @param string $game
	 * @return array<int, array>
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	public static function get_master_socket_endpoints( string $game ): array
	{
		$game_key = strtolower(trim($game));
		$endpoints = GAMES_CONFIG[$game_key]['master']['master_socket']['endpoints'] ?? [];

		return (is_array($endpoints) ? $endpoints : []);
	}

	/**
	 * Проверяет, обязателен ли свежий cache для preload-игры
	 *
	 * @param string $game
	 * @return bool
	 */
	public static function is_preload_required( string $game ): bool
	{
		$game_key = strtolower(trim($game));

		return self::is_cache_required($game_key) && self::has_preload_resolver($game_key);
	}

	/**
	 * Проверяет, готов ли preload после подготовки
	 *
	 * @param string $game
	 * @return bool
	 */
	public static function is_preload_ready( string $game ): bool
	{
		$game_key = strtolower(trim($game));

		if( !self::is_preload_required($game_key) )
			return true;

		return !empty(self::$preload_ready_status[$game_key]['ready']);
	}

	/**
	 * Готовит cache/preload и сохраняет status для cron
	 *
	 * @param array $games
	 * @return array<string, array>
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	public static function prepare_preload_for_games( array $games ): array
	{
		$resolvers = self::preload_resolvers();
		$uniq_games = array_unique(array_filter(array_map('trim', $games)));

		foreach( $uniq_games as $game )
		{
			$game_key = strtolower($game);
			if( empty($resolvers[$game_key]) )
				continue;

			$ready = true;
			$required = self::is_preload_required($game_key);

			if( $required )
				$ready = self::refresh_cache_for_game($game_key);

			self::$preload_ready_status[$game_key] = [
				'ready' => $ready,
				'cache_required' => $required,
			];

			if( !$ready )
				continue;

			self::prime_preload_for_games([$game_key]);
		}

		return self::$preload_ready_status;
	}

	/**
	 * Обновляет cache игры и возвращает true только если он свежий
	 *
	 * @param string $game
	 * @return bool
	 */
	private static function refresh_cache_for_game( string $game ): bool
	{
		$game_key = strtolower(trim($game));
		$checked = false;
		$ready = true;

		$master_list = self::get_master_sources($game_key);
		if( !empty($master_list) && is_array($master_list) )
		{
			foreach( $master_list as $entry )
			{
				$cache_key = (string)($entry['cache_key'] ?? '');
				$url = (string)($entry['url'] ?? '');
				$ttl = (int)($entry['ttl'] ?? 0);
				$refresh_method = (string)($entry['refresh_method'] ?? '');

				if( $cache_key === '' || $ttl < 1 )
					continue;

				$checked = true;

				if( !self::is_fresh($cache_key, $ttl) )
				{
					if( $refresh_method !== '' && method_exists(__CLASS__, $refresh_method) )
					{
						$refresh_result = self::$refresh_method( );
						if( is_string($refresh_result) && $refresh_result !== '' )
							self::write_json($cache_key, $refresh_result);
					}
					elseif( $url !== '' )
					{
						$body = self::download_json($url);
						if( $body !== null )
							self::write_json($cache_key, $body);
					}
				}

				if( !self::is_fresh($cache_key, $ttl) )
					$ready = false;
			}
		}

		if( $game_key === 'scum' && !empty(self::get_master_socket_endpoints($game_key)) )
		{
			$checked = true;
			$ttl = self::get_preload_ttl($game_key, 300);

			if( !self::ensure_scum_cache_ready($ttl) )
				$ready = false;
		}

		return ($checked ? $ready : true);
	}

	/**
	 * Обновляет локальные master json файлы по играм
	 *
	 * @param array $games
	 * @return void
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	public static function refresh_for_games( array $games ): void
	{
		$uniq_games = array_unique(array_filter(array_map('trim', $games)));

		foreach( $uniq_games as $game )
			self::refresh_cache_for_game($game);
	}

	/**
	 * Collects active Palworld servers from the paginated public master
	 *
	 * @return string|null
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function refresh_palworld_master_cache( )
	{
		$cached = self::read('palworld_master_servers');
		$page_hint = (int)($cached['last_page'] ?? 0);
		$last_page = self::find_palworld_last_page($page_hint);

		if( $last_page < 1 )
			return null;

		$responses = self::download_palworld_pages(range(1, $last_page));
		if( count($responses) !== $last_page )
			return null;

		$servers = [];

		foreach( $responses as $response )
		{
			foreach( (array)($response['server_list'] ?? []) as $server )
			{
				if( !is_array($server) )
					continue;

				$address = strtolower(trim((string)($server['address'] ?? '')));
				$port = (int)($server['port'] ?? 0);

				if( $address === '' || $port < 1 )
					continue;

				$servers[] = $server;
			}
		}

		$json = @json_encode([
			'generated_at' => time(),
			'last_page' => $last_page,
			'servers' => $servers,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return (is_string($json) && $json !== '' ? $json : null);
	}

	/**
	 * Finds the current last Palworld master page
	 *
	 * @param int $page_hint Last page from the previous cache
	 * @return int
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function find_palworld_last_page( int $page_hint = 0 ): int
	{
		if( $page_hint > 0 )
		{
			$responses = self::download_palworld_pages(range($page_hint, $page_hint + 2));

			foreach( $responses as $page => $response )
			{
				if( !empty($response['server_list']) && empty($response['is_next_page']) )
					return (int)$page;
			}

		}

		$first = self::download_palworld_pages([1]);
		if( empty($first[1]['server_list']) )
			return 0;

		if( empty($first[1]['is_next_page']) )
			return 1;

		$low = 1;
		$high = max(512, $page_hint + 64);

		while( true )
		{
			$response = self::download_palworld_pages([$high]);

			if( empty($response[$high]['server_list']) )
				break;

			$low = $high;
			$high *= 2;
		}

		while( $low < $high )
		{
			$middle = (int)floor(($low + $high + 1) / 2);
			$response = self::download_palworld_pages([$middle]);

			if( !empty($response[$middle]['server_list']) )
				$low = $middle;
			else
				$high = $middle - 1;
		}

		return $low;
	}

	/**
	 * Downloads several Palworld master pages in one batch
	 *
	 * @param array $pages Page numbers
	 * @return array<int, array>
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function download_palworld_pages( array $pages ): array
	{
		$pages = array_values(array_unique(array_filter(array_map('intval', $pages), function( $page )
		{
			return $page > 0;
		})));

		if( empty($pages) )
			return [];

		$result = [];
		$curl = self::get_instance()->curl;
		$cookie_file = @tempnam(sys_get_temp_dir(), 'palworld_');

		foreach( array_chunk($pages, 2) as $chunk )
		{
			$pending = $chunk;

			for( $attempt = 1; $attempt <= 2 && !empty($pending); $attempt++ )
			{
				$requests = [];

				foreach( $pending as $page )
				{
					$requests[] = [
						'url' => 'https://api.palworldgame.com/server/list?page=' . $page,
						'options' => [
							CURLOPT_HTTPHEADER => ['Accept: application/json'],
							CURLOPT_COOKIEFILE => ($cookie_file ?: ''),
							CURLOPT_COOKIEJAR => ($cookie_file ?: ''),
						],
					];
				}

				try
				{
					$responses = $curl->send_multi($requests);
				}
				catch( Throwable $e )
				{
					$responses = [];
				}

				$failed = [];

				foreach( $pending as $index => $page )
				{
					$response = $responses[$index] ?? [];
					$data = (!empty($response['success']) && !empty($response['data'])
						? self::decode_json_array((string)$response['data'])
						: null);

					if( is_array($data) && isset($data['server_list']) && is_array($data['server_list']) )
						$result[$page] = $data;
					else
						$failed[] = $page;
				}

				$pending = $failed;

				if( !empty($pending) )
					sleep($attempt);
			}

			if( !empty($pending) )
				break;

			usleep(500000);
		}

		if( $cookie_file )
			@unlink($cookie_file);

		ksort($result);

		return $result;
	}

	/**
	 * Обновляет локальный rust master кеш без зависимости от Monitoring
	 *
	 * @return string|null JSON для записи в rust_master_servers или null при ошибке
	 */
	private static function refresh_rust_master_cache( )
	{
		$limit = 1000;
		$chunk = 50;
		$delay = 0.2;

		try
		{
			$instance = self::get_instance( );
			$curl = $instance->curl;
			$jsnh = $instance->jsnh;

			$facepunch_resp = $curl->send_single(
				'https://api.facepunch.com/api/public/steamServers/playerCounts/rust'
			);

			if( empty($facepunch_resp['success']) || empty($facepunch_resp['data']) )
				return null;

			$facepunch_ips = $jsnh->decode($facepunch_resp['data'], true);
			if( !is_array($facepunch_ips) || empty($facepunch_ips) )
				return null;

			$steam_servers = [];

			$steam_resp = $curl->send_single(
				'https://api.steampowered.com/IGameServersService/GetServerList/v1/?filter=\\gamedir\\rust&limit=10000&key=24BB0534EB1DC672B1354BBB9478E98A'
			);

			if( !empty($steam_resp['success']) && !empty($steam_resp['data']) )
			{
				$data = $jsnh->decode($steam_resp['data'], true);

				foreach( $data['response']['servers'] ?? [] as $server )
				{
					$ip = explode(':', (string)($server['addr'] ?? ''))[0] ?? '';
					$port = $server['gameport'] ?? null;

					if( !$ip || !$port )
						continue;

					$key = $ip . ':' . $port;

					if( isset($facepunch_ips[$key]) )
					{
						$steam_servers[$key] = $server;
						unset($facepunch_ips[$key]);
					}
				}
			}

			$remaining_ips = array_slice(array_keys($facepunch_ips), 0, $limit);
			$chunks = array_chunk($remaining_ips, $chunk);

			foreach( $chunks as $chunk_ips )
			{
				$requests = [];

				foreach( $chunk_ips as $key )
				{
					[ $ip, $port ] = explode(':', $key) + [ '', '' ];
					if( !$ip || !$port )
						continue;

					$requests[] = [
						'url' => "https://api.steampowered.com/IGameServersService/GetServerList/v1/?filter=\\gamedir\\rust\\gameaddr\\{$ip}:{$port}&key=24BB0534EB1DC672B1354BBB9478E98A",
					];
				}

				if( empty($requests) )
					continue;

				try
				{
					$responses = $curl->send_multi($requests);
				}
				catch( Throwable $e )
				{
					usleep((int)($delay * 1000000));
					continue;
				}

				foreach( $responses as $i => $check_resp )
				{
					$key = $chunk_ips[$i] ?? '';
					[ $ip, $port ] = explode(':', $key) + [ '', '' ];
					if( !$ip || !$port )
						continue;

					if( empty($check_resp['success']) || empty($check_resp['data']) )
						continue;

					$data = $jsnh->decode($check_resp['data'], true);
					foreach( $data['response']['servers'] ?? [] as $server )
					{
						$addr_ip = explode(':', (string)($server['addr'] ?? ''))[0] ?? '';
						$gameport = $server['gameport'] ?? null;
						if( !$addr_ip || !$gameport )
							continue;

						if( $key === $addr_ip . ':' . $gameport )
						{
							$steam_servers[$key] = $server;
							break;
						}
					}
				}

				usleep((int)($delay * 1000000));
			}

			if( empty($steam_servers) )
				return null;

			$rows = [];

			foreach( $steam_servers as $server )
			{
				[ $ip_raw, $query_port_raw ] = explode(':', (string)($server['addr'] ?? ''), 2) + ['', 0];
				$ip = $ip_raw;
				$query_port = (int)$query_port_raw;
				$game_port = (int)($server['gameport'] ?? 0);

				if( $ip === '' || $game_port < 1 )
					continue;

				$server['addr'] = $ip . ':' . $game_port;
				$server['ip'] = $ip;
				$server['clientport'] = $game_port;
				$server['gq_port_query'] = $query_port;

				$normalized = self::normalize_master_server_row($server, 'rust');
				if( !is_array($normalized) || empty($normalized['addr']) )
					continue;

				$rows[$normalized['addr']] = $normalized;
			}

			if( empty($rows) )
				return null;
		}
		catch( Throwable $e )
		{
			return null;
		}

		if( !is_array($rows) || empty($rows) )
			return null;

		$jsnh = self::get_instance()->jsnh;
		if( is_object($jsnh) && method_exists($jsnh, 'encode') )
			return $jsnh->encode($rows);

		$json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return (is_string($json) && $json !== '' ? $json : null);
	}

	/**
	 * Обновляет локальный ARK:SA master кеш без зависимости от Monitoring
	 *
	 * @return bool true если кеш записан, false если чтото пошло не так
	 */
	private static function refresh_arksa_master_cache( )
	{
		$urls = [
			'https://cdn2.arkdedicated.com/servers/asa/officialserverlist.json',
			'https://cdn2.arkdedicated.com/servers/asa/unofficialserverlist.json',
		];
		$max_arksa_object_bytes = 262144;

		try
		{
			if( function_exists('gc_enable') )
				gc_enable( );

			$instance = self::get_instance();
			$jsnh = $instance->jsnh;
			$file = self::build_file_path('arksa_master_servers');
			$temp_file = ($file !== null ? $file . '.tmp' : null);
			$processed_rows = 0;

			if( $file === null || $temp_file === null )
				return false;

			if( !is_object($jsnh) || !method_exists($jsnh, 'stream_json_objects') || !method_exists($jsnh, 'encode') )
				return false;

			$dir = dirname($file);
			if( !is_dir($dir) )
				@mkdir($dir, 0777, true);

			$fp = @fopen($temp_file, 'wb');
			if( !$fp )
				return false;

			fwrite($fp, '[');
			$is_first = true;

			foreach( $urls as $url )
			{
				foreach( $jsnh->stream_json_objects($url, $max_arksa_object_bytes) as $srv )
				{
					if( !is_array($srv) )
						continue;

					$raw = self::map_arksa_master_row_to_raw($srv);
					if( empty($raw) )
						continue;

					$server = self::normalize_master_server_row($raw, 'arksa');
					if( !is_array($server) || empty($server['addr']) )
						continue;

					$server = self::compact_arksa_normalized_row($server);
					if( !is_array($server) || empty($server['addr']) )
						continue;

					$json_row = $jsnh->encode($server);
					if( !is_string($json_row) || $json_row === '' )
						continue;

					if( !$is_first )
						fwrite($fp, ',');

					fwrite($fp, $json_row);
					$is_first = false;
					$processed_rows++;

					unset($srv, $raw, $server, $json_row);

					if( $processed_rows % 1000 === 0 )
					{
						gc_collect_cycles( );

						if( function_exists('gc_mem_caches') )
							gc_mem_caches( );
					}
				}

				gc_collect_cycles( );

				if( function_exists('gc_mem_caches') )
					gc_mem_caches( );
			}

			fwrite($fp, ']');
			fclose($fp);

			if( $is_first )
			{
				@unlink($temp_file);
				return false;
			}

			if( !@rename($temp_file, $file) )
			{
				@unlink($temp_file);
				return false;
			}

			return true;
		}
		catch( Throwable $e )
		{
			if( !empty($fp) && is_resource($fp) )
				fclose($fp);

			if( !empty($temp_file) && is_string($temp_file) && is_file($temp_file) )
				@unlink($temp_file);

			return false;
		}
	}

	/**
	 * Приводит upstream строку ARK:SA к сырому виду для общего normalize
	 * важное оставляем в корне, остальное кладем в rules чтобы не плодить хардкод по полям
	 *
	 * @param array $row
	 * @return array
	 */
	private static function map_arksa_master_row_to_raw( array $row ): array
	{
		$ip = strtolower(trim((string)($row['IP'] ?? '')));
		$port = (int)($row['Port'] ?? 0);
		$source_updated_at = (int)round(((float)($row['LastUpdated'] ?? 0)) / 1000);

		if( $ip === '' || $port < 1 )
			return [];

		$hostname = trim((string)($row['SessionName'] ?? $row['Name'] ?? ''));
		$version = trim((string)($row['BuildId'] ?? '') . '.' . (string)($row['MinorBuildId'] ?? ''), '.');
		$rules = [];
		$skip_keys = [
			'IP' => true,
			'Port' => true,
			'SessionName' => true,
			'Name' => true,
			'MapName' => true,
			'NumPlayers' => true,
			'MaxPlayers' => true,
			'HasPassword' => true,
			'BuildId' => true,
			'MinorBuildId' => true,
			'IsOfficial' => true,
		];

		foreach( $row as $key => $value )
		{
			if( isset($skip_keys[$key]) )
				continue;

			$rules[$key] = $value;
		}

		$raw = [
			'ip' => $ip,
			'addr' => $ip . ':' . $port,
			'port' => $port,
			'clientport' => $port,
			'game' => 'arksa',
			'gamedir' => 'arksa',
			'hostname' => $hostname,
			'gq_hostname' => $hostname,
			'map' => (string)($row['MapName'] ?? ''),
			'players' => (int)($row['NumPlayers'] ?? 0),
			'actual_players' => (int)($row['NumPlayers'] ?? 0),
			'numplayers' => (int)($row['NumPlayers'] ?? 0),
			'gq_numplayers' => (int)($row['NumPlayers'] ?? 0),
			'max_players' => (int)($row['MaxPlayers'] ?? 0),
			'maxplayers' => (int)($row['MaxPlayers'] ?? 0),
			'bots' => 0,
			'password' => (int)($row['HasPassword'] ?? 0),
			'version' => $version,
			'official' => (int)($row['IsOfficial'] ?? 0),
			'dedicated' => 1,
			'gq_online' => true,
			'rules' => $rules,
		];

		if( $source_updated_at > 0 )
			$raw['_source_updated_at'] = $source_updated_at;

		return $raw;
	}

	/**
	 * Поджимает общий normalize-результат ARK:SA к старой форме cache row
	 * тут только приводим поля к ожидаемому виду, сама нормализация уже выше общая
	 *
	 * @param array $server
	 * @return array
	 */
	private static function compact_arksa_normalized_row( array $server ): array
	{
		$info_raw = (isset($server['info_raw']) && is_array($server['info_raw']) ? $server['info_raw'] : []);
		$rules = (isset($info_raw['rules']) && is_array($info_raw['rules']) ? $info_raw['rules'] : []);
		$source_updated_at = (int)($server['_source_updated_at'] ?? $info_raw['_source_updated_at'] ?? 0);

		$server['game'] = 'ark-survival-ascended';
		$server['query_port'] = 0;

		if( $source_updated_at > 0 )
			$server['_source_updated_at'] = $source_updated_at;

		$server['info_raw'] = [
			'ip' => (string)($server['ip'] ?? ''),
			'map' => (string)($server['map'] ?? ''),
			'addr' => (string)($server['addr'] ?? ''),
			'bots' => (int)($server['bots'] ?? 0),
			'game' => 'ark-survival-ascended',
			'rules' => $rules,
			'game_id' => (int)($info_raw['game_id'] ?? 0),
			'gamedir' => (string)($info_raw['gamedir'] ?? 'arksa'),
			'version' => (string)($info_raw['version'] ?? $server['version'] ?? ''),
			'hostname' => (string)($info_raw['hostname'] ?? $server['name'] ?? ''),
			'official' => (int)($info_raw['official'] ?? $server['official'] ?? 0),
			'password' => (int)($info_raw['password'] ?? $server['password'] ?? 0),
			'dedicated' => (int)($info_raw['dedicated'] ?? 1),
			'game_name' => ($info_raw['game_name'] ?? false),
			'gq_online' => (!isset($info_raw['gq_online']) ? true : (bool)$info_raw['gq_online']),
			'clientport' => (int)($info_raw['clientport'] ?? $server['port'] ?? 0),
			'maxplayers' => (int)($info_raw['maxplayers'] ?? $server['max_players'] ?? 0),
			'numplayers' => (int)($info_raw['numplayers'] ?? $server['players'] ?? 0),
			'steamappid' => (int)($info_raw['steamappid'] ?? 0),
			'gq_hostname' => (string)($info_raw['gq_hostname'] ?? $info_raw['hostname'] ?? $server['name'] ?? ''),
			'gq_numplayers' => (int)($info_raw['gq_numplayers'] ?? $server['players'] ?? 0),
			'gq_port_query' => false,
			'actual_players' => (int)($info_raw['actual_players'] ?? $server['actual_players'] ?? 0),
		];

		if( $source_updated_at > 0 )
			$server['info_raw']['_source_updated_at'] = $source_updated_at;

		return $server;
	}

	/**
	 * Проверяет включен ли preload-резолвер для игры
	 *
	 * @param string $game
	 * @return bool
	 */
	public static function has_preload_resolver( string $game ): bool
	{
		$game_key = strtolower(trim($game));
		$resolvers = self::preload_resolvers();

		return !empty($resolvers[$game_key]);
	}

	/**
	 * Проверяет есть ли для игры master json источник
	 *
	 * @param string $game
	 * @return bool
	 */
	public static function has_master_json_source( string $game ): bool
	{
		$game_key = strtolower(trim($game));

		return !empty(self::get_master_sources($game_key));
	}

	/**
	 * Проверяет есть ли для игры любой локальный кеш-источник
	 *
	 * @param string $game
	 * @return bool
	 */
	public static function has_local_cache_source( string $game ): bool
	{
		$game_key = strtolower(trim($game));

		return (
			self::has_master_json_source($game_key)
			|| self::get_cache_file_key($game_key) !== ''
		);
	}

	/**
	 * Стримит сервера игры из локального master кеша без большого массива в памяти
	 * это важно для тяжёлых кешей вроде ARK:SA
	 *
	 * @param string $game
	 * @param int $limit
	 * @return Generator<int, array>
	 */
	public static function stream_game_master_rows( string $game, int $limit = 0 ): Generator
	{
		$game_key = strtolower(trim($game));

		if( !self::has_local_cache_source($game_key) )
		{
			if( false )
				yield [];

			return;
		}

		$rows_count = 0;

		switch( $game_key )
		{
			case 'rust':
			{
				$index = self::get_rust_master_index();
				if( empty($index) )
					break;

				foreach( $index as $addr_key => $server )
				{
					if( !is_array($server) || !is_string($addr_key) || $addr_key !== strtolower((string)($server['addr'] ?? '')) )
						continue;

					yield $server;
					$rows_count++;

					if( $limit > 0 && $rows_count >= $limit )
						break;
				}
			}break;

				case 'scum':
				{
					$index = self::get_scum_master_index();
					if( empty($index) )
						break;

					foreach( $index as $raw )
					{
						if( !is_array($raw) )
							continue;

						$ip = strtolower((string)($raw['gq_address'] ?? $raw['ip'] ?? ''));
						$query_port = (int)($raw['gq_port_query'] ?? $raw['query_port'] ?? 0);
						$port = (int)($raw['gq_port_client'] ?? $raw['port'] ?? 0);

						if( $ip === '' )
							continue;

						if( $query_port < 1 && $port > 1 )
							$query_port = $port + 2;

						if( $port < 1 && $query_port > 1 )
							$port = $query_port - 2;

						if( $query_port < 1 || $port < 1 )
							continue;

						$master_hits = max(1, (int)($raw['master_hits'] ?? 1));
						$master_samples = max(1, (int)($raw['master_samples'] ?? 1));
						$players = (int)($raw['players'] ?? $raw['numplayers'] ?? 0);
						$actual_players = (int)($raw['actual_players'] ?? $raw['numplayers'] ?? 0);
						$official = (int)($raw['official'] ?? 0);

						# тихий неофициальный адрес должен пережить оба снимка master
						if( $master_samples > 1 && $master_hits < 2 && $players < 1 && $actual_players < 1 && $official < 1 )
							continue;

						$normalized = self::normalize_master_server_row([
							'addr' => $ip . ':' . $query_port,
							'ip' => $ip,
							'port' => $port,
							'query_port' => $query_port,
							'game' => 'scum',
							'name' => trim((string)($raw['hostname'] ?? $raw['gq_hostname'] ?? '')),
							'map' => (string)($raw['map'] ?? ''),
							'players' => $players,
							'actual_players' => $actual_players,
							'max_players' => (int)($raw['max_players'] ?? $raw['maxplayers'] ?? 0),
							'bots' => (int)($raw['bots'] ?? 0),
							'info' => (array)($raw['info'] ?? []),
							'info_raw' => (array)($raw['info_raw'] ?? $raw),
						], 'scum');

						if( !is_array($normalized) )
							continue;

						yield $normalized;
						$rows_count++;

						if( $limit > 0 && $rows_count >= $limit )
							break;
					}
				}break;

			case 'palworld':
			{
				$index = self::get_palworld_master_index();
				if( empty($index) )
					break;

				foreach( $index as $server )
				{
					if( !is_array($server) )
						continue;

					yield $server;
					$rows_count++;

					if( $limit > 0 && $rows_count >= $limit )
						break;
				}
			}break;

			case 'beammp':
			{
				$beam_rows = self::read('beammp_servers_info');
				if( !is_array($beam_rows) )
					break;

				if( isset($beam_rows['servers']) && is_array($beam_rows['servers']) )
					$beam_rows = $beam_rows['servers'];

				foreach( $beam_rows as $server_row )
				{
					if( !is_array($server_row) )
						continue;

					$mapped = self::map_beammp_row_to_server($server_row);
					if( empty($mapped) )
						continue;

					$normalized = self::normalize_master_server_row($mapped, 'beammp');
					if( !is_array($normalized) )
						continue;

					yield $normalized;
					$rows_count++;

					if( $limit > 0 && $rows_count >= $limit )
						break;
				}
			}break;

			case 'gta5':
			{
				$index = self::get_gta5_master_index();
				if( empty($index) )
					break;

				foreach( $index as $raw )
				{
					$ip = strtolower((string)($raw['gq_address'] ?? $raw['ip'] ?? ''));
					$query_port = (int)($raw['gq_port_query'] ?? 0);
					$port = (int)($raw['gq_port_client'] ?? $query_port);

					if( $ip === '' || $port < 1 )
						continue;

					$normalized = self::normalize_master_server_row([
						'addr' => $ip . ':' . $port,
						'ip' => $ip,
						'port' => $port,
						'query_port' => ($query_port > 0 ? $query_port : $port),
						'game' => 'gta5',
						'name' => (string)($raw['hostname'] ?? $raw['gq_hostname'] ?? ''),
						'map' => (string)($raw['map'] ?? ''),
						'players' => (int)($raw['players'] ?? $raw['numplayers'] ?? 0),
						'actual_players' => (int)($raw['actual_players'] ?? $raw['numplayers'] ?? 0),
						'max_players' => (int)($raw['max_players'] ?? $raw['maxplayers'] ?? 0),
						'bots' => (int)($raw['bots'] ?? 0),
						'info' => (array)($raw['info'] ?? []),
						'info_raw' => (array)($raw['info_raw'] ?? $raw),
					], 'gta5');

					if( !is_array($normalized) )
						continue;

					yield $normalized;
					$rows_count++;

					if( $limit > 0 && $rows_count >= $limit )
						break;
				}
			}break;

			case 'ark-survival-ascended':
			{
				$file = self::build_file_path('arksa_master_servers');
				$jsnh = self::get_instance()->jsnh;

				if( $file === null || !is_file($file) || !is_object($jsnh) || !method_exists($jsnh, 'stream_json_objects') )
					break;

				$arksa_mtime = (int)@filemtime($file);

				foreach( $jsnh->stream_json_objects($file) as $server )
				{
					if( !is_array($server) || empty($server['addr']) )
						continue;

					$server['game'] = 'ark-survival-ascended';

					if( $arksa_mtime > 0 )
					{
						$server['_source_updated_at'] = ($server['_source_updated_at'] ?? $arksa_mtime);

						if( !empty($server['info_raw']) && is_array($server['info_raw']) )
							$server['info_raw']['_source_updated_at'] = ($server['info_raw']['_source_updated_at'] ?? $arksa_mtime);
					}

					yield $server;
					$rows_count++;

					if( $limit > 0 && $rows_count >= $limit )
						break;
				}
			}break;
		}
	}

	/**
	 * Возвращает список серверов игры из локального master кеша
	 * для старого кода оставляем массив, но сами данные теперь можно читать и потоком
	 *
	 * @param string $game
	 * @param int $limit
	 * @return array<int, array>
	 */
	public static function get_game_master_rows( string $game, int $limit = 0 ): array
	{
		$rows = [];

		foreach( self::stream_game_master_rows($game, $limit) as $row )
			$rows[] = $row;

		return $rows;
	}

	/**
	 * Проверяет, можно ли отдать preload результат как есть
	 *
	 * @param string $game
	 * @return bool
	 */
	public static function is_preload_passthrough( string $game ): bool
	{
		# true если resolve_preload_* уже вернул готовый server row
		$game_key = strtolower(trim($game));
		$resolvers = self::preload_resolvers();

		if( empty($resolvers[$game_key]) )
			return false;

		$cfg = $resolvers[$game_key];

		return !empty($cfg['raw_passthrough']);
	}

	/**
	 * Проверяет, нужно ли добивать промахи preload через query_batch
	 *
	 * @param string $game
	 * @return bool
	 */
	public static function should_preload_fallback_query_missing( string $game ): bool
	{
		# если preload не нашел сервер, тут решаем добивать его query или нет
		$game_key = strtolower(trim($game));

		return !empty(GAMES_CONFIG[$game_key]['master']['fallback_query_missing']);
	}

	/**
	 * Запускает preload-резолвер для игры
	 * null если preload не настроен, [] если данных нет
	 *
	 * @param string $game
	 * @param array $rows
	 * @return array|null
	 */
	public static function resolve_preload_servers( string $game, array $rows )
	{
		$game_key = strtolower(trim($game));
		$resolvers = self::preload_resolvers();

		if( empty($resolvers[$game_key]) )
			return null;

		$cfg = $resolvers[$game_key];
		$method_name = (string)($cfg['method'] ?? '');
		$ttl = (int)($cfg['ttl'] ?? 180);

		if( $method_name === '' )
			return [];

		if( !method_exists(__CLASS__, $method_name) )
			return [];

		$result = self::$method_name($rows, $ttl);
		if( !is_array($result) )
			return [];

		return $result;
	}

	/**
	 * Готовит preload-данные до обхода чанков
	 * чтобы не дергать online master на каждом проходе
	 *
	 * @param array $games
	 * @return void
	 */
	public static function prime_preload_for_games( array $games ): void
	{
		# preload делаем один раз за запуск cron
		$resolvers = self::preload_resolvers();
		$uniq_games = array_unique(array_filter(array_map('trim', $games)));

		foreach( $uniq_games as $game )
		{
			$game_key = strtolower($game);
			if( empty($resolvers[$game_key]) )
				continue;

			if( !empty(self::$preload_primed_games[$game_key]) )
				continue;

			$cfg = $resolvers[$game_key];
			$prime_method = (string)($cfg['prime_method'] ?? '');
			$ttl = (int)($cfg['ttl'] ?? 180);

			if( $prime_method !== '' && method_exists(__CLASS__, $prime_method) )
			{
				self::$prime_method($ttl);
				self::$preload_primed_games[$game_key] = true;
			}
		}
	}

	/**
	 * Предзагрузка SCUM: refresh при необходимости + построение индекса
	 *
	 * @param int $ttl
	 * @return void
	 */
	private static function prime_preload_scum( int $ttl = 300 ): void
	{
		if( !self::ensure_scum_cache_ready($ttl) )
			return;

		self::get_scum_master_index();
	}

	/**
	 * Предзагрузка GTA5: refresh локальных master файлов + построение индекса
	 *
	 * @param int $ttl
	 * @return void
	 */
	private static function prime_preload_gta5( int $ttl = 300 ): void
	{
		if( !self::is_fresh('gtan_master_servers', $ttl) || !self::is_fresh('gtar_master_servers', $ttl) )
			self::refresh_for_games(['gta5']);

		self::get_gta5_master_index();
	}

	/**
	 * Предзагрузка BeamMP: refresh json при необходимости + построение индекса
	 *
	 * @param int $ttl
	 * @return void
	 */
	private static function prime_preload_beammp( int $ttl = 300 ): void
	{
		if( !self::is_fresh('beammp_servers_info', $ttl) )
			self::refresh_for_games(['beammp']);

		self::get_beammp_master_index();
	}

	/**
	 * Preloads the Palworld public master cache and address index
	 *
	 * @param int $ttl
	 * @return void
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function prime_preload_palworld( int $ttl = 300 ): void
	{
		if( !self::is_fresh('palworld_master_servers', $ttl) )
			self::refresh_for_games(['palworld']);

		self::get_palworld_master_index();
	}

	/**
	 * Предзагрузка Rust: refresh локального master cache + построение индекса
	 *
	 * @param int $ttl
	 * @return void
	 */
	private static function prime_preload_rust( int $ttl = 300 ): void
	{
		if( !self::is_fresh('rust_master_servers', $ttl) )
			self::refresh_for_games(['rust']);

		self::get_rust_master_index();
	}

	/**
	 * Предзагрузка ARK:SA из monitoring json
	 * тут нет локального кеш файла, просто собираем индекс один раз на запрос
	 *
	 * @param int $ttl
	 * @return void
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function prime_preload_arksa( int $ttl = 120 ): void
	{
		if( !self::is_fresh('arksa_master_servers', $ttl) )
			self::refresh_for_games(['ark-survival-ascended']);
	}

	/**
	 * Универсальный preload для SCUM
	 *
	 * @param array $rows
	 * @param int $ttl
	 * @return array<string, array>
	 */
	private static function resolve_preload_scum( array $rows, int $ttl = 300 ): array
	{
		if( empty(self::$preload_primed_games['scum']) )
		{
			self::prime_preload_scum($ttl);
			self::$preload_primed_games['scum'] = true;
		}

		$index = self::get_scum_master_index();
		if( empty($index) )
			return [];

		$result = [];

		foreach( $rows as $row )
		{
			$addr = strtolower(trim((string)($row['addr'] ?? '')));
			$ip = strtolower(trim((string)($row['ip'] ?? '')));
			$port = (int)($row['port'] ?? 0);
			$query_port = (int)($row['query_port'] ?? 0);
			$addr_host = '';

			if( $addr !== '' && strpos($addr, ':') !== false )
			{
				[ $addr_host, $addr_port ] = explode(':', $addr, 2) + [ '', '0' ];

				if( $query_port < 1 )
					$query_port = (int)$addr_port;
			}

			if( $query_port < 1 && $port > 1 )
				$query_port = $port + 2;

			if( $port < 1 && $query_port > 1 )
				$port = $query_port - 2;

			if( $ip === '' )
				$ip = $addr_host;

			if( $ip === '' || $query_port < 1 )
				continue;

			$candidates = [];
			if( $addr !== '' )
				$candidates[] = $addr;
			$candidates[] = $ip . ':' . $query_port;
			if( $port > 0 )
				$candidates[] = $ip . ':' . $port;

			$candidates = array_values(array_unique(array_filter($candidates)));

			$raw = null;
			foreach( $candidates as $candidate )
			{
				if( isset($index[$candidate]) )
				{
					$raw = $index[$candidate];
					break;
				}
			}

			if( !is_array($raw) )
				continue;

			$out_addr = ((string)($row['addr'] ?? '') !== '' ? (string)$row['addr'] : ($ip . ':' . $query_port));
			$raw['addr'] = $out_addr;
			$raw['ip'] = $ip;
			$raw['port'] = $port;
			$raw['query_port'] = $query_port;
			$raw['game'] = 'scum';

			$normalized = self::normalize_master_server_row($raw, 'scum');
			if( !is_array($normalized) )
				continue;

			$source_updated_at = (int)($raw['_source_updated_at'] ?? 0);
			if( $source_updated_at > 0 )
			{
				$normalized['_source_updated_at'] = $source_updated_at;

				if( isset($normalized['info_raw']) && is_array($normalized['info_raw']) )
					$normalized['info_raw']['_source_updated_at'] = $source_updated_at;
			}

			$result[$out_addr] = $normalized;
		}

		return $result;
	}

	/**
	 * Универсальный preload для GTA5 (gtan + gtar)
	 *
	 * @param array $rows
	 * @param int $ttl
	 * @return array<string, array>
	 */
	private static function resolve_preload_gta5( array $rows, int $ttl = 300 ): array
	{
		if( empty(self::$preload_primed_games['gta5']) )
		{
			self::prime_preload_gta5($ttl);
			self::$preload_primed_games['gta5'] = true;
		}

		$index = self::get_gta5_master_index();
		if( empty($index) )
			return [];

		$result = [];

		foreach( $rows as $row )
		{
			$addr = strtolower((string)($row['addr'] ?? ''));
			$ip = strtolower((string)($row['ip'] ?? ''));
			$query_port = (int)($row['query_port'] ?? 0);
			$port = (int)($row['port'] ?? 0);
			$addr_host = '';

			if( $addr !== '' && strpos($addr, ':') !== false )
				[$addr_host] = explode(':', $addr, 2) + [''];

			if( $query_port < 1 )
			{
				if( $addr !== '' && strpos($addr, ':') !== false )
				{
					[, $addr_port] = explode(':', $addr, 2) + ['', 0];
					$query_port = (int)$addr_port;
				}
			}

			if( $query_port < 1 )
				$query_port = $port;

			$candidates = [];
			if( $addr !== '' )
				$candidates[] = $addr;
			if( $ip !== '' && $query_port > 0 )
				$candidates[] = $ip . ':' . $query_port;
			if( $ip !== '' && $port > 0 )
				$candidates[] = $ip . ':' . $port;
			if( $addr_host !== '' && $query_port > 0 )
				$candidates[] = $addr_host . ':' . $query_port;
			if( $addr_host !== '' && $port > 0 )
				$candidates[] = $addr_host . ':' . $port;

			$candidates = array_values(array_unique(array_filter($candidates)));

			$found = null;
			foreach( $candidates as $candidate )
			{
				if( isset($index[$candidate]) )
				{
					$found = $index[$candidate];
					break;
				}
			}

			if( !$found )
				continue;

			$out_addr = (string)($row['addr'] ?? (($ip !== '' && $port > 0) ? ($ip . ':' . $port) : ''));
			if( $out_addr === '' )
				continue;

			$found['addr'] = $out_addr;
			$found['ip'] = $ip;
			$found['port'] = $port;
			$found['query_port'] = ($query_port > 0 ? $query_port : $port);
			$found['game'] = 'gta5';

			$normalized = self::normalize_master_server_row($found, 'gta5');
			if( !is_array($normalized) )
				continue;

			$result[$out_addr] = $normalized;
		}

		return $result;
	}

	/**
	 * Универсальный preload для BeamMP
	 *
	 * @param array $rows
	 * @param int $ttl
	 * @return array<string, array>
	 */
	private static function resolve_preload_beammp( array $rows, int $ttl = 300 ): array
	{
		if( empty(self::$preload_primed_games['beammp']) )
		{
			self::prime_preload_beammp($ttl);
			self::$preload_primed_games['beammp'] = true;
		}

		$index = self::get_beammp_master_index();
		if( empty($index) )
			return [];

		$result = [];

		foreach( $rows as $row )
		{
			$addr = strtolower((string)($row['addr'] ?? ''));
			$ip = strtolower((string)($row['ip'] ?? ''));
			$query_port = (int)($row['query_port'] ?? 0);
			$port = (int)($row['port'] ?? 0);
			$addr_host = '';

			if( $addr !== '' && strpos($addr, ':') !== false )
				[$addr_host] = explode(':', $addr, 2) + [''];

			if( $query_port < 1 )
			{
				if( $addr !== '' && strpos($addr, ':') !== false )
				{
					[, $addr_port] = explode(':', $addr, 2) + ['', 0];
					$query_port = (int)$addr_port;
				}
			}

			if( $query_port < 1 )
				$query_port = $port;

			$candidates = [];
			if( $addr !== '' )
				$candidates[] = $addr;
			if( $ip !== '' && $query_port > 0 )
				$candidates[] = $ip . ':' . $query_port;
			if( $ip !== '' && $port > 0 )
				$candidates[] = $ip . ':' . $port;
			if( $addr_host !== '' && $query_port > 0 )
				$candidates[] = $addr_host . ':' . $query_port;
			if( $addr_host !== '' && $port > 0 )
				$candidates[] = $addr_host . ':' . $port;

			$candidates = array_values(array_unique(array_filter($candidates)));

			$found = null;
			foreach( $candidates as $candidate )
			{
				if( isset($index[$candidate]) )
				{
					$found = $index[$candidate];
					break;
				}
			}

			if( !$found )
				continue;

			$out_addr = (string)($row['addr'] ?? (($ip !== '' && $port > 0) ? ($ip . ':' . $port) : ''));
			if( $out_addr === '' )
				continue;

			$result[$out_addr] = $found;
		}

		return $result;
	}

	/**
	 * Resolves Palworld rows by address from the public master cache
	 *
	 * @param array $rows
	 * @param int $ttl
	 * @return array<string, array>
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function resolve_preload_palworld( array $rows, int $ttl = 300 ): array
	{
		if( empty(self::$preload_primed_games['palworld']) )
		{
			self::prime_preload_palworld($ttl);
			self::$preload_primed_games['palworld'] = true;
		}

		$index = self::get_palworld_master_index();
		if( empty($index) )
			return [];

		$result = [];

		foreach( $rows as $row )
		{
			$addr = strtolower(trim((string)($row['addr'] ?? '')));
			$ip = strtolower(trim((string)($row['ip'] ?? '')));
			$port = (int)($row['port'] ?? 0);
			$query_port = (int)($row['query_port'] ?? 0);
			$addr_host = '';

			if( $addr !== '' && strpos($addr, ':') !== false )
			{
				[ $addr_host, $addr_port ] = explode(':', $addr, 2) + ['', 0];

				if( $port < 1 )
					$port = (int)$addr_port;
			}

			if( $query_port < 1 )
				$query_port = $port;

			$candidates = [];
			if( $addr !== '' )
				$candidates[] = $addr;
			if( $ip !== '' && $port > 0 )
				$candidates[] = $ip . ':' . $port;
			if( $ip !== '' && $query_port > 0 )
				$candidates[] = $ip . ':' . $query_port;
			if( $addr_host !== '' && $port > 0 )
				$candidates[] = $addr_host . ':' . $port;

			$candidates = array_values(array_unique(array_filter($candidates)));
			$found = null;

			foreach( $candidates as $candidate )
			{
				if( isset($index[$candidate]) )
				{
					$found = $index[$candidate];
					break;
				}
			}

			if( !is_array($found) )
				continue;

			$out_addr = (string)($row['addr'] ?? ($found['addr'] ?? ''));
			if( $out_addr === '' )
				continue;

			$found['addr'] = $out_addr;
			$found['ip'] = ($ip !== '' ? $ip : (string)($found['ip'] ?? ''));
			$found['port'] = ($port > 0 ? $port : (int)($found['port'] ?? 0));
			$found['query_port'] = ($query_port > 0 ? $query_port : (int)($found['query_port'] ?? $found['port'] ?? 0));

			$result[$out_addr] = $found;
		}

		return $result;
	}

	/**
	 * Resolves Rust rows from the local master cache
	 *
	 * @param array $rows
	 * @param int $ttl
	 * @return array<string, array>
	 */
	private static function resolve_preload_rust( array $rows, int $ttl = 300 ): array
	{
		if( empty(self::$preload_primed_games['rust']) )
		{
			self::prime_preload_rust($ttl);
			self::$preload_primed_games['rust'] = true;
		}

		$index = self::get_rust_master_index();
		if( empty($index) )
			return [];

		$result = [];

		foreach( $rows as $row )
		{
			$addr = strtolower((string)($row['addr'] ?? ''));
			$ip = strtolower((string)($row['ip'] ?? ''));
			$query_port = (int)($row['query_port'] ?? 0);
			$port = (int)($row['port'] ?? 0);

			$candidates = [];
			if( $addr !== '' )
				$candidates[] = $addr;
			if( $ip !== '' && $port > 0 )
				$candidates[] = $ip . ':' . $port;
			if( $ip !== '' && $query_port > 0 )
				$candidates[] = $ip . ':' . $query_port;

			$candidates = array_values(array_unique(array_filter($candidates)));

			$found = null;
			foreach( $candidates as $candidate )
			{
				if( isset($index[$candidate]) )
				{
					$found = $index[$candidate];
					break;
				}
			}

			if( !is_array($found) )
				continue;

			$out_addr = (string)($row['addr'] ?? ($found['addr'] ?? ''));
			if( $out_addr === '' )
				continue;

			$server = $found;
			$server['addr'] = $out_addr;
			$server['ip'] = ($ip !== '' ? $ip : (string)($server['ip'] ?? ''));
			$server['port'] = ($port > 0 ? $port : (int)($server['port'] ?? 0));
			$server['query_port'] = ($query_port > 0 ? $query_port : (int)($server['query_port'] ?? $server['port'] ?? 0));
			$server['game'] = 'rust';

			$result[$out_addr] = $server;
		}

		return $result;
	}

	/**
	 * Универсальный preload для ARK: Survival Ascended
	 * берёт те же данные, что и cron, чтобы точечное обновление не уходило в обычный query
	 *
	 * @param array $rows
	 * @param int $ttl
	 * @return array<string, array>
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function resolve_preload_arksa( array $rows, int $ttl = 120 ): array
	{
		$file = self::build_file_path('arksa_master_servers');
		$jsnh = self::get_instance()->jsnh;

		if( $file === null || !is_file($file) || !is_object($jsnh) || !method_exists($jsnh, 'stream_json_objects_by_field_values') )
			return [];

		$result = [];
		$search_map = [];
		$row_meta = [];

		foreach( $rows as $row )
		{
			$addr = strtolower((string)($row['addr'] ?? ''));
			$ip = strtolower((string)($row['ip'] ?? ''));
			$query_port = (int)($row['query_port'] ?? 0);
			$port = (int)($row['port'] ?? 0);
			$addr_host = '';

			if( $addr !== '' && strpos($addr, ':') !== false )
				[ $addr_host ] = explode(':', $addr, 2) + [''];

			if( $query_port < 1 && $addr !== '' && strpos($addr, ':') !== false )
			{
				[ , $addr_port ] = explode(':', $addr, 2) + ['', 0];
				$query_port = (int)$addr_port;
			}

			if( $port < 1 )
				$port = $query_port;

			if( $query_port < 1 )
				$query_port = $port;

			$out_addr = (string)($row['addr'] ?? (($ip !== '' && $port > 0) ? ($ip . ':' . $port) : ''));
			if( $out_addr === '' )
				continue;

			$candidates = [];
			if( $addr !== '' )
				$candidates[] = $addr;
			if( $ip !== '' && $port > 0 )
				$candidates[] = $ip . ':' . $port;
			if( $ip !== '' && $query_port > 0 )
				$candidates[] = $ip . ':' . $query_port;
			if( $addr_host !== '' && $port > 0 )
				$candidates[] = $addr_host . ':' . $port;
			if( $addr_host !== '' && $query_port > 0 )
				$candidates[] = $addr_host . ':' . $query_port;

			$candidates = array_values(array_unique(array_filter($candidates)));
			if( empty($candidates) )
				continue;

			$row_meta[$out_addr] = [
				'ip' => $ip,
				'port' => $port,
				'query_port' => $query_port,
			];

			foreach( $candidates as $candidate )
				$search_map[$candidate][$out_addr] = true;
		}

		if( empty($search_map) )
			return [];

		foreach( $jsnh->stream_json_objects_by_field_values($file, 'addr', array_keys($search_map)) as $server )
		{
			if( !is_array($server) )
				continue;

			$server_addr = strtolower((string)($server['addr'] ?? ''));
			$server_ip = strtolower((string)($server['ip'] ?? ''));
			$server_port = (int)($server['port'] ?? 0);
			$server_query_port = (int)($server['query_port'] ?? $server_port);
			$keys = array_values(array_unique(array_filter([
				$server_addr,
				($server_ip !== '' && $server_port > 0 ? $server_ip . ':' . $server_port : ''),
				($server_ip !== '' && $server_query_port > 0 ? $server_ip . ':' . $server_query_port : ''),
			])));

			foreach( $keys as $key )
			{
				if( empty($search_map[$key]) )
					continue;

				foreach( array_keys($search_map[$key]) as $out_addr )
				{
					if( isset($result[$out_addr]) )
						continue;

					$meta = $row_meta[$out_addr] ?? [];
					$matched = $server;
					$matched['addr'] = $out_addr;
					$matched['ip'] = (!empty($meta['ip']) ? (string)$meta['ip'] : (string)($matched['ip'] ?? ''));
					$matched['port'] = (!empty($meta['port']) ? (int)$meta['port'] : (int)($matched['port'] ?? 0));
					$matched['query_port'] = (!empty($meta['query_port']) ? (int)$meta['query_port'] : (int)($matched['query_port'] ?? $matched['port'] ?? 0));
					$matched['game'] = 'ark-survival-ascended';

					if( !isset($matched['name']) && isset($matched['hostname']) )
						$matched['name'] = (string)$matched['hostname'];

					if( !isset($matched['players']) && isset($matched['numplayers']) )
						$matched['players'] = (int)$matched['numplayers'];

					if( !isset($matched['max_players']) && isset($matched['maxplayers']) )
						$matched['max_players'] = (int)$matched['maxplayers'];

					if( !isset($matched['info_raw']) || !is_array($matched['info_raw']) )
					{
						$info_raw = $matched;
						unset($info_raw['info_raw']);
						$matched['info_raw'] = $info_raw;
					}

					$result[$out_addr] = $matched;
				}
			}

			if( count($result) >= count($row_meta) )
				break;
		}

		return $result;
	}

	/**
	 * Индекс для GTA5 из gtan + gtar
	 *
	 * @return array<string, array>
	 */
	private static function get_gta5_master_index(): array
	{
		$gtan_file = self::build_file_path('gtan_master_servers');
		$gtar_file = self::build_file_path('gtar_master_servers');

		$signature_parts = ['gta5'];
		foreach( [$gtan_file, $gtar_file] as $file )
		{
			if( $file === null || !is_file($file) )
			{
				$signature_parts[] = 'missing';
				continue;
			}

			$mtime = @filemtime($file);
			$size = @filesize($file);
			$signature_parts[] = $file . '|' . (int)$mtime . '|' . (int)$size;
		}

		$signature = implode(';', $signature_parts);
		if( isset(self::$preload_index_cache[$signature]) )
			return self::$preload_index_cache[$signature];

		$index = [];
		$gtar_mtime = (is_string($gtar_file) && is_file($gtar_file) ? (int)@filemtime($gtar_file) : 0);
		$gtan_mtime = (is_string($gtan_file) && is_file($gtan_file) ? (int)@filemtime($gtan_file) : 0);

		$gtar = self::read('gtar_master_servers');
		if( is_array($gtar) )
		{
			foreach( $gtar as $address => $server )
			{
				if( !is_array($server) || !is_string($address) )
					continue;

				$raw = self::map_gtar_row_to_raw($address, $server);
				if( empty($raw) )
					continue;

				if( $gtar_mtime > 0 )
					$raw['_source_updated_at'] = $gtar_mtime;

				$index[strtolower($address)] = $raw;
			}
		}

		$gtan = self::read('gtan_master_servers');
		if( is_array($gtan) )
		{
			$rows = $gtan;
			if( isset($gtan['servers']) && is_array($gtan['servers']) )
				$rows = $gtan['servers'];

			foreach( $rows as $key => $server )
			{
				if( !is_array($server) )
					continue;

				$raw = self::map_gtan_row_to_raw($key, $server);
				if( empty($raw) )
					continue;

				if( $gtan_mtime > 0 )
					$raw['_source_updated_at'] = $gtan_mtime;

				$address_key = strtolower((string)($raw['gq_address'] ?? '') . ':' . (int)($raw['gq_port_query'] ?? 0));
				if( $address_key !== ':' && $address_key !== '' )
					$index[$address_key] = $raw;
			}
		}

		self::$preload_index_cache = [$signature => $index];

		return $index;
	}

	/**
	 * Приводит кешированную Rust запись к виду пригодному для preload и harvester
	 *
	 * @param mixed $addr_key
	 * @param array $row
	 * @param int $rust_mtime
	 * @return array
	 */
	private static function hydrate_rust_cached_row( $addr_key, array $row, int $rust_mtime = 0 ): array
	{
		$server = $row;
		$addr = (string)($server['addr'] ?? (is_string($addr_key) ? $addr_key : ''));
		if( $addr === '' || strpos($addr, ':') === false )
			return [];

		[ $ip, $port_str ] = explode(':', $addr, 2) + [ '', '0' ];
		$ip = strtolower(trim($ip));
		$port = (int)($server['port'] ?? $server['gameport'] ?? $port_str ?? 0);
		if( $ip === '' || $port < 1 )
			return [];

		$info = (isset($server['info']) && is_array($server['info']) ? $server['info'] : []);
		$info_raw = (isset($server['info_raw']) && is_array($server['info_raw']) ? $server['info_raw'] : []);
		$query_port = (int)($server['query_port'] ?? $info_raw['gq_port_query'] ?? $server['gq_port_query'] ?? $port);
		$keywords = (string)($info_raw['keywords'] ?? $info_raw['gametype'] ?? '');

		$server['addr'] = $ip . ':' . $port;
		$server['ip'] = $ip;
		$server['port'] = $port;
		$server['query_port'] = ($query_port > 0 ? $query_port : $port);
		$server['game'] = 'rust';
		$server['info'] = $info;
		$server['info_raw'] = $info_raw;

		if( (int)($server['players'] ?? 0) < 1 && isset($info_raw['players']) && is_scalar($info_raw['players']) )
			$server['players'] = (int)$info_raw['players'];

		if( (int)($server['max_players'] ?? 0) < 1 && isset($info_raw['max_players']) && is_scalar($info_raw['max_players']) )
			$server['max_players'] = (int)$info_raw['max_players'];

		if( !isset($server['bots']) && isset($info_raw['bots']) && is_scalar($info_raw['bots']) )
			$server['bots'] = (int)$info_raw['bots'];

		$actual_players = (int)($server['actual_players'] ?? 0);
		if( $actual_players < 1 && isset($info_raw['actual_players']) && is_scalar($info_raw['actual_players']) )
			$actual_players = (int)$info_raw['actual_players'];

		if( $actual_players < 1 && $keywords !== '' )
		{
			foreach( explode(',', $keywords) as $kw )
			{
				if( strpos($kw, 'cp') === 0 )
				{
					$actual_players = (int)preg_replace('/\D/', '', $kw);
					break;
				}
			}
		}

		if( $actual_players < 0 )
			$actual_players = 0;

		if( $actual_players < 1 )
			$actual_players = max(0, (int)($server['players'] ?? 0) - (int)($server['bots'] ?? 0));

		$server['actual_players'] = $actual_players;

		if( empty($info['last_wipe']) && $keywords !== '' )
		{
			foreach( explode(',', $keywords) as $kw )
			{
				if( strpos($kw, 'born') === 0 )
				{
					$info['last_wipe'] = (int)mb_substr($kw, 4);
					break;
				}
			}
		}

		if( !isset($info['origin']) )
			$info['origin'] = false;

		if( !isset($info['players_count_arr']) )
			$info['players_count_arr'] = (int)($server['players'] ?? 0);

		$server['info'] = $info;

		if( $rust_mtime > 0 )
		{
			$server['_source_updated_at'] = $rust_mtime;

			if( is_array($server['info_raw']) )
				$server['info_raw']['_source_updated_at'] = $rust_mtime;
		}

		return $server;
	}

	/**
	 * Индекс Rust по ключу addr и ip:query_port
	 *
	 * @return array<string, array>
	 */
	private static function get_rust_master_index(): array
	{
		$rust_file = self::build_file_path('rust_master_servers');

		$signature_parts = ['rust'];
		if( $rust_file === null || !is_file($rust_file) )
			$signature_parts[] = 'missing';
		else
		{
			$mtime = @filemtime($rust_file);
			$size = @filesize($rust_file);
			$signature_parts[] = $rust_file . '|' . (int)$mtime . '|' . (int)$size;
		}

		$signature = implode(';', $signature_parts);
		if( isset(self::$preload_index_cache[$signature]) )
			return self::$preload_index_cache[$signature];

		$index = [];
		$rust_mtime = (is_string($rust_file) && is_file($rust_file) ? (int)@filemtime($rust_file) : 0);
		$rust_rows = self::read('rust_master_servers');
		if( !is_array($rust_rows) )
		{
			self::$preload_index_cache = [$signature => $index];
			return $index;
		}

		if( isset($rust_rows['servers']) && is_array($rust_rows['servers']) )
			$rust_rows = $rust_rows['servers'];

		foreach( $rust_rows as $addr_key => $raw )
		{
			if( !is_array($raw) )
				continue;

			$server = self::hydrate_rust_cached_row($addr_key, $raw, $rust_mtime);
			if( empty($server['addr']) )
				continue;

			$index[strtolower((string)$server['addr'])] = $server;

			if( !empty($server['ip']) && !empty($server['query_port']) )
				$index[strtolower((string)$server['ip'] . ':' . (int)$server['query_port'])] = $server;
		}

		self::$preload_index_cache = [$signature => $index];

		return $index;
	}

	/**
	 * Индекс ARK:SA по ключу addr и ip:query_port
	 *
	 * @return array<string, array>
	 */
	private static function get_arksa_master_index(): array
	{
		$arksa_file = self::build_file_path('arksa_master_servers');

		$signature_parts = ['arksa'];
		if( $arksa_file === null || !is_file($arksa_file) )
			$signature_parts[] = 'missing';
		else
		{
			$mtime = @filemtime($arksa_file);
			$size = @filesize($arksa_file);
			$signature_parts[] = $arksa_file . '|' . (int)$mtime . '|' . (int)$size;
		}

		$signature = implode(';', $signature_parts);
		if( isset(self::$preload_index_cache[$signature]) )
			return self::$preload_index_cache[$signature];

		$index = [];
		$arksa_mtime = (is_string($arksa_file) && is_file($arksa_file) ? (int)@filemtime($arksa_file) : 0);
		$arksa_rows = self::read('arksa_master_servers');
		if( !is_array($arksa_rows) )
		{
			self::$preload_index_cache = [$signature => $index];
			return $index;
		}

		if( isset($arksa_rows['servers']) && is_array($arksa_rows['servers']) )
			$arksa_rows = $arksa_rows['servers'];

		foreach( $arksa_rows as $addr_key => $server )
		{
			if( !is_array($server) )
				continue;

			$server_addr = (string)($server['addr'] ?? $addr_key ?? '');
			$server_ip = strtolower(trim((string)($server['ip'] ?? '')));
			$server_port = (int)($server['port'] ?? $server['clientport'] ?? 0);
			$server_query_port = (int)($server['query_port'] ?? $server['gq_port_query'] ?? 0);

			if( $server_addr === '' && $server_ip !== '' && $server_port > 0 )
				$server_addr = $server_ip . ':' . $server_port;

			if( $server_ip === '' && strpos($server_addr, ':') !== false )
				[ $server_ip ] = explode(':', $server_addr, 2) + [''];

			if( $server_port < 1 && strpos($server_addr, ':') !== false )
				[ , $server_port ] = explode(':', $server_addr, 2) + ['', 0];

			if( $server_query_port < 1 )
				$server_query_port = $server_port;

			if( $server_addr === '' || $server_ip === '' || $server_port < 1 )
				continue;

			$server['addr'] = $server_addr;
			$server['ip'] = $server_ip;
			$server['port'] = $server_port;
			$server['clientport'] = ($server['clientport'] ?? $server_port);
			$server['query_port'] = $server_query_port;
			$server['gq_port_query'] = $server_query_port;
			$server['game'] = 'ark-survival-ascended';

			if( $arksa_mtime > 0 )
			{
				$server['_source_updated_at'] = $arksa_mtime;

				if( !empty($server['info_raw']) && is_array($server['info_raw']) )
					$server['info_raw']['_source_updated_at'] = $arksa_mtime;
			}

			$index[strtolower($server_addr)] = $server;

			if( $server_ip !== '' && $server_query_port > 0 )
				$index[strtolower($server_ip . ':' . $server_query_port)] = $server;
		}

		self::$preload_index_cache = [$signature => $index];

		return $index;
	}

	/**
	 * Builds the Palworld master index by address
	 *
	 * @return array<string, array>
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function get_palworld_master_index(): array
	{
		$cache_file = self::build_file_path('palworld_master_servers');
		$signature_parts = ['palworld'];

		if( $cache_file === null || !is_file($cache_file) )
			$signature_parts[] = 'missing';
		else
			$signature_parts[] = $cache_file . '|' . (int)@filemtime($cache_file) . '|' . (int)@filesize($cache_file);

		$signature = implode(';', $signature_parts);
		if( isset(self::$preload_index_cache[$signature]) )
			return self::$preload_index_cache[$signature];

		$cache = self::read('palworld_master_servers');
		$rows = (is_array($cache['servers'] ?? null) ? $cache['servers'] : []);
		$cache_mtime = ($cache_file !== null && is_file($cache_file) ? (int)@filemtime($cache_file) : 0);
		$index = [];

		foreach( $rows as $row )
		{
			if( !is_array($row) )
				continue;

			$mapped = self::map_palworld_row_to_server($row);
			if( empty($mapped) )
				continue;

			$server = self::normalize_master_server_row($mapped, 'palworld');
			if( !is_array($server) )
				continue;

			if( $cache_mtime > 0 )
			{
				$server['_source_updated_at'] = $cache_mtime;
				$server['info_raw']['_source_updated_at'] = $cache_mtime;
			}

			$index[strtolower((string)$server['addr'])] = $server;
		}

		self::$preload_index_cache = [$signature => $index];

		return $index;
	}

	/**
	 * Maps one Palworld API row to the monitoring server format
	 *
	 * @param array $row
	 * @return array
	 *
	 * @version 1.0.0
	 * @author  bySanya@
	 */
	private static function map_palworld_row_to_server( array $row ): array
	{
		$ip = strtolower(trim((string)($row['address'] ?? '')));
		$port = (int)($row['port'] ?? 0);
		$name = trim((string)($row['name'] ?? ''));

		if( $ip === '' || $port < 1 || $name === '' )
			return [];

		$players = (int)($row['current_players'] ?? 0);
		$max_players = (int)($row['max_players'] ?? 0);
		$map = trim((string)($row['map_name'] ?? ''));

		return [
			'_preserve_provided_info' => true,
			'ip' => $ip,
			'port' => $port,
			'game' => 'palworld',
			'name' => $name,
			'map' => $map,
			'players' => $players,
			'max_players' => $max_players,
			'password' => !empty($row['is_password']),
			'version' => (string)($row['version'] ?? ''),
			'description' => (string)($row['description'] ?? ''),
			'info' => [
				'region' => (string)($row['region'] ?? ''),
				'platform' => (string)($row['platform'] ?? ''),
				'world_guid' => (string)($row['world_guid'] ?? ''),
				'days' => (int)($row['days'] ?? 0),
				'server_time' => (int)($row['server_time'] ?? 0),
			],
			'info_raw' => $row,
		];
	}

	/**
	 * Builds the BeamMP master index by address
	 *
	 * @return array<string, array>
	 */
	private static function get_beammp_master_index(): array
	{
		$beam_file = self::build_file_path('beammp_servers_info');

		$signature_parts = ['beammp'];
		if( $beam_file === null || !is_file($beam_file) )
			$signature_parts[] = 'missing';
		else
		{
			$mtime = @filemtime($beam_file);
			$size = @filesize($beam_file);
			$signature_parts[] = $beam_file . '|' . (int)$mtime . '|' . (int)$size;
		}

		$signature = implode(';', $signature_parts);
		if( isset(self::$preload_index_cache[$signature]) )
			return self::$preload_index_cache[$signature];

		$index = [];
		$beam_mtime = (is_string($beam_file) && is_file($beam_file) ? (int)@filemtime($beam_file) : 0);
		$beam_rows = self::read('beammp_servers_info');
		if( !is_array($beam_rows) )
		{
			self::$preload_index_cache = [$signature => $index];
			return $index;
		}

		$rows = $beam_rows;
		if( isset($beam_rows['servers']) && is_array($beam_rows['servers']) )
			$rows = $beam_rows['servers'];

		foreach( $rows as $server_row )
		{
			if( !is_array($server_row) )
				continue;

			$mapped = self::map_beammp_row_to_server($server_row);
			if( empty($mapped) )
				continue;

			if( $beam_mtime > 0 )
			{
				$mapped['_source_updated_at'] = $beam_mtime;

				if( isset($mapped['info_raw']) && is_array($mapped['info_raw']) )
					$mapped['info_raw']['_source_updated_at'] = $beam_mtime;
			}

			$key = strtolower((string)$mapped['ip'] . ':' . (int)($mapped['port'] ?? 0));
			$index[$key] = $mapped;
		}

		self::$preload_index_cache = [$signature => $index];

		return $index;
	}

	/**
	 * Преобразует строку BeamMP в формат для update_server_info
	 *
	 * @param array $server_row
	 * @return array
	 */
	private static function map_beammp_row_to_server( array $server_row ): array
	{
		$ip = strtolower((string)($server_row['ip'] ?? ''));
		$port = (int)($server_row['port'] ?? 0);

		if( $ip === '' || $port < 1 )
			return [];

		$name = (string)($server_row['sname'] ?? $server_row['name'] ?? '');
		$name = preg_replace('/\^./', '', $name) ?? '';
		$name = trim(strip_tags($name));

		$map = self::extract_beammp_map_name((string)($server_row['map'] ?? ''));

		$players = (int)($server_row['players'] ?? 0);
		$max_players = (int)($server_row['maxplayers'] ?? 0);
		$players_list = [];

		foreach( explode(';', (string)($server_row['playerslist'] ?? '')) as $player_name )
		{
			$player_name = trim($player_name);
			if( $player_name === '' )
				continue;

			$players_list[] = ['name' => $player_name];

			if( count($players_list) >= 512 )
				break;
		}

		$raw = $server_row;
		if( isset($raw['modlist']) && is_string($raw['modlist']) && strlen($raw['modlist']) > 3000 )
			$raw['modlist'] = substr($raw['modlist'], 0, 3000);
		if( isset($raw['playerslist']) && is_string($raw['playerslist']) && strlen($raw['playerslist']) > 1000 )
			$raw['playerslist'] = substr($raw['playerslist'], 0, 1000);

		return [
			'ip' => $ip,
			'port' => $port,
			'game' => 'beammp',
			'name' => $name,
			'map' => $map,
			'players' => $players,
			'actual_players' => $players,
			'max_players' => $max_players,
			'bots' => 0,
			'gq_online' => true,
			'players_list' => $players_list,
			'info' => [
				'ident' => (string)($server_row['ident'] ?? ''),
				'owner' => (string)($server_row['owner'] ?? ''),
				'location' => (string)($server_row['location'] ?? ''),
				'tags_raw' => (string)($server_row['tags'] ?? ''),
				'version' => (string)($server_row['version'] ?? ''),
				'cversion' => (string)($server_row['cversion'] ?? ''),
			],
			'info_raw' => $raw,
		];
	}

	/**
	 * Извлекает короткое имя карты из пути BeamMP
	 *
	 * @param string $map_raw
	 * @return string
	 */
	private static function extract_beammp_map_name( string $map_raw ): string
	{
		if( $map_raw === '' )
			return '';

		$map_raw = preg_replace('/[\x00-\x1F]+/', '', $map_raw);
		$map_raw = preg_replace('/\?.*$/', '', $map_raw);
		$map_raw = str_replace('\\', '/', $map_raw);

		if( preg_match('#/levels/([^/]+)/#i', $map_raw, $matches) )
			return trim($matches[1]);

		$trimmed = trim($map_raw, '/');
		$parts = explode('/', $trimmed);
		$last = end($parts);

		if( $last === 'info.json' && count($parts) > 1 )
			$last = $parts[count($parts) - 2];

		return trim(pathinfo($last, PATHINFO_FILENAME));
	}

	/**
	 * Преобразует строку GTAR в сырой формат для normalize
	 *
	 * @param string $address
	 * @param array $server
	 * @return array
	 */
	private static function map_gtar_row_to_raw( string $address, array $server ): array
	{
		$host = '';
		$query_port = 0;

		if( strpos($address, ':') !== false )
		{
			[$host, $port_str] = explode(':', $address, 2) + ['', '0'];
			$query_port = (int)$port_str;
		}

		if( $host === '' || $query_port < 1 )
			return [];

		$raw = [
			'ip' => $host,
			'gq_address' => $host,
			'gq_port_query' => $query_port,
			'gq_port_client' => $query_port,
			'gq_type' => 'gtar',
			'gq_name' => 'Grand Theft Auto Rage',
			'hostname' => (string)($server['name'] ?? $server['hostname'] ?? ''),
			'gq_hostname' => (string)($server['name'] ?? $server['hostname'] ?? ''),
			'numplayers' => (int)($server['players'] ?? 0),
			'gq_numplayers' => (int)($server['players'] ?? 0),
			'maxplayers' => (int)($server['maxplayers'] ?? 0),
			'gq_maxplayers' => (int)($server['maxplayers'] ?? 0),
			'mod' => (string)($server['gamemode'] ?? ''),
			'actual_players' => (int)($server['players'] ?? 0),
			'max_players' => (int)($server['maxplayers'] ?? 0),
			'players' => (int)($server['players'] ?? 0),
			'game' => 'gta5',
			'rules' => [],
		];

		return $raw;
	}

	/**
	 * Преобразует строку GTAN в сырой формат для normalize
	 *
	 * @param mixed $key
	 * @param array $server
	 * @return array
	 */
	private static function map_gtan_row_to_raw( $key, array $server ): array
	{
		$host = (string)($server['Host'] ?? $server['ip'] ?? $server['host'] ?? '');
		$query_port = (int)($server['Port'] ?? $server['QueryPort'] ?? $server['query_port'] ?? 0);

		if( $query_port < 1 && is_string($key) && strpos($key, ':') !== false )
		{
			[$addr_host, $addr_port] = explode(':', $key, 2) + ['', '0'];
			if( $host === '' )
				$host = $addr_host;
			$query_port = (int)$addr_port;
		}

		if( $host === '' || $query_port < 1 )
			return [];

		$raw = [
			'ip' => $host,
			'gq_address' => $host,
			'gq_port_query' => $query_port,
			'gq_port_client' => $query_port,
			'gq_type' => 'gtan',
			'gq_name' => 'GTA Five M',
			'hostname' => (string)($server['ServerName'] ?? $server['hostname'] ?? $server['name'] ?? ''),
			'gq_hostname' => (string)($server['ServerName'] ?? $server['hostname'] ?? $server['name'] ?? ''),
			'numplayers' => (int)($server['CurrentPlayers'] ?? $server['players'] ?? 0),
			'gq_numplayers' => (int)($server['CurrentPlayers'] ?? $server['players'] ?? 0),
			'maxplayers' => (int)($server['MaxPlayers'] ?? $server['maxplayers'] ?? 0),
			'gq_maxplayers' => (int)($server['MaxPlayers'] ?? $server['maxplayers'] ?? 0),
			'mod' => (string)($server['Gamemode'] ?? $server['gamemode'] ?? ''),
			'actual_players' => (int)($server['CurrentPlayers'] ?? $server['players'] ?? 0),
			'max_players' => (int)($server['MaxPlayers'] ?? $server['maxplayers'] ?? 0),
			'players' => (int)($server['CurrentPlayers'] ?? $server['players'] ?? 0),
			'game' => 'gta5',
			'rules' => [],
		];

		return $raw;
	}

	/**
	 * Возвращает путь к бинарному SCUM cache
	 *
	 * @return string
	 */
	private static function scum_cache_file(): string
	{
		$cache_key = self::get_cache_file_key('scum', 'scum_master_servers');
		$file = self::build_file_path($cache_key);

		if( is_string($file) && $file !== '' )
			return $file;

		return rtrim(self::base_dir(), '/\\') . '/scum_master_servers.json';
	}

	/**
	 * Читает один снимок SCUM master и возвращает raw rows
	 *
	 * @return array<int, array>
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	private static function read_scum_master_snapshot(): array
	{
		$endpoints = self::get_master_socket_endpoints('scum');
		$debug_log = self::is_debug_log_enabled('scum');
		$logger = $debug_log ? self::get_instance()->logger : null;
		$endpoint_num = 0;

		foreach( $endpoints as $endpoint )
		{
			$ip = (string)($endpoint['ip'] ?? '');
			$port = (int)($endpoint['port'] ?? 0);

			if( $ip === '' || $port < 1 )
				continue;

			$endpoint_num++;
			$endpoint_label = $ip . ':' . $port;
			$socket = @fsockopen('tcp://' . $ip, $port, $errno, $errstr, 5);
			if( !$socket )
			{
				if( $debug_log )
					$logger->write("SCUM master {$endpoint_label} connect failed: {$errno} {$errstr}", 'warning', 'master_cache.log');

				continue;
			}

			fwrite($socket, "\x4C\x53\x54\x00\x00");
			stream_set_timeout($socket, 5);

			$response = '';

			while( !feof($socket) )
			{
				$chunk = fread($socket, 4096);
				if( $chunk === false || $chunk === '' )
					break;

				$response .= $chunk;
			}

			$meta = stream_get_meta_data($socket);
			@fclose($socket);

			if( strlen($response) < 5 )
			{
				$timed_out = !empty($meta['timed_out']) ? 1 : 0;
				if( $debug_log )
					$logger->write("SCUM master {$endpoint_label} bad response: bytes=" . strlen($response) . " timeout={$timed_out}", 'warning', 'master_cache.log');

				continue;
			}

			$snapshot = self::parse_scum_master_payload_raw(substr($response, 4));
			if( empty($snapshot) )
			{
				if( $debug_log )
					$logger->write("SCUM master {$endpoint_label} empty snapshot: bytes=" . strlen($response), 'warning', 'master_cache.log');

				continue;
			}

			if( $debug_log )
				$logger->write("SCUM master {$endpoint_label} ok: endpoint={$endpoint_num} bytes=" . strlen($response) . " servers=" . count($snapshot), 'debug', 'master_cache.log');

			return $snapshot;
		}

		if( $debug_log )
			$logger->write('SCUM master failed: no valid endpoint response', 'warning', 'master_cache.log');

		return [];
	}

	/**
	 * Гарантирует что SCUM cache актуален
	 *
	 * @param int $ttl
	 * @return bool
	 */
	private static function ensure_scum_cache_ready( int $ttl ): bool
	{
		$cache_file = self::scum_cache_file();
		$cache_mtime = @filemtime($cache_file);
		if( is_file($cache_file) && $cache_mtime !== false && ((time() - $cache_mtime) < $ttl) )
			return true;

		$lock_file = $cache_file . '.refresh.lock';
		$lock_dir = dirname($lock_file);
		if( !is_dir($lock_dir) )
			@mkdir($lock_dir, 0777, true);
		else
			@chmod($lock_dir, 0777);

		$lock_fp = @fopen($lock_file, 'c');
		if( !$lock_fp )
			return false;

		@chmod($lock_file, 0666);

		if( !@flock($lock_fp, LOCK_EX | LOCK_NB) )
		{
			@fclose($lock_fp);
			return self::is_fresh('scum_master_servers', $ttl);
		}

		try
		{
			$cache_mtime = @filemtime($cache_file);
			if( is_file($cache_file) && $cache_mtime !== false && ((time() - $cache_mtime) < $ttl) )
				return true;

			if( !self::refresh_scum_cache_from_master($cache_file) )
				return false;

			return self::is_fresh('scum_master_servers', $ttl);
		}
		finally
		{
			@flock($lock_fp, LOCK_UN);
			@fclose($lock_fp);
		}
	}

	/**
	 * Строит индекс SCUM по ip:query_port
	 *
	 * @return array<string, array>
	 */
	private static function get_scum_master_index(): array
	{
		$cache_file = self::scum_cache_file();
		if( !is_file($cache_file) )
			return [];

		$mtime = @filemtime($cache_file);
		$size = @filesize($cache_file);
		if( $mtime === false || $size === false )
			return [];

		$signature = 'scum|' . $cache_file . '|' . $mtime . '|' . $size;
		if( isset(self::$preload_index_cache[$signature]) )
			return self::$preload_index_cache[$signature];

		$index = self::build_scum_index_from_file($cache_file);
		self::$preload_index_cache = [$signature => $index];

		return $index;
	}

	/**
	 * Обновляет SCUM cache из master сокета
	 *
	 * @param string $cache_file
	 * @return bool
	 */
	private static function refresh_scum_cache_from_master( string $cache_file ): bool
	{
		$cache_dir = dirname($cache_file);
		if( !is_dir($cache_dir) )
			@mkdir($cache_dir, 0777, true);
		else
			@chmod($cache_dir, 0777);

		$raw_snapshots = [];
		for( $snapshot_index = 0; $snapshot_index < 2; $snapshot_index++ )
		{
			$snapshot = self::read_scum_master_snapshot();
			if( empty($snapshot) )
				continue;

			$raw_snapshots[] = $snapshot;
		}

		if( empty($raw_snapshots) )
			return false;

		$sample_count = count($raw_snapshots);
		$raw_response_map = [];
		foreach( $raw_snapshots as $snapshot )
		{
			$seen_in_snapshot = [];

			foreach( $snapshot as $row )
			{
				if( !is_array($row) )
					continue;

				$ip = strtolower(trim((string)($row['ip'] ?? '')));
				$q_port = (int)($row['query_port'] ?? 0);
				if( $ip === '' || $q_port < 1 )
					continue;

				$key = $ip . ':' . $q_port;
				if( isset($seen_in_snapshot[$key]) )
					continue;

				$seen_in_snapshot[$key] = true;

				if( !isset($raw_response_map[$key]) )
				{
					$row['master_hits'] = 1;
					$row['master_samples'] = $sample_count;
					$raw_response_map[$key] = $row;
					continue;
				}

				$hits = (int)($raw_response_map[$key]['master_hits'] ?? 0) + 1;
				$raw_response_map[$key] = array_replace($raw_response_map[$key], $row);
				$raw_response_map[$key]['master_hits'] = $hits;
				$raw_response_map[$key]['master_samples'] = $sample_count;
			}
		}

		$raw_response = array_values($raw_response_map);
		if( empty($raw_response) )
			return false;

		$json_body = self::encode_scum_cache_json($raw_response);
		if( !is_string($json_body) || $json_body === '' )
			return false;

		$tmp_file = $cache_file . '.tmp';
		if( @file_put_contents($tmp_file, $json_body, LOCK_EX) === false )
			return false;

		@chmod($tmp_file, 0666);

		if( !@rename($tmp_file, $cache_file) )
			return false;

		@chmod($cache_file, 0666);
		@unlink($cache_file . '.seen.json');
		@unlink($cache_file . '.seen.json.tmp');
		clearstatcache(true, $cache_file);

		return true;
	}


	/**
	 * Упаковывает SCUM master cache в JSON
	 * храним raw rows прямо массивом, без лишней обертки
	 *
	 * @param array $raw_response
	 * @return string|null
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	private static function encode_scum_cache_json( array $raw_response )
	{
		$json = @json_encode(array_values($raw_response), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return (is_string($json) ? $json : null);
	}

	/**
	 * Парсит payload SCUM master и возвращает raw response rows
	 *
	 * @param string $payload
	 * @return array<int, array>
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	private static function parse_scum_master_payload_raw( string $payload ): array
	{
		if( $payload === '' )
			return [];

		$rows = [];
		$blocks = explode("\xEF\xBE", $payload);

		foreach( $blocks as $block )
		{
			$row = self::parse_scum_server_block_raw($block);
			if( $row === null )
				continue;

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Читает SCUM cache и строит индекс по ip:query_port
	 *
	 * @param string $cache_file Путь к cache файлу
	 * @return array<string, array>
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	private static function build_scum_index_from_file( string $cache_file ): array
	{
		$cache_mtime = (int)@filemtime($cache_file);
		$fp = @fopen($cache_file, 'rb');
		if( !$fp )
			return [];

		$first_byte = fread($fp, 1);
		rewind($fp);

		if( $first_byte === '[' || $first_byte === '{' )
		{
			$raw = @file_get_contents($cache_file);
			@fclose($fp);

			if( !is_string($raw) || $raw === '' )
				return [];

			$data = self::decode_json_array($raw);
			if( !is_array($data) )
				return [];

			if( isset($data['raw_response']) && is_array($data['raw_response']) )
				$data = self::map_scum_master_rows_to_servers($data['raw_response']);
			elseif( isset($data['servers']) && is_array($data['servers']) )
				$data = $data['servers'];
			elseif( !empty($data['raw_response_base64']) && is_string($data['raw_response_base64']) )
			{
				$binary_response = base64_decode($data['raw_response_base64'], true);
				if( !is_string($binary_response) || strlen($binary_response) < 5 )
					return [];

				$data = self::map_scum_master_rows_to_servers(
					self::parse_scum_master_payload_raw(substr($binary_response, 4))
				);
			}

			$index = [];
			foreach( $data as $server )
			{
				if( !is_array($server) )
					continue;

				if( empty($server['gq_address']) && !empty($server['ip']) && !empty($server['query_port']) )
				{
					$server = self::map_scum_master_row_to_server($server);
					if( !is_array($server) )
						continue;
				}

				# старый json cache мог хранить raw внутри info_raw
				if( !empty($server['info_raw']) && is_array($server['info_raw']) )
					$server = array_replace($server['info_raw'], $server);

				$ip = (string)($server['gq_address'] ?? '');
				$q_port = (int)($server['gq_port_query'] ?? 0);

				if( ($ip === '' || $q_port < 1) && !empty($server['addr']) )
				{
					[ $addr_ip, $addr_port ] = explode(':', (string)$server['addr'], 2) + [ '', '0' ];
					if( $ip === '' )
						$ip = $addr_ip;
					if( $q_port < 1 )
						$q_port = (int)$addr_port;
				}

				if( $ip === '' || $q_port < 1 )
					continue;

				$server['gq_address'] = $ip;
				$server['gq_port_query'] = $q_port;
				$server['master_hits'] = max(1, (int)($server['master_hits'] ?? 1));
				$server['master_samples'] = max(1, (int)($server['master_samples'] ?? 1));
				unset($server['info'], $server['info_raw']);

				if( $cache_mtime > 0 )
					$server['_source_updated_at'] = $cache_mtime;

				$index[strtolower($ip . ':' . $q_port)] = $server;
			}

			return $index;
		}

		$index = [];
		$skip_header = 4;
		$pending = '';

		while( !feof($fp) )
		{
			$chunk = fread($fp, 4096);
			if( $chunk === false || $chunk === '' )
				break;

			if( $skip_header > 0 )
			{
				$skip_now = min($skip_header, strlen($chunk));
				$chunk = substr($chunk, $skip_now);
				$skip_header -= $skip_now;

				if( $chunk === '' )
					continue;
			}

			$pending .= $chunk;
			$blocks = explode("\xEF\xBE", $pending);
			$pending = array_pop($blocks);

			foreach( $blocks as $block )
			{
				$row = self::parse_scum_server_block_raw($block);
				$server = (is_array($row) ? self::map_scum_master_row_to_server($row) : null);
				if( $server === null )
					continue;

				if( $cache_mtime > 0 )
					$server['_source_updated_at'] = $cache_mtime;

				$server['master_hits'] = 1;
				$server['master_samples'] = 1;

				$key = strtolower((string)$server['gq_address'] . ':' . (int)$server['gq_port_query']);
				$index[$key] = $server;
			}
		}

		if( strlen($pending) >= 30 )
		{
			$row = self::parse_scum_server_block_raw($pending);
			$server = (is_array($row) ? self::map_scum_master_row_to_server($row) : null);
			if( $server !== null )
			{
				if( $cache_mtime > 0 )
					$server['_source_updated_at'] = $cache_mtime;

				$server['master_hits'] = 1;
				$server['master_samples'] = 1;

				$key = strtolower((string)$server['gq_address'] . ':' . (int)$server['gq_port_query']);
				$index[$key] = $server;
			}
		}

		@fclose($fp);

		return $index;
	}

	/**
	 * Парсит один блок SCUM master в raw row
	 *
	 * @param string $block Бинарный блок одного сервера
	 * @return array|null
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	private static function parse_scum_server_block_raw( string $block )
	{
		if( strlen($block) < 30 )
			return null;

		$ip = implode('.', array_reverse(unpack('C4', substr($block, 0, 4))));
		$q_port = unpack('v', substr($block, 4, 2))[1];
		$client_port = $q_port - 2;

		$players = ord($block[9]);
		$players_max = ord($block[10]);
		$password = (ord($block[11]) >> 1) & 1;
		$official = ((strlen($block) > 12) ? ((ord($block[12]) & 1)) : 0);

		$name_len = ((strlen($block) > 29) ? ord($block[29]) : 0);
		$name_part = '';
		$name = '';
		if( strlen($block) > 30 )
		{
			$name_part = substr($block, 30, ($name_len > 0 ? $name_len : null));
			$name = trim(@mb_convert_encoding($name_part, 'UTF-8', 'UTF-8'));
		}

		$time = self::parse_scum_master_time($block);
		$version = '';

		if( strlen($block) >= 29 )
		{
			$v2 = unpack('v', substr($block, 27, 2))[1];
			$v3 = unpack('v', substr($block, 25, 2))[1];
			$v4 = unpack('V', substr($block, 21, 4))[1];
			$version = '0.' . $v2 . '.' . $v3 . '.' . $v4;
		}

		return [
			'ip' => $ip,
			'port' => $client_port,
			'query_port' => $q_port,
			'name' => $name,
			'players' => $players,
			'actual_players' => $players,
			'max_players' => $players_max,
			'players' => $players,
			'password' => $password,
			'time' => $time,
			'version' => $version,
			'official' => $official,
		];
	}

	/**
	 * Разбирает время суток SCUM из master-блока
	 *
	 * В байтах 13..16 master отдаёт little-endian float32 в часах суток.
	 * Пример: 7.449 -> 07:27.
	 *
	 * @param string $block
	 * @return string
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	public static function parse_scum_master_time( string $block ): string
	{
		if( strlen($block) < 17 )
			return '00:00';

		$hours = unpack('g', substr($block, 13, 4))[1];
		if( !is_float($hours) && !is_int($hours) )
			return '00:00';

		if( $hours < 0 || $hours >= 24.000001 )
			return '00:00';

		$total_minutes = ((int)round($hours * 60)) % 1440;

		return str_pad(intdiv($total_minutes, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad($total_minutes % 60, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Преобразует raw rows SCUM в обычные server rows
	 *
	 * @param array $rows Сырые строки из master
	 * @return array<int, array>
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	private static function map_scum_master_rows_to_servers( array $rows ): array
	{
		$servers = [];

		foreach( $rows as $row )
		{
			if( !is_array($row) )
				continue;

			$server = self::map_scum_master_row_to_server($row);
			if( $server === null )
				continue;

			$servers[] = $server;
		}

		return $servers;
	}

	/**
	 * Преобразует raw_response row SCUM в рабочий server row
	 *
	 * @param array $row
	 * @return array|null
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	private static function map_scum_master_row_to_server( array $row )
	{
		$ip = strtolower(trim((string)($row['ip'] ?? '')));
		$q_port = (int)($row['query_port'] ?? 0);
		$client_port = (int)($row['port'] ?? 0);

		if( $ip === '' || $q_port < 1 || $client_port < 1 )
			return null;

		$name = trim((string)($row['name'] ?? ''));

		$players = (int)($row['players'] ?? 0);
		$max_players = (int)($row['max_players'] ?? 0);
		$actual_players = (int)($row['actual_players'] ?? $players);

		return [
			'addr' => $ip . ':' . $q_port,
			'ip' => $ip,
			'port' => $client_port,
			'query_port' => $q_port,
			'gq_address' => $ip,
			'gq_port_client' => $client_port,
			'gq_port_query' => $q_port,
			'gq_type' => 'scum',
			'gq_hostname' => $name,
			'gq_numplayers' => $players,
			'gq_maxplayers' => $max_players,
			'hostname' => $name,
			'numplayers' => $players,
			'maxplayers' => $max_players,
			'actual_players' => $actual_players,
			'max_players' => $max_players,
			'players' => $players,
			'password' => (int)($row['password'] ?? 0),
			'time' => (string)($row['time'] ?? '00:00'),
			'version' => (string)($row['version'] ?? ''),
			'official' => (int)($row['official'] ?? 0),
			'game' => 'scum',
			'rules' => [],
		];
	}

	/**
	 * Проверяет свежесть локального master json файла
	 *
	 * @param string $cache_key
	 * @param int $ttl
	 * @return bool
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	public static function is_fresh( string $cache_key, int $ttl ): bool
	{
		$file = self::build_file_path($cache_key);
		if( $file === null || !is_file($file) )
			return false;

		$mtime = @filemtime($file);
		if( $mtime === false )
			return false;

		return ((time() - $mtime) < $ttl);
	}

	/**
	 * Совместимость со старым названием has_fresh_cache
	 *
	 * @param string $cache_key
	 * @param int $ttl
	 * @return bool
	 */
	public static function has_fresh( string $cache_key, int $ttl ): bool
	{
		return self::is_fresh($cache_key, $ttl);
	}

	/**
	 * Качает master json с проверкой статуса и структуры
	 *
	 * @param string $url
	 * @return string|null
	 *
	 * @version 1.0.0
	 * @author bySanya@
	 */
	public static function download_json( string $url )
	{
		$ch = curl_init($url);

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 4,
			CURLOPT_TIMEOUT => 8,
			CURLOPT_USERAGENT => 'lagnetic-master-cache/1.0',
			CURLOPT_HTTPHEADER => ['Accept: application/json'],
		]);

		$body = curl_exec($ch);
		$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if( !is_string($body) || $body === '' || $http_code < 200 || $http_code >= 300 )
			return null;

		$decoded = self::decode_json_array($body);
		if( !is_array($decoded) )
			return null;

		return $body;
	}

	/**
	 * Возвращает путь к каталогу локальных master файлов
	 *
	 * @return string
	 */
	private static function base_dir( ): string
	{
		# если define уже есть, берем его
		if( defined('CACHEDIR') )
			return CACHEDIR . '/gameq_json';

		# fallback на случай раннего вызова до загрузки paths.php
		$src_dir = dirname(__DIR__, 2);

		return $src_dir . '/backend/cache/gameq_json';
	}

	/**
	 * Нормализует ключ кеша
	 *
	 * @param string $cache_key
	 * @return string
	 */
	private static function normalize_key( string $cache_key ): string
	{
		$key = strtolower(trim($cache_key));
		$key = preg_replace('/[^a-z0-9_\-]/', '', $key) ?? '';

		return $key;
	}

	/**
	 * Путь к локальному master json файлу
	 *
	 * @param string $cache_key
	 * @return string|null
	 */
	public static function build_file_path( string $cache_key )
	{
		$key = self::normalize_key($cache_key);
		if( $key === '' )
			return null;

		return self::base_dir() . '/' . $key . '.json';
	}

	/**
	 * Проверяет наличие локального кеша
	 *
	 * @param string $cache_key
	 * @return bool
	 */
	public static function has( string $cache_key ): bool
	{
		$file = self::build_file_path($cache_key);
		if( $file === null || !is_file($file) )
			return false;

		$size = @filesize($file);

		return ($size !== false && $size > 0);
	}

	/**
	 * Читает json кеш как массив
	 *
	 * @param string $cache_key
	 * @return array|null
	 */
	public static function read( string $cache_key )
	{
		$file = self::build_file_path($cache_key);
		if( $file === null || !is_file($file) )
			return null;

		$raw = @file_get_contents($file);
		if( $raw === false || $raw === '' )
			return null;

		$data = self::decode_json_array($raw);
		if( !is_array($data) )
			return null;

		return $data;
	}

	/**
	 * Читает сырой json из файла
	 *
	 * @param string $cache_key
	 * @return string|null
	 */
	public static function read_raw( string $cache_key )
	{
		$file = self::build_file_path($cache_key);
		if( $file === null || !is_file($file) )
			return null;

		$raw = @file_get_contents($file);

		return (is_string($raw) && $raw !== '') ? $raw : null;
	}

	/**
	 * Пишет готовый json в локальный кеш
	 *
	 * @param string $cache_key
	 * @param string $json_body
	 * @return bool
	 */
	public static function write_json( string $cache_key, string $json_body ): bool
	{
		if( $json_body === '' )
			return false;

		# перед записью валидируем json и utf8
		$data = self::decode_json_array($json_body);
		if( !is_array($data) )
			return false;

		$file = self::build_file_path($cache_key);
		if( $file === null )
			return false;

		$dir = dirname($file);
		if( !is_dir($dir) )
			@mkdir($dir, 0777, true);
		else
			@chmod($dir, 0777);

		$tmp_file = $file . '.tmp';
		if( @file_put_contents($tmp_file, $json_body, LOCK_EX) === false )
			return false;

		@chmod($tmp_file, 0666);

		if( !@rename($tmp_file, $file) )
			return false;

		@chmod($file, 0666);

		return true;
	}

	/**
	 * Начинает refresh lock для cache_key
	 *
	 * @param string $cache_key
	 * @param int $ttl
	 * @return bool
	 */
	public static function begin_refresh( string $cache_key, int $ttl ): bool
	{
		if( self::has_fresh($cache_key, $ttl) )
			return false;

		$key = self::normalize_key($cache_key);
		if( $key === '' )
			return false;

		$lock_file = self::base_dir() . '/locks/' . $key . '.lock';

		$dir = dirname($lock_file);
		if( !is_dir($dir) )
			@mkdir($dir, 0777, true);
		else
			@chmod($dir, 0777);

		$fp = @fopen($lock_file, 'c');
		if( !$fp )
			return false;

		@chmod($lock_file, 0666);

		if( !@flock($fp, LOCK_EX | LOCK_NB) )
		{
			@fclose($fp);
			return false;
		}

		self::$refresh_locks[$cache_key] = $fp;

		return true;
	}

	/**
	 * Завершает refresh lock
	 *
	 * @param string $cache_key
	 * @return void
	 */
	public static function end_refresh( string $cache_key ): void
	{
		if( empty(self::$refresh_locks[$cache_key]) )
			return;

		$fp = self::$refresh_locks[$cache_key];
		@flock($fp, LOCK_UN);
		@fclose($fp);
		unset(self::$refresh_locks[$cache_key]);
	}
}
