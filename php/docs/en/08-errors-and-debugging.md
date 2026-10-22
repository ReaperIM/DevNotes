# Errors and debugging

[English](../en/08-errors-and-debugging.md) | [Українська](../uk/08-errors-and-debugging.md) | [Русский](../ru/08-errors-and-debugging.md)

[Contents](../../README.md)

Good diagnostics distinguish invalid input, a programming mistake and a runtime failure. These sections show the language tools without turning every recoverable situation into an exception. Run each PHP block separately.

**On this page**

- [Throwable covers Exception and Error](#throwable)
- [One catch for several exception types](#multi-catch)
- [catch without an unused variable](#catch-without-variable)
- [ValueError versus TypeError](#value-error)
- [Exceptions thrown by __toString()](#to-string-exceptions)
- [Identify a resource with get_resource_id()](#resource-id)
- [Readable runtime types with get_debug_type()](#debug-types)
- [Understand a failed regular expression](#regex-errors)
- [Distinguish an unmatched group from an empty match](#unmatched-groups)
- [Map regex patterns to replacement callbacks](#regex-callback-map)
- [Keep secret arguments out of stack traces](#sensitive-parameter)
- [Mark an old API with #[Deprecated]](#deprecated-attribute)
- [Notice ignored results with #[NoDiscard]](#nodiscard)
- [Inspect the current error and exception handlers](#current-handlers)
- [Backtraces for fatal errors](#fatal-backtraces)
- [Read PHP source with PhpToken](#php-token)

<a id="throwable"></a>
## Throwable covers Exception and Error

**PHP 7.0+ | Core**

PHP 7 introduces a separate `Error` hierarchy for many engine errors. Both `Error` and `Exception` implement `Throwable`. A boundary that must handle both can catch `Throwable`; catching only `Exception` does not catch `TypeError`.

```php
function double_number( int $value ): int
{
	return $value * 2;
}

try
{
	double_number( [] );
}
catch( Throwable $error )
{
	echo get_class( $error );
}
# TypeError
```

This is not a mechanism for catching every PHP diagnostic. Ordinary warnings are not automatically exceptions, and some fatal conditions cannot be recovered from. Catch a narrower exception where recovery is specific, and reserve a broad boundary for logging or turning a failed operation into an appropriate response.

[PHP documentation](https://www.php.net/manual/en/class.throwable.php)

<a id="multi-catch"></a>
## One catch for several exception types

**PHP 7.1+ | Core**

A `catch` can name several types separated by `|`. Use this when distinct failures really need the same recovery action, rather than copying the same block for each class.

```php
try
{
	throw new InvalidArgumentException( 'Invalid format' );
}
catch( InvalidArgumentException | RuntimeException $error )
{
	echo 'Cannot import: ', $error->getMessage();
}
# Cannot import: Invalid format
```

This is an exception selection rule, not a union return type. More specific `catch` blocks must come before a broader parent type if both are present; only the first matching block handles the exception.

[PHP documentation](https://www.php.net/exceptions)

<a id="catch-without-variable"></a>
## catch without an unused variable

**PHP 8.0+ | Core**

The exception variable is optional. Omit it when the handler only chooses a fallback and genuinely does not need the message, trace or previous exception.

```php
try
{
	intdiv( 10, 0 );
}
catch( DivisionByZeroError )
{
	echo 'No ratio';
}
# No ratio
```

Dropping the variable should not become a reason to hide unexpected failures. If investigation requires details, keep the variable and send useful diagnostics to the application log.

[PHP documentation](https://www.php.net/exceptions)

<a id="value-error"></a>
## ValueError versus TypeError

**PHP 8.0+ | Core**

`TypeError` signals that a type contract was not satisfied. `ValueError` signals a value that has an accepted type but is outside the function’s allowed domain, such as an empty input array where an element must be selected.

```php
try
{
	array_rand( [] );
}
catch( ValueError $error )
{
	echo 'The array must contain an item';
}
# The array must contain an item
```

Do not generalize this to every API: some functions still return `false` or another documented failure value. Read the contract of the particular function. For expected user input, validating before the call may give a clearer message.

[PHP documentation](https://www.php.net/manual/en/class.valueerror.php)

<a id="to-string-exceptions"></a>
## Exceptions thrown by __toString()

**PHP 7.4+ | Core**

A failed string conversion can now throw an exception that the caller catches normally. Before PHP 7.4, throwing from `__toString()` caused a fatal error.

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

A conversion should still be predictable and inexpensive. Code can trigger it indirectly through concatenation, logging or output, so hiding network requests or large side effects inside it makes failures harder to understand.

[PHP documentation](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

<a id="resource-id"></a>
## Identify a resource with get_resource_id()

**PHP 8.0+ | Core**

`get_resource_id()` explicitly returns the integer identifier of a resource. It replaces the less descriptive `(int)$resource` cast when associating stream handles with diagnostic information.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

try
{
	$id = get_resource_id( $stream );
	var_dump( is_int($id) );
	var_dump( $id === get_resource_id($stream) );
}
finally
{
	fclose( $stream );
}
# bool(true)
# bool(true)
```

The numeric ID varies between runs. Keep it only for identifying resources within the current process; it is not a persistent application identifier or an operating-system file descriptor. The example closes its temporary stream in `finally`; `tmpfile()` also removes that temporary file when it is closed. Objects use different identity APIs.

[PHP documentation](https://www.php.net/manual/en/function.get-resource-id.php)

<a id="debug-types"></a>
## Readable runtime types with get_debug_type()

**PHP 8.0+ | Core**

`get_debug_type()` returns names suited to diagnostic messages: `int`, `bool`, a class name or a resource kind. It avoids manually combining `gettype()` and `get_class()`.

```php
foreach( [42, false, null, new DateTimeImmutable('2025-01-01')] as $value )
	echo get_debug_type( $value ), PHP_EOL;
# int
# bool
# null
# DateTimeImmutable
```

Use it to describe a rejected value without printing the value itself. It is a diagnostic label, not a replacement for `instanceof`, native type declarations or explicit validation.

[PHP documentation](https://www.php.net/manual/en/function.get-debug-type.php)

<a id="regex-errors"></a>
## Understand a failed regular expression

**PHP 8.0+ | PCRE**

`preg_match()` has three meaningful outcomes: `1` is a match, `0` is no match, and `false` is an execution failure. `preg_last_error_msg()` explains the most recent PCRE error without a hand-written table of numeric codes.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Read the error immediately after the failing call; another regular expression can replace that state. The example has a valid pattern but invalid UTF-8 input. A malformed pattern is a separate problem and may also emit a warning.

[PHP documentation](https://www.php.net/manual/en/function.preg-last-error-msg.php)

<a id="unmatched-groups"></a>
## Distinguish an unmatched group from an empty match

**PHP 7.2+ | PCRE**

`PREG_UNMATCHED_AS_NULL` reports an optional group that did not participate as `null`. A group that participated and matched an empty string remains `''`. This distinction is useful when an empty field and an absent field have different meanings.

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );

var_dump( $matches['digits'] );
var_dump( $matches['tail'] );
# NULL
# string(0) ""
```

The whole pattern still matched in this example. The flag does not replace checking `preg_match()` for `1`, `0` or `false`. From PHP 7.4, trailing unmatched capturing groups are also included as `null`; earlier versions could omit those trailing entries even with this flag.

[PHP documentation](https://www.php.net/manual/en/function.preg-match.php)

<a id="regex-callback-map"></a>
## Map regex patterns to replacement callbacks

**PHP 7.0+ | PCRE**

`preg_replace_callback_array()` accepts a map from patterns to callbacks. Each rule can calculate its own replacement, keeping related text transformations together without a large callback that guesses which pattern matched.

```php
$rules = [
	'/\{name\}/' => static function( array $matches ): string
	{
		return 'Ada';
	},
	'/\d+/' => static function( array $matches ): string
	{
		return (string)((int)$matches[0] * 2);
	},
];

$result = preg_replace_callback_array( $rules, '{name}: 7' );

if( $result === null )
	throw new RuntimeException( 'Replacement failed' );

echo $result;
# Ada: 14
```

Rules run in array order, so a later pattern sees text changed by an earlier rule. Keep the patterns under application control. A regex execution failure returns `null`, not `false`; an invalid pattern may emit a warning. The optional `flags` parameter was added in PHP 7.4, later than the function itself.

[PHP documentation](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

<a id="sensitive-parameter"></a>
## Keep secret arguments out of stack traces

**PHP 8.2+ | Core**

Mark a secret-bearing parameter with `#[SensitiveParameter]`. When arguments are included in a stack trace, PHP replaces that argument with a `SensitiveParameterValue` object. This helps keep passwords and tokens out of ordinary rendered traces.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}

$previous = ini_get( 'zend.exception_ignore_args' );
ini_set( 'zend.exception_ignore_args', '0' );

try
{
	authenticate( 'example-secret' );
}
catch( RuntimeException $error )
{
	$trace = $error->getTrace();
	echo get_debug_type( $trace[0]['args'][0] );
}
finally
{
	ini_set( 'zend.exception_ignore_args', $previous );
}
# SensitiveParameterValue
```

This is not encryption or a general logging filter. Explicitly printing the password or placing it in an exception message still exposes it. The temporary INI setting only makes the demonstration visible; restore the previous setting after it.

[PHP documentation](https://www.php.net/manual/en/class.sensitiveparameter.php)

<a id="deprecated-attribute"></a>
## Mark an old API with #[Deprecated]

**PHP 8.4+ | Core**

The built-in attribute marks functions, methods and class constants as deprecated. Using them emits `E_USER_DEPRECATED`. PHP 8.5 extends support to traits and global constants declared with `const`.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}

set_error_handler( static function( int $level, string $message ): bool
{
	echo 'Deprecated API', PHP_EOL;
	return true;
}, E_USER_DEPRECATED );

try
{
	echo old_name();
}
finally
{
	restore_error_handler();
}
# Deprecated API
# report
```

`since` is your API’s version or date, not necessarily a PHP version. Provide a useful replacement in the message. The temporary handler above makes the output predictable; the deprecation does not prevent the function body from running.

[PHP documentation](https://www.php.net/manual/en/class.deprecated.php)

<a id="nodiscard"></a>
## Notice ignored results with #[NoDiscard]

**PHP 8.5+ | Core**

Some functions return information that the caller must inspect, such as validation results or a newly created immutable value. `#[NoDiscard]` emits a warning if a call’s result is discarded.

```php
#[NoDiscard]
function validate_name( string $name ): bool
{
	return trim( $name ) !== '';
}

$is_valid = validate_name( 'Ada' );
var_dump( $is_valid );
(void)validate_name( ' ' );
# bool(true)
```

`(void)` is an explicit decision to ignore the result and suppresses the warning; that cast also requires PHP 8.5. Assigning the result does not prove that the caller used it meaningfully. Overriding implementations need their own attribute: it is not automatically enforced through an interface declaration.

[PHP documentation](https://www.php.net/manual/en/class.nodiscard.php)

<a id="current-handlers"></a>
## Inspect the current error and exception handlers

**PHP 8.5+ | Core**

`get_error_handler()` and `get_exception_handler()` read the currently registered callable without temporarily changing it. This helps diagnostics and integration code inspect the process state without installing a dummy handler.

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

A `null` result means no custom handler is registered for that kind. The getter returns the callable, not the error-level mask passed to `set_error_handler()`. For temporary installation, keep using the matching `restore_*_handler()` in `finally` to restore the previous handler stack.

[PHP documentation](https://www.php.net/manual/en/function.get-error-handler.php) | [get_exception_handler()](https://www.php.net/manual/en/function.get-exception-handler.php)

<a id="fatal-backtraces"></a>
## Backtraces for fatal errors

**PHP 8.5+ | Core**

Fatal failures such as exhausted execution time can include a backtrace controlled by `fatal_error_backtraces`. In PHP 8.5, `error_get_last()` also exposes a `trace` entry when such a fatal backtrace is available.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];

echo count( $trace );
# 0 in a fresh process with no preceding error
```

The example only reads diagnostics and deliberately does not exhaust memory or hang the script. A trace improves investigation; it does not make the fatal error catchable. An uncaught exception uses its own trace and does not add this `trace` entry to `error_get_last()`. Keep diagnostic output away from public responses.

[PHP documentation](https://www.php.net/manual/en/function.error-get-last.php)

<a id="php-token"></a>
## Read PHP source with PhpToken

**PHP 8.0+ | tokenizer**

`PhpToken::tokenize()` represents every lexical token with the same object shape: `id`, `text`, `line` and byte position `pos`. It is easier to work with than mixing arrays and single-character strings from `token_get_all()`.

```php
$source = '<?php echo 42;';

foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

This is useful for code-inspection tools and small source transformations. Tokenization does not execute the source. Tokens are not a full syntax tree, so complex refactoring still needs a parser and attention to scope.

[PHP documentation](https://www.php.net/manual/en/class.phptoken.php)
