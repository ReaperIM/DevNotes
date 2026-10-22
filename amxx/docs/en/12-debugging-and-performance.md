# Debugging and performance

[English](../en/12-debugging-and-performance.md) | [Українська](../uk/12-debugging-and-performance.md) | [Русский](../ru/12-debugging-and-performance.md)

[Contents](../../README.md)

Loading, runtime diagnostics, ownership and work in frequent callbacks. Optimize the confirmed hot path while preserving the event and data contract.

- [Compiler errors and warnings are different evidence](#compile)
- [Separate a load failure from a runtime error](#load-errors)
- [Log the failed operation with its context](#logging)
- [Stop a plugin that cannot operate correctly](#fail-state)
- [Do cheap checks before expensive work](#hot-paths)
- [Compare squared distance when only a radius matters](#distance)
- [Static locals retain state between calls](#static-locals)
- [Track ownership, not just whether a handle is nonzero](#ownership)
- [Game time is not a precise profiler](#clocks)

<a id="compile"></a>

## Compiler errors and warnings are different evidence

An undefined symbol often points to a missing or wrong include; a tag mismatch often means that a Float, enum or handle was passed as another kind of cell. Fix the contract instead of hiding the warning with a cast.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:duration = 1.5;
	new seconds = floatround( duration, floatround_ceil );
	server_print( "duration=%.1f, whole seconds=%d", duration, seconds ); // 1.5, 2
}
```

Compile against the intended include set. A successful build does not check entity validity, module availability, file paths or callback timing on the server.

[Compiler options](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))

<a id="load-errors"></a>

## Separate a load failure from a runtime error

For a plugin that does not start, check `amxx plugins`, `amxx modules` and the AMXX error log. An unknown native usually means a missing provider or an older runtime/module than the include used for compilation.

For runtime failures, enable the plugin debug flag in `plugins.ini`, reproduce the event and inspect the reported function and line. Check that the loaded `.amxx` actually matches the source. [Compatibility](COMPATIBILITY.md).

[Debugging](https://wiki.alliedmods.net/Debugging_Plugins_(AMX_Mod_X))

<a id="logging"></a>

## Log the failed operation with its context

Use a constant format string and pass external text as an argument. Include the operation and stable connection context so the message is useful after the player leaves.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	log_amx( "Authorized: slot=%d userid=%d auth=%s", id, get_user_userid(id), authid );
}
```

This is a diagnostic example, not a requirement to log every authorization. Do not print passwords, database credentials or every frame. Rate-limit repetitive failures at their source.

[amxmodx.inc](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="fail-state"></a>

## Stop a plugin that cannot operate correctly

`set_fail_state()` stops the calling plugin and records the reason. Use it when a required dependency or data file is missing and continuing would leave broken behavior.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_cfg()
{
	new directory[128], path[192];
	get_configsdir( directory, charsmax(directory) );
	formatex( path, charsmax(path), "%s/required_rules.ini", directory );
	if( !file_exists(path) )
	{
		set_fail_state( "Required rules file is missing: %s", path );
		return;
	}

	server_print( "Required file found" );
}
```

A missing optional cosmetic feature can simply be disabled. Choose a fail state for a real inability to satisfy the plugin contract, not for every rejected user command.

[amxmodx.inc](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="hot-paths"></a>

## Do cheap checks before expensive work

A PreThink hook can be called very frequently. Reject disabled or irrelevant states first, and avoid reading files, opening SQL connections or scanning all players for a local operation.

```pawn
#include <amxmodx>
#include <fakemeta>
new bool:g_enabled;
new Float:g_next_check[MAX_PLAYERS + 1];

public plugin_init()
{
	bind_pcvar_num( create_cvar("notes_enabled", "1"), g_enabled );
	register_forward( FM_PlayerPreThink, "player_prethink" );
}

public client_putinserver( id )
{
	g_next_check[id] = 0.0;
}

public player_prethink( id )
{
	if( !g_enabled || !is_user_alive(id) )
		return FMRES_IGNORED;

	new Float:now = get_gametime();
	if( now < g_next_check[id] )
		return FMRES_IGNORED;

	g_next_check[id] = now + 1.0;
	// Perform the periodic check here, not on every call.
	return FMRES_IGNORED;
}
```

This throttles the body, not the hook itself. If the feature does not need frame access, a task, entity think or a specific event can be clearer. Do not move a calculation away from the event if that changes required timing.

[get_gametime](https://www.amxmodx.org/api/amxmodx/get_gametime)

<a id="distance"></a>

## Compare squared distance when only a radius matters

For a radius test, compare the sum of squared coordinate differences with the squared radius. This keeps the same decision for a nonnegative radius without needing the actual distance.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:first[3] = {0.0, 0.0, 0.0};
	new Float:second[3] = {3.0, 4.0, 0.0};
	new Float:radius = 5.0, Float:distance_squared;
	for( new axis; axis < 3; axis++ )
	{
		new Float:delta = second[axis] - first[axis];
		distance_squared += delta * delta;
	}

	server_print( "Inside: %d", distance_squared <= radius * radius ); // 1
}
```

This is a geometric radius test, not a visibility or reachability check. A 2D radius deliberately omits the vertical component; do not drop it accidentally.

[Vector functions](https://www.amxmodx.org/api/vector)

<a id="static-locals"></a>

## Static locals retain state between calls

A local `static` buffer is reused across calls; an ordinary local `new` has a separate lifetime for each call. Static storage can be useful, but recursive or reentrant calls share it.

```pawn
#include <amxmodx>

public plugin_init()
{
	new first = next_counter();
	new second = next_counter();
	server_print( "%d, %d", first, second ); // 1, 2
}

next_counter()
{
	static count;
	return ++count;
}
```

Static storage keeps the counter between calls. For recursive or reentrant code, use ordinary locals for temporary data that must belong to one call. Increasing `#pragma dynamic` does not fix unbounded recursion or invalid indexes.

[Pawn language](https://wiki.alliedmods.net/Pawn_Tutorial)

<a id="ownership"></a>

## Track ownership, not just whether a handle is nonzero

A handle is meaningful only to its own API. Record who creates it, who closes it and which invalid sentinel that API uses.

- Array / Trie: `ArrayDestroy()` / `TrieDestroy()` for owned containers.
- nVault: `nvault_close()`; failed open is `INVALID_HANDLE` (`-1`).
- SQLX: free owned tuples, connections and prepared queries with `SQL_FreeHandle()`; do not free a threaded callback query.
- New menu: destroy according to its lifetime; a shared menu cannot be destroyed after the first player uses it.

[Containers](03-arrays-and-containers.md) | [Menus](07-tasks-and-menus.md) | [Storage](09-files-vault-and-sql.md)

[ArrayDestroy](https://www.amxmodx.org/api/cellarray/ArrayDestroy) | [SQL_ThreadQuery](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery)

<a id="clocks"></a>

## Game time is not a precise profiler

`get_gametime()` is useful for cooldowns and scheduling within a map. `get_systime()` returns integer wall-clock seconds for persistent timestamps. They solve different tasks.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:next_action = get_gametime() + 2.0;
	new expires_at = get_systime() + 3600;
	server_print( "Map deadline: %.2f, wall-clock expiry: %d", next_action, expires_at );
}
```

Do not benchmark a tiny block by subtracting two game-time reads in the same frame. Compare the same workload with controlled player/entity counts, and separate callback frequency from the cost of one call.

[get_gametime](https://www.amxmodx.org/api/amxmodx/get_gametime) | [get_systime](https://www.amxmodx.org/api/amxmodx/get_systime)
