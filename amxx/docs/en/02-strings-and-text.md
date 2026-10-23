# Strings and text

[EN](../en/02-strings-and-text.md) | [UK](../uk/02-strings-and-text.md) | [RU](../ru/02-strings-and-text.md)

[Contents](../../README.md)

- [How much text fits in a buffer](#buffers-and-charsmax)
- [Format values into a buffer](#format-values)
- [When to use `format` instead of `formatex`](#overlapping-buffers)
- [Append text in parts](#append-with-offset)
- [Quotes and newlines with `^`](#caret-escaping)
- [String comparison and substring position](#comparison-and-search)
- [`parse` for a known number of fields](#parse-fields)
- [First argument plus the remaining text](#argbreak-and-argparse)
- [Trim outer whitespace and quotes](#trim-and-remove-quotes)
- [Check the whole numeric input](#number-parsing)
- [UTF-8 bytes and case conversion](#unicode-bytes)
- [Dictionary messages with `%L`](#multilingual-messages)

<a id="buffers-and-charsmax"></a>
## How much text fits in a buffer

A string buffer needs one cell for the terminating zero. That leaves room for up to 15 bytes of text in `name[16]`. `charsmax(name)` gives us that limit of `15`, while `strlen(name)` tells us how many bytes are already used. The name `Ada` uses just three.

```pawn
#include <amxmodx>

public plugin_init()
{
	new name[16];
	formatex( name, charsmax( name ), "%s", "Ada" );
	server_print( "capacity=%d length=%d text=%s", charsmax( name ), strlen( name ), name );
	// capacity=15 length=3 text=Ada
}
```

Pass the kind of length the function expects. String functions often want the limit without the terminating zero; an array API may want the full cell count. Check before replacing `charsmax` with `sizeof`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strlen)

<a id="format-values"></a>
## Format values into a buffer

Use `formatex()` to build text from a name, numbers and other values. Pass the buffer and available length, followed by the format string: `%d` for integers, `%f` for `Float`, and `%s` for text. Here, `%.2f` keeps two decimal places, and `%%` prints an ordinary percent sign.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[96];
	new Float:distance = 12.345;
	new written = formatex( text, charsmax( text ), "%s: %d points, %.2f units, 100%%", "Ada", 7, distance );

	server_print( "%s", text );
	server_print( "written=%d", written );
	// Ada: 7 points, 12.35 units, 100%
}
```

Pass external text through `%s`: `server_print( "%s", text )`. A percent sign in a name or message will then stay part of the text instead of being treated as a formatting instruction.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="overlapping-buffers"></a>
## When to use `format` instead of `formatex`

To put brackets around a name and write it back into the same string, use `format()`, as below. It handles the input and output sharing a buffer. `formatex()` can overwrite text it has not finished reading, so it does not suit this case.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[64] = "Ada";
	format( text, charsmax( text ), "[%s]", text );
	server_print( "%s", text );
	// [Ada]
}
```

The same applies to a slice of that buffer, such as `text[5]`. When the source strings are separate from the destination, the usual `formatex()` call is fine.

[AMXX / Pawn](https://www.amxmodx.org/api/string/format)

<a id="append-with-offset"></a>
## Append text in parts

Build a message in pieces by keeping track of the length already written. Start the next piece at `text[length]` and pass only the space that remains. Here, the score is appended after the name without replacing the first part.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[64], length;
	length += formatex( text[length], charsmax( text ) - length, "Player: %s", "Ada" );
	// Only pass the capacity still available after the first part.
	length += formatex( text[length], charsmax( text ) - length, "; score: %d", 7 );

	server_print( "%s", text );
	// Player: Ada; score: 7
}
```

When appending in a loop, stop once the buffer is full. Check the offset itself too. Do not read an earlier part of this same buffer through `%s` in that `formatex()` call: the source and destination would overlap.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="caret-escaping"></a>
## Quotes and newlines with `^`

In ordinary AMXX Pawn source, `^` introduces special characters. Write `^"` for a quotation mark, `^n` for a newline, and `^^` for one caret. The first output below has quotes around the name and a literal `^` after it. These escapes are processed in the source; they are separate from chat colors.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[] = "^"Ada^"^^notes";
	server_print( "%s", text );
	// "Ada"^notes

	new lines[] = "first^nsecond";
	server_print( "%s", lines );
}
```

Reading the literal characters `^n` from a file does not create a newline automatically. The compiler handles escapes in source strings; text received while the plugin runs needs separate processing if that is desired.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Strings)

<a id="comparison-and-search"></a>
## String comparison and substring position

Use `equal()` to compare strings, or `equali()` when case should be ignored. `containi()` searches without regard to case and returns the match position. In the example, `admin` is found at position `0`, right at the start.

```pawn
#include <amxmodx>

public plugin_init()
{
	new name[] = "Admin Ada";
	new position = containi( name, "admin" );

	if( position != -1 )
		server_print( "match at %d", position );

	server_print( "equal=%d", equali( "YES", "yes" ) );
	// match at 0
	// equal=1
}
```

Check the search result with `!= -1`. Writing `if( containi(...) )` rejects a valid match at `0` and accepts the failure value `-1`. For case-insensitive Unicode searches, check that the chosen API supports the characters you need.

[AMXX / Pawn](https://www.amxmodx.org/api/string/containi)

<a id="parse-fields"></a>
## `parse` for a known number of fields

When a line should contain several fields, `parse()` splits them into separate buffers. Spaces separate fields, while quoted text stays together. That puts all of `Ada Lovelace` into `name` and `42` into `score_text`. Pass an allowed text length for each buffer.

```pawn
#include <amxmodx>

public plugin_init()
{
	new line[] = "^"Ada Lovelace^" 42";
	new name[32], score_text[12];
	new fields = parse( line, name, charsmax( name ), score_text, charsmax( score_text ) );

	if( fields == 2 )
		server_print( "name=%s score=%s", name, score_text );
	// name=Ada Lovelace score=42
}
```

Check the number of fields before using them. We expect `2` here; an incomplete line needs handling first. For CSV data, use an appropriate parser: `parse()` follows command-argument rules and does not implement CSV escaping.

[AMXX / Pawn](https://www.amxmodx.org/api/string/parse)

<a id="argbreak-and-argparse"></a>
## First argument plus the remaining text

Sometimes only the first argument needs to be separated, with the remaining text kept together. Use `argbreak()` for that. Below, the quoted name goes into `target`, and the rest goes into `message`. To continue reading arguments one at a time, `argparse()` lets you resume from an offset.

```pawn
#include <amxmodx>

public plugin_init()
{
	new line[] = "^"Ada Lovelace^" keep this text";
	new target[32], message[64];
	new next_position = argbreak( line, target, charsmax( target ), message, charsmax( message ) );

	if( next_position != -1 )
		server_print( "target=%s; message=%s", target, message );
	// target=Ada Lovelace; message=keep this text
}
```

A result of `-1` means no argument was found. An empty quoted string still counts as an argument. For new code, use these functions instead of the deprecated `strbreak()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/argparse)

<a id="trim-and-remove-quotes"></a>
## Trim outer whitespace and quotes

For a single value such as a name, first use `trim()` to remove surrounding whitespace, then `remove_quotes()` to remove the outer quotes. The example leaves `Ada Lovelace`. If the line contains several arguments, parse them first so removing quotes does not change what the spaces mean.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[32] = "  ^"Ada Lovelace^"  ";
	trim( text );
	remove_quotes( text );
	server_print( "[%s]", text );
	// [Ada Lovelace]
}
```

The order matters: `trim()` brings the quotes to the edges so `remove_quotes()` can remove them. If whitespace inside the quotes is meaningful, leave it alone instead of trimming again afterward.

[AMXX / Pawn](https://www.amxmodx.org/api/string/remove_quotes)

<a id="number-parsing"></a>
## Check the whole numeric input

The beginning of `12x` can be read as `12`, but that does not make the whole input a valid number. `strtol()` returns the value and tells us where parsing stopped. Below, we check that a number was found, no text remains, and the value is within `1..32`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new token[] = "12x";
	new end_position;
	new value = strtol( token, end_position, 10 );

	if( end_position == 0 || token[end_position] != 0 || value < 1 || value > 32 )
		server_print( "invalid player count" );
	else
		server_print( "count=%d", value );
	// invalid player count
}
```

This accepts whitespace and a sign according to the function’s own rules. If the format allows digits only, check that separately. `str_to_num()` alone cannot confirm that the entire input is valid.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strtol)

<a id="unicode-bytes"></a>
## UTF-8 bytes and case conversion

A UTF-8 letter can use several bytes. That is why `strlen()` returns `6` for `Кіт`, even though it has three visible letters. Buffer space still needs to be counted in bytes. Use the `mb_*` functions to change the case of this text; the example produces `КІТ`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[32] = "Кіт";
	server_print( "bytes=%d", strlen( text ) ); // bytes=6

	mb_strtoupper( text, charsmax( text ) );
	server_print( "%s", text ); // КІТ
}
```

Case conversion can change the byte length, so pass the buffer’s actual capacity. Cutting at an arbitrary byte can split a UTF-8 character. A byte count also does not tell you how many visible grapheme clusters the text contains.

[AMXX / Pawn](https://www.amxmodx.org/api/string/mb_strtoupper)

<a id="multilingual-messages"></a>
## Dictionary messages with `%L`

When a message depends on the player’s language, put it in a dictionary and use `%L`. Register the dictionary once, then pass the language target and message key. This example uses the existing `YES` key in the standard `common.txt`; an English server gets `Yes`.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_dictionary( "common.txt" );

	new text[64];
	formatex( text, charsmax( text ), "%L", LANG_SERVER, "YES" );
	server_print( "%s", text );
	// Yes, when the server language is English.
}
```

For one player, pass that player’s index instead of `LANG_SERVER`. `LANG_PLAYER` is for printing functions that support multiple recipients; it cannot preformat different languages into one shared buffer. The dictionary file must be present on the server.

[AMXX / Pawn](https://www.amxmodx.org/api/lang/register_dictionary)
