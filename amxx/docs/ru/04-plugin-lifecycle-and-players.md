# Жизненный цикл плагина и игроки

[EN](../en/04-plugin-lifecycle-and-players.md) | [UK](../uk/04-plugin-lifecycle-and-players.md) | [RU](../ru/04-plugin-lifecycle-and-players.md)

[Содержание](../../README.ru.md)

При подключении игрока есть несколько отдельных этапов: вход, авторизация, появление в игре. Ниже разберём, где готовить данные, когда их очищать и как не перепутать вышедшего игрока с новым человеком в том же слоте.

- [Регистрация один раз на карту](#registration)
- [Когда конфиги уже выполнились](#initialization-order)
- [Авторизация и вход в игру происходят отдельно](#authorization-order)
- [Сначала проверяем, что индекс принадлежит игроку](#player-index)
- [Как узнать то же подключение спустя время](#userid)
- [Идентификатор после авторизации](#authid)
- [Получаем нужных игроков и обходим список](#player-list)
- [Очистка при отключении](#disconnect-cleanup)
- [Что закрываем при завершении плагина](#handle-cleanup)

<a id="registration"></a>

## Регистрация один раз на карту

Команды и хуки регистрируем в `plugin_init()`: это общая настройка плагина на карту. Здесь же указываем его название и версию. Данные конкретного игрока готовим уже в клиентских обработчиках, когда появляется его подключение.

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

Глобальные переменные живут в текущем экземпляре плагина. Если данные должны пережить смену карты, отдельно сохраняем их в хранилище.

[`plugin_init`](https://www.amxmodx.org/api/amxmodx/plugin_init)

<a id="initialization-order"></a>

## Когда конфиги уже выполнились

Нужно прочитать cvar после загрузки конфигов? Для этого есть `OnConfigsExecuted()`. В `plugin_cfg()` уже отработали все `plugin_init()`, но часть команд конфигурации ещё может ждать выполнения. Ниже читаем лимит в момент, когда штатные конфиги AMXX уже выполнены.

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

Другой плагин всё ещё может позднее запустить свой `exec`. Этот обработчик относится к обычному порядку настройки AMXX. Подробнее: [привязки cvar и изменения конфигурации](05-commands-cvars-and-configs.md).

[`OnConfigsExecuted`](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

<a id="authorization-order"></a>

## Авторизация и вход в игру происходят отдельно

Игрок может авторизоваться до `client_putinserver()` или после него. Если для действия нужны и авторизация, и вход в игру, отмечаем оба события отдельно. В примере каждый обработчик проверяет второй флаг, поэтому сообщение появляется, когда выполнены оба условия.

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

Работу сделает тот обработчик, который сработал вторым. Боты тоже проходят авторизацию: эти два флага ещё не означают, что перед нами человек или что ему разрешена команда.

[`client_authorized`](https://www.amxmodx.org/api/amxmodx/client_authorized)

<a id="player-index"></a>

## Сначала проверяем, что индекс принадлежит игроку

Слоты игроков начинаются с `1`, а `0` во многих функциях означает сервер или мир. Перед чтением здоровья сначала проверяем диапазон, затем состояние игрока. В примере команда работает только для живого игрока, поэтому достаточно проверки `is_user_alive()`.

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

Не любой индекс сущности подходит функциям игрока. Условия `id >= 1 && id <= MaxClients` явно показывают нужный диапазон; хотя Pawn поддерживает цепочки сравнений, такую запись проще читать и переносить.

[`is_user_alive`](https://www.amxmodx.org/api/amxmodx/is_user_alive)

<a id="userid"></a>

## Как узнать то же подключение спустя время

Игрок вышел, а его слот занял другой человек. Если сохранить только `id`, отложенное приветствие может уйти новому игроку. Поэтому запоминаем userid текущего подключения через `get_user_userid()`, а перед выводом ищем по нему актуальный слот. Если подключение уже закончилось, `find_player()` его не найдёт.

```pawn
#include <amxmodx>

public client_putinserver( id )
{
	new data[1];
	// Запоминаем подключение: слот позже может занять другой игрок.
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

Userid подходит для отложенного действия в рамках подключения. Для сохранения прогресса аккаунта нужен другой ключ. Тот же приём полезен для SQL-ответов и целей меню. [Задачи и задержки](07-tasks-and-menus.md).

[`get_user_userid`](https://www.amxmodx.org/api/amxmodx/get_user_userid)

<a id="authid"></a>

## Идентификатор после авторизации

Когда нужен ключ аккаунта для сохранения данных, берём `authid` в `client_authorized()`: к этому моменту идентификатор уже получен. Имя может измениться, IP тоже, поэтому для такого ключа они не подходят. Ниже выводим `authid`, пропуская ботов и HLTV.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	if( is_user_bot(id) || is_user_hltv(id) )
		return;

	server_print( "Authorized slot %d: %s", id, authid );
}
```

Надёжность идентификатора зависит от настроенной на сервере авторизации. Один префикс `STEAM_` прав не даёт. У бота значение auth равно `BOT`.

[`get_user_authid`](https://www.amxmodx.org/api/amxmodx/get_user_authid)

<a id="player-list"></a>

## Получаем нужных игроков и обходим список

`get_players_ex()` собирает подходящие индексы подряд в массив `players` и записывает их количество в `count`. Ниже исключаем ботов и HLTV, затем обходим только полученный список. Заново проверять все возможные слоты для такого обхода не требуется.

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

`index` здесь обозначает позицию в списке, а `players[index]` слот игрока. Если никого не нашли, `count` будет `0` и цикл не выполнится.

[`get_players_ex`](https://www.amxmodx.org/api/amxmisc/get_players_ex)

<a id="disconnect-cleanup"></a>

## Очистка при отключении

Для очистки после выхода используем `client_disconnected()`. Он охватывает и прерванные подключения, заменяя старый `client_disconnect()`. В примере убираем задачу и очки этого слота, чтобы они не достались следующему игроку. Сущность ещё существует, но сообщение уходящему клиенту уже не доставить.

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
	// Следующий игрок в этом слоте должен начать со своими данными.
	g_points[id] = 0;
}
```

Если данные нужны для сохранения, сохраняем их до обнуления. `client_remove()` придёт позже, когда сущность уже удалена. В `client_disconnected()` проверка `is_user_connected()` ещё может возвращать истину, так что ориентируемся на само событие выхода.

[`client_disconnected`](https://www.amxmodx.org/api/amxmodx/client_disconnected)

<a id="handle-cleanup"></a>

## Что закрываем при завершении плагина

Созданный на всю карту `Trie` держим в глобальной переменной и освобождаем в `plugin_end()`. Его handle обозначает конкретный ресурс, поэтому выбираем соответствующую функцию закрытия: здесь `TrieDestroy()`. Идентификаторы разных API нельзя использовать вместо друг друга.

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

Закрываем те ресурсы, которыми управляет наш плагин. Полученный в обработчике handle может принадлежать самому API. Например, результат потокового SQL-запроса освобождает SQLX. Подробнее: [хранилища](09-files-vault-and-sql.md).

[`plugin_end`](https://www.amxmodx.org/api/amxmodx/plugin_end)
