# Errors and debugging

[EN](../en/08-errors-and-debugging.md) | [UK](../uk/08-errors-and-debugging.md) | [RU](../ru/08-errors-and-debugging.md)

[Contents](../../README.md)

**On this page**

- [`Throwable` covers `Exception` and `Error`](#throwable)
- [One `catch` for several exception types](#multi-catch)
- [`catch` without an unused variable](#catch-without-variable)
- [`ValueError` versus `TypeError`](#value-error)
- [Exceptions thrown by `__toString()`](#to-string-exceptions)
- [Identify a resource with `get_resource_id()`](#resource-id)
- [Readable runtime types with `get_debug_type()`](#debug-types)
- [Understand a failed regular expression](#regex-errors)
- [Distinguish an unmatched group from an empty match](#unmatched-groups)
- [Map regex patterns to replacement callbacks](#regex-callback-map)
- [Keep secret arguments out of stack traces](#sensitive-parameter)
- [Mark an old API with `#[Deprecated]`](#deprecated-attribute)
- [Notice ignored results with `#[NoDiscard]`](#nodiscard)
- [Inspect the current error and exception handlers](#current-handlers)
- [Backtraces for fatal errors](#fatal-backtraces)
- [Read PHP source with `PhpToken`](#php-token)

<a id="throwable"></a>
## `Throwable` covers `Exception` and `Error`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.throwable.php)

`Throwable` covers both the `Exception` and engine `Error` hierarchies; catching only `Exception` misses `TypeError`.

```php
try
{
	strlen( [] );
}
catch( Throwable $error )
{
	echo get_class( $error ); # TypeError
}
```

Ordinary warnings are not automatically exceptions, and some fatal conditions remain unrecoverable.

<a id="multi-catch"></a>
## One `catch` for several exception types

**PHP 7.1+ | Core** | [PHP documentation](https://www.php.net/exceptions)

A `catch` may list several exception types with `|`.

```php
try
{
	throw new InvalidArgumentException( 'Invalid format' );
}
catch( InvalidArgumentException | RuntimeException $error )
{
	echo $error->getMessage(); # Invalid format
}
```

Only the first matching handler runs, so put specific types before broader parent types.

<a id="catch-without-variable"></a>
## `catch` without an unused variable

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/exceptions)

The variable in a `catch` declaration is optional when the handler does not need the exception object.

```php
try
{
	intdiv( 10, 0 );
}
catch( DivisionByZeroError )
{
	echo 'No ratio'; # No ratio
}
```

<a id="value-error"></a>
## `ValueError` versus `TypeError`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.valueerror.php)

`TypeError` reports a violated type contract; `ValueError` reports an accepted type with an invalid value.

```php
try
{
	array_rand( [] );
}
catch( ValueError $error )
{
	echo $error->getMessage();
}
```

This is API-specific: some functions still report failure with `false` or another documented result.

<a id="to-string-exceptions"></a>
## Exceptions thrown by `__toString()`

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

From PHP 7.4, exceptions thrown from `__toString()` can be caught by the caller.

```php
class ReportLabel
{
	public function __toString(): string
	{
		throw new RuntimeException( 'Label is unavailable' );
	}
}

try
{
	echo new ReportLabel();
}
catch( RuntimeException $error )
{
	echo $error->getMessage();
}
# Label is unavailable
```

Earlier versions treated throwing during this conversion as a fatal error.

<a id="resource-id"></a>
## Identify a resource with `get_resource_id()`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.get-resource-id.php)

`get_resource_id()` returns a resource’s integer identifier explicitly instead of `(int)$resource`.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

$resource_id = get_resource_id( $stream );
fclose( $stream );
```

It identifies the resource within the current process, not across runs, and is not an operating-system file descriptor.

<a id="debug-types"></a>
## Readable runtime types with `get_debug_type()`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.get-debug-type.php)

`get_debug_type()` returns diagnostic type names such as `int`, a class name or a resource kind.

```php
$type = get_debug_type( 42 ); # int
$class_name = get_debug_type( new DateTimeImmutable() ); # DateTimeImmutable
```

It describes a value rather than validating it.

<a id="regex-errors"></a>
## Understand a failed regular expression

**PHP 8.0+ | PCRE** | [PHP documentation](https://www.php.net/manual/en/function.preg-last-error-msg.php)

`preg_last_error_msg()` explains the most recent PCRE failure; `preg_match()` returns `1`, `0` or `false` for match, no match or failure.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Read the error before another regex call replaces it; an invalid pattern may also emit a warning.

<a id="unmatched-groups"></a>
## Distinguish an unmatched group from an empty match

**PHP 7.2+ | PCRE** | [PHP documentation](https://www.php.net/manual/en/function.preg-match.php)

`PREG_UNMATCHED_AS_NULL` distinguishes a group that did not participate (`null`) from an empty match (`''`).

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );
var_dump( $matches['digits'], $matches['tail'] ); # null, ''
```

From PHP 7.4 trailing unmatched groups are also included as `null`; the flag does not replace checking the overall match result.

<a id="regex-callback-map"></a>
## Map regex patterns to replacement callbacks

**PHP 7.0+ | PCRE** | [PHP documentation](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

`preg_replace_callback_array()` maps patterns to callbacks and applies rules in array order, so later rules see earlier replacements.

```php
$result = preg_replace_callback_array( [
	'/\{name\}/' => static function( array $matches ): string
	{
		return 'Ada';
	},
	'/\d+/' => static function( array $matches ): string
	{
		return (string)((int)$matches[0] * 2);
	},
], '{name}: 7' );

if( $result === null )
	throw new RuntimeException( 'Replacement failed' );

echo $result; # Ada: 14
```

Regex failures return `null` and invalid patterns may warn; the optional `flags` parameter requires PHP 7.4.

<a id="sensitive-parameter"></a>
## Keep secret arguments out of stack traces

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.sensitiveparameter.php)

`#[SensitiveParameter]` replaces an argument with `SensitiveParameterValue` when arguments appear in stack traces.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}
```

It does not hide values printed explicitly or included in exception messages.

<a id="deprecated-attribute"></a>
## Mark an old API with `#[Deprecated]`

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.deprecated.php)

`#[Deprecated]` emits `E_USER_DEPRECATED` for functions, methods and class constants; PHP 8.5 adds traits and global `const` declarations.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}
```

`since` describes your API version or date, and the diagnostic does not prevent execution.

<a id="nodiscard"></a>
## Notice ignored results with `#[NoDiscard]`

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.nodiscard.php)

`#[NoDiscard]` warns when a call’s result is discarded; `(void)` explicitly suppresses the warning and also requires PHP 8.5.

```php
#[NoDiscard]
function validate_name( string $name ): bool
{
	return trim( $name ) !== '';
}

$is_valid = validate_name( 'Ada' );
var_dump( $is_valid ); # bool(true)
(void)validate_name( ' ' );
```

The attribute is not inherited automatically by overriding implementations.

<a id="current-handlers"></a>
## Inspect the current error and exception handlers

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.get-error-handler.php) | [`get_exception_handler()`](https://www.php.net/manual/en/function.get-exception-handler.php)

`get_error_handler()` and `get_exception_handler()` read the registered `callable` without changing handler stacks.

```php
$error_handler = static function( int $level, string $message ): bool
{
	return false;
};
$exception_handler = static function( Throwable $error ): void {};

set_error_handler( $error_handler );
set_exception_handler( $exception_handler );

try
{
	var_dump( get_error_handler() === $error_handler );
	var_dump( get_exception_handler() === $exception_handler );
}
finally
{
	restore_exception_handler();
	restore_error_handler();
}
# bool(true)
# bool(true)
```

`null` means no custom handler; the error getter does not return the registered error-level mask.

<a id="fatal-backtraces"></a>
## Backtraces for fatal errors

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.error-get-last.php)

`fatal_error_backtraces` controls fatal backtraces, and `error_get_last()` exposes `trace` when one is available.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];
```

This does not make fatal errors catchable; uncaught exceptions retain their own traces and do not add this entry.

<a id="php-token"></a>
## Read PHP source with `PhpToken`

**PHP 8.0+ | tokenizer** | [PHP documentation](https://www.php.net/manual/en/class.phptoken.php)

`PhpToken::tokenize()` returns uniform token objects with `id`, `text`, `line` and byte position `pos`.

```php
$source = '<?php echo 42;';
foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Tokenization does not execute the code or produce a full syntax tree.
