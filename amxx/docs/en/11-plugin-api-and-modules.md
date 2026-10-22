# Plugin APIs and modules

[English](../en/11-plugin-api-and-modules.md) | [Українська](../uk/11-plugin-api-and-modules.md) | [Русский](../ru/11-plugin-api-and-modules.md) | [Contents](../../README.md)

Natives for requests, forwards for notifications, and the memory / dependency contracts between plugins. Provider and consumer examples are separate plugins; each block compiles independently.

- [An include is not a loaded module](#include-and-runtime)
- [A provider native and its consumer](#register-native)
- [Strings cross plugin memory boundaries](#native-strings)
- [By-reference values need by-reference access](#native-byref)
- [Float parameters and native return cells](#native-floats)
- [Publish a forward and release its handle](#publish-forward)
- [Define what consumer returns mean](#forward-return-policy)
- [Prepare arrays before calling a forward](#forward-arrays)
- [Optional natives need a deliberate fallback](#optional-natives)
- [Keep the API contract small and explicit](#api-contract)

<a id="include-and-runtime"></a>
## An include is not a loaded module

An `.inc` gives the compiler declarations, constants and stocks. A native still needs a provider at runtime: either an AMXX module or another plugin. Compiling successfully proves that the names and signatures are available to the compiler, not that the server has their implementation.

| Kind | Compiled into your plugin? | Runtime provider |
| --- | --- | --- |
| `stock` with Pawn code | Used code is included | Your plugin |
| Native from `<cstrike>` | Declaration only | CStrike module |
| Native from another plugin | Declaration only | Provider `.amxx` |
| ReAPI native | Declaration only | Separate ReAPI module and required engine / game API |

AMXX includes normally declare module requirements and autoload metadata. Do not bring back `plugin_modules` / `require_module`: they are deprecated and do not provide a modern loading mechanism. Keep provider plugins before their consumers in `plugins.ini` and document the dependency.

Sources: [AMXX include declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [CStrike include](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/cstrike.inc).

<a id="register-native"></a>
## A provider native and its consumer

Register natives in `plugin_natives`, which runs before `plugin_init`. Style `0` receives the calling plugin ID and argument count; actual parameters are read through `get_param` and related natives.

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

The consumer prints `100`. In a shared API, put the declaration and `#pragma reqlib notes_api` in its include. The library requirement gives a clear load failure when the provider is absent. Style `1` natives are deprecated; do not use them for a new API. Never call the same native recursively from its own handler.

Sources: [plugin_natives](https://www.amxmodx.org/api/amxmodx/plugin_natives), [register_native](https://www.amxmodx.org/api/amxmodx/register_native), [register_library](https://www.amxmodx.org/api/amxmodx/register_library).

<a id="native-strings"></a>
## Strings cross plugin memory boundaries

A string parameter is not a local string address in the provider. Copy input with `get_string`; write output with `set_string`. The public API below is `native notes_make_label( const prefix[], output[], maxlen )`.


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

A caller with `new label[64]` passes `charsmax( label )`, not `sizeof label`. A prefix of `VIP` produces `[VIP] ready`, truncated if the destination is smaller. The caller owns the real buffer size; the provider cannot infer a correct capacity from an arbitrary integer. Array parameters similarly use `get_array` / `set_array` with an explicit cell count.

Sources: [get_string](https://www.amxmodx.org/api/amxmodx/get_string), [set_string](https://www.amxmodx.org/api/amxmodx/set_string), [get_array](https://www.amxmodx.org/api/amxmodx/get_array).

<a id="native-byref"></a>
## By-reference values need by-reference access

For `native notes_add_bonus( &score, bonus )`, argument `1` refers to a cell in the consumer. `get_param( 1 )` would read the argument’s address value, not the score stored there.


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

	new score = get_param_byref( 1 );
	score += get_param( 2 );
	set_param_byref( 1, score );
	return score;
}
```

Starting with `score = 20`, a call `notes_add_bonus( score, 5 )` leaves the consumer’s score at `25`. For `Float` references use `get_float_byref` / `set_float_byref`. Specify in the API whether the result is the updated value, a success flag or an error code.

Sources: [get_param_byref](https://www.amxmodx.org/api/amxmodx/get_param_byref), [set_param_byref](https://www.amxmodx.org/api/amxmodx/set_param_byref), [get_float_byref](https://www.amxmodx.org/api/amxmodx/get_float_byref).

<a id="native-floats"></a>
## Float parameters and native return cells

For `native Float:notes_scale( Float:value, Float:factor )`, read parameters with `get_param_f`. A style-0 native handler returns an untagged cell; remove the tag without numerically converting the float.


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
	return _:(value * factor);
}
```

The declaration on the consumer side restores the `Float` tag for the returned bits. `notes_scale( 2.5, 2.0 )` produces `5.0`. `floatround` here would change the result’s representation and break that declaration. Tag removal with `_:` and numeric conversion are different operations.

Sources: [get_param_f](https://www.amxmodx.org/api/amxmodx/get_param_f), [register_native](https://www.amxmodx.org/api/amxmodx/register_native).

<a id="publish-forward"></a>
## Publish a forward and release its handle

A native lets a consumer call the provider. A forward lets the provider notify consumers. `CreateMultiForward` targets matching public functions in plugins; keep its handle and destroy it when the provider ends.


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

A consumer implements `public notes_rank_changed( player, rank )`. `ET_IGNORE` means the notification does not use consumer return values. `ExecuteForward` itself returns whether execution succeeded; its `result` output is the aggregated callback result. Those are separate meanings. `CreateOneForward` targets one specified plugin rather than all matching consumers.

Sources: [forward API declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [ExecuteForward](https://www.amxmodx.org/api/amxmodx/ExecuteForward), [DestroyForward](https://www.amxmodx.org/api/amxmodx/DestroyForward).

<a id="forward-return-policy"></a>
## Define what consumer returns mean

A pre-action forward can offer a veto, but only when the provider checks its result before applying the action. Choose the execution mode as part of the API contract.

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

The consumer contract is `PLUGIN_CONTINUE` to allow, `PLUGIN_HANDLED` to deny. With no consumers the result is zero and the check allows the action. This example treats forward creation or execution failure as denial. A post-notification cannot cancel a reward already applied.

Sources: [ET constants](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxconst.inc), [forward execution](https://github.com/alliedmodders/amxmodx/blob/1.9-dev/amxmodx/CForward.cpp).

<a id="forward-arrays"></a>
## Prepare arrays before calling a forward

`FP_ARRAY` is not passed as an ordinary local array address. `PrepareArray` creates the parameter expected by `ExecuteForward`. Set `copyback = 1` only when consumer modifications should be copied back.


```pawn
#include <amxmodx>

public plugin_cfg()
{
	new handle = CreateMultiForward( "notes_adjust_values", ET_IGNORE, FP_ARRAY, FP_CELL );

	if( handle == -1 )
		return;

	new values[] = { 10, 20, 30 };
	new prepared = PrepareArray( values, sizeof values, 1 );
	new result;
	ExecuteForward( handle, result, prepared, sizeof values );
	DestroyForward( handle );
	server_print( "First value after callbacks: %d", values[0] );
}
```

A consumer implements `public notes_adjust_values( values[], count )` and stays within `count`. The length is passed explicitly because the callback does not receive the original array declaration. The prepared value is for forward execution, not an `Array:` container handle; do not pass it to `ArrayDestroy`.

Sources: [PrepareArray](https://www.amxmodx.org/api/amxmodx/PrepareArray), [ExecuteForward](https://www.amxmodx.org/api/amxmodx/ExecuteForward).

<a id="optional-natives"></a>
## Optional natives need a deliberate fallback

A runtime `if` does not by itself make an unresolved native optional: the plugin may fail while loading. For one optional plugin API, filter exactly the missing native during `plugin_natives` and guard its call. This consumer uses the `notes_api` provider from the earlier example.


```pawn
#include <amxmodx>

native notes_clamp_score( score );

public plugin_natives()
{
	set_native_filter( "filter_optional_native" );
}

public filter_optional_native( const native_name[], index, trap )
{
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

This intentionally has no hard `#pragma reqlib notes_api`. `trap = 0` means the native was not found during resolution; `trap = 1` means code attempted to call a missing native. Do not hide the latter error, or unrelated missing natives, behind a blanket filter. An include that also requires an optional module may additionally need a narrowly scoped module filter or separate build; removing a native error does not satisfy a library requirement.

Sources: [native and module filter declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [LibraryExists](https://www.amxmodx.org/api/amxmodx/LibraryExists).

<a id="api-contract"></a>
## Keep the API contract small and explicit

A shared include should explain what a consumer cannot discover from the signature alone:

- required provider and API version; library presence alone does not prove compatibility;
- whether an ID is a player slot, userid, entity index or opaque handle;
- whether disconnected players, bots and zero values are accepted;
- maximum output length and who owns any returned handle;
- success, failure and callback return values;
- whether a forward runs before or after state changes and whether consumers may modify its arguments.

Do not change an existing native’s parameter order or meaning silently. Add a version check or a new entry point when compatibility changes. `log_error( AMX_ERR_NATIVE, ... )` reports a provider-side validation failure as an error in the calling plugin, which is more useful than accepting an invalid player or handle and failing later.

Sources: [register_library](https://www.amxmodx.org/api/amxmodx/register_library), [log_error](https://www.amxmodx.org/api/amxmodx/log_error).
