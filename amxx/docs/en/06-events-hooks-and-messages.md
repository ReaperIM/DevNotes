# Events, hooks and messages

[EN](../en/06-events-hooks-and-messages.md) | [UK](../uk/06-events-hooks-and-messages.md) | [RU](../ru/06-events-hooks-and-messages.md) | [Contents](../../README.md)

An event notification and a hook on the operation itself give us different options. Below, we work through when a handler runs, what it can change and what its return value means. Run the examples separately; game and optional-module dependencies are marked alongside them.

- [Choose what you need to intercept](#which-hook)
- [Filtered events and `read_data`](#filtered-events)
- [Read an event from the game log](#log-events)
- [Change the message the client receives](#message-hooks)
- [Change the action before the call, read the result after](#pre-and-post)
- [Pass changed damage into Ham](#ham-parameters)
- [The hook status and the game function result](#ham-return)
- [Fakemeta uses `FMRES_*`](#fakemeta-status)
- [Enable hooks when they are needed](#hook-lifetime)
- [ReAPI hooks and their dependencies](#reapi-hookchains)

<a id="which-hook"></a>
## Choose what you need to intercept

Do you need to learn that a player died, reduce incoming damage, or change a message sent to the client? These are three different jobs. A hook intercepts an operation; choose one that actually controls the behavior you want to change. If you only need the completed result, an event notification is enough.

| API | When to use it | Accessing the data |
| --- | --- | --- |
| `register_event_ex` | Observe a game message selected by argument filters | `read_data` |
| `register_logevent` | Respond to a parsed game log entry | `read_logargv` |
| `register_message` | Read, change or block an outgoing message | `get_msg_arg_*`, `set_msg_arg_*` |
| Ham Sandwich | Intercept an entity virtual function | Handler parameters, `SetHam*` |
| Fakemeta | Intercept engine and game DLL calls exposed by the module | Handler parameters, `FMRES_*` |
| ReAPI, optional module | Intercept functions exposed by ReHLDS / ReGameDLL APIs | Chain parameters, `HC_*` |

A message hook changes what the client sees. Hiding a message after damage has been dealt will not undo that damage. Entity state and game rules stay as the game logic left them.

Sources: [AMXX events](https://www.amxmodx.org/api/amxmodx/register_event_ex), [message hooks](https://www.amxmodx.org/api/messages/register_message), [Ham](https://www.amxmodx.org/api/hamsandwich/RegisterHam).

<a id="filtered-events"></a>
## Filtered events and `read_data`

**CS 1.6.** `DeathMsg` contains the killer, victim, headshot flag and weapon name. We only want headshot notifications, so registration includes the filter `3=1`. AMXX selects matching messages and calls `on_headshot()` for us.


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

Message arguments start at `1`; read them through `read_data()` inside this handler because they belong to the current event. The killer may be `0`, for example for world damage, so check the index before using player functions. Combine `RegisterEvent_*` flags with `|` as needed; they have their own tag.

Sources: [`register_event_ex`](https://www.amxmodx.org/api/amxmodx/register_event_ex), [`read_data`](https://www.amxmodx.org/api/amxmodx/read_data).

<a id="log-events"></a>
## Read an event from the game log

**CS 1.6.** Use the `Round_Start` log event for work that should happen once at round start. It is separate from each individual player spawn. Below, we catch the matching log entry and read the action name.


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

Log arguments start at `0`, so `Round_Start` is argument `1` here. The registration value `2` is the parsed log-argument count, not the function’s parameter count. Read these through `read_logargv()`. `HLTV`, `Round_Start` and a successful player spawn happen at different stages of the round.

Sources: [`register_logevent`](https://www.amxmodx.org/api/amxmodx/register_logevent), [`read_logargv`](https://www.amxmodx.org/api/amxmodx/read_logargv).

<a id="message-hooks"></a>
## Change the message the client receives

**CS 1.6.** `HideWeapon` contains flags for hidden HUD elements. Below, we read the current flags and add the crosshair bit. We preserve the others so the game’s settings or an earlier hook’s changes stay intact.


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
	// Keep all flags that were already set.
	set_msg_arg_int( 1, ARG_BYTE, flags | (1 << 6) );
	return PLUGIN_CONTINUE;
}
```

Use `get_msg_arg_*` in this kind of handler; returning `PLUGIN_HANDLED` blocks the entire message. For a broadcast, `receiver` can be `0`, so it is not always a player index. This hook only runs when `HideWeapon` is sent; it does not immediately refresh a HUD already on screen.

Sources: [`register_message`](https://www.amxmodx.org/api/messages/register_message), [`set_msg_arg_int`](https://www.amxmodx.org/api/messages/set_msg_arg_int), [CS HUD flags](https://github.com/rehlds/ReGameDLL_CS/blob/master/regamedll/dlls/cdll_dll.h).

<a id="pre-and-post"></a>
## Change the action before the call, read the result after

A pre hook runs before the original function: it can change parameters or suppress the call. A post hook runs afterward, when its resulting state is needed. Below, we print after a player spawns. Keep the `is_user_alive()` check: entering the handler alone does not prove that the player is alive.


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

Changing an input parameter in post cannot repeat the operation that already happened. `RegisterHam()` hooks a class virtual function, so registration covers the class rather than only players already on the server. Check the parameters for the selected `Ham_*` entry; a handler copied from another function may receive entirely different arguments.

Sources: [`RegisterHam`](https://www.amxmodx.org/api/hamsandwich/RegisterHam), [Ham signatures](https://www.amxmodx.org/api/ham_const).

<a id="ham-parameters"></a>
## Pass changed damage into Ham

Assigning a number to the local `damage` variable does not pass it to the original function. Use `SetHamParamFloat()` to change the call’s parameter. Damage is parameter `4` of `Ham_TakeDamage`, so below we pass half the original amount and return the status `HAM_HANDLED`.


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

	// Change the original call parameter, not just our local variable.
	SetHamParamFloat( 4, damage * 0.5 );
	return HAM_HANDLED;
}
```

The original function receives half the damage. The eventual health loss can differ because armor, damage rules and other hooks still participate. Choose `SetHamParam*` by the parameter’s declared type. Numbering includes the entity itself as parameter `1`.

Sources: [`SetHamParamFloat`](https://www.amxmodx.org/api/hamsandwich/SetHamParamFloat), [`Ham_TakeDamage`](https://www.amxmodx.org/api/ham_const).

<a id="ham-return"></a>
## The hook status and the game function result

The handler and the intercepted function return different things. Return a `HAM_*` status from the handler and set the game function’s value separately. Below, we reject healing through `Ham_TakeHealth`: the original function is skipped, and its caller receives `0`.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_TakeHealth, "player", "on_heal_pre" );
}

public on_heal_pre( player, Float:amount, damage_bits )
{
	// Set the result the game function caller will receive.
	SetHamReturnInteger( 0 );
	return HAM_SUPERCEDE;
}
```

`HAM_IGNORED` leaves the call alone. `HAM_HANDLED` also allows the original to run. `HAM_OVERRIDE` replaces its result while allowing execution, and `HAM_SUPERCEDE` skips the original in pre. The chosen result must match the function’s return type. Other code can still set health directly; this hook blocks the `Ham_TakeHealth` route.

Sources: [Ham return constants](https://www.amxmodx.org/api/ham_const), [`SetHamReturnInteger`](https://www.amxmodx.org/api/hamsandwich/SetHamReturnInteger).

<a id="fakemeta-status"></a>
## Fakemeta uses `FMRES_*`

Fakemeta uses its own `FMRES_*` statuses. This example intercepts the game DLL’s `ClientKill` callback before execution and returns `FMRES_SUPERCEDE` to skip the original. The client’s `kill` command usually leads to this callback.


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

`FMRES_IGNORED`, `FMRES_HANDLED`, `FMRES_OVERRIDE` and `FMRES_SUPERCEDE` make decisions similar to Ham’s statuses, but belong to a separate API. To replace a non-void result, set a matching value with `forward_return( FMV_*, ... )` as well. This blocks the `ClientKill` route; other causes of death remain possible.

Sources: [`register_forward`](https://www.amxmodx.org/api/fakemeta/register_forward), [`forward_return`](https://www.amxmodx.org/api/fakemeta/forward_return).

<a id="hook-lifetime"></a>
## Enable hooks when they are needed

If a hook is needed only some of the time, keep the handle from `RegisterHam()` and disable it with `DisableHamForward()`. AMXX then avoids entering the Pawn handler at all. Below, a server command enables spawn handling. An always-enabled hook with an early `return` would still require the handler call.


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

Register once, then toggle the existing hook. `ExecuteHamB()` calls Ham hooks again; using it from that same hook can cause recursion. `ExecuteHam()` does not call them, so choose deliberately. `RegisterHamFromEntity()` also hooks the class virtual table rather than only the supplied entity.

Sources: [`DisableHamForward`](https://www.amxmodx.org/api/hamsandwich/DisableHamForward), [`ExecuteHamB`](https://www.amxmodx.org/api/hamsandwich/ExecuteHamB), [`RegisterHamFromEntity`](https://www.amxmodx.org/api/hamsandwich/RegisterHamFromEntity).

<a id="reapi-hookchains"></a>
## ReAPI hooks and their dependencies

**CS 1.6 + ReAPI + ReGameDLL_CS.** This example needs the separate ReAPI module and a supported game DLL; AMXX 1.9 alone is not enough. We intercept ReGameDLL’s player-damage function. If attacker and victim match, we skip the original call and return `0`.


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

In pre, change inputs with `SetHookChainArg()` and the function result with `SetHookChainReturn()`. `HC_SUPERCEDE` skips the original. `HC_BREAK` also stops later handlers in the chain, affecting other plugins. Check the function’s `enum` signature and required ReAPI / ReGameDLL versions; replacing names in a Ham hook is not enough.

Sources: [ReAPI hookchain API](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi.inc), [game DLL signatures](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll_const.inc).
