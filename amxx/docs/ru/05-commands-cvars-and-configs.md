# Команды, cvar и конфиги

[English](../en/05-commands-cvars-and-configs.md) | [Українська](../uk/05-commands-cvars-and-configs.md) | [Русский](../ru/05-commands-cvars-and-configs.md)

[Содержание](../../README.ru.md)

Точки входа команд, проверка доступа и настройки с понятным поведением после конфигов и изменений во время игры. Примеры используют API AMXX 1.9.

**В этой главе**

- [Клиентские, серверные и общие консольные команды](#command-routes)
- [Проверяй доступ в обработчике](#command-access)
- [Один аргумент или вся строка аргументов](#read-arguments)
- [Перехвати команду, не скрывая весь чат](#chat-commands)
- [Храни pointer cvar вместо поиска по имени](#cvar-pointers)
- [Описание cvar и границы числовых значений](#cvar-bounds)
- [Привяжи текущие настройки к глобальным переменным](#cvar-bindings)
- [Реагируй на изменение cvar](#cvar-hooks)
- [Создай конфиг плагина через AutoExecConfig()](#auto-config)
- [Читай окончательные значения в OnConfigsExecuted()](#configs-executed)
- [Не вставляй текст игрока в выполняемые команды](#safe-command-values)

<a id="command-routes"></a>

## Клиентские, серверные и общие консольные команды

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_concmd)

Регистрация зависит от того, кто вызывает команду. `register_clcmd()` слушает клиента, `register_srvcmd()` серверную консоль, а `register_concmd()` оба источника. Общий обработчик получает `id == 0` для сервера, поэтому не передавай его без проверки в функции только для игроков.

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

Команда отвечает в консоли вызова, включая серверную. Сокращение для чата игрока удобнее регистрировать через `register_clcmd( "say /notes", ... )`.

<a id="command-access"></a>

## Проверяй доступ в обработчике

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/cmd_access)

Флаги регистрации описывают команду; в обработчике всё равно нужен `cmd_access()`. Передавай полученный `level`, чтобы работали изменения через `cmdaccess.ini`. Количество аргументов включает название команды: `2` означает один обязательный аргумент.

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

`cmd_access()` проверяет права и минимальное количество, но не правильность аргументов. Обычная проверка флагов принимает любое совпадение бита; если нужны все флаги маски, проверяй `(get_user_flags( id ) & mask) == mask`. Серверная консоль в stock обрабатывается отдельно.

<a id="read-arguments"></a>

## Один аргумент или вся строка аргументов

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/read_argv)

`read_argv( 0, ... )` возвращает название команды, `read_argv( 1, ... )` первый разобранный аргумент. `read_args()` читает всю строку аргументов. Кавычки удобны для сообщения, но `remove_quotes()` лишь убирает внешние кавычки; это не проверка и не экранирование команд.

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

`note_echo "hello world"` выведет `hello world`. Фиксированный буфер может обрезать ввод; для важного действия не принимай обрезанный токен за правильный идентификатор.

<a id="chat-commands"></a>

## Перехвати команду, не скрывая весь чат

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_clcmd)

Hook `say` видит и обычные сообщения. Для чужого текста возвращай `PLUGIN_CONTINUE`, а `PLUGIN_HANDLED` только для распознанной команды. Отдельно регистрируй `say_team`, если сокращение нужно и в командном чате.

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

Точное сравнение не позволяет `/notes_extra` запустить команду. Другие плагины тоже могут перехватывать `say`; блокировку и порядок проверяй с реальным набором плагинов сервера.

<a id="cvar-pointers"></a>

## Храни pointer cvar вместо поиска по имени

[AMX Mod X API](https://www.amxmodx.org/api/cvars/get_cvar_pointer)

`create_cvar()` возвращает handle собственной настройки. Для существующей cvar игры получи pointer один раз и проверь его на `0`. Значение читают или меняют через `get_pcvar_*` / `set_pcvar_*`; сам pointer не является значением cvar.

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

Не регистрируй cvar игры только ради pointer. Отсутствие pointer может означать, что в этом моде нет такой настройки.

<a id="cvar-bounds"></a>

## Описание cvar и границы числовых значений

[AMX Mod X API](https://www.amxmodx.org/api/cvars/create_cvar)

Описание и границы делают настройку понятной в коде и созданных конфигах. Здесь задержка остаётся между `0.1` и `60.0`, поэтому не нужно ограничивать её при каждом событии.

```pawn
#include <amxmodx>

public plugin_init()
{
	new delay = create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	server_print( "Delay: %.1f", get_pcvar_float( delay ) );
}
```

Значение по умолчанию применяется при первой регистрации после запуска сервера, а не на каждой карте. Повторный `create_cvar()` не создаёт копию, но может поменять границы. Для своих настроек используй отдельный префикс.

<a id="cvar-bindings"></a>

## Привяжи текущие настройки к глобальным переменным

[AMX Mod X API](https://www.amxmodx.org/api/cvars/bind_pcvar_num)

Привязка обновляет переменную при изменении cvar. Это удобно для часто читаемых настроек: целого переключателя, дробной задержки или короткого текстового префикса. Нужна глобальная или static-переменная; локальная переменная не живёт после завершения функции.

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

Изменение `note_enabled` автоматически поменяет `g_enabled`. Присваивание в `g_enabled` не записывает значение обратно в cvar. Для изменения настройки используй `set_pcvar_*`; одну переменную нельзя привязывать к нескольким cvar.

<a id="cvar-hooks"></a>

## Реагируй на изменение cvar

[AMX Mod X API](https://www.amxmodx.org/api/cvars/hook_cvar_change)

Привязки достаточно, если нужно только текущее значение. Hook изменения полезен, когда надо пересобрать зависимые данные или перезапустить таймер. Callback получает старое и новое значение строками; возврат из него не отменяет изменение.

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

Изменение той же cvar в её hook может вызвать бесконечную рекурсию. Для простого диапазона лучше задать границы при создании; зависимое состояние также инициализируй после конфигов, а не жди обязательного события изменения.

<a id="auto-config"></a>

## Создай конфиг плагина через AutoExecConfig()

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

Вызывай `AutoExecConfig()` после регистрации своих cvar. Если автогенерация включена, отсутствующий конфиг создаётся из cvar плагина и планируется к выполнению. Существующий файл используется как есть; новые значения в коде не переписывают настройки администратора.

```pawn
#include <amxmodx>

public plugin_init()
{
	create_cvar( "note_enabled", "1", FCVAR_NONE, "Enable notes", true, 0.0, true, 1.0 );
	create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	AutoExecConfig( true, "notes" );
}
```

Будет использован `addons/amxmodx/configs/plugins/notes.cfg`. Передавай базовое имя без `.cfg` и без точек. Необязательная папка находится внутри `configs/plugins/`, это не произвольный путь файловой системы.

<a id="configs-executed"></a>

## Читай окончательные значения в OnConfigsExecuted()

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

`plugin_cfg()` не гарантирует, что все команды конфигов из очереди уже выполнены. `OnConfigsExecuted()` вызывается один раз за карту после серверных, AMXX, плагинных и покартовых конфигов. Здесь удобно запускать поведение, зависящее от окончательных значений.

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

Границы отдельных cvar не описывают зависимости между настройками. Пример проверяет связь на старте карты; если разрешены изменения во время игры, проверяй её и при изменениях. Forward вызывается даже при ошибке выполнения запланированного конфига, поэтому обязательные настройки проверяй сам.

<a id="safe-command-values"></a>

## Не вставляй текст игрока в выполняемые команды

[AMX Mod X API](https://www.amxmodx.org/api/cvars/set_pcvar_string)

Подстановка через формат не экранирует команды. `server_cmd( "hostname %s", text )` всё равно передаёт результат парсеру команд, где разделители и кавычки имеют значение. Cvar меняй через pointer; для настоящего аргумента команды принимай только допустимые значения.

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

Значение записывается строкой cvar, а не выполняется. То же относится к `client_cmd()` и `console_cmd()`: фиксированное имя команды не делает произвольные подставленные аргументы безопасными.
