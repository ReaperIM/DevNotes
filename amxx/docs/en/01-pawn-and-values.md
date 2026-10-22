# Pawn and values

[EN](../en/01-pawn-and-values.md) | [UK](../uk/01-pawn-and-values.md) | [RU](../ru/01-pawn-and-values.md)

[Contents](../../README.md)

- [Cells and tags](#cells-and-tags)
- [Convert a value, or only change its tag?](#float-conversion)
- [Integer division before `Float` conversion](#division-and-rounding)
- [Comparisons, booleans and short-circuiting](#conditions-and-comparisons)
- [Constants and `enum` values](#constants-and-enums)
- [Output values through reference parameters](#reference-parameters)
- [Default and named arguments](#default-and-named-arguments)
- [Bit flags: set, test and clear](#bit-flags)
- [Local `new` vs `static`](#static-storage)
- [`switch` without fall-through](#switch-and-ranges)
- [`public` callbacks and `stock` helpers](#function-visibility)

<a id="cells-and-tags"></a>
## Cells and tags

In AMXX, an ordinary integer, a boolean and a `Float` each occupy one 32-bit cell. A tag tells the compiler how to interpret a value and which operators to use; it does not create a separate boxed object.

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

A tag mismatch is a warning worth fixing. Silencing it with `_:` can leave the wrong bit representation in a native call.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Types)

<a id="float-conversion"></a>
## Convert a value, or only change its tag?

`float()` converts an integer to a `Float`. `Float:` only reinterprets the existing bits. The reverse conversion is explicit too: choose the rounding rule with `floatround()`.

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

Use bit reinterpretation only when an API explicitly transports `Float` bits in an untagged cell. It is not a replacement for numeric conversion.

[AMXX / Pawn](https://www.amxmodx.org/api/float/floatround)

<a id="division-and-rounding"></a>
## Integer division before `Float` conversion

The operands determine how division runs. Assigning the result to a `Float` does not recover a fractional part already discarded by integer division.

```pawn
#include <amxmodx>

public plugin_init()
{
	new total = 5, players = 2;
	new whole = total / players;
	new remainder = total % players;
	new Float:average = float( total ) / float( players );

	server_print( "%d remainder %d; %.1f", whole, remainder, average );
	// 2 remainder 1; 2.5
}
```

Convert before dividing. Check a denominator obtained from input or a count before using it; `%` also requires a nonzero divisor.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="conditions-and-comparisons"></a>
## Comparisons, booleans and short-circuiting

Pawn uses `==` and `!=`; there is no PHP-style `===`. Combine conditions with `&&` and `||`. The right operand is skipped when the left one already decides the result.

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

The bounds check must come before the array access. For strings, use `equal()` or `equali()` rather than comparing arrays with `==`.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="constants-and-enums"></a>
## Constants and `enum` values

Use named constants for limits and `enum` values for a closed set of states. An `enum` can carry a tag, which helps catch mixing unrelated values.

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

`#define` performs textual substitution. `const` declares a compiler constant. Neither allocates a mutable global variable.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Compiler_Pre-processor_Directives)

<a id="reference-parameters"></a>
## Output values through reference parameters

A scalar parameter normally receives a copy. Prefix it with `&` when the function must update the caller’s variable. An array parameter already refers to the caller’s array.

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

Declare input arrays `const` when they must not change. For an output string, also pass its capacity; `output[]` does not tell the function the caller’s buffer size.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="default-and-named-arguments"></a>
## Default and named arguments

Defaults keep optional settings out of a simple call. A named argument lets a later setting be changed without manually repeating all earlier defaults.

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

The parameter name becomes part of these call sites. A rename may require changes beyond the function definition.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="bit-flags"></a>
## Bit flags: set, test and clear

A bit mask stores several independent switches in one cell. Use powers of two so each flag occupies its own bit.

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

	if( (flags & (flag_sound | flag_hud)) == (flag_sound | flag_hud) )
		server_print( "both enabled" );

	flags &= ~flag_sound;
	server_print( "sound=%d hud=%d", !!(flags & flag_sound), !!(flags & flag_hud) );
	// both enabled
	// sound=0 hud=1
}
```

`flags & mask` tests whether any requested bit is set; `(flags & mask) == mask` tests all of them. Do not shift by an unchecked player index: a cell has only 32 bits.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="static-storage"></a>
## Local `new` vs `static`

A local `new` variable starts fresh on each call. A local `static` is initialized once and keeps its value across calls, while its name remains local to the function.

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

A `static` buffer is shared by every call of that function. Reset the parts that belong to one operation, and do not treat it as per-player storage or safe scratch space during recursion.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Variables)

<a id="switch-and-ranges"></a>
## `switch` without fall-through

Pawn `switch` supports several values in one `case` and inclusive ranges. A matching `case` finishes without a `break`; it does not continue into the next `case`.

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

Use braces when a `case` performs several statements. Case labels are constant integer values, not arbitrary runtime conditions.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Switch_Statements)

<a id="function-visibility"></a>
## `public` callbacks and `stock` helpers

AMXX finds callbacks by their `public` name. An internal helper called directly does not need `public`. A `stock` helper is omitted from the compiled plugin when nothing uses it.

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

A callback registered as a string must keep the exact name and required signature. Marking it `stock` does not make it visible to AMXX.

[AMXX / Pawn](https://www.amxmodx.org/api/amxmodx/register_srvcmd)
