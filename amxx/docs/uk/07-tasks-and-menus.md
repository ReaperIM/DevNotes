# Задачі, час і меню

[EN](../en/07-tasks-and-menus.md) | [UK](../uk/07-tasks-and-menus.md) | [RU](../ru/07-tasks-and-menus.md)

[Зміст](../../README.uk.md)

Відкладені дії та інтерактивні меню мають спільну пастку: до callback стан може змінитися. Власник ресурсу, ідентичність і звільнення мають бути очевидними. Приклади для AMXX 1.9; приклад `Think` також потребує Engine.

**У цьому розділі**

- [Виклич `public`-функцію пізніше](#one-shot-task)
- [Зрозумілі прапорці повторення через `set_task_ex()`](#repeat-flags)
- [Розділяй ID різних задач](#task-id-ownership)
- [Відкладена дія потребує ідентичності підключення](#player-task-identity)
- [Явно запускай та зупиняй повторювану задачу](#stop-repeat-task)
- [Для cooldown зберігай час завершення](#cooldowns-and-clocks)
- [Прив'яжи таймер до entity через `Think`](#entity-think)
- [Звільняй динамічне меню при будь-якому завершенні](#dynamic-menu)
- [Спільне меню має інший час життя](#shared-menu)
- [Повторно перевіряй права при виборі пункту](#menu-access)

<a id="one-shot-task"></a>

## Виклич `public`-функцію пізніше

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_task)

Task планує callback і не блокує сервер. Callback має бути `public`, тому що AMXX шукає його за назвою. Без масиву параметрів першим аргументом callback може отримати ID задачі.

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

Повідомлення з'явиться на одному з наступних кадрів сервера, приблизно за дві секунди. Tasks не є окремими потоками і не роблять важкий SQL, файлові операції чи цикли асинхронними.

<a id="repeat-flags"></a>

## Зрозумілі прапорці повторення через `set_task_ex()`

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/set_task_ex)

`set_task_ex()` є `stock` з `amxmisc` з іменованими прапорцями. `SetTask_RepeatTimes` повторює задану кількість разів, `SetTask_Repeat` працює до зупинки. Старий `set_task()` використовує `"a"` та `"b"` для тих самих режимів.

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

Приклад виведе три повідомлення. `SetTask_AfterMapStart` (`"c"`) і `SetTask_BeforeMapChange` (`"d"`) змінюють значення аргументу часу: це планування відносно карти, а не коротші інтервали.

<a id="task-id-ownership"></a>

## Розділяй ID різних задач

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/remove_task)

Два виклики `set_task()` з однаковим ID не означають заміну. Кілька задач можуть мати один ID; `remove_task()` прибирає всі збіги. Якщо потрібна одна відкладена дія, спершу видали попередню.

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

Повторні запити тепер відкладають одне оновлення. За замовчуванням ID шукаються в поточному плагіні. Параметр `outside` дозволяє зачепити задачі інших плагінів; для власних задач залишай `0`.

<a id="player-task-identity"></a>

## Відкладена дія потребує ідентичності підключення

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_user_userid)

Індекс гравця є слотом, який використовується повторно. Після відключення цей самий `id` може отримати інший гравець до виконання callback. Збережи userid підключення разом зі слотом, видаляй задачу при виході та перевіряй обидва значення при виконанні.

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

Якщо передано масив параметрів, він стає першим аргументом callback. Тут потрібен `sizeof data`, а не `charsmax( data )`: це масив комірок, не рядок із термінатором. Userid позначає підключення, а не постійний обліковий запис.

<a id="stop-repeat-task"></a>

## Явно запускай та зупиняй повторювану задачу

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/task_exists)

`task_exists()` зручний для повторного запуску без дублювання: увімкнення вже активної функції не має створювати другий таймер. При вимкненні прибирай таймер, щоб він не прокидався постійно лише заради негайного повернення.

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

Для різних задач гравців і глобальних задач вибирай різні діапазони ID. Рестарт раунду сам по собі не скасовує task плагіна; якщо цього потребує правило, обробляй життєвий цикл раунду.

<a id="cooldowns-and-clocks"></a>

## Для cooldown зберігай час завершення

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_gametime)

Cooldown зазвичай не потребує постійного task. Збережи час завершення за ігровим годинником і перевір його при запиті дії. `get_gametime()` рахується від старту карти, а `get_systime()` є Unix-часом для календаря та збереження. Значення цих годинників не порівнюють між собою.

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

Стан слота скидається для нового підключення. Ігровий час незмінний у межах одного кадру рушія, тому ним не вимірюють тривалість окремої функції.

<a id="entity-think"></a>

## Прив'яжи таймер до entity через `Think`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/engine.inc)

Якщо дія належить entity, її `nextthink` може прямо описати цю прив'язку. Приклад створює один невидимий `info_target`, задає окремий classname та планує наступний `Think` після кожного виклику. Потрібен модуль Engine.

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

`Think` не повторюється без наступного `nextthink`. Видалення entity прибирає власника таймера. Це теж робота в кадрах сервера, не фоновий потік і не причина створювати entity на кожен простий cooldown.

<a id="dynamic-menu"></a>

## Звільняй динамічне меню при будь-якому завершенні

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Меню, створене для одного показу, займає ресурс до `menu_destroy()`. Обробник може отримати вибір, Exit, timeout чи скасування. Перед читанням даних перевіряй `item < 0`: скасування не завжди дорівнює лише `MENU_EXIT`.

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

Item info є стабільним ключем дії; видимий підпис можна змінити чи перекласти. Не викликай `menu_display()` одразу з обробника від'ємного `item`: API забороняє цей повторний вхід при скасуванні. Timeout повідомляється при подальшій взаємодії, а не гарантує callback точно в момент завершення часу.

<a id="shared-menu"></a>

## Спільне меню має інший час життя

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Для однакових статичних варіантів створи меню один раз і використовуй його handle повторно. Закриття одним гравцем не має знищувати ресурс для інших. Не змішуй це з попереднім підходом, де меню створюється на кожен показ.

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

Меню переживає окремі вибори та звільняється при завершенні плагіна. Сам об'єкт теж спільний: зміна заголовка, підписів чи властивостей для одного гравця змінює той самий об'єкт. Для персонального вмісту використовуй окремі меню.

<a id="menu-access"></a>

## Повторно перевіряй права при виборі пункту

[AMX Mod X API](https://www.amxmodx.org/api/newmenus/menu_additem)

Маска доступу пункту чи callback відображення визначає вигляд меню зараз. Дія виконується пізніше, коли права або стан гри могли змінитися. В обробнику повторно перевіряй умови самої дії.

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

Тут одна дія, тому читати item info не потрібно. У меню вибору гравця зберігай userid підключення як ключ цілі та знаходь або перевіряй її при виборі; збережений слот може вже належати іншому.
