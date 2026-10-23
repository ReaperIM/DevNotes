# Задачи, время и меню

[EN](../en/07-tasks-and-menus.md) | [UK](../uk/07-tasks-and-menus.md) | [RU](../ru/07-tasks-and-menus.md)

[Содержание](../../README.ru.md)

Пока таймер ждёт или игрок выбирает пункт меню, ситуация на сервере может измениться. Поэтому сохраняем нужное подключение, перепроверяем условия перед действием и вовремя убираем задачи и меню. Примеры рассчитаны на AMXX 1.9; для `Think` ещё нужен модуль Engine.

**Темы главы**

- [Вызываем `public`-функцию позже](#one-shot-task)
- [Задача на несколько повторов или до остановки](#repeat-flags)
- [Одна ожидающая задача с одним ID](#task-id-ownership)
- [Проверяем игрока после задержки](#player-task-identity)
- [Включаем повторение один раз и умеем его остановить](#stop-repeat-task)
- [Задержка между действиями без отдельного таймера](#cooldowns-and-clocks)
- [Периодическое действие самой сущности через `Think`](#entity-think)
- [Создаём меню на один показ и освобождаем после](#dynamic-menu)
- [Одно общее меню для нескольких показов](#shared-menu)
- [Перепроверяем права при выборе пункта](#menu-access)

<a id="one-shot-task"></a>

## Вызываем `public`-функцию позже

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_task)

Нужно выполнить действие позже? `set_task()` запланирует вызов и сразу отдаст управление серверу. Обработчик отмечаем `public`, потому что AMXX найдёт его по имени. Если массив параметров не передавали, первым аргументом обработчик может получить ID задачи.

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

Сообщение из примера появится примерно через две секунды, на одном из следующих кадров сервера. Работа внутри задачи всё ещё выполняется на сервере: долгий синхронный SQL-запрос, чтение файла или тяжёлый цикл будут задерживать игру и там.

<a id="repeat-flags"></a>

## Задача на несколько повторов или до остановки

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/set_task_ex)

У `set_task_ex()` из `amxmisc` флаги подписаны именами. `SetTask_RepeatTimes` повторяет действие заданное число раз, а `SetTask_Repeat` продолжает до остановки. В старом `set_task()` этим режимам соответствуют строки `"a"` и `"b"`. Ниже выбраны три повтора.

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

В этом примере получим три сообщения. Флаги `SetTask_AfterMapStart` (`"c"`) и `SetTask_BeforeMapChange` (`"d"`) задают время относительно начала или смены карты. Из-за них меняется смысл времени, переданного в функцию, а не просто уменьшается интервал.

<a id="task-id-ownership"></a>

## Одна ожидающая задача с одним ID

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/remove_task)

Повторный `set_task()` с тем же ID не заменяет прежнюю задачу: таких задач может стать несколько. `remove_task()` удаляет все совпавшие. Поэтому для одного ожидающего обновления сначала убираем прежнее, потом планируем новое.

```pawn
#include <amxmodx>

const TASK_REFRESH = 100;

public plugin_init()
{
	register_srvcmd( "note_refresh", "command_refresh" );
}

public command_refresh()
{
	// Убираем прежнюю задачу, чтобы после повторных запросов осталась одна.
	remove_task( TASK_REFRESH );
	set_task( 1.0, "task_refresh", TASK_REFRESH );
	return PLUGIN_HANDLED;
}

public task_refresh()
{
	server_print( "One refresh after the last request" );
}
```

Теперь каждый вызов команды откладывает одно обновление до секунды после последнего запроса. По умолчанию ID относится к текущему плагину. Параметр `outside` позволяет затронуть другие плагины; для обычной своей задачи оставляем `0`.

<a id="player-task-identity"></a>

## Проверяем игрока после задержки

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_user_userid)

За пять секунд игрок может выйти, а его слот занять другой. Чтобы приветствие не досталось новому человеку, вместе с `id` сохраняем userid подключения. При выходе удаляем задачу, а в самом обработчике ещё раз проверяем, что это то же подключение.

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

	// Слот уже может принадлежать другому подключению.
	if( !is_user_connected( id ) || get_user_userid( id ) != data[1] )
		return;

	client_print( id, print_chat, "Your connection is still the same" );
}
```

Если передан массив параметров, первым аргументом обработчика становится этот массив. Его длина здесь `sizeof data`: передаём все ячейки, без правил строкового `charsmax()`. Userid обозначает одно подключение; для постоянного аккаунта нужен другой ключ.

<a id="stop-repeat-task"></a>

## Включаем повторение один раз и умеем его остановить

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/task_exists)

Если команда включения пришла повторно, второй таймер нам не нужен. Проверяем `task_exists()` и создаём задачу только при её отсутствии. При выключении удаляем её целиком, чтобы она больше не просыпалась ради пустой проверки. Ниже статус выводится раз в десять секунд, пока не придёт команда остановки.

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

Для разных задач игроков и общих задач выделяем разные диапазоны ID. Перезапуск раунда сам по себе нашу задачу не отменит. Если по правилам она должна закончиться вместе с раундом, добавляем очистку в соответствующее событие.

<a id="cooldowns-and-clocks"></a>

## Задержка между действиями без отдельного таймера

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_gametime)

Для задержки между командами обычно достаточно сохранить время следующего разрешённого вызова. При новой команде сравниваем его с `get_gametime()`. Ниже `/ping` срабатывает не чаще раза в три секунды. Игровое время считается от начала карты; `get_systime()` даёт календарное время Unix для сохранений и дат. Значения этих часов между собой не сравниваем.

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

При новом подключении сбрасываем состояние слота. В пределах одного кадра `get_gametime()` не меняется, поэтому измерять им длительность отдельной функции не получится.

<a id="entity-think"></a>

## Периодическое действие самой сущности через `Think`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/engine.inc)

Если действие относится к сущности, время следующего вызова можно хранить в её `nextthink`. Ниже создаём невидимую `info_target`, даём ей свой classname и при каждом вызове планируем следующий. Для этого примера нужен Engine.

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

Сам по себе `Think` не повторяется: новый `nextthink` задаём снова. Удаление сущности уберёт и объект, которому принадлежал этот таймер. Работа всё ещё привязана к кадрам сервера; для обычной задержки команды заводить отдельную сущность на игрока не требуется.

<a id="dynamic-menu"></a>

## Создаём меню на один показ и освобождаем после

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Меню, созданное через `menu_create()`, нужно закрыть через `menu_destroy()`. Обработчик может прийти после выбора пункта, выхода, отмены или истечения времени. Сначала проверяем `item < 0`, затем читаем данные пункта: отрицательный результат не всегда равен только `MENU_EXIT`.

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
	// При отмене нет выбранного пункта, данные которого можно читать.
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

В `info` хранится ключ действия, поэтому подпись пункта можно переименовать или перевести. Из обработчика с отрицательным `item` сразу вызывать `menu_display()` нельзя: API запрещает такой повторный вход при отмене. Истечение времени сообщается при следующем взаимодействии с меню, точного вызова обработчика в указанный момент ждать не нужно.

<a id="shared-menu"></a>

## Одно общее меню для нескольких показов

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Если пункты одинаковые и не меняются для каждого игрока, создаём меню один раз. Затем повторно используем его handle. Закрытие одним игроком не должно уничтожить меню для остальных, поэтому `menu_destroy()` в обработчике здесь не вызываем.

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

Меню остаётся после отдельных выборов и освобождается в `plugin_end()`. Его заголовок, пункты и свойства тоже общие: изменение для одного игрока поменяет тот же объект. Для персонального содержания нужны отдельные меню.

<a id="menu-access"></a>

## Перепроверяем права при выборе пункта

[AMX Mod X API](https://www.amxmodx.org/api/newmenus/menu_additem)

Флаг доступа у пункта или обработчик его отрисовки описывает меню в момент показа. Пока игрок выбирал, права могли измениться, а цель выйти из игры. Поэтому перед самим действием снова проверяем всё, что для него требуется.

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

В этом меню всего одно действие, данные пункта читать не нужно. Если меню выбирает другого игрока, сохраняем userid цели и проверяем или ищем её заново при выборе. Один сохранённый слот к тому времени может принадлежать уже другому человеку.
