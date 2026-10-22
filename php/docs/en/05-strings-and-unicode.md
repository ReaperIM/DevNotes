# Strings and Unicode

[English](../en/05-strings-and-unicode.md) | [Українська](../uk/05-strings-and-unicode.md) | [Русский](../ru/05-strings-and-unicode.md)

[Contents](../../README.md)

Choose a byte operation for binary or ASCII data and a Unicode-aware operation for human text. The distinction matters more than the length of the function name. Examples containing mb_ require mbstring; grapheme_ and Locale examples require intl.

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

**PHP 7.0+ | Core / mbstring / intl | Background**

A PHP string stores bytes. Different operations can count bytes, Unicode code points, or user-perceived characters called grapheme clusters. Unicode escapes in double-quoted strings were added in PHP 7.0 and make invisible combining characters explicit.

```php
$text = "e\u{0301}"; # e + combining acute accent
echo strlen( $text ) . PHP_EOL; # 3 bytes
echo mb_strlen( $text, 'UTF-8' ) . PHP_EOL; # 2 code points
echo grapheme_strlen( $text ) . PHP_EOL; # 1 grapheme cluster
```

**Remember:** Choose the unit before cutting, padding or validating length. Grapheme clusters still do not equal terminal column width. mbstring and intl must be installed and enabled for their respective calls.

