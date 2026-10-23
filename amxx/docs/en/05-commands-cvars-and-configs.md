# Commands, cvars and configs

[EN](../en/05-commands-cvars-and-configs.md) | [UK](../uk/05-commands-cvars-and-configs.md) | [RU](../ru/05-commands-cvars-and-configs.md)

[Contents](../../README.md)

A command needs the right registration, an access check and argument handling. Settings need care too: when they load and how they change during play both matter. The examples below use the AMXX 1.9 API.

**Topics in this chapter**

- [Where a command can be called from](#command-routes)
- [Check access inside the handler](#command-access)
- [Read one argument or the whole command text](#read-arguments)
- [Handle your command and let other chat through](#chat-commands)
- [Keep a cvar pointer for repeated reads](#cvar-pointers)
- [Give a cvar a description and allowed range](#cvar-bounds)
- [Bind live settings to global variables](#cvar-bindings)
- [React to a cvar change](#cvar-hooks)
- [Generate a plugin config with `AutoExecConfig()`](#auto-config)
- [Check settings after the configs load](#configs-executed)
- [Change a cvar without executing external text](#safe-command-values)

<a id="command-routes"></a>

## Where a command can be called from

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_concmd)

First decide who will call the command. Use `register_clcmd()` for a player, `register_srvcmd()` for the server console, or `register_concmd()` for both. In a shared handler, the server arrives with `id == 0`. Do not pass that index to functions that require a player.

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

In this example, the command replies in the console it was called from, including the server console. For a player chat shortcut, register a separate route through `register_clcmd( "say /notes", ... )`.

<a id="command-access"></a>

## Check access inside the handler

[AMX Mod X API](https://www.amxmodx.org/api/amxmisc/cmd_access)

Setting a flag when registering a command is not enough: call `cmd_access()` in its handler. Pass the supplied `level` so changes in `cmdaccess.ini` take effect. The last number counts the command name too: `2` means one argument is required after it.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	register_concmd( "amx_note_mode", "command_mode", ADMIN_CFG, "<on|off>" );
}

public command_mode( id, level, command_id )
{
	// The command name counts too: require one more argument.
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

Privileges and argument count are checked, but the value still needs checking separately: this example accepts only `on` and `off`. The usual `cmd_access()` flag check accepts any matching bit. If all bits are required, use `(get_user_flags( id ) & mask) == mask`. The `stock` handles server-console access separately.

<a id="read-arguments"></a>

## Read one argument or the whole command text

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/read_argv)

`read_argv( 0, ... )` gives the command name, and `read_argv( 1, ... )` the first parsed argument. Use `read_args()` for all text after the command. Below, we remove surrounding quotes and print the message. `remove_quotes()` only removes those quotes; it does not validate the contents or make them safe to execute as a command.

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

Calling `note_echo "hello world"` prints `hello world`. A line longer than the buffer can be truncated. For a sensitive action, check this explicitly so truncated text cannot become a different valid identifier.

<a id="chat-commands"></a>

## Handle your command and let other chat through

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/register_clcmd)

A `say` hook receives ordinary messages as well as your command. Let everything other than `/notes` through with `PLUGIN_CONTINUE`. For the recognized command, print a reply and return `PLUGIN_HANDLED`. Register `say_team` separately if it should work in team chat too.

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

	// Leave other messages available to chat and other handlers.
	if( !equali( text, "/notes" ) )
		return PLUGIN_CONTINUE;

	client_print( id, print_chat, "Use note_echo to test a command" );
	return PLUGIN_HANDLED;
}
```

The exact comparison prevents `/notes_extra` from accidentally triggering `/notes`. Other plugins can handle `say` too, so check ordering and blocking with the server’s actual plugin list.

<a id="cvar-pointers"></a>

## Keep a cvar pointer for repeated reads

[AMX Mod X API](https://www.amxmodx.org/api/cvars/get_cvar_pointer)

For your own setting, `create_cvar()` returns a handle. For an existing game cvar, get its pointer once with `get_cvar_pointer()`. This identifies the setting; read its value through `get_pcvar_*` and change it through `set_pcvar_*`. Below, we check that the pointer was found before reading `mp_roundtime`.

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

A pointer of `0` means it was not found: the mod may not provide that setting at all. Do not register a game-owned cvar just to obtain a pointer.

<a id="cvar-bounds"></a>

## Give a cvar a description and allowed range

[AMX Mod X API](https://www.amxmodx.org/api/cvars/create_cvar)

Set a clear description and allowed bounds when creating a setting. They help both in the source and in generated configs. Here, the delay stays between `0.1` and `60.0`, so code does not need to clamp it again on every game event.

```pawn
#include <amxmodx>

public plugin_init()
{
	new delay = create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	server_print( "Delay: %.1f", get_pcvar_float( delay ) );
}
```

The default applies on first registration after server startup, not on every map. An existing cvar is not duplicated, but this call can replace its bounds. Use a distinctive prefix to avoid affecting another plugin’s settings.

<a id="cvar-bindings"></a>

## Bind live settings to global variables

[AMX Mod X API](https://www.amxmodx.org/api/cvars/bind_pcvar_num)

For a frequently read setting, bind it to a variable. AMXX then updates the number, delay or string when its cvar changes. The command below can read the current `g_enabled`, `g_delay` and `g_prefix` directly. Bind to global or static storage: an ordinary local variable is no longer suitable once its function returns.

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

Changing `note_enabled` updates `g_enabled`, but assigning to `g_enabled` does not change the cvar. Use `set_pcvar_*` to change the setting itself. Do not bind one variable to several cvars: it would have no single setting to represent.

<a id="cvar-hooks"></a>

## React to a cvar change

[AMX Mod X API](https://www.amxmodx.org/api/cvars/hook_cvar_change)

A binding is enough when you only need to read the current value. If a change must rebuild data or restart a timer, add `hook_cvar_change()`. Its handler receives the old and new values as strings. Returning a value from the handler cannot cancel the change.

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

Changing the same cvar from its handler can start an endless chain of calls. For ordinary limits, prefer creation-time bounds. Prepare the initial state after configs too: a change event may never happen.

<a id="auto-config"></a>

## Generate a plugin config with `AutoExecConfig()`

[AMX Mod X API](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

Call `AutoExecConfig()` after registering your cvars. With creation enabled, AMXX fills a missing file with the plugin’s settings, then queues it for execution. An existing config is used as it stands. Changing a source default does not overwrite the administrator’s settings.

```pawn
#include <amxmodx>

public plugin_init()
{
	create_cvar( "note_enabled", "1", FCVAR_NONE, "Enable notes", true, 0.0, true, 1.0 );
	create_cvar( "note_delay", "3.0", FCVAR_NONE, "Delay in seconds", true, 0.1, true, 60.0 );
	AutoExecConfig( true, "notes" );
}
```

This example uses `addons/amxmodx/configs/plugins/notes.cfg`. Pass the name without `.cfg` or dots. An optional folder sits inside `configs/plugins/`; that parameter does not accept an arbitrary disk path.

<a id="configs-executed"></a>

## Check settings after the configs load

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

Config commands may still be queued in `plugin_cfg()`. `OnConfigsExecuted()` runs once per map after server, AMXX, plugin and per-map configs. Use it to read the resulting values and prepare the plugin’s startup behavior.

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
	// Separate cvar bounds cannot check this relationship.
	if( g_min_players > g_max_players )
	{
		set_fail_state( "note_min_players must not exceed note_max_players" );
		return;
	}

	server_print( "Configured player range: %d..%d", g_min_players, g_max_players );
}
```

Separate cvar bounds cannot check that the minimum is no greater than the maximum. In this example, we compare the settings together. This checks map startup; if live changes are supported, validate those too. The callback runs even if a scheduled config file fails to execute, so check required values yourself.

<a id="safe-command-values"></a>

## Change a cvar without executing external text

[AMX Mod X API](https://www.amxmodx.org/api/cvars/set_pcvar_string)

Text substituted into `server_cmd( "hostname %s", text )` still goes through a command parser. Quotes and separators can change what the result means; `%s` does not neutralize them. For a cvar, write directly through its pointer. For an actual command, allow only the intended argument values.

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

	// Store a value directly, without passing it through a command parser.
	set_pcvar_string( g_hostname, hostname );
	return PLUGIN_HANDLED;
}
```

In this example, the server name is stored as a cvar string. Take the same care with `client_cmd()` and `console_cmd()`: a fixed command name does not make arbitrary appended text safe.
