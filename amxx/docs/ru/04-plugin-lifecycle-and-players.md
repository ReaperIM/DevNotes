# Жизненный цикл плагина и игроки

[English](../en/04-plugin-lifecycle-and-players.md) | [Українська](../uk/04-plugin-lifecycle-and-players.md) | [Русский](../ru/04-plugin-lifecycle-and-players.md)

[Содержание](../../README.ru.md)

Настройка карты, идентификация игроков и очистка. Главное различать слот, который можно занять повторно, и конкретное подключение.

- [Регистрация один раз на карту](#registration)
- [plugin_cfg не означает конец настройки](#initialization-order)
- [Авторизация и вход в игру происходят отдельно](#authorization-order)
- [Проверка индекса перед доступом к состоянию игрока](#player-index)
- [Userid для текущего подключения](#userid)
- [Идентификатор после авторизации](#authid)
- [Обход выбранного списка игроков](#player-list)
- [Очистка при отключении](#disconnect-cleanup)
- [Закрытие ресурсов плагина](#handle-cleanup)

<a id="registration"></a>

## Регистрация один раз на карту

`plugin_init()` регистрирует команды, хуки и сведения о плагине. Состояние отдельного игрока инициализируют в клиентских forwards.

```pawn
#include <amxmodx>
#define VERSION "1.0.0"

public plugin_init()
{
	register_plugin( "Lifecycle note", VERSION, "DevNotes" );
	register_clcmd( "say /ready", "command_ready" );
}

public command_ready( id )
{
	client_print( id, print_chat, "Ready" );
	return PLUGIN_HANDLED;
}
```

Глобальные переменные относятся к текущему экземпляру плагина, а не к постоянному хранилищу. Смена карты не заменяет сохранение данных.

[plugin_init](https://www.amxmodx.org/api/amxmodx/plugin_init)

<a id="initialization-order"></a>

## plugin_cfg не означает конец настройки

`plugin_cfg()` вызывается после всех `plugin_init()`. Для итоговых значений конфигурации AMXX есть `OnConfigsExecuted()`: команды в буфере ещё могут ждать выполнения.

```pawn
#include <amxmodx>
new g_limit;

public plugin_init()
{
	g_limit = create_cvar( "notes_limit", "10" );
	AutoExecConfig( true, "notes" );
}

public OnConfigsExecuted()
{
	server_print( "Configured limit: %d", get_pcvar_num(g_limit) );
}
```

Это штатная фаза настройки AMXX, а не произвольный поздний `exec` другого плагина. См. [привязки cvar и изменения конфигурации](05-commands-cvars-and-configs.md).

[OnConfigsExecuted](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

<a id="authorization-order"></a>

## Авторизация и вход в игру происходят отдельно

`client_authorized()` может сработать до или после `client_putinserver()`. Если действию нужны оба условия, храните их отдельно и проверяйте вместе.

```pawn
#include <amxmodx>
new bool:g_authorized[MAX_PLAYERS + 1];
new bool:g_in_game[MAX_PLAYERS + 1];

public client_connect( id )
{
	g_authorized[id] = false;
	g_in_game[id] = false;
}

public client_authorized( id, const authid[] )
{
	g_authorized[id] = true;
	if( g_in_game[id] )
		server_print( "Player %d is ready, auth: %s", id, authid );
}

public client_putinserver( id )
{
	g_in_game[id] = true;
	if( g_authorized[id] )
		server_print( "Player %d is ready", id );
}
```

Действие выполняется в том forward, который пришёл вторым. Боты тоже авторизуются; сама эта проверка не определяет человека и не даёт прав доступа.

[client_authorized](https://www.amxmodx.org/api/amxmodx/client_authorized)

<a id="player-index"></a>

## Проверка индекса перед доступом к состоянию игрока

Слоты игроков начинаются с 1; 0 во многих API означает сервер или мир. Сначала проверьте диапазон, затем подключение или жизнь, нужные для действия.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /health", "command_health" );
}

public command_health( id )
{
	if( id < 1 || id > MaxClients || !is_user_alive(id) )
		return PLUGIN_HANDLED;

	client_print( id, print_chat, "Health: %d", get_user_health(id) );
	return PLUGIN_HANDLED;
}
```

Pawn поддерживает цепочки сравнений, но явное `id >= 1 && id <= MaxClients` проще читать и переносить. Не каждый индекс сущности является игроком.

[is_user_alive](https://www.amxmodx.org/api/amxmodx/is_user_alive)

<a id="userid"></a>

## Userid для текущего подключения

После выхода слот может занять другой игрок. `get_user_userid()` обозначает текущее подключение; для отложенного результата найдите слот заново.

```pawn
#include <amxmodx>

public client_putinserver( id )
{
	new data[1];
	data[0] = get_user_userid( id );
	set_task( 2.0, "show_welcome", 0, data, sizeof data );
}

public show_welcome( const data[] )
{
	new id = find_player( "k", data[0] );
	if( id != 0 )
		client_print( id, print_chat, "Welcome" );
}
```

Userid не является ключом аккаунта для сохранения прогресса. То же правило действует для SQL callbacks и целей меню. [Жизненный цикл tasks](07-tasks-and-menus.md).

[get_user_userid](https://www.amxmodx.org/api/amxmodx/get_user_userid)

<a id="authid"></a>

## Идентификатор после авторизации

Для постоянного ключа аккаунта используйте `authid` из `client_authorized()`. Имя и IP не являются стабильной заменой.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	if( is_user_bot(id) || is_user_hltv(id) )
		return;

	server_print( "Authorized slot %d: %s", id, authid );
}
```

Гарантии идентификации зависят от настройки авторизации сервера. Префикс `STEAM_` сам по себе не даёт прав; auth бота: `BOT`.

[get_user_authid](https://www.amxmodx.org/api/amxmodx/get_user_authid)

<a id="player-list"></a>

## Обход выбранного списка игроков

`get_players_ex()` создаёт плотный список подходящих индексов. Когда список уже нужен, обходите его количество, а не все возможные слоты.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	register_srvcmd( "notes_players", "command_players" );
}

public command_players()
{
	new players[MAX_PLAYERS], count;
	get_players_ex( players, count, GetPlayers_ExcludeBots | GetPlayers_ExcludeHLTV );
	for( new index; index < count; index++ )
		server_print( "Human slot: %d", players[index] );

	return PLUGIN_HANDLED;
}
```

`players[index]` является слотом, `index` только позицией в списке. Пустой результат означает ноль итераций.

[get_players_ex](https://www.amxmodx.org/api/amxmisc/get_players_ex)

<a id="disconnect-cleanup"></a>

## Очистка при отключении

`client_disconnected()` охватывает также прерванные подключения и заменяет устаревший `client_disconnect()`. Сущность ещё доступна, но сообщения клиенту уже не дойдут.

```pawn
#include <amxmodx>
#define TASK_HINT 2000
new g_points[MAX_PLAYERS + 1];

public client_connect( id )
{
	g_points[id] = 0;
}

public client_disconnected( id )
{
	remove_task( TASK_HINT + id );
	g_points[id] = 0;
}
```

`client_remove()` вызывается позже, после удаления сущности. Сохраните нужные данные перед очисткой; в `client_disconnected()` не следует ожидать, что `is_user_connected()` уже возвращает false.

[client_disconnected](https://www.amxmodx.org/api/amxmodx/client_disconnected)

<a id="handle-cleanup"></a>

## Закрытие ресурсов плагина

Долгоживущие handles хранят в глобальных переменных и освобождают в `plugin_end()`. Функция закрытия зависит от API; handles не являются взаимозаменяемыми числами.

```pawn
#include <amxmodx>
new Trie:g_cache = Invalid_Trie;

public plugin_init()
{
	g_cache = TrieCreate();
}

public plugin_end()
{
	if( g_cache != Invalid_Trie )
		TrieDestroy( g_cache );
}
```

Закрытие собственного handle отличается от освобождения заимствованного handle callback. В частности, handle потокового SQL-запроса принадлежит SQLX. [Хранилища](09-files-vault-and-sql.md).

[plugin_end](https://www.amxmodx.org/api/amxmodx/plugin_end)
