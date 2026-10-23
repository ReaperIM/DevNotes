# Pawn and values

[EN](../en/01-pawn-and-values.md) | [UK](../uk/01-pawn-and-values.md) | [RU](../ru/01-pawn-and-values.md)

[Contents](../../README.md)

- [Cells and tags](#cells-and-tags)
- [Convert a value, or only change its tag?](#float-conversion)
- [Keep the fractional part when dividing](#division-and-rounding)
- [Comparisons and the order of checks](#conditions-and-comparisons)
- [Constants and `enum` values](#constants-and-enums)
- [Return several values through parameters](#reference-parameters)
- [Default and named arguments](#default-and-named-arguments)
- [Bit flags: set, test and clear](#bit-flags)
- [Local `new` vs `static`](#static-storage)
- [Multiple values and ranges in `switch`](#switch-and-ranges)
- [When `public` and `stock` are needed](#function-visibility)

<a id="cells-and-tags"></a>
## Cells and tags

An integer, a `bool` and a `Float` all take the same space in AMXX Pawn: one 32-bit cell. A tag tells the compiler how to interpret that value and which operators to use. For example, `Float:` tells it to treat `0.25` as a floating-point number. Adding a tag does not create a separate object.

```pawn
#include <amxmodx>

public plugin_init()
{
	new count = 7;
	new bool:enabled = true;
	new Float:delay = 0.25;

	server_print( "count=%d enabled=%d delay=%.2f", count, enabled, delay );
	// count=7 enabled=1 delay=0.25
}
```

When you see `tag mismatch`, check which kinds of values got mixed together. `_:` removes the tag, but it leaves the bits unchanged. A native can still receive a completely different number from the one you intended.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Types)

<a id="float-conversion"></a>
## Convert a value, or only change its tag?

To turn an integer into a floating-point value, use `float()`. Writing `Float:` only changes how the existing bits are interpreted. In this example, `7` becomes `7.0`, and division produces `3.5`. To go back to an integer, use `floatround()` and choose the rounding rule you need.

```pawn
#include <amxmodx>

public plugin_init()
{
	new count = 7;
	new Float:scaled = float( count ) / 2.0;

	server_print( "%.1f / %d / %d", scaled,
		floatround( scaled, floatround_floor ),
		floatround( scaled, floatround_ceil ) );
	// 3.5 / 3 / 4
}
```

Changing only the tag is useful when an API deliberately passes the bits of a `Float` in an untagged cell. For an ordinary numeric conversion, use the conversion function.

[AMXX / Pawn](https://www.amxmodx.org/api/float/floatround)

<a id="division-and-rounding"></a>
## Keep the fractional part when dividing

Divide `5` by `2` and Pawn gives you `2`: both operands are integers. Storing that result in a `Float` will not recover the missing `0.5`. Convert before dividing. The example shows the integer result, remainder and floating-point average side by side.

```pawn
#include <amxmodx>

public plugin_init()
{
	new total = 5, players = 2;
	new whole = total / players;
	new remainder = total % players;
	// Convert before dividing so the fractional part survives.
	new Float:average = float( total ) / float( players );

	server_print( "%d remainder %d; %.1f", whole, remainder, average );
	// 2 remainder 1; 2.5
}
```

If the divisor comes from a command or a counter, check it before calculating. Zero is invalid for both division with `/` and remainder with `%`.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="conditions-and-comparisons"></a>
## Comparisons and the order of checks

Compare values with `==` and `!=`; Pawn has no PHP-style `===`. Join conditions with `&&` or `||`. Checks run from left to right, and the right side is skipped once the result is known. This lets us check an index before reading the array element.

```pawn
#include <amxmodx>

public plugin_init()
{
	new values[] = { 10, 20, 30 };
	new index = 2;

	if( index >= 0 && index < sizeof values && values[index] == 30 )
		server_print( "found" );
	// found
}
```

In the example, `values[index]` is read only after the bounds checks pass. For strings, use `equal()` or `equali()`: `==` does not compare their contents.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="constants-and-enums"></a>
## Constants and `enum` values

When a number has a purpose, give it a name: `MAX_ROUNDS` is easier to follow than an unexplained `30` in a condition. Use an `enum` for a set of states. Here, the `round_state` tag helps the compiler spot a different kind of value being used as a state.

```pawn
#include <amxmodx>

const MAX_ROUNDS = 30;

enum round_state
{
	round_waiting,
	round_live,
	round_finished
};

public plugin_init()
{
	new round_state:current_state = round_live;
	server_print( "limit=%d live=%d", MAX_ROUNDS, current_state == round_live );
	// limit=30 live=1
}
```

`#define` substitutes text before compilation, while `const` declares a compiler constant. Neither creates a mutable global variable.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Compiler_Pre-processor_Directives)

<a id="reference-parameters"></a>
## Return several values through parameters

An ordinary scalar parameter receives a copy of the value. Add `&` when a function needs to write its result into the caller’s variable. Below, one call fills both `quotient` and `remainder`. An array parameter already refers to the caller’s array.

```pawn
#include <amxmodx>

public plugin_init()
{
	new quotient, remainder;
	divide_with_remainder( 17, 5, quotient, remainder );
	server_print( "%d remainder %d", quotient, remainder );
	// 3 remainder 2
}

divide_with_remainder( value, divisor, &quotient, &remainder )
{
	quotient = value / divisor;
	remainder = value % divisor;
}
```

Mark an array `const` if the function only reads it. For an output string, pass its capacity too: the declaration `output[]` does not tell the function how much space the caller allocated.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="default-and-named-arguments"></a>
## Default and named arguments

A function often gets called with the same settings. Give those parameters defaults so a simple call only needs the values that vary. Writing `.bonus = 5` changes the last parameter while leaving `multiplier` at `10`. That is why the two calls below produce `30` and `35`.

```pawn
#include <amxmodx>

public plugin_init()
{
	server_print( "%d / %d", calculate_reward( 3 ),
		calculate_reward( 3, .bonus = 5 ) );
	// 30 / 35
}

calculate_reward( count, multiplier = 10, bonus = 0 )
{
	return count * multiplier + bonus;
}
```

A named argument uses the parameter’s name as part of the call. If you rename that parameter, check these calls as well as the declaration.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="bit-flags"></a>
## Bit flags: set, test and clear

Several independent settings can fit in one number. Give each flag its own bit: `1`, `2`, `4` and so on. The example enables the HUD, checks two flags together, then disables sound while keeping the other settings.

```pawn
#include <amxmodx>

enum (<<= 1)
{
	flag_sound = 1,
	flag_hud,
	flag_trail
};

public plugin_init()
{
	new flags = flag_sound;
	flags |= flag_hud;

	// Require both flags, rather than either one.
	if( (flags & (flag_sound | flag_hud)) == (flag_sound | flag_hud) )
		server_print( "both enabled" );

	flags &= ~flag_sound;
	server_print( "sound=%d hud=%d", !!(flags & flag_sound), !!(flags & flag_hud) );
	// both enabled
	// sound=0 hud=1
}
```

Use `flags & mask` when any flag in the mask is enough. To require all of them, use `(flags & mask) == mask`. Check a player index before using it in a bit shift: a cell has only 32 bits.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="static-storage"></a>
## Local `new` vs `static`

A local `new` variable starts from its initial value on every call. A local `static` keeps its value between calls, while its name remains available only inside that function. So `next_sequence()` below returns `1` on the first call and `2` on the second.

```pawn
#include <amxmodx>

public plugin_init()
{
	server_print( "%d", next_sequence() ); // 1
	server_print( "%d", next_sequence() ); // 2
}

next_sequence()
{
	static sequence;
	return ++sequence;
}
```

A `static` buffer uses the same memory for every call. Clear data when it no longer belongs to the current operation. Each player does not get a separate buffer, and recursive calls share that memory too.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Variables)

<a id="switch-and-ranges"></a>
## Multiple values and ranges in `switch`

A `case` can list several values or an inclusive range. The range `3..5` includes both ends, so `level = 4` selects that branch. Once the matching branch finishes, Pawn leaves the `switch`; no `break` is needed.

```pawn
#include <amxmodx>

public plugin_init()
{
	new level = 4;

	switch( level )
	{
		case 0: server_print( "none" );
		case 1, 2: server_print( "basic" );
		case 3..5: server_print( "advanced" );
		default: server_print( "custom" );
	}
	// advanced
}
```

Use braces if a branch performs several actions. A `case` label needs integer constants; it cannot contain an arbitrary condition evaluated at runtime.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Switch_Statements)

<a id="function-visibility"></a>
## When `public` and `stock` are needed

A command is registered with a handler name: AMXX later finds and calls `command_double`. Mark that function `public`; this kind of handler is often called a callback. A normal direct call to `double_value()` does not need that visibility. Marking a helper `stock` lets the compiler omit it when nothing uses it.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_srvcmd( "notes_double", "command_double" );
}

public command_double()
{
	server_print( "%d", double_value( 7 ) ); // 14
	return PLUGIN_HANDLED;
}

stock double_value( value )
{
	return value * 2;
}
```

A handler name stored in a string must exactly match the `public` function name, and its parameters must match the API. Marking it `stock` alone does not make it available to AMXX.

[AMXX / Pawn](https://www.amxmodx.org/api/amxmodx/register_srvcmd)
