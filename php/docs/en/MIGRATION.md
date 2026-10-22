# Migration notes

[EN](../en/MIGRATION.md) | [UK](../uk/MIGRATION.md) | [RU](../ru/MIGRATION.md)

[Contents](../../README.md) | [PHP 7.0-8.5](VERSIONS.md)

<a id="approach"></a>
## Order of changes

First check the changes that can alter existing behavior or break a call. Modern syntax can be adopted separately, once the old paths work on the target version.

<a id="removed-php8"></a>
## Removed in PHP 8.0

- `create_function()` -> closures; `each()` -> `foreach`. Both deprecated in 7.2.
- Curly-brace offsets -> square brackets. Deprecated in 7.4.
- `__autoload()` -> `spl_autoload_register()`; class-name constructors -> `__construct()`.
- Nested full ternaries need explicit parentheses.
- `implode($separator, $parts)`; reversed argument order removed.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="comparisons"></a>
## Comparisons

PHP 8: `0 == 'foo'` changed from `true` to `false`. Numeric strings retain separate comparison rules.

`switch` -> [match](01-syntax-and-functions.md#match) changes loose comparison to strict comparison; no fall-through, no match/`default` -> `UnhandledMatchError`.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php#string-number-comparison)

<a id="arguments-errors"></a>
## Arguments and exceptions

- PHP 8: string keys in `call_user_func_array()` become parameter names.
- Invalid internal-function arguments can throw `TypeError`/`ValueError`. `catch(Exception)` does not catch `Error`; `Throwable` covers both.
- PDO defaults to exceptions from 8.0; mysqli to `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT` from 8.1. [DB notes](10-databases.md).

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="nullable"></a>
## Nullable parameters

- 8.1: passing `null` to non-nullable built-in scalar parameters is deprecated in coercive mode.
- 8.4: `string $name = null` is deprecated; use `?string $name = null` or `string|null` (8.0+). [Types](02-types.md#nullable).

[PHP](https://www.php.net/manual/en/migration84.deprecated.php)

<a id="object-state"></a>
## Object state

- 8.2: [dynamic properties](03-classes-and-objects.md#dynamic-properties) deprecated, with `stdClass` / __set / `AllowDynamicProperties` exceptions.
- 8.1: `Serializable` without `__serialize()`/`__unserialize()` deprecated. The new methods exist from 7.4; `__sleep()`/`__wakeup()` are soft-deprecated in 8.5 (no new runtime diagnostic).
- PHP 8: cURL/GD handles are objects; old `is_resource()` checks can reject a valid handle.
- 8.2: old dollar-brace interpolation deprecated; use `$name` or `{$name}`.

[PHP](https://www.php.net/manual/en/migration82.deprecated.php)

<a id="php85"></a>
## PHP 8.5

- `(boolean)`/`(integer)`/`(double)`/`(binary)` deprecated -> `(bool)`/`(int)`/`(float)`/`(string)`.
- Backtick shell operator deprecated -> an explicit process API.
- Using `null` as an array offset or an `array_key_exists()` key is deprecated; use an empty string if that is the intended key. Incrementing nonnumeric strings is deprecated; use `str_increment()`.
- `curl_close()`, `curl_share_close()`, `finfo_close()`, `imagedestroy()` deprecated: object lifetime handles cleanup.
- Destructuring a scalar with `[]` or `list()` now warns instead of silently producing `null` values. A `null` source remains allowed; supported `ArrayAccess` objects still work.

[PHP: deprecated](https://www.php.net/manual/en/migration85.deprecated.php) | [PHP: compatibility](https://www.php.net/manual/en/migration85.incompatible.php)

<a id="rewrites"></a>
## Useful replacements

- [`??` / `??=`](01-syntax-and-functions.md#null-coalescing): missing/`null`; [`fn`](01-syntax-and-functions.md#arrow-functions): one expression, capture by value.
- [`str_contains()`](05-strings-and-unicode.md#string-search): boolean search; [`array_find()`](04-arrays-and-iteration.md#predicate-search): first match.
- [`json_validate()`](06-json-xml-and-serialization.md#json-validation): validity only; decode once when the values are needed.
- [`readonly`](03-classes-and-objects.md#readonly-properties): shallow protection; [hooks](03-classes-and-objects.md#property-hooks): property access; [`clone()`](03-classes-and-objects.md#clone-with): changed copy.

<a id="verification"></a>
## Before release

Check changed failure paths and meaningful `0`/`null`/`false` cases on the target PHP version.
