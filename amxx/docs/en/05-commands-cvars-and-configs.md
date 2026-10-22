# Commands, cvars and configs

[EN](../en/05-commands-cvars-and-configs.md) | [UK](../uk/05-commands-cvars-and-configs.md) | [RU](../ru/05-commands-cvars-and-configs.md)

[Contents](../../README.md)

Command entry points, access checks and settings that remain predictable after configs and live edits. Examples use the AMXX 1.9 API.

**In this chapter**

- [Client, server and shared console commands](#command-routes)
- [Check access inside the handler](#command-access)
- [One argument or the whole argument line](#read-arguments)
- [Intercept a chat command without swallowing all chat](#chat-commands)
- [Keep the cvar pointer, not repeated name lookups](#cvar-pointers)
- [Describe cvars and give numeric values bounds](#cvar-bounds)
- [Bind live settings to global variables](#cvar-bindings)
- [React to a cvar change](#cvar-hooks)
- [Generate a plugin config with `AutoExecConfig()`](#auto-config)
- [Use final config values in `OnConfigsExecuted()`](#configs-executed)
- [Keep player text out of executable command strings](#safe-command-values)

<a id="command-routes"></a>

## Client, server and shared console commands

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_concmd)

Choose the registration by who can call the command. `register_clcmd()` listens to a client, `register_srvcmd()` to the server console, and `register_concmd()` to both. A shared handler receives `id == 0` for the server, so do not pass it straight into player-only natives.

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

The same command now replies in the caller's console, including the server console. A player-specific chat shortcut belongs in `register_clcmd( "say /notes", ... )` instead.

<a id="command-access"></a>

## Check access inside the handler

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/cmd_access)

Registration flags describe the command; the handler still needs `cmd_access()`. Pass the supplied `level`, not a hardcoded replacement, so `cmdaccess.ini` changes work. The argument count includes the command itself: `2` means one required argument.

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

`cmd_access()` checks privileges and the minimum count, not argument validity. Its normal flag check accepts any matching bit; if an operation needs every flag in a mask, use `(get_user_flags( id ) & mask) == mask`. Server-console access has its own treatment in the `stock`.

<a id="read-arguments"></a>

## One argument or the whole argument line

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/read_argv)

`read_argv( 0, ... )` is the command name, `read_argv( 1, ... )` the first parsed argument. `read_args()` reads the complete argument line. Quoted text is useful for a message, but `remove_quotes()` only removes surrounding quotes; it is not validation or command escaping.

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

`note_echo "hello world"` prints `hello world`. A fixed buffer can truncate input; do not silently treat a truncated token as a valid identifier for a sensitive action.

<a id="chat-commands"></a>

## Intercept a chat command without swallowing all chat

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_clcmd)

A `say` hook sees ordinary messages too. Return `PLUGIN_CONTINUE` for text that is not yours, and `PLUGIN_HANDLED` only for the recognized command. Register `say_team` separately when the shortcut should also work in team chat.

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

The exact comparison prevents `/notes_extra` from triggering the command. Other chat plugins may also hook `say`; blocking and ordering must be checked with the actual server plugin list.

<a id="cvar-pointers"></a>

## Keep the cvar pointer, not repeated name lookups

[AMX Mod X API](https://www.amxmodx.org/api/cvars/get_cvar_pointer)

`create_cvar()` returns a handle for your setting. For an existing game cvar, get its pointer once and check for `0`. Read or write it with `get_pcvar_*` / `set_pcvar_*`; the pointer itself is not the cvar value.

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

Do not register a game-owned cvar just to obtain a pointer. A missing pointer can mean that this mod does not provide the setting.

<a id="cvar-bounds"></a>

## Describe cvars and give numeric values bounds

[AMX Mod X API](https://www.amxmodx.org/api/cvars/create_cvar)

A description and bounds keep the setting understandable both in code and in generated configs. Here a delay stays between `0.1` and `60.0`; code using it does not need to clamp it on every event.

```pawn
#include <amxmodx>

public plugin_init()
{
	new delay = create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	server_print( "Delay: %.1f", get_pcvar_float( delay ) );
}
```

The default is applied on first registration since server startup, not on every map. Creating an already existing cvar does not duplicate it, but the call can replace its bounds. Use a distinctive prefix for plugin settings.

<a id="cvar-bindings"></a>

## Bind live settings to global variables

[AMX Mod X API](https://www.amxmodx.org/api/cvars/bind_pcvar_num)

Bindings keep variables updated when a cvar changes. They are useful for frequently read settings: an integer switch, a floating delay, or a short text prefix. Bound storage must be global or static; a local stack variable cannot outlive its function.

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

Changing `note_enabled` changes `g_enabled` automatically. Writing to `g_enabled` does not write back to the cvar. Use `set_pcvar_*` when changing the actual setting; do not bind the same variable to several cvars.

<a id="cvar-hooks"></a>

## React to a cvar change

[AMX Mod X API](https://www.amxmodx.org/api/cvars/hook_cvar_change)

A binding is enough when you only need the current value. A change hook is useful when a new value must rebuild derived data or restart a timer. The callback receives old and new values as strings; its return value cannot veto the change.

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

Changing the same cvar from its own hook can recurse indefinitely. Prefer creation-time bounds for simple ranges; initialize derived state after configs as well, instead of relying on a change event to happen.

<a id="auto-config"></a>

## Generate a plugin config with `AutoExecConfig()`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

Call `AutoExecConfig()` after registering your cvars. With automatic creation enabled, a missing config is generated from the plugin's cvars and then scheduled for execution. An existing file is used as it is; new defaults in the source do not rewrite the administrator's settings.

```pawn
#include <amxmodx>

public plugin_init()
{
	create_cvar( "note_enabled", "1", FCVAR_NONE, "Enable notes", true, 0.0, true, 1.0 );
	create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	AutoExecConfig( true, "notes" );
}
```

This uses `addons/amxmodx/configs/plugins/notes.cfg`. Pass the base name without `.cfg` and without dots. The optional folder is under `configs/plugins/`, not an arbitrary filesystem path.

<a id="configs-executed"></a>

## Use final config values in `OnConfigsExecuted()`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

`plugin_cfg()` is not a guarantee that every queued config command has already run. `OnConfigsExecuted()` runs once per map after server, AMXX, plugin and per-map configs. This is the place to derive startup behavior from their final values.

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

Individual cvar bounds cannot express relationships between settings. This example checks the relationship at map startup; if live edits are supported, validate it on changes too. The forward still runs if a scheduled config file fails to execute, so validate required settings yourself.

<a id="safe-command-values"></a>

## Keep player text out of executable command strings

[AMX Mod X API](https://www.amxmodx.org/api/cvars/set_pcvar_string)

A formatting placeholder is not command escaping. `server_cmd( "hostname %s", text )` still feeds the result to a command parser, where separators and quotes have meaning. For a cvar, write through its pointer; for a real command argument, accept only the intended values.

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

The value is stored as a cvar string instead of being executed. The same principle applies to `client_cmd()` and `console_cmd()`: fixed command names do not make arbitrary concatenated arguments safe.
