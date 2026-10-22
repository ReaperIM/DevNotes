# Tasks, clocks and menus

[EN](../en/07-tasks-and-menus.md) | [UK](../uk/07-tasks-and-menus.md) | [RU](../ru/07-tasks-and-menus.md)

[Contents](../../README.md)

Delayed work and interactive menus share the same trap: the world can change before the callback runs. Keep ownership, identity and cleanup visible. Examples use AMXX 1.9; the `Think` example also uses Engine.

**In this chapter**

- [Call a `public` function later](#one-shot-task)
- [Readable repeat flags with `set_task_ex()`](#repeat-flags)
- [Keep unrelated task IDs separate](#task-id-ownership)
- [A delayed player action needs connection identity](#player-task-identity)
- [Start and stop a repeating task explicitly](#stop-repeat-task)
- [Use a deadline for a cooldown](#cooldowns-and-clocks)
- [Attach timing to an entity with `Think`](#entity-think)
- [Destroy a dynamic menu on every completion path](#dynamic-menu)
- [A shared menu has a different lifetime](#shared-menu)
- [Recheck permissions when an item is selected](#menu-access)

<a id="one-shot-task"></a>

## Call a `public` function later

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_task)

A task schedules a callback without blocking the server. The callback must be `public` because AMXX finds it by name. With no parameter array, the callback can receive the task ID as its first argument.

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

The message appears on a later server frame, roughly two seconds later. Tasks are not worker threads and do not make expensive SQL, file work or loops asynchronous.

<a id="repeat-flags"></a>

## Readable repeat flags with `set_task_ex()`

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/set_task_ex)

`set_task_ex()` is the `amxmisc` `stock` with named task flags. `SetTask_RepeatTimes` repeats a fixed number of times; `SetTask_Repeat` keeps running until stopped. The older `set_task()` uses `"a"` and `"b"` for the same modes.

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

This prints three pulses. `SetTask_AfterMapStart` (`"c"`) and `SetTask_BeforeMapChange` (`"d"`) change the meaning of the time argument; they are map-relative scheduling modes, not shorter intervals.

<a id="task-id-ownership"></a>

## Keep unrelated task IDs separate

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/remove_task)

Calling `set_task()` twice with the same ID does not mean replacement. Several tasks may share an ID; `remove_task()` removes all matching ones. Remove before scheduling when the intended behavior is one pending task.

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

Repeated requests now postpone a single refresh. IDs are scoped to the current plugin by default. The `outside` argument deliberately reaches other plugins; leave it at `0` for ordinary plugin-owned tasks.

<a id="player-task-identity"></a>

## A delayed player action needs connection identity

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_user_userid)

A player index is a reusable slot. After a disconnect, another player may occupy the same `id` before a callback runs. Store the connection userid alongside the slot, remove the task on disconnect, and verify both at execution.

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

With a parameter array, that array becomes the callback's first argument. Use `sizeof data`, not `charsmax( data )`: this is cell data, not a terminated string. A userid identifies a connection, not a persistent account.

<a id="stop-repeat-task"></a>

## Start and stop a repeating task explicitly

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/task_exists)

`task_exists()` is useful for an idempotent start: enabling an already running feature should not create a second timer. Stop the timer when the feature is disabled instead of leaving it to wake up and immediately return forever.

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

Choose different ID ranges for unrelated player tasks and global tasks. A round restart does not automatically cancel your plugin's task; hook the relevant round lifecycle when cancellation is part of the rule.

<a id="cooldowns-and-clocks"></a>

## Use a deadline for a cooldown

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/get_gametime)

A cooldown usually needs no ticking task. Store a game-time deadline and compare it when the action is requested. `get_gametime()` counts from map start; `get_systime()` is a Unix timestamp for calendar time and persistence. Do not compare values from those clocks.

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

The slot state is reset for a new connection. Game time is constant during one engine frame, so it is unsuitable for measuring how long an individual function takes.

<a id="entity-think"></a>

## Attach timing to an entity with `Think`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/engine.inc)

When the timed behavior belongs to an entity, its `nextthink` can express ownership directly. This example creates one invisible `info_target`, gives it a distinct classname and schedules its next `Think` after each call. It requires the Engine module.

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

`Think` does not repeat unless you schedule another `nextthink`. Removing the entity removes this owner of the timer. It is still frame-driven server work, not a background thread or a reason to create one entity per simple cooldown.

<a id="dynamic-menu"></a>

## Destroy a dynamic menu on every completion path

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

A menu created for one display owns a resource until `menu_destroy()`. A selection, Exit, timeout or cancellation can all reach its handler. Check `item < 0` before reading item data; cancellation is not always just `MENU_EXIT`.

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

The item info is a stable action key; the visible caption can later change or be translated. Do not immediately call `menu_display()` from a handler with negative `item`: the API forbids that re-entrant cancellation path. A timeout is reported on subsequent menu interaction, not a promise that the callback runs exactly at the deadline.

<a id="shared-menu"></a>

## A shared menu has a different lifetime

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/newmenus.inc)

For identical static choices, create the menu once and reuse its handle. A player closing it must not destroy it for everyone else. Keep this separate from the create-per-display pattern above.

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

The menu survives individual selections and is released at plugin shutdown. The menu object is shared too: changing its title, item text or properties for one player changes that same object. Use separate menus for genuinely personalized contents.

<a id="menu-access"></a>

## Recheck permissions when an item is selected

[AMX Mod X API](https://www.amxmodx.org/api/newmenus/menu_additem)

An item access mask or drawing callback controls how a menu looks now. The action happens later, when the player may have lost permission or the game state may have changed. Recheck the actual action conditions in the handler.

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

This menu has one action, so no item-data lookup is needed. For a player-target menu, store a connection userid as the target key and resolve or verify it at selection time; a stored slot alone can refer to someone else.
