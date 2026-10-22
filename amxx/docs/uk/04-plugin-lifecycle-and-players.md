# Життєвий цикл плагіна та гравці

[English](../en/04-plugin-lifecycle-and-players.md) | [Українська](../uk/04-plugin-lifecycle-and-players.md) | [Русский](../ru/04-plugin-lifecycle-and-players.md)

[Зміст](../../README.uk.md)

Налаштування мапи, ідентифікація гравців і очищення. Головне розрізняти слот, який можна зайняти повторно, та конкретне підключення.

- [Реєстрація один раз на мапу](#registration)
- [plugin_cfg не означає кінець конфігурації](#initialization-order)
- [Авторизація та вхід у гру є різними подіями](#authorization-order)
- [Перевірка індексу перед доступом до стану гравця](#player-index)
- [Userid для поточного підключення](#userid)
- [Ідентифікатор після авторизації](#authid)
- [Обхід відібраного списку гравців](#player-list)
- [Очищення під час відключення](#disconnect-cleanup)
- [Закриття ресурсів плагіна](#handle-cleanup)

<a id="registration"></a>

## Реєстрація один раз на мапу

`plugin_init()` реєструє команди, хуки й відомості про плагін. Стан окремого гравця ініціалізують у клієнтських forwards.

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

Глобальні змінні належать поточному екземпляру плагіна, а не постійному сховищу. Зміна мапи не замінює збереження даних.

[plugin_init](https://www.amxmodx.org/api/amxmodx/plugin_init)

<a id="initialization-order"></a>

## plugin_cfg не означає кінець конфігурації

`plugin_cfg()` викликається після всіх `plugin_init()`. Для підсумкових значень конфігурації AMXX є `OnConfigsExecuted()`: команди в буфері ще можуть чекати виконання.

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

Це звичайна фаза конфігурації AMXX, а не довільний пізніший `exec` іншого плагіна. Див. [прив’язки cvar та зміни конфігурації](05-commands-cvars-and-configs.md).

[OnConfigsExecuted](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

<a id="authorization-order"></a>

## Авторизація та вхід у гру є різними подіями

`client_authorized()` може спрацювати до або після `client_putinserver()`. Якщо дії потрібні обидві умови, зберігайте їх окремо та перевіряйте разом.

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

Дія виконується у forward, який прийшов другим. Боти також авторизуються; сама подія не доводить, що це людина, і не надає доступу.

[client_authorized](https://www.amxmodx.org/api/amxmodx/client_authorized)

<a id="player-index"></a>

## Перевірка індексу перед доступом до стану гравця

Слоти гравців починаються з 1; 0 у багатьох API означає сервер або світ. Спочатку перевірте діапазон, потім підключення або життя, потрібні для дії.

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

Pawn підтримує ланцюжки порівнянь, але явне `id >= 1 && id <= MaxClients` простіше читати й переносити. Не кожен індекс сутності є гравцем.

[is_user_alive](https://www.amxmodx.org/api/amxmodx/is_user_alive)

<a id="userid"></a>

## Userid для поточного підключення

Після виходу слот може зайняти інший гравець. `get_user_userid()` позначає поточне підключення; для відкладеного результату знайдіть слот заново.

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

Userid не є ключем облікового запису для збереження прогресу. Це ж правило діє для SQL callbacks і цілей меню. [Життєвий цикл tasks](07-tasks-and-menus.md).

[get_user_userid](https://www.amxmodx.org/api/amxmodx/get_user_userid)

<a id="authid"></a>

## Ідентифікатор після авторизації

Для постійного ключа облікового запису використовуйте `authid` з `client_authorized()`. Ім’я та IP не є стабільною заміною.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	if( is_user_bot(id) || is_user_hltv(id) )
		return;

	server_print( "Authorized slot %d: %s", id, authid );
}
```

Гарантії ідентифікації залежать від налаштування авторизації сервера. Префікс `STEAM_` сам по собі не надає прав; auth бота: `BOT`.

[get_user_authid](https://www.amxmodx.org/api/amxmodx/get_user_authid)

<a id="player-list"></a>

## Обхід відібраного списку гравців

`get_players_ex()` створює суцільний список відповідних індексів. Якщо список уже потрібен, обходьте його кількість, а не всі можливі слоти.

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

`players[index]` є слотом, `index` лише позицією у списку. Порожній результат означає нуль ітерацій.

[get_players_ex](https://www.amxmodx.org/api/amxmisc/get_players_ex)

<a id="disconnect-cleanup"></a>

## Очищення під час відключення

`client_disconnected()` охоплює також перервані підключення й замінює застарілий `client_disconnect()`. Сутність ще доступна, але повідомлення клієнту вже не дійдуть.

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

`client_remove()` викликається пізніше, після видалення сутності. Збережіть потрібні дані перед очищенням; у `client_disconnected()` не слід очікувати, що `is_user_connected()` уже повертає false.

[client_disconnected](https://www.amxmodx.org/api/amxmodx/client_disconnected)

<a id="handle-cleanup"></a>

## Закриття ресурсів плагіна

Довгоживучі handles зберігають у глобальних змінних і звільняють у `plugin_end()`. Функція закриття залежить від API; handles не є взаємозамінними числами.

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

Закриття власного handle відрізняється від звільнення позиченого handle callback. Зокрема, handle потокового SQL-запиту належить SQLX. [Сховища](09-files-vault-and-sql.md).

[plugin_end](https://www.amxmodx.org/api/amxmodx/plugin_end)
