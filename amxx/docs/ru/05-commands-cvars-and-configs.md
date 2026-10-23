# Команды, cvar и конфиги

[EN](../en/05-commands-cvars-and-configs.md) | [UK](../uk/05-commands-cvars-and-configs.md) | [RU](../ru/05-commands-cvars-and-configs.md)

[Содержание](../../README.ru.md)

Команду нужно правильно зарегистрировать, проверить права и разобрать аргументы. С настройками похожая история: важно, когда они загружены и как меняются во время игры. Ниже примеры для API AMXX 1.9.

**Темы главы**

- [Откуда можно вызвать команду](#command-routes)
- [Проверяем доступ в обработчике](#command-access)
- [Читаем аргумент или весь текст команды](#read-arguments)
- [Обрабатываем команду и пропускаем остальной чат](#chat-commands)
- [Сохраняем указатель cvar для повторного чтения](#cvar-pointers)
- [Описание и допустимые границы cvar](#cvar-bounds)
- [Привязываем настройки к глобальным переменным](#cvar-bindings)
- [Реагируем на изменение cvar](#cvar-hooks)
- [Создаём конфиг плагина через `AutoExecConfig()`](#auto-config)
- [Проверяем настройки после загрузки конфигов](#configs-executed)
- [Меняем cvar без выполнения чужого текста](#safe-command-values)

<a id="command-routes"></a>

## Откуда можно вызвать команду

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_concmd)

Сначала выбираем, кто будет вызывать команду. Для игрока есть `register_clcmd()`, для серверной консоли `register_srvcmd()`, для обоих вариантов `register_concmd()`. В общем обработчике сервер приходит с `id == 0`. Передавать такой `id` в функцию, рассчитанную только на игрока, нельзя.

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

В этом примере команда отвечает в ту консоль, из которой её вызвали, включая серверную. Если нужен именно чат игрока, регистрируем отдельный вариант через `register_clcmd( "say /notes", ... )`.

<a id="command-access"></a>

## Проверяем доступ в обработчике

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/cmd_access)

Указать флаг при регистрации команды недостаточно: в обработчике вызываем `cmd_access()`. Передаём полученный `level`, чтобы работали настройки из `cmdaccess.ini`. Последнее число задаёт минимум аргументов вместе с именем команды: `2` означает, что после имени нужен ещё один аргумент.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	register_concmd( "amx_note_mode", "command_mode", ADMIN_CFG, "<on|off>" );
}

public command_mode( id, level, command_id )
{
	// Имя команды тоже считается: нужен ещё один аргумент.
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

Права и количество аргументов проверены, но само значение ещё проверяем отдельно: в примере разрешены только `on` и `off`. Обычная проверка флагов в `cmd_access()` принимает любой совпавший бит. Если нужны все биты маски, используем `(get_user_flags( id ) & mask) == mask`. Для серверной консоли внутри этой `stock` есть отдельная обработка доступа.

<a id="read-arguments"></a>

## Читаем аргумент или весь текст команды

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/read_argv)

`read_argv( 0, ... )` даёт имя команды, `read_argv( 1, ... )` первый разобранный аргумент. Если нужен весь текст после команды, берём `read_args()`. Ниже снимаем внешние кавычки и выводим сообщение. `remove_quotes()` только снимает кавычки: содержимое от этого не становится проверенным или безопасным для выполнения как команда.

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

Вызов `note_echo "hello world"` выведет `hello world`. Если строка длиннее буфера, она может обрезаться. Для действия с правами доступа проверяем это явно, чтобы обрезанный текст не стал другим допустимым идентификатором.

<a id="chat-commands"></a>

## Обрабатываем команду и пропускаем остальной чат

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_clcmd)

При перехвате `say` получаем и свою команду, и обычные сообщения. Всё, что не равно `/notes`, пропускаем через `PLUGIN_CONTINUE`. Для своей команды выводим ответ и возвращаем `PLUGIN_HANDLED`. Чтобы она работала и в командном чате, отдельно регистрируем `say_team`.

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

	// Оставляем остальные сообщения чату и другим обработчикам.
	if( !equali( text, "/notes" ) )
		return PLUGIN_CONTINUE;

	client_print( id, print_chat, "Use note_echo to test a command" );
	return PLUGIN_HANDLED;
}
```

Точное сравнение не даст `/notes_extra` случайно вызвать `/notes`. Другие плагины тоже могут обрабатывать `say`, поэтому порядок работы и блокировку проверяем с настоящим набором плагинов сервера.

<a id="cvar-pointers"></a>

## Сохраняем указатель cvar для повторного чтения

[AMX Mod X API](https://www.amxmodx.org/api/cvars/get_cvar_pointer)

Для своей настройки `create_cvar()` возвращает handle. Для уже существующей игровой cvar один раз получаем указатель через `get_cvar_pointer()`. Это способ обратиться к настройке, а её значение читаем через `get_pcvar_*` и меняем через `set_pcvar_*`. В примере проверяем, что указатель найден, затем читаем `mp_roundtime`.

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

Значение `0` означает, что указателя нет: возможно, такой настройки в этом моде вообще не существует. Регистрировать чужую игровую cvar только ради указателя не нужно.

<a id="cvar-bounds"></a>

## Описание и допустимые границы cvar

[AMX Mod X API](https://www.amxmodx.org/api/cvars/create_cvar)

У настройки сразу задаём понятное описание и допустимые границы. Они пригодятся и в коде, и в созданном конфиге. Здесь задержка ограничена от `0.1` до `60.0`, поэтому не приходится заново ограничивать её при каждом игровом событии.

```pawn
#include <amxmodx>

public plugin_init()
{
	new delay = create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	server_print( "Delay: %.1f", get_pcvar_float( delay ) );
}
```

Значение по умолчанию применяется при первой регистрации после запуска сервера, а не на каждой карте. Если cvar уже есть, новая не создаётся, но вызов может заменить её границы. Свой префикс в именах помогает не задеть настройки другого плагина.

<a id="cvar-bindings"></a>

## Привязываем настройки к глобальным переменным

[AMX Mod X API](https://www.amxmodx.org/api/cvars/bind_pcvar_num)

Если настройку часто читаем, можно привязать её к переменной. При изменении cvar AMXX сам обновит число, задержку или строку. В примере команда сразу видит текущие `g_enabled`, `g_delay` и `g_prefix`. Для привязки нужна глобальная или `static`-память: обычная локальная переменная после выхода из функции уже не подходит.

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

Изменение `note_enabled` обновит `g_enabled`, но обратное присваивание в `g_enabled` cvar не поменяет. Для изменения самой настройки используем `set_pcvar_*`. Одну переменную к нескольким cvar не привязываем: иначе непонятно, значение какой из них она должна хранить.

<a id="cvar-hooks"></a>

## Реагируем на изменение cvar

[AMX Mod X API](https://www.amxmodx.org/api/cvars/hook_cvar_change)

Привязки достаточно, если нужно только читать актуальное значение. Если после изменения надо пересобрать данные или перезапустить таймер, добавляем `hook_cvar_change()`. Обработчик получит старое и новое значения строками. Его возвращаемое значение не отменяет изменение.

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

Если менять ту же cvar из её обработчика, можно запустить бесконечную цепочку вызовов. Для обычных ограничений лучше границы при создании. Начальное состояние тоже готовим после загрузки конфигов: само событие изменения может вообще не произойти.

<a id="auto-config"></a>

## Создаём конфиг плагина через `AutoExecConfig()`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

После регистрации своих cvar вызываем `AutoExecConfig()`. С флагом создания отсутствующий файл заполнится настройками плагина, затем AMXX поставит его на выполнение. Если конфиг уже есть, он используется как есть. Новое значение по умолчанию в исходнике не перезапишет настройки администратора.

```pawn
#include <amxmodx>

public plugin_init()
{
	create_cvar( "note_enabled", "1", FCVAR_NONE, "Enable notes", true, 0.0, true, 1.0 );
	create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	AutoExecConfig( true, "notes" );
}
```

Пример использует `addons/amxmodx/configs/plugins/notes.cfg`. Имя передаём без `.cfg` и без точек. Дополнительная папка выбирается внутри `configs/plugins/`; произвольный путь на диске этот параметр не задаёт.

<a id="configs-executed"></a>

## Проверяем настройки после загрузки конфигов

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

В `plugin_cfg()` ещё могут ждать выполнения команды конфигурации. `OnConfigsExecuted()` приходит один раз на карту, после серверных, AMXX, плагинных и покартовых конфигов. Тогда можно прочитать итоговые значения и на их основе подготовить работу плагина.

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
	// Отдельные границы cvar не проверяют эту зависимость.
	if( g_min_players > g_max_players )
	{
		set_fail_state( "note_min_players must not exceed note_max_players" );
		return;
	}

	server_print( "Configured player range: %d..%d", g_min_players, g_max_players );
}
```

Отдельные границы cvar не проверят, что минимум не больше максимума. Поэтому в примере сравниваем настройки вместе. Это проверка при запуске карты; если разрешены изменения во время игры, делаем её и при изменениях. Обработчик придёт даже при неудачном выполнении конфигурационного файла, так что обязательные значения всё равно проверяем.

<a id="safe-command-values"></a>

## Меняем cvar без выполнения чужого текста

[AMX Mod X API](https://www.amxmodx.org/api/cvars/set_pcvar_string)

Если подставить текст в `server_cmd( "hostname %s", text )`, его всё равно будет разбирать обработчик команд. Кавычки и разделители могут изменить смысл результата; `%s` их не обезвреживает. Для cvar используем прямую запись через указатель. Если нужна настоящая команда, отдельно разрешаем только подходящие значения аргумента.

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

	// Записываем значение напрямую, без разбора как команды.
	set_pcvar_string( g_hostname, hostname );
	return PLUGIN_HANDLED;
}
```

В примере имя сервера сохраняется как строковое значение cvar. Похожая осторожность нужна с `client_cmd()` и `console_cmd()`: фиксированное имя команды ещё не делает любой добавленный текст безопасным.
