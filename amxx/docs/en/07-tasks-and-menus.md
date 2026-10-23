# Tasks, clocks and menus

[EN](../en/07-tasks-and-menus.md) | [UK](../uk/07-tasks-and-menus.md) | [RU](../ru/07-tasks-and-menus.md)

[Contents](../../README.md)

While a timer waits or a player chooses a menu item, the server can change. Keep the connection identity, check conditions again before acting, and clean up tasks and menus when finished. These examples use AMXX 1.9; the `Think` example also needs Engine.

**Topics in this chapter**

- [Call a `public` function later](#one-shot-task)
- [Repeat a task a few times or until stopped](#repeat-flags)
- [Keep one pending task with one ID](#task-id-ownership)
- [Check the player again after a delay](#player-task-identity)
- [Start repeating once, and stop when finished](#stop-repeat-task)
- [Add a cooldown without a separate timer](#cooldowns-and-clocks)
- [Give an entity recurring work through `Think`](#entity-think)
- [Create a menu for one display and release it afterward](#dynamic-menu)
- [Reuse one menu for several displays](#shared-menu)
- [Check permissions when the item is selected](#menu-access)

<a id="one-shot-task"></a>

## Call a `public` function later

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_task)

Need to do something later? `set_task()` schedules a call and immediately gives control back to the server. Mark the handler `public` so AMXX can find it by name. Without a parameter array, its first argument can receive the task ID.

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

The example prints roughly two seconds later, on a subsequent server frame. Work inside the task still runs on the server: a long synchronous SQL query, file read or expensive loop can hold up the game there too.

<a id="repeat-flags"></a>

## Repeat a task a few times or until stopped

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/set_task_ex)

The `amxmisc` function `set_task_ex()` gives task flags readable names. `SetTask_RepeatTimes` repeats a fixed number of times; `SetTask_Repeat` continues until stopped. The older `set_task()` uses `"a"` and `"b"` for those modes. Below, we choose three repeats.

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

This produces three messages. `SetTask_AfterMapStart` (`"c"`) and `SetTask_BeforeMapChange` (`"d"`) schedule relative to map start or map change. They change what the time argument means rather than simply making the interval shorter.

<a id="task-id-ownership"></a>

## Keep one pending task with one ID

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/remove_task)

Calling `set_task()` again with the same ID does not replace the previous task; several tasks can share it. `remove_task()` removes all matches. For one pending refresh, remove the old task before scheduling its replacement.

```pawn
#include <amxmodx>

const TASK_REFRESH = 100;

public plugin_init()
{
	register_srvcmd( "note_refresh", "command_refresh" );
}

public command_refresh()
{
	// Remove the previous task so repeated requests leave only one.
	remove_task( TASK_REFRESH );
	set_task( 1.0, "task_refresh", TASK_REFRESH );
	return PLUGIN_HANDLED;
}

public task_refresh()
{
	server_print( "One refresh after the last request" );
}
```

Each command call now postpones one refresh until a second after the last request. IDs belong to the current plugin by default. The `outside` parameter can reach other plugins; leave it at `0` for an ordinary task of your own.

<a id="player-task-identity"></a>

## Check the player again after a delay

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_user_userid)

In five seconds, a player can leave and someone else can take their slot. Save the connection userid alongside `id` so the welcome does not reach the new person. Remove the task on disconnect, then check the connection again inside the handler.

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

	// The slot may already belong to a different connection.
	if( !is_user_connected( id ) || get_user_userid( id ) != data[1] )
		return;

	client_print( id, print_chat, "Your connection is still the same" );
}
```

With a parameter array, the array becomes the handler’s first argument. Its length is `sizeof data`: pass all cells, without applying the string rule of `charsmax()`. A userid identifies one connection; a persistent account needs a different key.

<a id="stop-repeat-task"></a>

## Start repeating once, and stop when finished

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/task_exists)

A repeated enable command should not create a second timer. Check `task_exists()` and create the task only when it is absent. On disable, remove it entirely so it stops waking up just to do nothing. Below, status prints every ten seconds until the stop command is called.

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

Use separate ID ranges for unrelated player tasks and global tasks. A round restart does not cancel your task automatically. If it should end with the round, add cleanup to the appropriate event.

<a id="cooldowns-and-clocks"></a>

## Add a cooldown without a separate timer

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_gametime)

A command cooldown usually only needs the next allowed time. Compare it with `get_gametime()` when another command arrives. Below, `/ping` works at most once every three seconds. Game time counts from map start; `get_systime()` provides a Unix timestamp for dates and saved data. Do not compare values from those two clocks.

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

Reset slot state for a new connection. `get_gametime()` stays unchanged within one frame, so it cannot measure the duration of an individual function.

<a id="entity-think"></a>

## Give an entity recurring work through `Think`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/engine.inc)

If a timed action belongs to an entity, keep its next call time in `nextthink`. Below, we create an invisible `info_target`, give it a distinct classname, and schedule another call each time it runs. This example needs Engine.

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

`Think` does not repeat by itself: set `nextthink` again. Removing the entity also removes the object that owns this timer. The work still follows server frames; an ordinary command cooldown does not need a separate entity for each player.

<a id="dynamic-menu"></a>

## Create a menu for one display and release it afterward

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

A menu created with `menu_create()` needs releasing through `menu_destroy()`. Its handler can run after a selection, Exit, cancellation or timeout. Check `item < 0` before reading item data: a negative result is not always just `MENU_EXIT`.

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
	// Cancellation has no selected item to read.
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

The `info` string holds an action key, so the visible caption can be renamed or translated. Do not immediately call `menu_display()` from a handler with negative `item`; the API forbids that re-entry during cancellation. A timeout is reported on subsequent menu interaction, so do not expect a callback exactly at the deadline.

<a id="shared-menu"></a>

## Reuse one menu for several displays

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

For identical choices that do not vary by player, create the menu once and reuse its handle. One player closing it must not destroy it for everyone else, so this handler does not call `menu_destroy()`.

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

The menu remains after individual selections and is released in `plugin_end()`. Its title, items and properties are shared too: changing them for one player changes the same object. Use separate menus for personalized contents.

<a id="menu-access"></a>

## Check permissions when the item is selected

[AMX Mod X API](https://www.amxmodx.org/api/newmenus/menu_additem)

An item access flag or drawing callback describes the menu when it is displayed. While the player chooses, permissions may change or the target may leave. Check everything the actual action requires again just before performing it.

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

This menu has only one action, so item data does not need reading. For a menu targeting another player, save the target’s userid and verify or resolve it at selection time. A saved slot alone may belong to someone else by then.
