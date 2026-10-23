# Команди, cvar та конфіги

[EN](../en/05-commands-cvars-and-configs.md) | [UK](../uk/05-commands-cvars-and-configs.md) | [RU](../ru/05-commands-cvars-and-configs.md)

[Зміст](../../README.uk.md)

Команду треба правильно зареєструвати, перевірити права й розібрати аргументи. З налаштуваннями схожа історія: важливо, коли вони завантажені та як змінюються під час гри. Нижче приклади для API AMXX 1.9.

**Теми розділу**

- [Звідки можна викликати команду](#command-routes)
- [Перевіряємо доступ в обробнику](#command-access)
- [Читаємо аргумент або весь текст команди](#read-arguments)
- [Обробляємо команду й пропускаємо решту чату](#chat-commands)
- [Зберігаємо вказівник cvar для повторного читання](#cvar-pointers)
- [Опис і допустимі межі cvar](#cvar-bounds)
- [Прив’язуємо налаштування до глобальних змінних](#cvar-bindings)
- [Реагуємо на зміну cvar](#cvar-hooks)
- [Створюємо конфіг плагіна через `AutoExecConfig()`](#auto-config)
- [Перевіряємо налаштування після завантаження конфігів](#configs-executed)
- [Змінюємо cvar без виконання чужого тексту](#safe-command-values)

<a id="command-routes"></a>

## Звідки можна викликати команду

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_concmd)

Спочатку вибираємо, хто викликатиме команду. Для гравця є `register_clcmd()`, для серверної консолі `register_srvcmd()`, для обох варіантів `register_concmd()`. У спільному обробнику сервер приходить з `id == 0`. Передавати такий `id` у функцію, розраховану лише на гравця, не можна.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	register_concmd( "amx_note_status", "command_status", ADMIN_CFG );
}

public command_status( id, level, command_id )
{
	if( !cmd_access( id, level, command_id, 1 ) )
		return PLUGIN_HANDLED;

	console_print( id, "Notes plugin is active" );
	return PLUGIN_HANDLED;
}
```

У цьому прикладі команда відповідає в ту консоль, із якої її викликали, включно із серверною. Якщо потрібен саме чат гравця, реєструємо окремий варіант через `register_clcmd( "say /notes", ... )`.

<a id="command-access"></a>

## Перевіряємо доступ в обробнику

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/cmd_access)

Вказати прапорець під час реєстрації команди недостатньо: в обробнику викликаємо `cmd_access()`. Передаємо отриманий `level`, щоб працювали налаштування з `cmdaccess.ini`. Останнє число задає мінімум аргументів разом з іменем команди: `2` означає, що після імені потрібен ще один аргумент.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	register_concmd( "amx_note_mode", "command_mode", ADMIN_CFG, "<on|off>" );
}

public command_mode( id, level, command_id )
{
	// Ім’я команди теж рахується: потрібен ще один аргумент.
	if( !cmd_access( id, level, command_id, 2 ) )
		return PLUGIN_HANDLED;

	new mode[16];
	read_argv( 1, mode, charsmax( mode ) );

	if( !equal( mode, "on" ) && !equal( mode, "off" ) )
	{
		console_print( id, "Expected on or off" );
		return PLUGIN_HANDLED;
	}

	console_print( id, "Requested mode: %s", mode );
	return PLUGIN_HANDLED;
}
```

Права й кількість аргументів перевірені, але саме значення ще перевіряємо окремо: у прикладі дозволені лише `on` та `off`. Звичайна перевірка прапорців у `cmd_access()` приймає будь-який збіг біта. Якщо потрібні всі біти маски, використовуємо `(get_user_flags( id ) & mask) == mask`. Для серверної консолі всередині цієї `stock` є окрема обробка доступу.

<a id="read-arguments"></a>

## Читаємо аргумент або весь текст команди

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/read_argv)

`read_argv( 0, ... )` дає ім’я команди, `read_argv( 1, ... )` перший розібраний аргумент. Якщо потрібен увесь текст після команди, беремо `read_args()`. Нижче знімаємо зовнішні лапки й виводимо повідомлення. `remove_quotes()` лише знімає лапки: вміст від цього не стає перевіреним чи безпечним для виконання як команда.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "note_echo", "command_echo" );
}

public command_echo( id )
{
	new text[128];
	read_args( text, charsmax( text ) );
	remove_quotes( text );
	trim( text );

	if( !text[0] )
		return PLUGIN_HANDLED;

	client_print( id, print_chat, "%s", text );
	return PLUGIN_HANDLED;
}
```

Виклик `note_echo "hello world"` виведе `hello world`. Якщо рядок довший за буфер, він може обрізатися. Для дії з правами доступу перевіряємо це явно, щоб обрізаний текст не став іншим допустимим ідентифікатором.

<a id="chat-commands"></a>

## Обробляємо команду й пропускаємо решту чату

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_clcmd)

Під час перехоплення `say` отримуємо і власну команду, і звичайні повідомлення. Усе, що не дорівнює `/notes`, пропускаємо через `PLUGIN_CONTINUE`. Для своєї команди виводимо відповідь і повертаємо `PLUGIN_HANDLED`. Щоб вона працювала й у командному чаті, окремо реєструємо `say_team`.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say", "command_say" );
	register_clcmd( "say_team", "command_say" );
}

public command_say( id )
{
	new text[192];
	read_args( text, charsmax( text ) );
	remove_quotes( text );
	trim( text );

	// Залишаємо інші повідомлення чату й решті обробників.
	if( !equali( text, "/notes" ) )
		return PLUGIN_CONTINUE;

	client_print( id, print_chat, "Use note_echo to test a command" );
	return PLUGIN_HANDLED;
}
```

Точне порівняння не дасть `/notes_extra` випадково викликати `/notes`. Інші плагіни теж можуть обробляти `say`, тому порядок роботи та блокування перевіряємо зі справжнім набором плагінів сервера.

<a id="cvar-pointers"></a>

## Зберігаємо вказівник cvar для повторного читання

[AMX Mod X API](https://www.amxmodx.org/api/cvars/get_cvar_pointer)

Для свого налаштування `create_cvar()` повертає handle. Для вже наявної ігрової cvar один раз отримуємо вказівник через `get_cvar_pointer()`. Це спосіб звернутися до налаштування, а його значення читаємо через `get_pcvar_*` і змінюємо через `set_pcvar_*`. У прикладі перевіряємо, що вказівник знайдено, потім читаємо `mp_roundtime`.

```pawn
#include <amxmodx>

new g_round_time;

public plugin_init()
{
	g_round_time = get_cvar_pointer( "mp_roundtime" );
	register_srvcmd( "note_roundtime", "command_roundtime" );
}

public command_roundtime()
{
	if( !g_round_time )
		return PLUGIN_HANDLED;

	server_print( "Round time: %.2f", get_pcvar_float( g_round_time ) );
	return PLUGIN_HANDLED;
}
```

Значення `0` означає, що вказівника немає: можливо, такого налаштування в цьому моді взагалі не існує. Реєструвати чужу ігрову cvar лише заради вказівника не потрібно.

<a id="cvar-bounds"></a>

## Опис і допустимі межі cvar

[AMX Mod X API](https://www.amxmodx.org/api/cvars/create_cvar)

Налаштуванню одразу задаємо зрозумілий опис і допустимі межі. Вони знадобляться і в коді, і у створеному конфігу. Тут затримка обмежена від `0.1` до `60.0`, тому не доводиться заново обмежувати її під час кожної ігрової події.

```pawn
#include <amxmodx>

public plugin_init()
{
	new delay = create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	server_print( "Delay: %.1f", get_pcvar_float( delay ) );
}
```

Значення за замовчуванням застосовується під час першої реєстрації після запуску сервера, а не на кожній карті. Якщо cvar уже є, нова не створюється, але виклик може замінити її межі. Власний префікс в іменах допомагає не зачепити налаштування іншого плагіна.

<a id="cvar-bindings"></a>

## Прив’язуємо налаштування до глобальних змінних

[AMX Mod X API](https://www.amxmodx.org/api/cvars/bind_pcvar_num)

Якщо налаштування часто читаємо, можна прив’язати його до змінної. Під час зміни cvar AMXX сам оновить число, затримку чи рядок. У прикладі команда одразу бачить поточні `g_enabled`, `g_delay` та `g_prefix`. Для прив’язки потрібна глобальна або `static`-пам’ять: звичайна локальна змінна після виходу з функції вже не підходить.

```pawn
#include <amxmodx>

new g_enabled;
new Float:g_delay;
new g_prefix[32];

public plugin_init()
{
	bind_pcvar_num( create_cvar( "note_enabled", "1" ), g_enabled );
	bind_pcvar_float( create_cvar( "note_delay", "3.0" ), g_delay );
	bind_pcvar_string( create_cvar( "note_prefix", "[Notes]" ), g_prefix, charsmax( g_prefix ) );
	register_srvcmd( "note_settings", "command_settings" );
}

public command_settings()
{
	server_print( "%s enabled=%d delay=%.1f", g_prefix, g_enabled, g_delay );
	return PLUGIN_HANDLED;
}
```

Зміна `note_enabled` оновить `g_enabled`, але зворотне присвоєння в `g_enabled` cvar не змінить. Для зміни самого налаштування використовуємо `set_pcvar_*`. Одну змінну до кількох cvar не прив’язуємо: інакше незрозуміло, значення якої з них вона має зберігати.

<a id="cvar-hooks"></a>

## Реагуємо на зміну cvar

[AMX Mod X API](https://www.amxmodx.org/api/cvars/hook_cvar_change)

Прив’язки достатньо, якщо треба лише читати актуальне значення. Якщо після зміни треба перебудувати дані або перезапустити таймер, додаємо `hook_cvar_change()`. Обробник отримає старе й нове значення рядками. Його повернуте значення не скасовує зміну.

```pawn
#include <amxmodx>

public plugin_init()
{
	new enabled = create_cvar( "note_enabled", "1", FCVAR_NONE, "Enable notes", true, 0.0, true, 1.0 );
	hook_cvar_change( enabled, "on_enabled_changed" );
}

public on_enabled_changed( cvar, const old_value[], const new_value[] )
{
	server_print( "Cvar %d changed: %s -> %s", cvar, old_value, new_value );
}
```

Якщо змінювати ту саму cvar з її обробника, можна запустити нескінченний ланцюжок викликів. Для звичайних обмежень краще межі під час створення. Початковий стан теж готуємо після завантаження конфігів: сама подія зміни може взагалі не відбутися.

<a id="auto-config"></a>

## Створюємо конфіг плагіна через `AutoExecConfig()`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

Після реєстрації своїх cvar викликаємо `AutoExecConfig()`. Із прапорцем створення відсутній файл заповниться налаштуваннями плагіна, потім AMXX поставить його на виконання. Якщо конфіг уже є, він використовується як є. Нове значення за замовчуванням у вихідному коді не перезапише налаштування адміністратора.

```pawn
#include <amxmodx>

public plugin_init()
{
	create_cvar( "note_enabled", "1", FCVAR_NONE, "Enable notes", true, 0.0, true, 1.0 );
	create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	AutoExecConfig( true, "notes" );
}
```

Приклад використовує `addons/amxmodx/configs/plugins/notes.cfg`. Ім’я передаємо без `.cfg` і без крапок. Додаткова папка вибирається всередині `configs/plugins/`; довільний шлях на диску цей параметр не задає.

<a id="configs-executed"></a>

## Перевіряємо налаштування після завантаження конфігів

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

У `plugin_cfg()` ще можуть чекати виконання команди конфігурації. `OnConfigsExecuted()` надходить один раз на карту, після серверних, AMXX, плагінних і покартових конфігів. Тоді можна прочитати остаточні значення й на їх основі підготувати роботу плагіна.

```pawn
#include <amxmodx>

new g_min_players;
new g_max_players;

public plugin_init()
{
	bind_pcvar_num( create_cvar( "note_min_players", "2", FCVAR_NONE, "Minimum players", true, 1.0, true, 32.0 ), g_min_players );
	bind_pcvar_num( create_cvar( "note_max_players", "16", FCVAR_NONE, "Maximum players", true, 1.0, true, 32.0 ), g_max_players );
	AutoExecConfig( true, "notes" );
}

public OnConfigsExecuted()
{
	// Окремі межі cvar не перевіряють цю залежність.
	if( g_min_players > g_max_players )
	{
		set_fail_state( "note_min_players must not exceed note_max_players" );
		return;
	}

	server_print( "Configured player range: %d..%d", g_min_players, g_max_players );
}
```

Окремі межі cvar не перевірять, що мінімум не більший за максимум. Тому в прикладі порівнюємо налаштування разом. Це перевірка під час запуску карти; якщо дозволені зміни під час гри, робимо її й під час змін. Обробник надійде навіть за невдалого виконання конфігураційного файлу, тому обов’язкові значення все одно перевіряємо.

<a id="safe-command-values"></a>

## Змінюємо cvar без виконання чужого тексту

[AMX Mod X API](https://www.amxmodx.org/api/cvars/set_pcvar_string)

Якщо підставити текст у `server_cmd( "hostname %s", text )`, його однаково розбиратиме обробник команд. Лапки й роздільники можуть змінити зміст результату; `%s` їх не знешкоджує. Для cvar використовуємо прямий запис через вказівник. Якщо потрібна справжня команда, окремо дозволяємо лише відповідні значення аргументу.

```pawn
#include <amxmodx>
#include <amxmisc>

new g_hostname;

public plugin_init()
{
	g_hostname = get_cvar_pointer( "hostname" );
	register_concmd( "amx_note_hostname", "command_hostname", ADMIN_CFG, "<name>" );
}

public command_hostname( id, level, command_id )
{
	if( !cmd_access( id, level, command_id, 2 ) || !g_hostname )
		return PLUGIN_HANDLED;

	new hostname[64];
	read_argv( 1, hostname, charsmax( hostname ) );
	trim( hostname );

	if( !hostname[0] )
		return PLUGIN_HANDLED;

	// Записуємо значення напряму, без розбору як команди.
	set_pcvar_string( g_hostname, hostname );
	return PLUGIN_HANDLED;
}
```

У прикладі ім’я сервера зберігається як рядкове значення cvar. Схожа обережність потрібна з `client_cmd()` та `console_cmd()`: фіксоване ім’я команди ще не робить будь-який доданий текст безпечним.
