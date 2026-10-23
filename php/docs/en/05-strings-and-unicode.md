# Strings and Unicode

[EN](../en/05-strings-and-unicode.md) | [UK](../uk/05-strings-and-unicode.md) | [RU](../ru/05-strings-and-unicode.md)

[Contents](../../README.md)

With strings, first decide whether you need bytes, code points or whole visible characters. That choice affects which function to use for length, searching and splitting.

- [Why one character can have three lengths](#byte-codepoint-grapheme)
- [Read a string from the end](#negative-offsets)
- [Check the contents, start or end of a string](#string-search)
- [Split text without cutting through a letter’s bytes](#mb-split)
- [Pad a string to the required length](#mb-pad)
- [Change only the first character’s case](#mb-case)
- [Trim Unicode whitespace at the edges](#unicode-trim)
- [Keep the accent with its letter](#grapheme-split)
- [Get the next alphanumeric label](#string-increment)
- [Keep multiline text neatly indented](#heredoc)
- [Pass allowed tags as an array](#allowed-tags)
- [Format a list for the reader’s language](#intl-lists)
- [Find a locale’s writing direction](#locale-direction)
- [Compare text using whole visible characters](#grapheme-distance)

<a id="byte-codepoint-grapheme"></a>
## Why one character can have three lengths

**PHP 7.0+ | Core / mbstring / intl | Background** | [PHP](https://www.php.net/manual/en/migration70.new-features.php) | [PHP](https://www.php.net/manual/en/function.grapheme-strlen.php)

PHP stores a string as bytes, but one visible character is not always one byte or even one Unicode code point. Here `e` and a separate accent combine into one visible character. `strlen()` counts `3` bytes, `mb_strlen()` counts two code points and `grapheme_strlen()` counts one grapheme cluster: the visible character as a whole.

```php
$text = "e\u{0301}";
$bytes = strlen( $text ); # 3
$code_points = mb_strlen( $text, 'UTF-8' ); # 2
$graphemes = grapheme_strlen( $text ); # 1
```

The Unicode escape notation used here is available from PHP 7.0. Choose the unit you need before searching or splitting text. Even a grapheme count does not tell you how many terminal columns it occupies.

<a id="negative-offsets"></a>
## Read a string from the end

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

A negative offset counts backwards: `-1` takes the last byte and `-2` the one before it. For short ASCII codes such as `INV-42`, that is convenient and predictable.

```php
$code = 'INV-42';
$last = $code[-1]; # '2'
$previous = $code[-2]; # '4'
```

A UTF-8 letter may occupy several bytes, so this can pick only part of it. Use `mb_substr()` for code points or `grapheme_substr()` for whole visible characters.

<a id="string-search"></a>
## Check the contents, start or end of a string

**PHP 8.0+ | Core** | [PHP](https://www.php.net/manual/en/function.str-contains.php) | [PHP](https://www.php.net/manual/en/function.str-starts-with.php) | [PHP](https://www.php.net/manual/en/function.str-ends-with.php)

When all you need is a yes or no, there is no need to find a position. `str_contains()` checks for a substring, `str_starts_with()` checks the beginning and `str_ends_with()` checks the end. All three return `true` or `false` directly.

```php
$path = '/api/users.php';
$contains = str_contains( $path, 'users' ); # true
$starts = str_starts_with( $path, '/api/' ); # true
$ends = str_ends_with( $path, '.php' ); # true
```

The search is case-sensitive and works on bytes. An empty search string matches in all three functions, so check empty input separately if that should not count.

<a id="mb-split"></a>
## Split text without cutting through a letter’s bytes

**PHP 7.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-split.php)

`mb_str_split()` splits by characters in the chosen encoding. Here we ask for chunks of two UTF-8 characters and get `Ки` and `їв` rather than pieces of individual bytes.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' ); # ['Ки', 'їв']
```

For Unicode, it counts code points. A separate combining accent can still be split from its letter. To keep those combinations together, PHP 8.4 adds `grapheme_str_split()`.

<a id="mb-pad"></a>
## Pad a string to the required length

**PHP 8.3+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-pad.php)

`mb_str_pad()` adds characters on the left or right until the string reaches the requested length. For UTF-8 it counts code points, so `Київ` counts as four and needs two dots to reach six.

```php
$right = mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ); # Київ..
$left = mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ); # ...PHP
```

It does not cut down a longer string. It is not an exact ruler for screen alignment either: emoji and other text can have a displayed width different from their code-point count.

<a id="mb-case"></a>
## Change only the first character’s case

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-ucfirst.php) | [PHP](https://www.php.net/manual/en/function.mb-lcfirst.php)

`mb_ucfirst()` applies Unicode title case to the first character, while `mb_lcfirst()` lowercases it. The rest of the string stays as it was. The change from `київ` to `Київ`, or back again, is visible in the example.

```php
$upper = mb_ucfirst( 'київ', 'UTF-8' ); # Київ
$lower = mb_lcfirst( 'Київ', 'UTF-8' ); # київ
```

This handles the first character, not title capitalization rules for a particular language. Unicode case conversion can sometimes change the number of code points too.

<a id="unicode-trim"></a>
## Trim Unicode whitespace at the edges

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-trim.php)

Regular `trim()` does not cover every Unicode whitespace character. `mb_trim()`, `mb_ltrim()` and `mb_rtrim()` use a broader default set. Here they remove the non-breaking space on the left and the wide space on the right.

```php
$text = "\u{00A0}PHP\u{3000}";
$trimmed = mb_trim( $text, null, 'UTF-8' ); # PHP
```

A custom character list replaces the default set. It is a list of characters, so it does not support the range notation used by `trim()`.

<a id="grapheme-split"></a>
## Keep the accent with its letter

**PHP 8.4+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-str-split.php)

When one visible character consists of a letter and a separate combining mark, splitting should often keep them together. `grapheme_str_split()` splits UTF-8 by grapheme clusters, so `e` and its accent stay in one chunk and `x` goes in the next.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text ); # ["e\u{0301}", 'x']
```

Chunk size must be at least `1`. Invalid UTF-8 or another error can return `false`, so do not immediately assume the result is an array.

<a id="string-increment"></a>
## Get the next alphanumeric label

**PHP 8.3+ | Core** | [PHP](https://www.php.net/manual/en/function.str-increment.php) | [PHP](https://www.php.net/manual/en/function.str-decrement.php)

For ASCII labels such as document numbers, use the explicit `str_increment()` and `str_decrement()` functions. They move a label forwards or backwards using alphanumeric rules: `INV0099` becomes `INV0100`, while `ZZ` becomes `AAA`.

```php
$next = str_increment( 'INV0099' ); # INV0100
$previous = str_decrement( 'INV0100' ); # INV0099
$carry = str_increment( 'ZZ' ); # AAA
```

Only non-empty strings of ASCII letters and digits are accepted. Punctuation, Unicode letters and an empty string cause `ValueError`.

<a id="heredoc"></a>
## Keep multiline text neatly indented

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

A heredoc or nowdoc closing marker no longer has to sit at the left edge. Its indentation is removed from the body, so the text can fit neatly into the surrounding code without adding those tabs to the result.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;
```

Heredoc substitutes variables, so `$name` becomes part of the greeting. Nowdoc uses a quoted opening marker and keeps the text literal. Do not mix tabs and spaces in the indentation.

<a id="allowed-tags"></a>
## Pass allowed tags as an array

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/function.strip-tags.php)

`strip_tags()` accepts an array of tag names to keep. Here it preserves `p` and removes the `script` tag itself.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
$text = strip_tags( $html, ['p'] ); # <p class="note">Hello</p>bad()
```

Notice the result: the `class` attribute remains, and `bad()` becomes plain text. Removing tags does not make arbitrary HTML safe. Attributes on allowed tags survive too, so this is not a complete HTML sanitizer.

<a id="intl-lists"></a>
## Format a list for the reader’s language

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/class.intllistformatter.php)

A list in running text sometimes needs more than `implode()`: the final item needs the right conjunction for the language. `IntlListFormatter` handles that. The English locale and `TYPE_AND` give `PHP and HTML` here.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] ); # PHP and HTML
```

`TYPE_OR` and `TYPE_UNITS` provide other joining styles. ICU 67 or newer is required, and exact output depends on its locale data. A formatting failure can return `false`.

<a id="locale-direction"></a>
## Find a locale’s writing direction

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/locale.isrighttoleft.php)

`Locale::isRightToLeft()` tells us whether a locale’s dominant script normally runs right to left. English `en` gives `false` and Arabic `ar` gives `true`.

```php
$english_rtl = Locale::isRightToLeft( 'en' ); # false
$arabic_rtl = Locale::isRightToLeft( 'ar' ); # true
```

This describes the locale; it does not analyze text. It cannot tell you the direction of an arbitrary string or the individual parts of mixed-direction text.

<a id="grapheme-distance"></a>
## Compare text using whole visible characters

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-levenshtein.php)

`grapheme_levenshtein()` measures edit distance in grapheme clusters rather than individual bytes. It also recognizes equivalent Unicode forms: the precomposed accented letter and the letter with a separate accent here count as the same, giving a distance of `0`.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
$distance = grapheme_levenshtein( $composed, $decomposed ); # 0
```

Invalid UTF-8 or an ICU error can return `false`. Check specifically for `=== false`; zero is a valid result for matching strings.
