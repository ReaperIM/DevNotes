# Updating existing PHP code

[English](../en/MIGRATION.md) | [Українська](../uk/MIGRATION.md) | [Русский](../ru/MIGRATION.md)

[Contents](../../README.md) | [PHP 7.0-8.5](VERSIONS.md)

- [Separate compatibility from modernization](#approach)
- [Before moving from PHP 7 to PHP 8](#removed-php8)
- [Comparisons, switch and match](#comparisons)
- [Arguments and errors became more explicit](#arguments-errors)
- [Make nullability intentional](#nullable)
- [Properties, serialization and resource objects](#object-state)
- [PHP 8.5: small changes worth checking](#php85)

<a id="approach"></a>
## Separate compatibility from modernization

First make the application behave correctly on the target interpreter. Then introduce new syntax in small, reviewable changes. Changing the PHP version and rewriting a large amount of logic at the same time makes a regression harder to locate.

Record the current results for important inputs: missing fields, null, false, 0, '0', an empty string, an empty array and malformed data. These values are not interchangeable. A syntax upgrade should preserve their meaning unless you deliberately change the contract.

<a id="removed-php8"></a>
## Before moving from PHP 7 to PHP 8

Check these common leftovers:

- create_function() and each() were deprecated in 7.2 and removed in 8.0. Use a closure and foreach respectively. A foreach rewrite must still account for code that intentionally managed an array's internal pointer.
- String and array offsets written with curly braces were deprecated in 7.4 and removed in 8.0. Use square brackets.
- Replace the old __autoload() mechanism with spl_autoload_register().
- Add explicit parentheses to nested ternaries. Unparenthesized nested full ternaries were deprecated in 7.4 and are not accepted in 8.0.
- Use implode($separator, $parts); the reversed historical parameter order no longer works in 8.0.
- Methods named after a class are not constructors in PHP 8. Use __construct().

[PHP 7.2 deprecations](https://www.php.net/manual/en/migration72.deprecated.php), [PHP 7.4 deprecations](https://www.php.net/manual/en/migration74.deprecated.php), [PHP 8.0 incompatibilities](https://www.php.net/manual/en/migration80.incompatible.php).

<a id="comparisons"></a>
## Comparisons, switch and match

PHP 8 changed loose number-to-nonnumeric-string comparisons. For example, the old expression 0 == 'foo' changed from true to false. Numeric strings have separate comparison rules. When comparing concrete application values, prefer === and !== after intentional normalization.

A switch-to-match rewrite also changes comparison semantics: switch uses loose comparison, match uses strict comparison. match has no fall-through and throws UnhandledMatchError when no arm matches and no default exists. Verify integer database fields versus strings from HTTP before changing the construct.

[PHP 8 comparison changes](https://www.php.net/manual/en/migration80.incompatible.php#string-number-comparison), [match with examples](01-syntax-and-functions.md#match).

<a id="arguments-errors"></a>
## Arguments and errors became more explicit

In PHP 8, string keys passed through call_user_func_array() become parameter names. Code that previously ignored those keys can now call different parameters or fail. Use a deliberately positional list when positional dispatch is intended.

Many invalid internal-function arguments now throw TypeError or ValueError instead of only issuing a warning and returning a fallback. A catch(Exception) block does not catch Error; catch Throwable only where both categories can be handled meaningfully. Do not swallow an error and return success.

PDO defaults to exception mode from 8.0; mysqli defaults to error-and-strict reporting from 8.1. Review failure paths, rollback and the response sent to the caller. [Database examples](10-databases.md), [PHP 8.0 migration](https://www.php.net/manual/en/migration80.incompatible.php).

<a id="nullable"></a>
## Make nullability intentional

Passing null to a non-nullable scalar parameter of a built-in function is deprecated from 8.1 in coercive mode. Fix the source or explicitly decide what missing input means; adding a blind string cast can erase a meaningful null.

Implicit nullable parameters such as string $name = null are deprecated from 8.4. Write ?string $name = null, or string|null $name = null when the minimum version is 8.0. Also check required parameters following optional ones.

[Nullable examples](02-types.md#nullable), [8.1 deprecations](https://www.php.net/manual/en/migration81.deprecated.php), [8.4 deprecations](https://www.php.net/manual/en/migration84.deprecated.php).

<a id="object-state"></a>
## Properties, serialization and resource objects

- From 8.2, creating undeclared properties on ordinary classes is deprecated, with documented exceptions. Declare known fields; use an explicit dynamic-data container when keys are truly variable. See [dynamic properties](03-classes-and-objects.md#dynamic-properties).
- Implementing Serializable without the newer magic methods is deprecated from 8.1. __serialize()/__unserialize() have existed since 7.4. In 8.5, __sleep()/__wakeup() are soft-deprecated: this is guidance for new code, not a newly emitted runtime warning. See [serialization](06-json-xml-and-serialization.md).
- Extension handles such as cURL and GD changed from resources to objects in PHP 8. An is_resource() success check can therefore misclassify a valid handle. Use the documented result type and failure value of that API.
- Dynamic string interpolation using the older dollar-brace form is deprecated in 8.2; use the regular $name or {$name} form.

[8.1 deprecations](https://www.php.net/manual/en/migration81.deprecated.php), [8.2 deprecations](https://www.php.net/manual/en/migration82.deprecated.php), [8.5 deprecations](https://www.php.net/manual/en/migration85.deprecated.php).

<a id="php85"></a>
## PHP 8.5: small changes worth checking

Use canonical casts: (bool), (int), (float), (string). The aliases (boolean), (integer), (double), (binary) are deprecated. The backtick command operator is deprecated too; choose a process API with deliberate arguments instead of mechanically switching to another shell string.

Using null as an array offset is deprecated. Decide whether it meant a missing key or intentionally meant the empty-string key. Incrementing nonnumeric strings is deprecated; use str_increment() for its supported alphanumeric domain.

curl_close(), curl_share_close(), finfo_close() and imagedestroy() are deprecated because these objects are freed automatically. Releasing your reference and reusing a process-level persistent connection are different concerns.

Destructuring an unsupported value, such as a scalar, now warns. Arrays, null and objects supporting the required ArrayAccess offsets remain distinct cases. A successful lint will not find this data-dependent problem. [Official 8.5 deprecations](https://www.php.net/manual/en/migration85.deprecated.php), [8.5 incompatibilities](https://www.php.net/manual/en/migration85.incompatible.php).

<a id="rewrites"></a>
## Useful rewrites and their conditions

Follow the link for full examples. These are choices, not automatic search-and-replace rules.

- [?? / ??=](01-syntax-and-functions.md#null-coalescing): Missing or null means default; preserve meaningful zero and false.
- [fn](01-syntax-and-functions.md#arrow-functions): One expression and value capture are appropriate.
- [match](01-syntax-and-functions.md#match): Strict matching and exhaustive handling match the intended contract.
- [?->](01-syntax-and-functions.md#nullsafe): A missing object legitimately produces null.
- [enum](03-classes-and-objects.md#enums): The allowed states form a fixed set.
- [readonly](03-classes-and-objects.md#readonly-properties): One-time assignment is the actual contract; nested mutation is understood.
- [array_find()](04-arrays-and-iteration.md#predicate-search): You need the first matching element, not a prebuilt index.
- [str_contains()](05-strings-and-unicode.md#string-search): A boolean substring check is needed; empty-needle behavior is understood.
- [json_validate()](06-json-xml-and-serialization.md): Validation only; decode once instead when the data will be read.
- [Randomizer](11-security-and-random.md): Choose a secure engine for secrets and a seeded engine only for reproducibility.
- [property hooks](03-classes-and-objects.md#property-hooks): Small property access logic; substantial actions remain methods.
- [clone()](03-classes-and-objects.md#clone-with): Copy with validated property changes while respecting visibility.

<a id="verification"></a>
## Verify the affected behavior

Run the application's real tests on the target version, including error paths, missing records and repeated calls. Check the web server's PHP separately from CLI, loaded extensions and database drivers. Read every intervening migration guide if you skip minor versions. A successful php -l result proves parsing, not application correctness.

[Contents](../../README.md)
