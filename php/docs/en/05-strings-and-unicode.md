# Strings and Unicode

[EN](../en/05-strings-and-unicode.md) | [UK](../uk/05-strings-and-unicode.md) | [RU](../ru/05-strings-and-unicode.md)

[Contents](../../README.md)

String APIs differ in the unit they operate on: bytes, code points or grapheme clusters. Pick that unit before choosing a search, split or length operation.

- [UTF-8: bytes, code points and grapheme clusters](#byte-codepoint-grapheme)
- [Read from the end with negative offsets](#negative-offsets)
- [Search for a substring, prefix or suffix](#string-search)
- [Split a multibyte string by code points](#mb-split)
- [Pad text by code-point length](#mb-pad)
- [Change the first Unicode character’s case](#mb-case)
- [Remove Unicode whitespace at the edges](#unicode-trim)
- [Split by visible character boundaries](#grapheme-split)
- [Increment an alphanumeric ASCII label explicitly](#string-increment)
- [Indent heredoc and nowdoc naturally](#heredoc)
- [Pass allowed tag names to `strip_tags()` as an array](#allowed-tags)
- [Format a list using the reader’s language](#intl-lists)
- [Determine a locale’s default writing direction](#locale-direction)
- [Compare Unicode text by grapheme edit distance](#grapheme-distance)

<a id="byte-codepoint-grapheme"></a>

## UTF-8: bytes, code points and grapheme clusters

**PHP 7.0+ | Core / mbstring / intl | Background** | [PHP](https://www.php.net/manual/en/migration70.new-features.php) | [PHP](https://www.php.net/manual/en/function.grapheme-strlen.php)

A PHP string stores bytes; `strlen()`, `mb_strlen()` and `grapheme_strlen()` count bytes, code points and grapheme clusters respectively in this UTF-8 example. Unicode escapes appeared in 7.0.

```php
$text = "e\u{0301}";
$bytes = strlen( $text ); # 3
$code_points = mb_strlen( $text, 'UTF-8' ); # 2
$graphemes = grapheme_strlen( $text ); # 1
```

Code points and grapheme clusters are different units, and neither guarantees terminal column width.

<a id="negative-offsets"></a>

## Read from the end with negative offsets

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Negative string offsets address bytes from the end.

```php
$code = 'INV-42';
$last = $code[-1]; # '2'
$previous = $code[-2]; # '4'
```

For UTF-8 text, choose `mb_substr()` for code points or `grapheme_substr()` for grapheme clusters.

<a id="string-search"></a>

## Search for a substring, prefix or suffix

**PHP 8.0+ | Core** | [PHP](https://www.php.net/manual/en/function.str-contains.php) | [PHP](https://www.php.net/manual/en/function.str-starts-with.php) | [PHP](https://www.php.net/manual/en/function.str-ends-with.php)

`str_contains()`, `str_starts_with()` and `str_ends_with()` return booleans instead of positions.

```php
$path = '/api/users.php';
$contains = str_contains( $path, 'users' ); # true
$starts = str_starts_with( $path, '/api/' ); # true
$ends = str_ends_with( $path, '.php' ); # true
```

Searches are case-sensitive and byte-based; an empty needle matches all three.

<a id="mb-split"></a>

## Split a multibyte string by code points

**PHP 7.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-split.php)

`mb_str_split()` splits by characters of the selected encoding rather than bytes.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' ); # ['Ки', 'їв']
```

Combining marks may separate from their base; `grapheme_str_split()` preserves grapheme clusters from 8.4.

<a id="mb-pad"></a>

## Pad text by code-point length

**PHP 8.3+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-pad.php)

`mb_str_pad()` pads by code-point length.

```php
$right = mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ); # Київ..
$left = mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ); # ...PHP
```

It does not truncate longer input or measure the displayed width of emoji and other text.

<a id="mb-case"></a>

## Change the first Unicode character’s case

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-ucfirst.php) | [PHP](https://www.php.net/manual/en/function.mb-lcfirst.php)

`mb_ucfirst()` title-cases the first character; `mb_lcfirst()` lowercases it, retaining the rest.

```php
$upper = mb_ucfirst( 'київ', 'UTF-8' ); # Київ
$lower = mb_lcfirst( 'Київ', 'UTF-8' ); # київ
```

Unicode case mapping may change the number of code points; this is not locale-specific title capitalization.

<a id="unicode-trim"></a>

## Remove Unicode whitespace at the edges

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-trim.php)

`mb_trim()`, `mb_ltrim()` and `mb_rtrim()` remove multibyte edge characters with a broader default whitespace set than `trim()`.

```php
$text = "\u{00A0}PHP\u{3000}";
$trimmed = mb_trim( $text, null, 'UTF-8' ); # PHP
```

A custom character list replaces the defaults and does not support `trim()` range notation.

<a id="grapheme-split"></a>

## Split by visible character boundaries

**PHP 8.4+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-str-split.php)

`grapheme_str_split()` splits UTF-8 at grapheme boundaries, keeping combining marks with their base.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text ); # ["e\u{0301}", 'x']
```

Invalid UTF-8 or another failure can return `false`; chunk size must be at least 1.

<a id="string-increment"></a>

## Increment an alphanumeric ASCII label explicitly

**PHP 8.3+ | Core** | [PHP](https://www.php.net/manual/en/function.str-increment.php) | [PHP](https://www.php.net/manual/en/function.str-decrement.php)

`str_increment()` and `str_decrement()` advance alphanumeric ASCII labels explicitly.

```php
$next = str_increment( 'INV0099' ); # INV0100
$previous = str_decrement( 'INV0100' ); # INV0099
$carry = str_increment( 'ZZ' ); # AAA
```

Empty strings, punctuation and Unicode letters cause `ValueError`.

<a id="heredoc"></a>

## Indent heredoc and nowdoc naturally

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

Indented heredoc and nowdoc closing markers strip that indentation from the body.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;
```

Heredoc interpolates variables; nowdoc uses a quoted opening marker and stays literal; indentation cannot mix tabs and spaces.

<a id="allowed-tags"></a>

## Pass allowed tag names to `strip_tags()` as an array

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/function.strip-tags.php)

`strip_tags()` accepts an array of allowed tag names from 7.4.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
$text = strip_tags( $html, ['p'] ); # <p class="note">Hello</p>bad()
```

It is not a sanitizer: attributes on allowed tags survive, and script contents may remain as text.

<a id="intl-lists"></a>

## Format a list using the reader’s language

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/class.intllistformatter.php)

`IntlListFormatter` joins lists with locale-specific conjunctions; `TYPE_OR` and `TYPE_UNITS` select other styles.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] ); # PHP and HTML
```

Requires ICU 67+; output depends on locale data, and formatting may return `false`.

<a id="locale-direction"></a>

## Determine a locale’s default writing direction

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/locale.isrighttoleft.php)

`Locale::isRightToLeft()` reports the default writing direction of a locale’s dominant script.

```php
$english_rtl = Locale::isRightToLeft( 'en' ); # false
$arabic_rtl = Locale::isRightToLeft( 'ar' ); # true
```

It describes the locale, not the direction of arbitrary text or mixed-direction fragments.

<a id="grapheme-distance"></a>

## Compare Unicode text by grapheme edit distance

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-levenshtein.php)

`grapheme_levenshtein()` measures edit distance in grapheme clusters and recognizes canonical equivalence.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
$distance = grapheme_levenshtein( $composed, $decomposed ); # 0
```

Invalid UTF-8 or ICU errors can return `false`; test `=== false` before using the distance.
