# Задачи, время и меню

[English](../en/07-tasks-and-menus.md) | [Українська](../uk/07-tasks-and-menus.md) | [Русский](../ru/07-tasks-and-menus.md)

[Содержание](../../README.ru.md)

У отложенных действий и интерактивных меню общая ловушка: до callback состояние может измениться. Владелец ресурса, идентичность и освобождение должны быть очевидны. Примеры для AMXX 1.9; пример Think также требует Engine.

**В этой главе**

- [Вызови public-функцию позже](#one-shot-task)
- [Понятные флаги повторения через set_task_ex()](#repeat-flags)
- [Разделяй ID разных задач](#task-id-ownership)
- [Отложенному действию нужна идентичность подключения](#player-task-identity)
- [Явно запускай и останавливай повторяющуюся задачу](#stop-repeat-task)
- [Для cooldown храни время окончания](#cooldowns-and-clocks)
- [Привяжи таймер к entity через Think](#entity-think)
- [Освобождай динамическое меню при любом завершении](#dynamic-menu)
- [У общего меню другой срок жизни](#shared-menu)
- [Повторно проверяй права при выборе пункта](#menu-access)

<a id="one-shot-task"></a>

## Вызови public-функцию позже

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_task)

Task планирует callback и не блокирует сервер. Callback должен быть `public`, потому что AMXX ищет его по имени. Без массива параметров первым аргументом callback может получить ID задачи.

```pawn
#include <amxmodx>

const TASK_READY = 100;

public plugin_init()
{
	set_task( 2.0, "task_ready", TASK_READY );
}

public task_ready( task_id )
{
	server_print( "Task %d: ready", task_id );
}
```

Сообщение появится на одном из следующих кадров сервера, примерно через две секунды. Tasks не являются отдельными потоками и не делают тяжёлый SQL, файловые операции или циклы асинхронными.

<a id="repeat-flags"></a>

## Понятные флаги повторения через set_task_ex()

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/set_task_ex)

`set_task_ex()` является stock из `amxmisc` с именованными флагами. `SetTask_RepeatTimes` повторяет заданное число раз, `SetTask_Repeat` работает до остановки. Старый `set_task()` использует `"a"` и `"b"` для тех же режимов.

```pawn
#include <amxmodx>
#include <amxmisc>

const TASK_PULSE = 100;
new g_pulses;

public plugin_init()
{
	set_task_ex( 1.0, "task_pulse", TASK_PULSE, .flags = SetTask_RepeatTimes, .repeat = 3 );
}

public task_pulse()
{
	server_print( "Pulse %d/3", ++g_pulses );
}
```

Пример выведет три сообщения. `SetTask_AfterMapStart` (`"c"`) и `SetTask_BeforeMapChange` (`"d"`) меняют смысл аргумента времени: это планирование относительно карты, а не более короткие интервалы.

<a id="task-id-ownership"></a>

## Разделяй ID разных задач

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/remove_task)

Два вызова `set_task()` с одинаковым ID не означают замену. Несколько задач могут иметь один ID; `remove_task()` убирает все совпадения. Если нужна одна отложенная операция, сначала удали предыдущую.

```pawn
#include <amxmodx>

const TASK_REFRESH = 100;

public plugin_init()
{
	register_srvcmd( "note_refresh", "command_refresh" );
}

public command_refresh()
{
	remove_task( TASK_REFRESH );
	set_task( 1.0, "task_refresh", TASK_REFRESH );
	return PLUGIN_HANDLED;
}

public task_refresh()
{
	server_print( "One refresh after the last request" );
}
```

Повторные запросы теперь откладывают одно обновление. По умолчанию ID ищутся в текущем плагине. Параметр `outside` позволяет затронуть задачи других плагинов; для своих задач оставляй `0`.

<a id="player-task-identity"></a>

## Отложенному действию нужна идентичность подключения

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_user_userid)

Индекс игрока является повторно используемым слотом. После отключения тот же `id` может получить другой игрок до выполнения callback. Сохрани userid подключения вместе со слотом, удаляй задачу при выходе и проверяй оба значения при выполнении.

```pawn
#include <amxmodx>

const TASK_WELCOME = 1000;

public client_putinserver( id )
{
	new data[2];
	data[0] = id;
	data[1] = get_user_userid( id );
	remove_task( TASK_WELCOME + id );
	set_task( 5.0, "task_welcome", TASK_WELCOME + id, data, sizeof data );
}

public client_disconnected( id )
{
	remove_task( TASK_WELCOME + id );
}

public task_welcome( const data[] )
{
	new id = data[0];

	if( !is_user_connected( id ) || get_user_userid( id ) != data[1] )
		return;

	client_print( id, print_chat, "Your connection is still the same" );
}
```

Если передан массив параметров, он становится первым аргументом callback. Здесь нужен `sizeof data`, а не `charsmax( data )`: это массив ячеек, а не строка с терминатором. Userid обозначает подключение, а не постоянную учётную запись.

<a id="stop-repeat-task"></a>

## Явно запускай и останавливай повторяющуюся задачу

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/task_exists)

`task_exists()` удобен для повторного запуска без дублирования: включение уже работающей функции не должно создавать второй таймер. При выключении убирай таймер, чтобы он не просыпался постоянно ради немедленного возврата.

```pawn
#include <amxmodx>
#include <amxmisc>

const TASK_STATUS = 100;

public plugin_init()
{
	register_srvcmd( "note_start", "command_start" );
	register_srvcmd( "note_stop", "command_stop" );
}

public command_start()
{
	if( !task_exists( TASK_STATUS ) )
		set_task_ex( 10.0, "task_status", TASK_STATUS, .flags = SetTask_Repeat );

	return PLUGIN_HANDLED;
}

public command_stop()
{
	remove_task( TASK_STATUS );
	return PLUGIN_HANDLED;
}

public task_status()
{
	server_print( "Players: %d", get_playersnum() );
}
```

Для разных задач игроков и глобальных задач выбирай разные диапазоны ID. Рестарт раунда сам по себе не отменяет task плагина; если этого требует правило, обрабатывай жизненный цикл раунда.

<a id="cooldowns-and-clocks"></a>

## Для cooldown храни время окончания

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_gametime)

Cooldown обычно не требует постоянного task. Сохрани время окончания по игровым часам и проверь его при запросе действия. `get_gametime()` считается от начала карты, а `get_systime()` является Unix-временем для календаря и сохранения. Значения этих часов не сравнивают между собой.

```pawn
#include <amxmodx>

new Float:g_next_use[33];

public plugin_init()
{
	register_clcmd( "say /ping", "command_ping" );
}

public client_putinserver( id )
{
	g_next_use[id] = 0.0;
}

public command_ping( id )
{
	new Float:now = get_gametime();

	if( now < g_next_use[id] )
		return PLUGIN_HANDLED;

	g_next_use[id] = now + 3.0;
	client_print( id, print_chat, "Pong" );
	return PLUGIN_HANDLED;
}
```

Состояние слота сбрасывается для нового подключения. Игровое время постоянно в пределах одного кадра движка, поэтому им не измеряют длительность отдельной функции.

<a id="entity-think"></a>

## Привяжи таймер к entity через Think

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/engine.inc)

Если действие принадлежит entity, её `nextthink` может прямо описать эту связь. Пример создаёт один невидимый `info_target`, задаёт отдельный classname и планирует следующий Think после каждого вызова. Нужен модуль Engine.

```pawn
#include <amxmodx>
#include <engine>

public plugin_init()
{
	register_think( "note_clock", "on_clock_think" );
	new entity = create_entity( "info_target" );

	if( !entity )
		return;

	entity_set_string( entity, EV_SZ_classname, "note_clock" );
	entity_set_float( entity, EV_FL_nextthink, get_gametime() + 1.0 );
}

public on_clock_think( entity )
{
	server_print( "Clock entity: %d", entity );
	entity_set_float( entity, EV_FL_nextthink, get_gametime() + 1.0 );
}
```

Think не повторяется без следующего `nextthink`. Удаление entity убирает владельца таймера. Это тоже работа в кадрах сервера, не фоновый поток и не повод создавать entity на каждый простой cooldown.

<a id="dynamic-menu"></a>

## Освобождай динамическое меню при любом завершении

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Меню, созданное для одного показа, занимает ресурс до `menu_destroy()`. Обработчик может получить выбор, Exit, timeout или отмену. Перед чтением данных проверяй `item < 0`: отмена не всегда равна только `MENU_EXIT`.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /choice", "command_choice" );
}

public command_choice( id )
{
	new menu = menu_create( "Choose a mode", "menu_choice" );
	menu_additem( menu, "Classic", "classic" );
	menu_additem( menu, "Practice", "practice" );
	menu_display( id, menu, 0, 15 );
	return PLUGIN_HANDLED;
}

public menu_choice( id, menu, item )
{
	if( item < 0 )
	{
		menu_destroy( menu );
		return PLUGIN_HANDLED;
	}

	new info[16];
	menu_item_getinfo( menu, item, .info = info, .infolen = charsmax( info ) );
	menu_destroy( menu );
	client_print( id, print_chat, "Selected: %s", info );
	return PLUGIN_HANDLED;
}
```

Item info является стабильным ключом действия; видимую подпись можно изменить или перевести. Не вызывай `menu_display()` сразу из обработчика отрицательного `item`: API запрещает этот повторный вход при отмене. Timeout сообщается при последующем взаимодействии, а не гарантирует callback точно в момент окончания времени.

<a id="shared-menu"></a>

## У общего меню другой срок жизни

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Для одинаковых статических вариантов создай меню один раз и используй его handle повторно. Закрытие одним игроком не должно уничтожать ресурс для остальных. Не смешивай это с предыдущим подходом, где меню создаётся на каждый показ.

```pawn
#include <amxmodx>

new g_help_menu;

public plugin_init()
{
	g_help_menu = menu_create( "Help", "menu_help" );
	menu_additem( g_help_menu, "Commands", "commands" );
	menu_additem( g_help_menu, "Rules", "rules" );
	register_clcmd( "say /help", "command_help" );
}

public command_help( id )
{
	menu_display( id, g_help_menu );
	return PLUGIN_HANDLED;
}

public menu_help( id, menu, item )
{
	if( item < 0 )
		return PLUGIN_HANDLED;

	new info[16];
	menu_item_getinfo( menu, item, .info = info, .infolen = charsmax( info ) );
	client_print( id, print_chat, "Help section: %s", info );
	return PLUGIN_HANDLED;
}

public plugin_end()
{
	menu_destroy( g_help_menu );
}
```

Меню переживает отдельные выборы и освобождается при завершении плагина. Сам объект тоже общий: изменение заголовка, подписей или свойств для одного игрока меняет тот же объект. Для персонального содержимого используй отдельные меню.

<a id="menu-access"></a>

## Повторно проверяй права при выборе пункта

[AMX Mod X API](https://www.amxmodx.org/api/newmenus/menu_additem)

Маска доступа пункта или callback отрисовки определяет вид меню сейчас. Действие выполняется позже, когда права или состояние игры могли измениться. В обработчике повторно проверяй условия самого действия.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /admin_note", "command_admin_note" );
}

public command_admin_note( id )
{
	new menu = menu_create( "Admin notes", "menu_admin_note" );
	menu_additem( menu, "Print status", "status", ADMIN_CFG );
	menu_display( id, menu );
	return PLUGIN_HANDLED;
}

public menu_admin_note( id, menu, item )
{
	menu_destroy( menu );

	if( item < 0 || !(get_user_flags( id ) & ADMIN_CFG) )
		return PLUGIN_HANDLED;

	client_print( id, print_chat, "Admin action is allowed now" );
	return PLUGIN_HANDLED;
}
```

Здесь одно действие, поэтому читать item info не нужно. В меню выбора игрока сохраняй userid подключения как ключ цели и находи или проверяй её при выборе; сохранённый слот может уже принадлежать другому.