[PHP manual: Unicode escapes](https://www.php.net/manual/en/migration70.new-features.php) | [PHP manual: grapheme_strlen()](https://www.php.net/manual/en/function.grapheme-strlen.php)

<a id="negative-offsets"></a>

## Read from the end with negative offsets

**PHP 7.1+ | Core**

Negative string offsets count back from the end. For ASCII tokens this is a clear way to read a suffix character without calculating `strlen()` - 1.

```php
$code = 'INV-42';
echo $code[-1] . PHP_EOL; # 2
echo $code[-2] . PHP_EOL; # 4
```

**Remember:** Offsets still address bytes. For UTF-8 text use `mb_substr()` or `grapheme_substr()`, depending on the required unit. An out-of-range offset is not an empty-field test.

[PHP manual: negative offsets](https://www.php.net/manual/en/migration71.new-features.php)

<a id="string-search"></a>

## Search for a substring, prefix or suffix

**PHP 8.0+ | Core**

`str_contains()`, `str_starts_with()` and `str_ends_with()` return a boolean for their exact question. They avoid mixing a position such as 0 with the absence marker false returned by `strpos()`.

```php
$path = '/api/users.php';
var_dump( str_contains($path, 'users') ); # bool(true)
var_dump( str_starts_with($path, '/api/') ); # bool(true)
var_dump( str_ends_with($path, '.php') ); # bool(true)
var_dump( str_contains($path, '') ); # bool(true)
```

**Remember:** These searches are case-sensitive and byte-based. An empty needle matches. Reject an empty search term separately if your application considers it invalid; a substring check is not URL or path validation.

[PHP manual: str_contains()](https://www.php.net/manual/en/function.str-contains.php) | [PHP manual: str_starts_with()](https://www.php.net/manual/en/function.str-starts-with.php) | [PHP manual: str_ends_with()](https://www.php.net/manual/en/function.str-ends-with.php)

<a id="mb-split"></a>

## Split a multibyte string by code points

**PHP 7.4+ | mbstring**

`mb_str_split()` creates chunks measured in characters of the selected encoding. It replaces byte-based `str_split()` when processing UTF-8 letters.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' );
echo json_encode( $parts, JSON_UNESCAPED_UNICODE ) . PHP_EOL; # ["Ки","їв"]
```

**Remember:** A combining accent may be separated from its base letter. For user-visible character boundaries use `grapheme_str_split()` from PHP 8.4. Pass an explicit encoding for predictable text processing.

[PHP manual: mb_str_split()](https://www.php.net/manual/en/function.mb-str-split.php)

<a id="mb-pad"></a>

## Pad text by code-point length

**PHP 8.3+ | mbstring**

`mb_str_pad()` uses code-point length rather than byte length. A Cyrillic word and an ASCII word can therefore receive comparable character-based padding.

```php
echo mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ) . PHP_EOL; # Київ..
echo mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ) . PHP_EOL; # ...PHP
```

**Remember:** Padding does not truncate a longer input. Unicode code points are not visual columns, so this alone cannot align every terminal table or emoji-containing label.

[PHP manual: mb_str_pad()](https://www.php.net/manual/en/function.mb-str-pad.php)

<a id="mb-case"></a>

## Change the first Unicode character’s case

**PHP 8.4+ | mbstring**

`mb_ucfirst()` title-cases the first character; `mb_lcfirst()` lowercases it. The remaining text is retained. This removes a small but error-prone combination of substring and case-conversion calls.

```php
echo mb_ucfirst( 'київ', 'UTF-8' ) . PHP_EOL; # Київ
echo mb_lcfirst( 'Київ', 'UTF-8' ) . PHP_EOL; # київ
```

**Remember:** This is not title capitalization for a whole sentence or a locale-specific naming policy. Unicode case mapping can change the number of code points.

[PHP manual: mb_ucfirst()](https://www.php.net/manual/en/function.mb-ucfirst.php) | [PHP manual: mb_lcfirst()](https://www.php.net/manual/en/function.mb-lcfirst.php)

<a id="unicode-trim"></a>

## Remove Unicode whitespace at the edges

**PHP 8.4+ | mbstring**

`mb_trim()`, `mb_ltrim()` and `mb_rtrim()` understand multibyte characters and a broader default whitespace set than `trim()`. They are useful for text pasted from documents and web pages.

```php
$text = "\u{00A0}PHP\u{3000}";
echo mb_trim( $text, null, 'UTF-8' ) . PHP_EOL; # PHP
```

**Remember:** Trimming affects only the selected edges. It neither removes internal whitespace nor validates text. A supplied character list replaces the default set; the byte-oriented `trim()` range notation is not supported.

[PHP manual: mb_trim()](https://www.php.net/manual/en/function.mb-trim.php)

<a id="grapheme-split"></a>

## Split by visible character boundaries

**PHP 8.4+ | intl**

`grapheme_str_split()` keeps a base character together with its combining marks. Use it when splitting text into units that a person is likely to perceive as one character.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text );
echo count( $parts ) . PHP_EOL; # 2
echo bin2hex( $parts[0] ) . PHP_EOL; # 65cc81
```

**Remember:** Input must be valid UTF-8. The result can be false on failure, so check it before using array operations on untrusted input. The chunk size must be at least 1.

[PHP manual: grapheme_str_split()](https://www.php.net/manual/en/function.grapheme-str-split.php)

<a id="string-increment"></a>

## Increment an alphanumeric ASCII label explicitly

**PHP 8.3+ | Core**

`str_increment()` and `str_decrement()` make the intention explicit when advancing ASCII labels. They avoid the historical, type-dependent behavior of ++ on a string variable.

```php
echo str_increment( 'INV0099' ) . PHP_EOL; # INV0100
echo str_decrement( 'INV0100' ) . PHP_EOL; # INV0099
echo str_increment( 'ZZ' ) . PHP_EOL; # AAA
```

**Remember:** Only non-empty alphanumeric ASCII strings are accepted. Punctuation and Unicode letters cause `ValueError`. This is label arithmetic, not a concurrent invoice-number allocator.

[PHP manual: str_increment()](https://www.php.net/manual/en/function.str-increment.php) | [PHP manual: str_decrement()](https://www.php.net/manual/en/function.str-decrement.php)

<a id="heredoc"></a>

## Indent heredoc and nowdoc naturally

**PHP 7.3+ | Core**

The closing heredoc or nowdoc marker can be indented from PHP 7.3. That indentation is stripped from the body. Multiline text can then follow the surrounding code layout.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;

echo $message . PHP_EOL;
# Hello, Ada.
# PHP notes
```

**Remember:** Heredoc interpolates variables; nowdoc uses a quoted opening marker and keeps text literal. Do not mix tabs and spaces in indentation. Interpolation does not escape HTML, SQL or shell arguments.

[PHP manual: heredoc](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

<a id="allowed-tags"></a>

## Pass allowed tag names to `strip_tags()` as an array

**PHP 7.4+ | Core**

`strip_tags()` accepts an array of allowed tag names. A small explicit list is easier to read than an angle-bracket string assembled elsewhere.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
echo strip_tags( $html, ['p'] ) . PHP_EOL;
# <p class="note">Hello</p>bad()
```

**Remember:** This is not an HTML sanitizer: attributes on allowed tags are retained and script contents can remain as text. For plain-text HTML output use context-appropriate escaping; rich untrusted HTML needs an actual sanitizer.

[PHP manual: strip_tags()](https://www.php.net/manual/en/function.strip-tags.php)

<a id="intl-lists"></a>

## Format a list using the reader’s language

**PHP 8.5+ | intl**

`IntlListFormatter` adds locale-appropriate punctuation and conjunctions. It is useful for a sentence listing selected formats, names or capabilities; `implode()` alone does not know how to join the last item.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] );
echo $text . PHP_EOL; # PHP and HTML
```

**Remember:** Requires ICU 67 or later. TYPE_OR and TYPE_UNITS express other list styles. Output details depend on locale data; check false on formatting failure before using the result.

[PHP manual: IntlListFormatter](https://www.php.net/manual/en/class.intllistformatter.php)

<a id="locale-direction"></a>

## Determine a locale’s default writing direction

**PHP 8.5+ | intl**

`Locale::isRightToLeft()` asks ICU whether a locale’s dominant script is right-to-left. It replaces a hand-maintained list of language codes for choosing a page direction.

```php
var_dump( Locale::isRightToLeft('en') ); # bool(false)
var_dump( Locale::isRightToLeft('ar') ); # bool(true)
```

**Remember:** This describes a locale, not arbitrary user-entered text. A page can still contain mixed-direction fragments that need their own markup.

[PHP manual: Locale::isRightToLeft()](https://www.php.net/manual/en/locale.isrighttoleft.php)

<a id="grapheme-distance"></a>

## Compare Unicode text by grapheme edit distance

**PHP 8.5+ | intl**

`grapheme_levenshtein()` measures insertion, deletion and replacement distance in grapheme clusters. Canonically equivalent composed and decomposed characters can compare alike instead of being counted as unrelated bytes.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
var_dump( grapheme_levenshtein($composed, $decomposed) ); # int(0)
```

**Remember:** A distance is a similarity hint, not identity or authorization. Invalid UTF-8 and ICU errors can return false; test === false before treating the result as a number.

[PHP manual: grapheme_levenshtein()](https://www.php.net/manual/en/function.grapheme-levenshtein.php)
