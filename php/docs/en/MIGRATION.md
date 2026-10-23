# Migration notes

[EN](../en/MIGRATION.md) | [UK](../uk/MIGRATION.md) | [RU](../ru/MIGRATION.md)

[Contents](../../README.md) | [PHP 7.0-8.5](VERSIONS.md)

<a id="approach"></a>
## Where to start an upgrade

When changing PHP versions, first look for places where the old code could stop working or give a different result. Add newer syntax later, once the usual scenarios have been checked on the target version. That makes it easier to tell where an error came from.

<a id="removed-php8"></a>
## What no longer works in PHP 8.0

- Replace `create_function()` with a closure and `each()` with `foreach`. Both functions were deprecated back in 7.2, then removed in 8.0.
- Replace curly-brace offsets with square brackets. The old spelling was deprecated in 7.4.
- Register the autoloader with `spl_autoload_register()` in place of `__autoload()`. Move a constructor named after its class into `__construct()`.
- Put explicit parentheses around nested full ternary expressions so it is clear which parts belong together.
- With `implode( $separator, $parts )`, the separator comes first and the array second. The reversed argument order is no longer supported.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="comparisons"></a>
## Where a comparison gives a different result

In PHP 7, `0 == 'foo'` gave `true`; in PHP 8, it gives `false`. Look separately at code comparing strings and numbers with `==`. Strings containing numbers have their own rules, so this example does not describe every string comparison.

When replacing `switch` with [`match`](01-syntax-and-functions.md#match), check the behavior too. `match` compares strictly, does not fall through to the next branch and throws `UnhandledMatchError` when nothing matches and there is no `default`.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php#string-number-comparison)

<a id="arguments-errors"></a>
## Arguments and error handling

- From PHP 8, string keys in `call_user_func_array()` are treated as parameter names. Check calls where those keys used to be arbitrary labels.
- A built-in function can respond to an unsuitable argument by throwing `TypeError` or `ValueError`. A `catch( Exception )` handler will not catch the `Error` family; `Throwable` covers both families.
- PDO throws exceptions by default from 8.0. mysqli enables `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT` from 8.1. Revisit query error handling if the old code only expected `false`. More in the [database notes](10-databases.md).

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="nullable"></a>
## `null` in parameters

- From 8.1, passing `null` to a built-in scalar parameter that does not accept it is deprecated in coercive mode. Check where the `null` came from and what value the function should actually receive.
- From 8.4, `string $name = null` is deprecated. If `null` is needed, say so explicitly: `?string $name = null`, or `string|null $name = null` on PHP 8.0+. More about [types](02-types.md#nullable).

[PHP](https://www.php.net/manual/en/migration84.deprecated.php)

<a id="object-state"></a>
## Properties and saving objects

- From 8.2, [dynamic properties](03-classes-and-objects.md#dynamic-properties) are deprecated. If an assignment creates a property, check the class declaration. Exceptions include `stdClass`, handling through `__set()` and permission through `AllowDynamicProperties`.
- From 8.1, implementing `Serializable` without `__serialize()`/`__unserialize()` is deprecated. Those newer methods have been available since 7.4. In 8.5, `__sleep()`/`__wakeup()` are soft-deprecated: replacing them is encouraged, but there is no new runtime deprecation message.
- In PHP 8, cURL and GD return objects in place of the old resources. An old `is_resource()` check can therefore reject a perfectly usable object.
- From 8.2, string interpolation with the dollar sign before the curly braces is deprecated. Use `$name` or `{$name}` to insert the value.

[PHP](https://www.php.net/manual/en/migration82.deprecated.php)

<a id="php85"></a>
## Other places to check for PHP 8.5

- The `(boolean)`, `(integer)`, `(double)` and `(binary)` casts are deprecated. Use `(bool)`, `(int)`, `(float)` and `(string)` respectively.
- Running a shell command through backticks is deprecated. Choose an explicit process function for these calls.
- `null` as an array key or an `array_key_exists()` key is now deprecated. If an empty string key was intended, pass `''` explicitly. For incrementing a nonnumeric string, use `str_increment()` in place of `++`.
- `curl_close()`, `curl_share_close()`, `finfo_close()` and `imagedestroy()` are deprecated: cleanup now follows the object's lifetime.
- Destructuring a scalar with `[]` or `list()` now warns where it previously filled the variables with `null` silently. A `null` source itself is still allowed, and supported `ArrayAccess` objects still work.

[PHP: deprecated](https://www.php.net/manual/en/migration85.deprecated.php) | [PHP: compatibility](https://www.php.net/manual/en/migration85.incompatible.php)

<a id="rewrites"></a>
## Things that can be easier to write

- [`??` / `??=`](01-syntax-and-functions.md#null-coalescing) supply a fallback when a key is missing or contains `null`. [`fn`](01-syntax-and-functions.md#arrow-functions) is handy for one expression and captures outside values by value.
- [`str_contains()`](05-strings-and-unicode.md#string-search) answers whether a substring is present. [`array_find()`](04-arrays-and-iteration.md#predicate-search) returns the first value that passes our check.
- [`json_validate()`](06-json-xml-and-serialization.md#json-validation) is useful when checking JSON is all we need. If the data will be read next, decode it once with error handling.
- [`readonly`](03-classes-and-objects.md#readonly-properties) prevents a new assignment to a property, but an object stored inside can still change. [Property hooks](03-classes-and-objects.md#property-hooks) let us set behavior for reads and writes, while [`clone()`](03-classes-and-objects.md#clone-with) creates a copy with selected changes.

<a id="verification"></a>
## Before release

Check the usual scenario and the error case on the target PHP version. Look separately at `0`, `null` and `false` when they mean different things in the code: those are easy to mix up accidentally during an upgrade.
