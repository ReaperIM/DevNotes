# Plugin APIs and modules

[EN](../en/11-plugin-api-and-modules.md) | [UK](../uk/11-plugin-api-and-modules.md) | [RU](../ru/11-plugin-api-and-modules.md) | [Contents](../../README.md)

Work through how one plugin calls another, passes data and receives a result. Natives provide calls; forwards provide notifications and checks before an action. The provider and caller examples are built separately: each code block is a standalone plugin.

- [Why an include alone is not enough](#include-and-runtime)
- [Expose a native to another plugin](#register-native)
- [Read and write another plugin’s strings](#native-strings)
- [Change another plugin’s variable by reference](#native-byref)
- [Pass a `Float` through a native without changing its value](#native-floats)
- [Notify other plugins through a forward](#publish-forward)
- [Let another plugin veto an action](#forward-return-policy)
- [Pass an array to a forward and copy changes back](#forward-arrays)
- [Keep working without an optional native](#optional-natives)
- [What to explain in a shared include](#api-contract)

<a id="include-and-runtime"></a>
## Why an include alone is not enough

An included `.inc` tells the compiler which names, parameters and constants exist. A used `stock` is compiled into our plugin, but a native still needs an implementation supplied by a module or another server plugin. Successful compilation does not prove that implementation is available at runtime.

| Kind | Compiled into your plugin? | Runtime provider |
| --- | --- | --- |
| `stock` with Pawn code | Used code is included | Your plugin |
| Native from `<cstrike>` | Declaration only | CStrike module |
| Native from another plugin | Declaration only | Provider `.amxx` |
| ReAPI native | Declaration only | Separate ReAPI module and required engine / game API |

AMXX includes usually already contain module requirements and information for automatic loading. Do not bring back the deprecated `plugin_modules` or `require_module`. If another plugin supplies the native, put it before the calling plugin in `plugins.ini` and document that dependency.

Sources: [AMXX include declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [CStrike include](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/cstrike.inc).

<a id="register-native"></a>
## Expose a native to another plugin

One plugin can expose a function to another as a native. Register it in `plugin_natives()`, before `plugin_init()`. With the usual style `0`, the handler receives the calling plugin ID and argument count, then reads values with `get_param()` and related functions. This example clamps the supplied score to `0..100`.

**Provider plugin:**
```pawn
#include <amxmodx>

public plugin_natives()
{
	register_library( "notes_api" );
	register_native( "notes_clamp_score", "native_clamp_score" );
}

public native_clamp_score( plugin_id, params )
{
	if( params != 1 )
	{
		log_error( AMX_ERR_NATIVE, "Expected one score argument" );
		return 0;
	}

	return clamp( get_param( 1 ), 0, 100 );
}
```

**Separate consumer plugin:**


```pawn
#include <amxmodx>

#pragma reqlib notes_api
native notes_clamp_score( score );

public plugin_init()
{
	server_print( "Clamped score: %d", notes_clamp_score( 135 ) );
}
```

The second block is a separate plugin: it passes `135` and prints `100`. For shared use, move the native declaration and `#pragma reqlib notes_api` into an include. The library requirement produces a clear load failure without the provider. Style `1` is deprecated and unnecessary for a new API. Do not recursively call the same native from its handler.

Sources: [`plugin_natives`](https://www.amxmodx.org/api/amxmodx/plugin_natives), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native), [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library).

<a id="native-strings"></a>
## Read and write another plugin’s strings

Each plugin has its own memory, so a passed string is not an ordinary local string in the handler. Read it with `get_string()` and write the answer with `set_string()`. The caller's declaration here is `native notes_make_label( const prefix[], output[], maxlen )`.


```pawn
#include <amxmodx>

public plugin_natives()
{
	register_native( "notes_make_label", "native_make_label" );
}

public native_make_label( plugin_id, params )
{
	if( params != 3 )
	{
		log_error( AMX_ERR_NATIVE, "Expected prefix, output and maxlen" );
		return 0;
	}

	new maxlen = get_param( 3 );

	if( maxlen < 0 )
	{
		log_error( AMX_ERR_NATIVE, "maxlen must not be negative" );
		return 0;
	}

	new prefix[32], label[64];
	get_string( 1, prefix, charsmax( prefix ) );
	formatex( label, charsmax( label ), "[%s] ready", prefix );
	return set_string( 2, label, maxlen );
}
```

For `new label[64]`, pass `charsmax( label )`, not `sizeof label`, leaving room for the string terminator. Prefix `VIP` produces `[VIP] ready`, truncated in a smaller buffer. The caller knows the real capacity; the handler cannot verify it from an arbitrary number. Arrays similarly use `get_array()` and `set_array()` with an explicit cell count.

Sources: [`get_string`](https://www.amxmodx.org/api/amxmodx/get_string), [`set_string`](https://www.amxmodx.org/api/amxmodx/set_string), [`get_array`](https://www.amxmodx.org/api/amxmodx/get_array).

<a id="native-byref"></a>
## Change another plugin’s variable by reference

To change a caller's variable through a native, declare it by reference: `native notes_add_bonus( &score, bonus )`. The first argument points to the cell holding the caller's `score`. Plain `get_param( 1 )` would read the address; use `get_param_byref()` to read the value stored there.


```pawn
#include <amxmodx>

public plugin_natives()
{
	register_native( "notes_add_bonus", "native_add_bonus" );
}

public native_add_bonus( plugin_id, params )
{
	if( params != 2 )
	{
		log_error( AMX_ERR_NATIVE, "Expected score reference and bonus" );
		return 0;
	}

	// Read the caller’s score value, not the address of its cell.
	new score = get_param_byref( 1 );
	score += get_param( 2 );
	set_param_byref( 1, score );
	return score;
}
```

`set_param_byref()` writes the updated value back to the caller. Starting at `score = 20`, `notes_add_bonus( score, 5 )` leaves `25`. For `Float` references, use `get_float_byref()` and `set_float_byref()`. Also specify what the native itself returns: the new value, success or an error code.

Sources: [`get_param_byref`](https://www.amxmodx.org/api/amxmodx/get_param_byref), [`set_param_byref`](https://www.amxmodx.org/api/amxmodx/set_param_byref), [`get_float_byref`](https://www.amxmodx.org/api/amxmodx/get_float_byref).

<a id="native-floats"></a>
## Pass a `Float` through a native without changing its value

For `native Float:notes_scale( Float:value, Float:factor )`, read the arguments with `get_param_f()`. A style-0 handler returns an untagged cell. Remove the result's tag with `_:` so the floating-point bits remain intact, rather than rounding the number to an integer.


```pawn
#include <amxmodx>

public plugin_natives()
{
	register_native( "notes_scale", "native_scale" );
}

public native_scale( plugin_id, params )
{
	if( params != 2 )
	{
		log_error( AMX_ERR_NATIVE, "Expected value and factor" );
		return _:0.0;
	}

	new Float:value = get_param_f( 1 );
	new Float:factor = get_param_f( 2 );
	// Keep the Float bits; remove only the result tag.
	return _:(value * factor);
}
```

The caller's declaration marks those returned bits as `Float`, so `notes_scale( 2.5, 2.0 )` gives `5.0`. Using `floatround()` instead would change the representation and make the caller read it incorrectly. `_:` only changes the tag; a numeric conversion changes the value representation.

Sources: [`get_param_f`](https://www.amxmodx.org/api/amxmodx/get_param_f), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native).

<a id="publish-forward"></a>
## Notify other plugins through a forward

A native lets another plugin call us; a forward lets us call handlers in other plugins. For example, we can announce that a player's rank changed. `CreateMultiForward()` finds matching `public` functions; keep its handle and release it with `DestroyForward()` when done.


```pawn
#include <amxmodx>

new g_rank_forward = -1;

public plugin_init()
{
	g_rank_forward = CreateMultiForward( "notes_rank_changed", ET_IGNORE, FP_CELL, FP_CELL );
}

public publish_rank( player, rank )
{
	if( g_rank_forward == -1 )
		return 0;

	new result;
	return ExecuteForward( g_rank_forward, result, player, rank );
}

public plugin_end()
{
	if( g_rank_forward != -1 )
		DestroyForward( g_rank_forward );
}
```

The other plugin declares `public notes_rank_changed( player, rank )`. With `ET_IGNORE`, we do not use its return value; this is only a notification. `ExecuteForward()` itself reports execution success, while `result` holds the combined handler responses. Those results mean different things. Use `CreateOneForward()` to target one particular plugin.

Sources: [forward API declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward), [`DestroyForward`](https://www.amxmodx.org/api/amxmodx/DestroyForward).

<a id="forward-return-policy"></a>
## Let another plugin veto an action

If another plugin can veto an action, call the forward before acting and check the response. For a reward, ask permission first and grant it afterwards. The `ET_*` mode determines which handlers run and how their results are combined.

| Mode | Policy |
| --- | --- |
| `ET_IGNORE` | Ignore callback results |
| `ET_STOP` | Stop at the first result greater than `0` |
| `ET_STOP2` | Stop on exactly `PLUGIN_HANDLED` (`1`); otherwise retain the greatest result |
| `ET_CONTINUE` | Call all callbacks and retain the greatest result |


```pawn
#include <amxmodx>

new g_reward_forward = -1;

public plugin_init()
{
	g_reward_forward = CreateMultiForward( "notes_can_reward", ET_STOP, FP_CELL );
}

public bool:can_reward( player )
{
	if( g_reward_forward == -1 )
		return false;

	new result;

	if( !ExecuteForward( g_reward_forward, result, player ) )
		return false;

	return result < PLUGIN_HANDLED;
}

public plugin_end()
{
	if( g_reward_forward != -1 )
		DestroyForward( g_reward_forward );
}
```

Here `PLUGIN_CONTINUE` allows the reward and `PLUGIN_HANDLED` denies it. With no handlers, the result is zero and the check allows the action. Failure to create or execute the forward denies it in this example. A notification sent after granting the reward cannot undo it, so the order matters.

Sources: [ET constants](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxconst.inc), [forward execution](https://github.com/alliedmodders/amxmodx/blob/1.9-dev/amxmodx/CForward.cpp).

<a id="forward-arrays"></a>
## Pass an array to a forward and copy changes back

A forward needs `PrepareArray()` for an array; a plain local address is not enough. Pass the prepared value as the `FP_ARRAY` argument of `ExecuteForward()`. The final `1` in `PrepareArray()` allows handler changes to be copied back into the original array; leave copyback off when that is not needed.


```pawn
#include <amxmodx>

public plugin_cfg()
{
	new handle = CreateMultiForward( "notes_adjust_values", ET_IGNORE, FP_ARRAY, FP_CELL );

	if( handle == -1 )
		return;

	new values[] = { 10, 20, 30 };
	// The final 1 copies handler changes back into values.
	new prepared = PrepareArray( values, sizeof values, 1 );
	new result;
	ExecuteForward( handle, result, prepared, sizeof values );
	DestroyForward( handle );
	server_print( "First value after callbacks: %d", values[0] );
}
```

The other plugin implements `public notes_adjust_values( values[], count )` and stays within `count`. Pass the length separately because the handler does not have the original array declaration. The prepared value is for calling the forward, not an `Array:` container; do not pass it to `ArrayDestroy()`.

Sources: [`PrepareArray`](https://www.amxmodx.org/api/amxmodx/PrepareArray), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward).

<a id="optional-natives"></a>
## Keep working without an optional native

An `if` before a call does not make a native optional: the plugin may fail to load before any code runs. Install a filter for exactly that native in `plugin_natives()`, then check the provider before calling it. Without `notes_api`, this example falls back to ordinary `clamp()`.


```pawn
#include <amxmodx>

native notes_clamp_score( score );

public plugin_natives()
{
	set_native_filter( "filter_optional_native" );
}

public filter_optional_native( const native_name[], index, trap )
{
	// Allow only this native to be missing during loading.
	if( !trap && equal( native_name, "notes_clamp_score" ) )
		return PLUGIN_HANDLED;

	return PLUGIN_CONTINUE;
}

public plugin_cfg()
{
	new score = 135;

	if( LibraryExists( "notes_api", LibType_Library ) )
		score = notes_clamp_score( score );
	else
		score = clamp( score, 0, 100 );

	server_print( "Score: %d", score );
}
```

There is deliberately no required `#pragma reqlib notes_api`. `trap = 0` means resolution could not find the native; `trap = 1` means code tried to call a missing native. Do not hide the latter case or unrelated errors with a blanket filter. An include requiring a module may also need a narrow module filter or a separate build: a native filter does not cancel the library requirement.

Sources: [native and module filter declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`LibraryExists`](https://www.amxmodx.org/api/amxmodx/LibraryExists).

<a id="api-contract"></a>
## What to explain in a shared include

A shared include should explain more than the parameters. The calling plugin needs to know how to use our function correctly and what to do with its result:

- which plugin or module is required and which API version works; a library being present is not enough;
- what an ID means: player slot, userid, entity index or a separate handle;
- whether disconnected players, bots or zero are accepted;
- how much fits in the output buffer and who releases a returned handle;
- what success and failure return, and what handler responses mean;
- whether a forward runs before or after the change, and whether its arguments may be modified.

Do not silently change an existing native's parameter order or meaning. Add a version check or a new API function when compatibility changes. Report an invalid argument with `log_error( AMX_ERR_NATIVE, ... )`: it points to the calling plugin, closer to the cause. That is clearer than accepting a bad ID or handle and failing somewhere else later.

Sources: [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library), [`log_error`](https://www.amxmodx.org/api/amxmodx/log_error).
