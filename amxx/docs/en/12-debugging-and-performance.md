# Debugging and performance

[EN](../en/12-debugging-and-performance.md) | [UK](../uk/12-debugging-and-performance.md) | [RU](../ru/12-debugging-and-performance.md)

[Contents](../../README.md)

First find out why the plugin did not load or where it failed, then look for unnecessary work in frequent calls. Along the way, check `static`, clocks and resource cleanup: these details often explain both bugs and unexpected load.

- [Compiler errors and warnings are different evidence](#compile)
- [Separate a load failure from a runtime error](#load-errors)
- [Log enough detail to understand the failure](#logging)
- [Stop a plugin when a required file is missing](#fail-state)
- [Check whether work is needed before doing it in a frequent hook](#hot-paths)
- [Compare squared distance when only a radius matters](#distance)
- [What a local `static` keeps between calls](#static-locals)
- [Who should release a handle](#ownership)
- [Game time, timestamps and measuring load](#clocks)

<a id="compile"></a>

## Compiler errors and warnings are different evidence

`Undefined symbol` often means the required include is missing or a different version was used. For `tag mismatch`, check what the function expects: a `Float`, an `enum` tag or a handle may have received another kind of cell. A cast can hide the warning without fixing the value. Here `floatround()` actually converts fractional time to whole seconds.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:duration = 1.5;
	new seconds = floatround( duration, floatround_ceil );
	server_print( "duration=%.1f, whole seconds=%d", duration, seconds ); // 1.5, 2
}
```

Compile with the include set intended for the server. A successful build checks the code, but does not prove that modules and files exist, entities are valid or handlers run at the right time on the server.

[Compiler options](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))

<a id="load-errors"></a>

## Separate a load failure from a runtime error

If the plugin never starts, begin with `amxx plugins`, `amxx modules` and the AMXX error log. An unknown native usually means its supplying module or plugin is missing. Another common cause is an include newer than the installed AMXX runtime or module.

If the plugin loads but fails during an action, enable its `debug` flag in `plugins.ini`, repeat the action and inspect the function and line in the log. Check the `.amxx` too: it must be built from the source you are using to locate the error. [Compatibility](COMPATIBILITY.md).

[Debugging](https://wiki.alliedmods.net/Debugging_Plugins_(AMX_Mod_X))

<a id="logging"></a>

## Log enough detail to understand the failure

A message saying only "player error" is not helpful after the player leaves. Log what happened, the slot, `userid` and connection details that let you reconstruct the situation. Keep the format string fixed and pass external text as an argument so its `%` characters cannot become format instructions.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	log_amx( "Authorized: slot=%d userid=%d auth=%s", id, get_user_userid(id), authid );
}
```

The example shows useful message details, rather than a requirement to log every authorization. Keep passwords and database credentials out of logs. If one failure repeats every frame, limit repeat messages at its source or useful entries will get buried.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="fail-state"></a>

## Stop a plugin when a required file is missing

If a required file or dependency is missing, stop the plugin with a clear reason through `set_fail_state()`. It records the reason and stops the calling plugin. Without `required_rules.ini`, this example cannot process its rules correctly, so it stops.

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

A missing optional sound or other cosmetic feature can be disabled on its own. A rejected player command is no reason to stop the whole plugin either; reserve a fail state for a plugin that cannot do its required work.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="hot-paths"></a>

## Check whether work is needed before doing it in a frequent hook

`PreThink` runs frequently, so start with short checks and return when the work is not needed. File reads, new SQL connections or scanning all players here can quickly add unnecessary load. This example reaches the main check at most once per second for each living player.

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
	// Run the check once per second, not on every hook call.
	return FMRES_IGNORED;
}
```

The hook still runs just as often; only our check body runs less often. If frame timing is not required, a task, entity think or a suitable event may be clearer. Move the calculation only when doing so preserves the timing the feature needs.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime)

<a id="distance"></a>

## Compare squared distance when only a radius matters

If we only need to know whether a point is within a radius, we do not need the actual distance. Sum the squared coordinate differences and compare with the squared radius. For a nonnegative radius, the answer is the same without taking a square root. Here the distance is five, so the point is within `5.0`.

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

This says nothing about walls, visibility or an accessible path. Use two coordinates when a flat circle deliberately ignores height; do not accidentally drop the third coordinate when a 3D radius is needed.

[Vector functions](https://www.amxmodx.org/api/vector)

<a id="static-locals"></a>

## What a local `static` keeps between calls

A local `static` survives between function calls. Here `count` is not recreated at zero, so the first call gives `1` and the next gives `2`. An ordinary local `new` has separate data and lifetime for each call.

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

Recursive or reentrant calls share the same `static`. Keep temporary data in ordinary locals when it must belong to one call. Raising `#pragma dynamic` will not fix infinite recursion or an out-of-bounds array access.

[Pawn language](https://wiki.alliedmods.net/Pawn_Tutorial)

<a id="ownership"></a>

## Who should release a handle

A nonzero number does not prove that a handle is usable or ours to close. Check which API created it, how that API reports failure and who is responsible for releasing it. Closing someone else's handle can be as wrong as leaking our own.

- Release our own `Array` and `Trie` containers with `ArrayDestroy()` and `TrieDestroy()`.
- Close nVault with `nvault_close()`. An open failure is `INVALID_HANDLE`, meaning `-1` here, not zero.
- For SQLX, release our own tuples, connections and prepared queries with `SQL_FreeHandle()`. Do not release the query supplied to a threaded callback ourselves.
- For a new menu, consider how long it is shared. If several players use the same menu, the first reply does not mean it is safe to destroy it.

[Containers](03-arrays-and-containers.md) | [Menus](07-tasks-and-menus.md) | [Storage](09-files-vault-and-sql.md)

[`ArrayDestroy`](https://www.amxmodx.org/api/cellarray/ArrayDestroy) | [`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery)

<a id="clocks"></a>

## Game time, timestamps and measuring load

Use `get_gametime()` for a delay within the current map. Use `get_systime()` for a persistent wall-clock timestamp in whole seconds. Here we separately calculate the next action after two game seconds and an expiry one hour later in wall-clock time.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:next_action = get_gametime() + 2.0;
	new expires_at = get_systime() + 3600;
	server_print( "Map deadline: %.2f, wall-clock expiry: %d", next_action, expires_at );
}
```

Do not time a tiny code block with two game-time reads in the same frame; that clock is not suitable for such precision. Compare load with matching player and entity counts. Keep the cost of one call separate from how often the call happens.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime) | [`get_systime`](https://www.amxmodx.org/api/amxmodx/get_systime)
