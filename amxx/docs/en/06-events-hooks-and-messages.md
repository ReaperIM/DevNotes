# Events, hooks and messages

[English](../en/06-events-hooks-and-messages.md) | [Українська](../uk/06-events-hooks-and-messages.md) | [Русский](../ru/06-events-hooks-and-messages.md) | [Contents](../../README.md)

Working notes on when a callback runs, which data it owns and what its return value actually controls. Examples are independent; game-specific protocols and optional modules are marked.

- [Choose the layer you need](#which-hook)
- [Filtered events and read_data](#filtered-events)
- [Log events have their own arguments](#log-events)
- [Change a message without replacing game state](#message-hooks)
- [Pre changes the call, post observes its result](#pre-and-post)
- [Ham: change parameters explicitly](#ham-parameters)
- [Ham: return status and function result](#ham-return)
- [Fakemeta uses FMRES_*](#fakemeta-status)
- [Enable hooks when they are needed](#hook-lifetime)
- [ReAPI hookchains, optional](#reapi-hookchains)

<a id="which-hook"></a>
## Choose the layer you need

A death notification, a call to `TakeDamage` and an outgoing message describe different moments. Hook the operation whose behavior you need to change; use notifications when you only need the result.

| API | Useful for | Data |
| --- | --- | --- |
| `register_event_ex` | Observe game messages, with argument filters | `read_data` |
| `register_logevent` | Observe parsed game log events | `read_logargv` |
| `register_message` | Read, change or block an outgoing message | `get_msg_arg_*`, `set_msg_arg_*` |
| Ham Sandwich | Hook an entity virtual function | Callback parameters, `SetHam*` |
| Fakemeta | Hook engine / DLL forwards exposed by the module | Callback parameters, `FMRES_*` |
| ReAPI, optional | Hook functions exposed by ReHLDS / ReGameDLL APIs | Hookchain parameters, `HC_*` |

A message hook changes what the client receives. It does not undo damage, restore an entity or reverse the game rules that produced the message.

Sources: [AMXX events](https://www.amxmodx.org/api/amxmodx/register_event_ex), [message hooks](https://www.amxmodx.org/api/messages/register_message), [Ham](https://www.amxmodx.org/api/hamsandwich/RegisterHam).

<a id="filtered-events"></a>
## Filtered events and read_data

**CS 1.6.** `DeathMsg` contains killer, victim, headshot flag and weapon name. Filtering `3=1` lets AMXX call this handler only for headshot notifications.


```pawn
#include <amxmodx>

public plugin_init()
{
	register_event_ex( "DeathMsg", "on_headshot", RegisterEvent_Global, "3=1" );
}

public on_headshot()
{
	new killer = read_data( 1 );
	new victim = read_data( 2 );
	new weapon[24];
	read_data( 4, weapon, charsmax( weapon ) );
	server_print( "Headshot: %d -> %d, %s", killer, victim, weapon );
}
```

Message arguments start at `1`. Read them inside this callback; they belong to the current event. A killer can be `0` for world damage, so validate it before using player natives. Flags are typed bit flags; combine them with `|` when needed.

Sources: [register_event_ex](https://www.amxmodx.org/api/amxmodx/register_event_ex), [read_data](https://www.amxmodx.org/api/amxmodx/read_data).

<a id="log-events"></a>
## Log events have their own arguments

**CS 1.6.** `Round_Start` is a game log event. It is convenient for round-level work that should run once, rather than once for every player spawn.


```pawn
#include <amxmodx>

public plugin_init()
{
	register_logevent( "on_round_start", 2, "1=Round_Start" );
}

public on_round_start()
{
	new action[32];
	read_logargv( 1, action, charsmax( action ) );
	server_print( "Log action: %s", action );
}
```

Log arguments start at `0`; here argument `1` is `Round_Start`. `argsnum = 2` matches the number of parsed log arguments, not the number of handler parameters. Use `read_logargv`, not `read_data`. `HLTV`, `Round_Start` and a successful player spawn represent different stages of the round.

Sources: [register_logevent](https://www.amxmodx.org/api/amxmodx/register_logevent), [read_logargv](https://www.amxmodx.org/api/amxmodx/read_logargv).

<a id="message-hooks"></a>
## Change a message without replacing game state

**CS 1.6.** `HideWeapon` carries a HUD bitmask. This hook adds the crosshair bit while preserving the other bits already set by the game or an earlier hook.


```pawn
#include <amxmodx>

public plugin_init()
{
	new message_id = get_user_msgid( "HideWeapon" );

	if( message_id )
		register_message( message_id, "on_hide_weapon" );
}

public on_hide_weapon( message_id, destination, receiver )
{
	new flags = get_msg_arg_int( 1 );
	set_msg_arg_int( 1, ARG_BYTE, flags | (1 << 6) );
	return PLUGIN_CONTINUE;
}
```

In message hooks use `get_msg_arg_*`, not `read_data`. Returning `PLUGIN_HANDLED` blocks the message entirely. `receiver` can be `0` for broadcast destinations; it is not always a usable player index. This example acts only when a `HideWeapon` message is sent and does not immediately update a client by itself.

Sources: [register_message](https://www.amxmodx.org/api/messages/register_message), [set_msg_arg_int](https://www.amxmodx.org/api/messages/set_msg_arg_int), [CS HUD flags](https://github.com/rehlds/ReGameDLL_CS/blob/master/regamedll/dlls/cdll_dll.h).

<a id="pre-and-post"></a>
## Pre changes the call, post observes its result

Use a pre hook to change parameters or suppress the original call. Use post when the original has already produced the state you need. A spawn callback still needs an alive check: a callback being invoked is not proof of a living player.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_Spawn, "player", "on_player_spawn_post", 1 );
}

public on_player_spawn_post( player )
{
	if( !is_user_alive( player ) )
		return HAM_IGNORED;

	client_print( player, print_chat, "Spawn complete" );
	return HAM_IGNORED;
}
```

In post, changing an input parameter cannot rewind the original operation. `RegisterHam` hooks a class virtual table, not just the players currently on the server. Its signature comes from the selected `Ham_*` entry; copying a callback from another function can produce invalid arguments.

Sources: [RegisterHam](https://www.amxmodx.org/api/hamsandwich/RegisterHam), [Ham signatures](https://www.amxmodx.org/api/ham_const).

<a id="ham-parameters"></a>
## Ham: change parameters explicitly

Assigning a new value to the local `damage` variable does not update the original function call. `SetHamParamFloat` changes parameter `4` in `Ham_TakeDamage`; the callback then returns a hook status.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_TakeDamage, "player", "on_damage_pre" );
}

public on_damage_pre( victim, inflictor, attacker, Float:damage, damage_bits )
{
	if( damage <= 0.0 )
		return HAM_IGNORED;

	SetHamParamFloat( 4, damage * 0.5 );
	return HAM_HANDLED;
}
```

This halves the amount passed into the original function. Final health loss can still differ because armor, damage rules or other hooks participate later. Choose the correct `SetHamParam*` for the declared parameter type; parameter numbering includes the entity itself as `1`.

Sources: [SetHamParamFloat](https://www.amxmodx.org/api/hamsandwich/SetHamParamFloat), [Ham_TakeDamage](https://www.amxmodx.org/api/ham_const).

<a id="ham-return"></a>
## Ham: return status and function result

A callback returns `HAM_*`, not the value that the game function should return. Set that value separately. Here healing through `Ham_TakeHealth` is rejected and the caller receives `0`.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_TakeHealth, "player", "on_heal_pre" );
}

public on_heal_pre( player, Float:amount, damage_bits )
{
	SetHamReturnInteger( 0 );
	return HAM_SUPERCEDE;
}
```

`HAM_IGNORED` leaves the call alone. `HAM_HANDLED` still permits the original. `HAM_OVERRIDE` replaces the return value while allowing the original, and `HAM_SUPERCEDE` skips the original in pre. A return override must match the function return type. Blocking this function does not block unrelated direct health setters.

Sources: [Ham return constants](https://www.amxmodx.org/api/ham_const), [SetHamReturnInteger](https://www.amxmodx.org/api/hamsandwich/SetHamReturnInteger).

<a id="fakemeta-status"></a>
## Fakemeta uses FMRES_*

Fakemeta forwards use their own status constants. This pre hook suppresses the game DLL `ClientKill` callback, normally reached through the client `kill` command.


```pawn
#include <amxmodx>
#include <fakemeta>

public plugin_init()
{
	register_forward( FM_ClientKill, "on_client_kill_pre" );
}

public on_client_kill_pre( player )
{
	return FMRES_SUPERCEDE;
}
```

`FMRES_IGNORED`, `FMRES_HANDLED`, `FMRES_OVERRIDE` and `FMRES_SUPERCEDE` express similar decisions to Ham, but belong to a different API. For a non-void return override, use the matching `forward_return( FMV_*, ... )` and status. This example blocks this callback, not every possible cause of suicide or death.

Sources: [register_forward](https://www.amxmodx.org/api/fakemeta/register_forward), [forward_return](https://www.amxmodx.org/api/fakemeta/forward_return).

<a id="hook-lifetime"></a>
## Enable hooks when they are needed

Keep the handle returned by `RegisterHam`. Disabling it avoids entering the Pawn callback at all; an early `return` inside an always-enabled callback still pays for that callback.


```pawn
#include <amxmodx>
#include <hamsandwich>

new HamHook:g_spawn_hook;

public plugin_init()
{
	g_spawn_hook = RegisterHam( Ham_Spawn, "player", "on_spawn_post", 1 );
	DisableHamForward( g_spawn_hook );
	register_srvcmd( "notes_enable_spawn", "enable_spawn_hook" );
}

public enable_spawn_hook()
{
	EnableHamForward( g_spawn_hook );
	return PLUGIN_HANDLED;
}

public on_spawn_post( player )
{
	if( is_user_alive( player ) )
		server_print( "Spawned: %d", player );

	return HAM_IGNORED;
}
```

Register once, then toggle that registration. `ExecuteHamB` triggers Ham hooks and can recurse when called from the same hook. `ExecuteHam` does not trigger those Ham hooks; this difference should be an intentional choice. `RegisterHamFromEntity` still hooks the virtual table of its class, not that single entity only.

Sources: [DisableHamForward](https://www.amxmodx.org/api/hamsandwich/DisableHamForward), [ExecuteHamB](https://www.amxmodx.org/api/hamsandwich/ExecuteHamB), [RegisterHamFromEntity](https://www.amxmodx.org/api/hamsandwich/RegisterHamFromEntity).

<a id="reapi-hookchains"></a>
## ReAPI hookchains, optional

**CS 1.6 + ReAPI + ReGameDLL_CS.** ReAPI is a separate module, not a newer spelling of Ham or an AMXX 1.9 built-in. This example suppresses self-inflicted calls to the ReGameDLL player damage function.


```pawn
#include <amxmodx>
#include <reapi>

public plugin_init()
{
	RegisterHookChain( RG_CBasePlayer_TakeDamage, "on_damage_pre" );
}

public on_damage_pre( victim, inflictor, attacker, Float:damage, damage_bits )
{
	if( victim != attacker )
		return HC_CONTINUE;

	SetHookChainReturn( ATYPE_INTEGER, 0 );
	return HC_SUPERCEDE;
}
```

`SetHookChainArg` changes inputs in pre; `SetHookChainReturn` sets the function result. `HC_SUPERCEDE` skips the original. `HC_BREAK` additionally skips following hook callbacks, so it can interfere with other plugins. Read the exact function enum signature and required ReAPI / ReGameDLL versions rather than mechanically translating a Ham hook.

Sources: [ReAPI hookchain API](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi.inc), [game DLL signatures](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll_const.inc).
