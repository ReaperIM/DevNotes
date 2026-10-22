# Команди, cvar та конфіги

[EN](../en/05-commands-cvars-and-configs.md) | [UK](../uk/05-commands-cvars-and-configs.md) | [RU](../ru/05-commands-cvars-and-configs.md)

[Зміст](../../README.uk.md)

Точки входу команд, перевірка доступу та налаштування з передбачуваною поведінкою після конфігів і змін під час гри. Приклади використовують API AMXX 1.9.

**У цьому розділі**

- [Клієнтські, серверні та спільні консольні команди](#command-routes)
- [Перевіряй доступ в обробнику](#command-access)
- [Один аргумент чи весь рядок аргументів](#read-arguments)
- [Перехопи команду, не приховуючи весь чат](#chat-commands)
- [Зберігай pointer cvar замість пошуку за назвою](#cvar-pointers)
- [Опис cvar та межі числових значень](#cvar-bounds)
- [Прив'яжи поточні налаштування до глобальних змінних](#cvar-bindings)
- [Реагуй на зміну cvar](#cvar-hooks)
- [Створи конфиг плагіна через `AutoExecConfig()`](#auto-config)
- [Читай остаточні значення в `OnConfigsExecuted()`](#configs-executed)
- [Не вставляй текст гравця в команди для виконання](#safe-command-values)

<a id="command-routes"></a>

## Клієнтські, серверні та спільні консольні команди

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_concmd)

Реєстрація залежить від того, хто викликає команду. `register_clcmd()` слухає клієнта, `register_srvcmd()` серверну консоль, а `register_concmd()` обидва джерела. Спільний обробник отримує `id == 0` для сервера, тому не передавай його без перевірки у функції лише для гравців.

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

Команда відповідає в консолі виклику, зокрема серверній. Скорочення для чату гравця зручніше реєструвати через `register_clcmd( "say /notes", ... )`.

<a id="command-access"></a>

## Перевіряй доступ в обробнику

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/cmd_access)

Прапорці реєстрації описують команду; в обробнику все одно потрібен `cmd_access()`. Передавай отриманий `level`, щоб працювали зміни через `cmdaccess.ini`. Кількість аргументів включає назву команди: `2` означає один обов'язковий аргумент.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	register_concmd( "amx_note_mode", "command_mode", ADMIN_CFG, "<on|off>" );
}

public command_mode( id, level, command_id )
{
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

`cmd_access()` перевіряє права та мінімальну кількість, але не правильність аргументів. Звичайна перевірка прапорців приймає будь-який збіг біта; якщо потрібні всі прапорці маски, перевіряй `(get_user_flags( id ) & mask) == mask`. Серверна консоль у `stock` обробляється окремо.

<a id="read-arguments"></a>

## Один аргумент чи весь рядок аргументів

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/read_argv)

`read_argv( 0, ... )` повертає назву команди, `read_argv( 1, ... )` перший розібраний аргумент. `read_args()` читає весь рядок аргументів. Лапки зручні для повідомлення, але `remove_quotes()` лише прибирає зовнішні лапки; це не перевірка і не екранування команд.

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

`note_echo "hello world"` виведе `hello world`. Фіксований буфер може обрізати введення; для важливої дії не приймай обрізаний токен за правильний ідентифікатор.

<a id="chat-commands"></a>

## Перехопи команду, не приховуючи весь чат

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_clcmd)

Hook `say` бачить і звичайні повідомлення. Для чужого тексту повертай `PLUGIN_CONTINUE`, а `PLUGIN_HANDLED` лише для впізнаної команди. Окремо реєструй `say_team`, якщо скорочення потрібне і в командному чаті.

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

	if( !equali( text, "/notes" ) )
		return PLUGIN_CONTINUE;

	client_print( id, print_chat, "Use note_echo to test a command" );
	return PLUGIN_HANDLED;
}
```

Точне порівняння не дозволяє `/notes_extra` запустити команду. Інші плагіни теж можуть перехоплювати `say`; блокування й порядок перевіряй з реальним набором плагінів сервера.

<a id="cvar-pointers"></a>

## Зберігай pointer cvar замість пошуку за назвою

[AMX Mod X API](https://www.amxmodx.org/api/cvars/get_cvar_pointer)

`create_cvar()` повертає handle власного налаштування. Для наявної cvar гри отримай pointer один раз і перевір його на `0`. Значення читають або змінюють через `get_pcvar_*` / `set_pcvar_*`; сам pointer не є значенням cvar.

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

Не реєструй cvar гри лише заради pointer. Відсутність pointer може означати, що цей мод не має такого налаштування.

<a id="cvar-bounds"></a>

## Опис cvar та межі числових значень

[AMX Mod X API](https://www.amxmodx.org/api/cvars/create_cvar)

Опис і межі роблять налаштування зрозумілим у коді та створених конфигах. Тут затримка залишається між `0.1` і `60.0`, тому не потрібно обмежувати її при кожній події.

```pawn
#include <amxmodx>

public plugin_init()
{
	new delay = create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	server_print( "Delay: %.1f", get_pcvar_float( delay ) );
}
```

Значення за замовчуванням застосовується при першій реєстрації після запуску сервера, а не на кожній карті. Повторний `create_cvar()` не створює копію, але може змінити межі. Для власних налаштувань використовуй окремий префікс.

<a id="cvar-bindings"></a>

## Прив'яжи поточні налаштування до глобальних змінних

[AMX Mod X API](https://www.amxmodx.org/api/cvars/bind_pcvar_num)

Прив'язка оновлює змінну при зміні cvar. Це зручно для налаштувань, які часто читаються: цілого перемикача, дробової затримки чи короткого текстового префікса. Потрібна глобальна або static-змінна; локальна змінна не живе після завершення функції.

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

Зміна `note_enabled` автоматично змінить `g_enabled`. Присвоєння в `g_enabled` не записує значення назад у cvar. Для зміни налаштування використовуй `set_pcvar_*`; одну змінну не можна прив'язувати до кількох cvar.

<a id="cvar-hooks"></a>

## Реагуй на зміну cvar

[AMX Mod X API](https://www.amxmodx.org/api/cvars/hook_cvar_change)

Прив'язки достатньо, якщо потрібне лише поточне значення. Hook зміни корисний, коли треба перебудувати залежні дані чи перезапустити таймер. Callback отримує старе й нове значення як рядки; повернення з нього не скасовує зміну.

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

Зміна тієї самої cvar у її hook може спричинити нескінченну рекурсію. Для простого діапазону краще задати межі при створенні; залежний стан також ініціалізуй після конфигів, а не чекай обов'язкової події зміни.

<a id="auto-config"></a>

## Створи конфиг плагіна через `AutoExecConfig()`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

Викликай `AutoExecConfig()` після реєстрації власних cvar. Якщо автогенерацію ввімкнено, відсутній конфиг буде створено з cvar плагіна та заплановано до виконання. Наявний файл використовується як є; нові значення в коді не переписують налаштування адміністратора.

```pawn
#include <amxmodx>

public plugin_init()
{
	create_cvar( "note_enabled", "1", FCVAR_NONE, "Enable notes", true, 0.0, true, 1.0 );
	create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	AutoExecConfig( true, "notes" );
}
```

Буде використано `addons/amxmodx/configs/plugins/notes.cfg`. Передавай базову назву без `.cfg` і без крапок. Необов'язкова папка розташовується всередині `configs/plugins/`, це не довільний шлях файлової системи.

<a id="configs-executed"></a>

## Читай остаточні значення в `OnConfigsExecuted()`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

`plugin_cfg()` не гарантує, що всі команди конфигів із черги вже виконано. `OnConfigsExecuted()` викликається один раз на карту після серверних, AMXX, плагінних і покартових конфигів. Тут зручно запускати поведінку, залежну від остаточних значень.

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
	if( g_min_players > g_max_players )
	{
		set_fail_state( "note_min_players must not exceed note_max_players" );
		return;
	}

	server_print( "Configured player range: %d..%d", g_min_players, g_max_players );
}
```

Межі окремих cvar не описують залежності між налаштуваннями. Приклад перевіряє зв'язок на старті карти; якщо дозволено живі зміни, перевіряй його і при змінах. Forward викликається навіть при помилці виконання запланованого конфига, тому обов'язкові налаштування перевіряй сам.

<a id="safe-command-values"></a>

## Не вставляй текст гравця в команди для виконання

[AMX Mod X API](https://www.amxmodx.org/api/cvars/set_pcvar_string)

Підстановка через формат не екранує команди. `server_cmd( "hostname %s", text )` усе одно передає результат парсеру команд, де роздільники й лапки мають значення. Cvar змінюй через pointer; для справжнього аргументу команди приймай лише дозволені значення.

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

	set_pcvar_string( g_hostname, hostname );
	return PLUGIN_HANDLED;
}
```

Значення записується як рядок cvar, а не виконується. Те саме стосується `client_cmd()` і `console_cmd()`: фіксована назва команди не робить довільні підставлені аргументи безпечними.
