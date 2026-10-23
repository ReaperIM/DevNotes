# Errors and debugging

[EN](../en/08-errors-and-debugging.md) | [UK](../uk/08-errors-and-debugging.md) | [RU](../ru/08-errors-and-debugging.md)

[Contents](../../README.md)

Work out what failed, catch the right error and keep useful debugging details. We also look at unnoticed validation results and passwords accidentally appearing in stack traces.

**On this page**

- [`Throwable` covers `Exception` and `Error`](#throwable)
- [One `catch` for several exception types](#multi-catch)
- [`catch` without an unused variable](#catch-without-variable)
- [`ValueError` versus `TypeError`](#value-error)
- [Exceptions thrown by `__toString()`](#to-string-exceptions)
- [Identify a resource with `get_resource_id()`](#resource-id)
- [Read a value type with `get_debug_type()`](#debug-types)
- [Understand a failed regular expression](#regex-errors)
- [Distinguish an unmatched group from an empty match](#unmatched-groups)
- [Map regex patterns to replacement callbacks](#regex-callback-map)
- [Keep secret arguments out of stack traces](#sensitive-parameter)
- [Mark an old API with `#[Deprecated]`](#deprecated-attribute)
- [Warn when a function result is discarded](#nodiscard)
- [Which error handlers are currently installed](#current-handlers)
- [Backtraces for fatal errors](#fatal-backtraces)
- [Read PHP source with `PhpToken`](#php-token)

<a id="throwable"></a>
## `Throwable` covers `Exception` and `Error`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.throwable.php)

Catching only `Exception` can miss a type error. Both `Exception` and engine errors derived from `Error` implement `Throwable`, so this catch handles either kind. Here we pass an array instead of a string, get a `TypeError` and enter `catch`.

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

An ordinary warning does not become an exception on its own. Being able to catch `Error` also does not mean every failure is recoverable; some fatal conditions still stop execution.

<a id="multi-catch"></a>
## One `catch` for several exception types

**PHP 7.1+ | Core** | [PHP documentation](https://www.php.net/exceptions)

If several exception types need the same handling, list them with `|` in one `catch`. There is no need to copy the handler for every type. This example prints the message for either `InvalidArgumentException` or `RuntimeException`.

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

With several `catch` blocks, the first matching one wins. Put specific types before their parents, or the broader handler will catch the exception first.

<a id="catch-without-variable"></a>
## `catch` without an unused variable

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/exceptions)

If we only need to know that an error happened, without using its message or other details, we can leave the variable out of `catch`. Here knowing that division by zero occurred is enough to print a fallback.

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

If we later need to log the error details, we can put the variable back. Until then, there is no need to declare an unused one.

<a id="value-error"></a>
## `ValueError` versus `TypeError`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.valueerror.php)

`TypeError` means the supplied type is wrong. With `ValueError`, the type is acceptable but the value is not. Here an array is the right type, but the empty array has no element to choose randomly.

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

Not every function uses exceptions this way. A particular function may still signal failure with `false` or another special return value, so check how that function reports errors.

<a id="to-string-exceptions"></a>
## Exceptions thrown by `__toString()`

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

Converting an object to a string can fail too. Since PHP 7.4, an exception thrown by `__toString()` can be caught with an ordinary `catch`. Here `echo` triggers the conversion, and the method throws `RuntimeException` instead of returning a string.

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

Earlier PHP versions treated this exception as a fatal error. Keep that difference in mind when the code also needs to run on an older PHP version.

<a id="resource-id"></a>
## Identify a resource with `get_resource_id()`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.get-resource-id.php)

Use `get_resource_id()` to tell open resources apart in debug output or logs. It says what we want more clearly than casting a resource to `(int)`. Here we first check that the temporary stream opened, then read its identifier.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

$resource_id = get_resource_id( $stream );
fclose( $stream );
```

The number identifies the resource within the current process. Do not save it as an identifier for a later run. It is not an operating-system file descriptor either.

<a id="debug-types"></a>
## Read a value type with `get_debug_type()`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.get-debug-type.php)

Use `get_debug_type()` when an error message needs a readable value type. An integer gives `int`, an object gives its class name, and a resource includes its kind. The example does not need separate branches for numbers and objects.

```php
$type = get_debug_type( 42 ); # int
$class_name = get_debug_type( new DateTimeImmutable() ); # DateTimeImmutable
```

The function describes the value. It does not validate whether that type is allowed for the operation.

<a id="regex-errors"></a>
## Understand a failed regular expression

**PHP 8.0+ | PCRE** | [PHP documentation](https://www.php.net/manual/en/function.preg-last-error-msg.php)

`preg_match()` has three different outcomes: `1` means a match, `0` means no match, and `false` means an error. Here invalid UTF-8 with the `u` flag causes an error. `preg_last_error_msg()` gives us a readable explanation.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Read the message before running another regular expression, which would overwrite the last error. A malformed pattern can also emit a warning, so check the pattern itself when that happens.

<a id="unmatched-groups"></a>
## Distinguish an unmatched group from an empty match

**PHP 7.2+ | PCRE** | [PHP documentation](https://www.php.net/manual/en/function.preg-match.php)

An empty matched string and a group that did not participate in the match are different things. `PREG_UNMATCHED_AS_NULL` keeps them apart. Here `digits` did not participate and becomes `null`, while `tail` successfully matches the empty string `''`.

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );
var_dump( $matches['digits'], $matches['tail'] ); # null, ''
```

Since PHP 7.4, trailing unmatched groups are included as `null` too. The flag does not tell us whether the search succeeded; check the return value of `preg_match()` separately.

<a id="regex-callback-map"></a>
## Map regex patterns to replacement callbacks

**PHP 7.0+ | PCRE** | [PHP documentation](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

If each pattern needs its own replacement function, put them in a map for `preg_replace_callback_array()`. The key is the regular expression and the value is its callback. Here we insert the name first, then double the numbers we find.

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

Rules run in array order, so each later pattern sees the earlier replacements. A matching error returns `null`, and a malformed pattern can also emit a warning. The optional `flags` parameter is available from PHP 7.4.

<a id="sensitive-parameter"></a>
## Keep secret arguments out of stack traces

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.sensitiveparameter.php)

A password passed to a function can accidentally appear among the arguments in a stack trace. Mark the parameter with `#[SensitiveParameter]`, and PHP puts a `SensitiveParameterValue` object in that position instead of the actual value.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}
```

The attribute does not hide a password that we write into an exception message or log ourselves. It protects the argument value in stack traces specifically.

<a id="deprecated-attribute"></a>
## Mark an old API with `#[Deprecated]`

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.deprecated.php)

When an old function must stay for now but callers should move to a replacement, mark it with `#[Deprecated]`. PHP emits `E_USER_DEPRECATED` when it is used. The attribute supports functions, methods and class constants; PHP 8.5 adds traits and global `const` declarations.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}
```

`since` is the version or date of our API, not the PHP version. The warning does not stop execution: the old function still runs until we remove it.

<a id="nodiscard"></a>
## Warn when a function result is discarded

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.nodiscard.php)

Use `#[NoDiscard]` when a function result should not be silently lost. Calling a name validator and doing nothing with its `bool`, for example, often means the caller has missed a check. PHP warns about that call. Here we keep the first result and explicitly discard the second with `(void)`.

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

`(void)` says that discarding the result is intentional and suppresses the warning. This cast also requires PHP 8.5. When a method is overridden, the new implementation does not automatically inherit the attribute.

<a id="current-handlers"></a>
## Which error handlers are currently installed

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.get-error-handler.php) | [`get_exception_handler()`](https://www.php.net/manual/en/function.get-exception-handler.php)

Use `get_error_handler()` and `get_exception_handler()` to see what currently handles errors and uncaught exceptions. They return the registered `callable` without replacing it. Here we check that our functions are installed, then restore the previous handlers in `finally`.

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

If no custom handler is installed, the result is `null`. `get_error_handler()` does not return the error-level mask passed to `set_error_handler()`.

<a id="fatal-backtraces"></a>
## Backtraces for fatal errors

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.error-get-last.php)

For a fatal error, the calls leading to the failure can be as useful as the failing line itself. `fatal_error_backtraces` controls these traces, and `error_get_last()` returns one in `trace` when available. The example falls back to an empty array if the field is missing.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];
```

This adds diagnostics; it does not make fatal errors catchable with `catch`. Uncaught exceptions already have their own stack traces and do not receive this field.

<a id="php-token"></a>
## Read PHP source with `PhpToken`

**PHP 8.0+ | tokenizer** | [PHP documentation](https://www.php.net/manual/en/class.phptoken.php)

Use `PhpToken::tokenize()` to split PHP source into individual elements without executing it. Each token has an element type `id`, its `text`, a `line` number and a byte position `pos`. Here we find `echo` and print the line it appears on.

```php
# This is source text to inspect, not code to execute.
$source = '<?php echo 42;';
foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Tokens help us inspect source text, but do not describe the entire program structure. The method does not build a full syntax tree or run the source code.
