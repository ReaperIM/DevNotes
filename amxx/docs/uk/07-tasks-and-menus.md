# Задачі, час і меню

[EN](../en/07-tasks-and-menus.md) | [UK](../uk/07-tasks-and-menus.md) | [RU](../ru/07-tasks-and-menus.md)

[Зміст](../../README.uk.md)

Поки таймер чекає або гравець вибирає пункт меню, ситуація на сервері може змінитися. Тому зберігаємо потрібне підключення, перевіряємо умови перед дією та вчасно прибираємо завдання й меню. Приклади розраховані на AMXX 1.9; для `Think` ще потрібен модуль Engine.

**Теми розділу**

- [Викликаємо `public`-функцію пізніше](#one-shot-task)
- [Завдання на кілька повторів або до зупинки](#repeat-flags)
- [Одне очікуване завдання з одним ID](#task-id-ownership)
- [Перевіряємо гравця після затримки](#player-task-identity)
- [Вмикаємо повторення один раз і вміємо його зупинити](#stop-repeat-task)
- [Затримка між діями без окремого таймера](#cooldowns-and-clocks)
- [Періодична дія самої сутності через `Think`](#entity-think)
- [Створюємо меню на один показ і звільняємо після](#dynamic-menu)
- [Одне спільне меню для кількох показів](#shared-menu)
- [Перевіряємо права під час вибору пункту](#menu-access)

<a id="one-shot-task"></a>

## Викликаємо `public`-функцію пізніше

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_task)

Треба виконати дію пізніше? `set_task()` запланує виклик і одразу віддасть керування серверу. Обробник позначаємо `public`, бо AMXX знайде його за ім’ям. Якщо масив параметрів не передавали, першим аргументом обробник може отримати ID завдання.

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

Повідомлення з прикладу з’явиться приблизно за дві секунди, на одному з наступних кадрів сервера. Робота всередині завдання все ще виконується на сервері: довгий синхронний SQL-запит, читання файлу чи важкий цикл затримуватимуть гру й там.

<a id="repeat-flags"></a>

## Завдання на кілька повторів або до зупинки

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/set_task_ex)

У `set_task_ex()` з `amxmisc` прапорці підписані іменами. `SetTask_RepeatTimes` повторює дію задану кількість разів, а `SetTask_Repeat` продовжує до зупинки. У старому `set_task()` цим режимам відповідають рядки `"a"` і `"b"`. Нижче вибрані три повтори.

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

У цьому прикладі отримаємо три повідомлення. Прапорці `SetTask_AfterMapStart` (`"c"`) і `SetTask_BeforeMapChange` (`"d"`) задають час відносно початку або зміни карти. Через них змінюється зміст часу, переданого у функцію, а не просто зменшується інтервал.

<a id="task-id-ownership"></a>

## Одне очікуване завдання з одним ID

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/remove_task)

Повторний `set_task()` із тим самим ID не замінює попереднє завдання: таких завдань може стати кілька. `remove_task()` видаляє всі збіги. Тому для одного очікуваного оновлення спочатку прибираємо попереднє, потім плануємо нове.

```pawn
#include <amxmodx>

const TASK_REFRESH = 100;

public plugin_init()
{
	register_srvcmd( "note_refresh", "command_refresh" );
}

public command_refresh()
{
	// Прибираємо попереднє завдання, щоб після повторних запитів залишилося одне.
	remove_task( TASK_REFRESH );
	set_task( 1.0, "task_refresh", TASK_REFRESH );
	return PLUGIN_HANDLED;
}

public task_refresh()
{
	server_print( "One refresh after the last request" );
}
```

Тепер кожен виклик команди відкладає одне оновлення до секунди після останнього запиту. За замовчуванням ID належить поточному плагіну. Параметр `outside` дозволяє зачепити інші плагіни; для звичайного власного завдання залишаємо `0`.

<a id="player-task-identity"></a>

## Перевіряємо гравця після затримки

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_user_userid)

За п’ять секунд гравець може вийти, а його слот зайняти інший. Щоб привітання не дісталося новій людині, разом з `id` зберігаємо userid підключення. Під час виходу видаляємо завдання, а в самому обробнику ще раз перевіряємо, що це те саме підключення.

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

	// Слот уже може належати іншому підключенню.
	if( !is_user_connected( id ) || get_user_userid( id ) != data[1] )
		return;

	client_print( id, print_chat, "Your connection is still the same" );
}
```

Якщо переданий масив параметрів, першим аргументом обробника стає цей масив. Його довжина тут `sizeof data`: передаємо всі комірки, без правил рядкового `charsmax()`. Userid означає одне підключення; для постійного акаунта потрібен інший ключ.

<a id="stop-repeat-task"></a>

## Вмикаємо повторення один раз і вміємо його зупинити

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/task_exists)

Якщо команда ввімкнення надійшла повторно, другий таймер нам не потрібен. Перевіряємо `task_exists()` і створюємо завдання лише за його відсутності. Під час вимкнення видаляємо його повністю, щоб воно більше не прокидалося заради порожньої перевірки. Нижче статус виводиться раз на десять секунд, доки не надійде команда зупинки.

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

Для різних завдань гравців і спільних завдань виділяємо різні діапазони ID. Перезапуск раунду сам собою наше завдання не скасує. Якщо за правилами воно має закінчитися разом із раундом, додаємо очищення у відповідну подію.

<a id="cooldowns-and-clocks"></a>

## Затримка між діями без окремого таймера

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_gametime)

Для затримки між командами зазвичай достатньо зберегти час наступного дозволеного виклику. За нової команди порівнюємо його з `get_gametime()`. Нижче `/ping` спрацьовує не частіше разу на три секунди. Ігровий час рахується від початку карти; `get_systime()` дає календарний час Unix для збережень і дат. Значення цих годинників між собою не порівнюємо.

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

Під час нового підключення скидаємо стан слота. У межах одного кадру `get_gametime()` не змінюється, тому вимірювати ним тривалість окремої функції не вийде.

<a id="entity-think"></a>

## Періодична дія самої сутності через `Think`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/engine.inc)

Якщо дія належить сутності, час наступного виклику можна зберігати в її `nextthink`. Нижче створюємо невидиму `info_target`, даємо їй власний classname і під час кожного виклику плануємо наступний. Для цього прикладу потрібен Engine.

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

Сам собою `Think` не повторюється: новий `nextthink` задаємо знову. Видалення сутності прибере й об’єкт, якому належав цей таймер. Робота все ще прив’язана до кадрів сервера; для звичайної затримки команди створювати окрему сутність на гравця не потрібно.

<a id="dynamic-menu"></a>

## Створюємо меню на один показ і звільняємо після

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Меню, створене через `menu_create()`, треба закрити через `menu_destroy()`. Обробник може надійти після вибору пункту, виходу, скасування або завершення часу. Спочатку перевіряємо `item < 0`, потім читаємо дані пункту: від’ємний результат не завжди дорівнює лише `MENU_EXIT`.

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
	// За скасування немає вибраного пункту, дані якого можна читати.
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

В `info` зберігається ключ дії, тому підпис пункту можна перейменувати або перекласти. З обробника з від’ємним `item` одразу викликати `menu_display()` не можна: API забороняє такий повторний вхід під час скасування. Завершення часу повідомляється за наступної взаємодії з меню, точного виклику обробника у вказаний момент чекати не треба.

<a id="shared-menu"></a>

## Одне спільне меню для кількох показів

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

Якщо пункти однакові й не змінюються для кожного гравця, створюємо меню один раз. Потім повторно використовуємо його handle. Закриття одним гравцем не має знищити меню для решти, тому `menu_destroy()` в обробнику тут не викликаємо.

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

Меню залишається після окремих виборів і звільняється в `plugin_end()`. Його заголовок, пункти та властивості теж спільні: зміна для одного гравця змінить той самий об’єкт. Для персонального вмісту потрібні окремі меню.

<a id="menu-access"></a>

## Перевіряємо права під час вибору пункту

[AMX Mod X API](https://www.amxmodx.org/api/newmenus/menu_additem)

Прапорець доступу у пункту чи обробник його малювання описує меню в момент показу. Поки гравець вибирав, права могли змінитися, а ціль вийти з гри. Тому перед самою дією знову перевіряємо все, що для неї потрібно.

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

У цьому меню лише одна дія, дані пункту читати не потрібно. Якщо меню вибирає іншого гравця, зберігаємо userid цілі й перевіряємо або шукаємо її заново під час вибору. Один збережений слот на той час може належати вже іншій людині.
