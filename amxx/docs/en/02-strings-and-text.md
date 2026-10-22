# Strings and text

[EN](../en/02-strings-and-text.md) | [UK](../uk/02-strings-and-text.md) | [RU](../ru/02-strings-and-text.md)

[Contents](../../README.md)

- [Buffer size and `charsmax`](#buffers-and-charsmax)
- [Format values into a buffer](#format-values)
- [`format` when input and output overlap](#overlapping-buffers)
- [Build text with a write offset](#append-with-offset)
- [Caret escaping in Pawn source](#caret-escaping)
- [String comparison and substring position](#comparison-and-search)
- [`parse` for a known number of fields](#parse-fields)
- [First argument plus the remaining text](#argbreak-and-argparse)
- [Trim outer whitespace and quotes](#trim-and-remove-quotes)
- [Parse a whole numeric token](#number-parsing)
- [UTF-8 bytes and case conversion](#unicode-bytes)
- [Dictionary messages with `%L`](#multilingual-messages)

<a id="buffers-and-charsmax"></a>
## Buffer size and `charsmax`

An ordinary AMXX string is an array terminated by a zero cell. A buffer of 16 cells holds at most 15 bytes of text plus the terminator. `charsmax()` gives that usable text capacity.

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

Use the length convention of the specific API: many string natives take a maximum text length, while some array APIs take a cell count. `sizeof` and `charsmax` are not interchangeable.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strlen)

<a id="format-values"></a>
## Format values into a buffer

`formatex()` writes a bounded result directly into the destination. Match the format specifier to the value: `%d` for integers, `%f` for `Float` and `%s` for strings.

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

Pass external text as a value: `server_print( "%s", text )`. If it becomes the format string, percent signs in that text can be interpreted as formatting directives.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="overlapping-buffers"></a>
## `format` when input and output overlap

`formatex()` cannot read from the buffer it is currently overwriting. For an in-place transformation with overlapping source and destination, `format()` provides the necessary copy-back handling.

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

This also applies when the source is a slice such as `text[5]`. Use `formatex()` for the usual case where all source buffers are separate.

[AMXX / Pawn](https://www.amxmodx.org/api/string/format)

<a id="append-with-offset"></a>
## Build text with a write offset

Track how much text has already been written when building a small message in parts. Each call receives only the remaining capacity, not the capacity of the whole buffer.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[64], length;
	length += formatex( text[length], charsmax( text ) - length, "Player: %s", "Ada" );
	length += formatex( text[length], charsmax( text ) - length, "; score: %d", 7 );

	server_print( "%s", text );
	// Player: Ada; score: 7
}
```

In a loop, stop adding when no capacity remains. Never construct the offset from an unchecked external length, and do not read an earlier part of the same buffer through a `%s` argument here.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="caret-escaping"></a>
## Caret escaping in Pawn source

The usual AMXX Pawn source escape character is `^`: `^"` inserts a quotation mark, `^n` a newline and `^^` a caret. These are source escapes, not chat-color codes.

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

A string read from a file does not automatically turn its literal `^n` characters into a newline. Source parsing and runtime text processing are different operations.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Strings)

<a id="comparison-and-search"></a>
## String comparison and substring position

`equal()` checks equality, `equali()` ignores case, and `containi()` returns a substring position. Position zero means a match at the beginning, not failure.

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

Do not write `if( containi(...) )`: it rejects position 0 and accepts the failure value -1. For Unicode case-insensitive rules, check whether the chosen API supports the required characters.

[AMXX / Pawn](https://www.amxmodx.org/api/string/containi)

<a id="parse-fields"></a>
## `parse` for a known number of fields

`parse()` splits whitespace-separated fields while preserving quoted groups. Supply a separate buffer and maximum text length for every output field.

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

Check the returned field count before using the outputs. This is a command-style tokenizer, not a CSV parser; it does not implement CSV escaping rules.

[AMXX / Pawn](https://www.amxmodx.org/api/string/parse)

<a id="argbreak-and-argparse"></a>
## First argument plus the remaining text

`argbreak()` is useful when only the first argument has structure and the remainder must stay as text. It recognizes a quoted first argument; `argparse()` also allows repeated tokenization using an offset.

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

`-1` means no argument was found; an empty quoted argument is still an argument. Prefer these APIs over the deprecated `strbreak()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/argparse)

<a id="trim-and-remove-quotes"></a>
## Trim outer whitespace and quotes

Normalize an entire quoted value with `trim()` and `remove_quotes()`. This suits a single value; do not remove the quotes from a multi-argument line before tokenizing it.

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

The order matters here: `trim` first so the quotes become the actual outer characters. If the quote contents may have meaningful whitespace, do not `trim` again after removing the quotes.

[AMXX / Pawn](https://www.amxmodx.org/api/string/remove_quotes)

<a id="number-parsing"></a>
## Parse a whole numeric token

`str_to_num()` alone does not establish that the entire input is numeric. With `strtol()` the end position lets you reject a valid numeric prefix followed by junk.

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

This example accepts the native’s whitespace/sign rules and then checks the allowed range. If a format must contain digits only, validate that requirement separately; a numeric conversion is not a complete format validator.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strtol)

<a id="unicode-bytes"></a>
## UTF-8 bytes and case conversion

UTF-8 letters can occupy several bytes. `strlen()` measures bytes, not visible letters; capacity calculations must still reserve enough space for those bytes. Use the `mb_*` case functions for UTF-8 text.

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

Changing case can change byte length, so pass the actual writable capacity. Cutting at an arbitrary byte offset can split a UTF-8 sequence; byte length is also not a grapheme count.

[AMXX / Pawn](https://www.amxmodx.org/api/string/mb_strtoupper)

<a id="multilingual-messages"></a>
## Dictionary messages with `%L`

Register a dictionary once, then let `%L` resolve its key for a language target. This example uses the standard `common.txt` dictionary, where `YES` is already defined.

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

For one player, pass that player’s index instead of `LANG_SERVER`. `LANG_PLAYER` is intended for supported multi-recipient printing, not a language to preformat once into a shared buffer. The dictionary file must exist on the server.

[AMXX / Pawn](https://www.amxmodx.org/api/lang/register_dictionary)
