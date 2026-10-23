# Життєвий цикл плагіна та гравці

[EN](../en/04-plugin-lifecycle-and-players.md) | [UK](../uk/04-plugin-lifecycle-and-players.md) | [RU](../ru/04-plugin-lifecycle-and-players.md)

[Зміст](../../README.uk.md)

Під час підключення гравця є кілька окремих етапів: вхід, авторизація, поява у грі. Нижче розберемо, де готувати дані, коли їх очищувати та як не сплутати гравця, який вийшов, із новою людиною в тому самому слоті.

- [Реєстрація один раз на мапу](#registration)
- [Коли конфіги вже виконалися](#initialization-order)
- [Авторизація та вхід у гру є різними подіями](#authorization-order)
- [Спочатку перевіряємо, що індекс належить гравцю](#player-index)
- [Як знайти те саме підключення згодом](#userid)
- [Ідентифікатор після авторизації](#authid)
- [Отримуємо потрібних гравців та обходимо список](#player-list)
- [Очищення під час відключення](#disconnect-cleanup)
- [Що закриваємо під час завершення плагіна](#handle-cleanup)

<a id="registration"></a>

## Реєстрація один раз на мапу

Команди й хуки реєструємо в `plugin_init()`: це загальне налаштування плагіна на карту. Тут же вказуємо його назву та версію. Дані конкретного гравця готуємо вже у клієнтських обробниках, коли з’являється його підключення.

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

Глобальні змінні живуть у поточному екземплярі плагіна. Якщо дані мають пережити зміну карти, окремо зберігаємо їх у сховищі.

[`plugin_init`](https://www.amxmodx.org/api/amxmodx/plugin_init)

<a id="initialization-order"></a>

## Коли конфіги вже виконалися

Треба прочитати cvar після завантаження конфігів? Для цього є `OnConfigsExecuted()`. У `plugin_cfg()` уже відпрацювали всі `plugin_init()`, але частина команд конфігурації ще може чекати виконання. Нижче читаємо ліміт тоді, коли штатні конфіги AMXX уже виконані.

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

Інший плагін усе ще може пізніше запустити власний `exec`. Цей обробник стосується звичайного порядку налаштування AMXX. Докладніше: [прив’язки cvar і зміни конфігурації](05-commands-cvars-and-configs.md).

[`OnConfigsExecuted`](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

<a id="authorization-order"></a>

## Авторизація та вхід у гру є різними подіями

Гравець може авторизуватися до `client_putinserver()` або після нього. Якщо для дії потрібні й авторизація, і вхід у гру, відзначаємо обидві події окремо. У прикладі кожен обробник перевіряє другий прапорець, тому повідомлення з’являється, коли виконані обидві умови.

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

Роботу зробить той обробник, який спрацював другим. Боти теж проходять авторизацію: ці два прапорці ще не означають, що перед нами людина або що їй дозволена команда.

[`client_authorized`](https://www.amxmodx.org/api/amxmodx/client_authorized)

<a id="player-index"></a>

## Спочатку перевіряємо, що індекс належить гравцю

Слоти гравців починаються з `1`, а `0` у багатьох функціях означає сервер або світ. Перед читанням здоров’я спочатку перевіряємо діапазон, потім стан гравця. У прикладі команда працює лише для живого гравця, тому достатньо перевірки `is_user_alive()`.

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

Не кожен індекс сутності підходить функціям гравця. Умови `id >= 1 && id <= MaxClients` явно показують потрібний діапазон; хоча Pawn підтримує ланцюжки порівнянь, такий запис легше читати й переносити.

[`is_user_alive`](https://www.amxmodx.org/api/amxmodx/is_user_alive)

<a id="userid"></a>

## Як знайти те саме підключення згодом

Гравець вийшов, а його слот зайняла інша людина. Якщо зберегти лише `id`, відкладене привітання може піти новому гравцю. Тому запам’ятовуємо userid поточного підключення через `get_user_userid()`, а перед виводом шукаємо за ним актуальний слот. Якщо підключення вже завершилося, `find_player()` його не знайде.

```pawn
#include <amxmodx>

public client_putinserver( id )
{
	new data[1];
	// Запам’ятовуємо підключення: слот згодом може зайняти інший гравець.
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

Userid підходить для відкладеної дії в межах підключення. Для збереження прогресу акаунта потрібен інший ключ. Такий самий прийом корисний для SQL-відповідей і цілей меню. [Завдання та затримки](07-tasks-and-menus.md).

[`get_user_userid`](https://www.amxmodx.org/api/amxmodx/get_user_userid)

<a id="authid"></a>

## Ідентифікатор після авторизації

Коли потрібен ключ акаунта для збереження даних, беремо `authid` у `client_authorized()`: на цей момент ідентифікатор уже отриманий. Ім’я може змінитися, IP теж, тому для такого ключа вони не підходять. Нижче виводимо `authid`, пропускаючи ботів і HLTV.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	if( is_user_bot(id) || is_user_hltv(id) )
		return;

	server_print( "Authorized slot %d: %s", id, authid );
}
```

Надійність ідентифікатора залежить від налаштованої на сервері авторизації. Один префікс `STEAM_` прав не дає. У бота значення auth дорівнює `BOT`.

[`get_user_authid`](https://www.amxmodx.org/api/amxmodx/get_user_authid)

<a id="player-list"></a>

## Отримуємо потрібних гравців та обходимо список

`get_players_ex()` збирає потрібні індекси підряд у масив `players` і записує їх кількість у `count`. Нижче виключаємо ботів та HLTV, потім обходимо лише отриманий список. Заново перевіряти всі можливі слоти для такого обходу не потрібно.

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

`index` тут означає позицію у списку, а `players[index]` слот гравця. Якщо нікого не знайшли, `count` буде `0` і цикл не виконається.

[`get_players_ex`](https://www.amxmodx.org/api/amxmisc/get_players_ex)

<a id="disconnect-cleanup"></a>

## Очищення під час відключення

Для очищення після виходу використовуємо `client_disconnected()`. Він охоплює і перервані підключення, замінюючи старий `client_disconnect()`. У прикладі прибираємо завдання та очки цього слота, щоб вони не дісталися наступному гравцю. Сутність ще існує, але повідомлення клієнту, який іде, вже не доставити.

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
	// Наступний гравець у цьому слоті має почати зі своїх даних.
	g_points[id] = 0;
}
```

Якщо дані потрібні для збереження, зберігаємо їх до обнулення. `client_remove()` надійде пізніше, коли сутність уже видалена. У `client_disconnected()` перевірка `is_user_connected()` ще може повертати істину, тому орієнтуємося на саму подію виходу.

[`client_disconnected`](https://www.amxmodx.org/api/amxmodx/client_disconnected)

<a id="handle-cleanup"></a>

## Що закриваємо під час завершення плагіна

Створений на всю карту `Trie` тримаємо у глобальній змінній і звільняємо в `plugin_end()`. Його handle означає конкретний ресурс, тому вибираємо відповідну функцію закриття: тут `TrieDestroy()`. Ідентифікатори різних API не можна використовувати замість одне одного.

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

Закриваємо ті ресурси, якими керує наш плагін. Отриманий в обробнику handle може належати самому API. Наприклад, результат потокового SQL-запиту звільняє SQLX. Докладніше: [сховища](09-files-vault-and-sql.md).

[`plugin_end`](https://www.amxmodx.org/api/amxmodx/plugin_end)
