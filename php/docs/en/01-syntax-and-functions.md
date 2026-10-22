# Syntax and functions

[English](../en/01-syntax-and-functions.md) | [Українська](../uk/01-syntax-and-functions.md) | [Русский](../ru/01-syntax-and-functions.md)

[Contents](../../README.md)

This chapter explains the syntax that makes everyday PHP shorter and more explicit. Each PHP block is independent. The version next to a section is the minimum for its main feature; later additions are identified in the text.

**On this page**

- [A default value with ??](#null-coalescing)
- [Fill missing configuration with ??=](#null-coalescing-assignment)
- [Sorting with the spaceship operator <=>](#spaceship)
- [Arrow functions and captured values](#arrow-functions)
- [Named arguments](#named-arguments)
- [String keys become named arguments](#unpacking-named-arguments)
- [Named arguments after unpacking](#named-after-unpacking)
- [match returns a value and compares strictly](#match)
- [A nullable object chain with ?->](#nullsafe)
- [throw inside an expression](#throw-expression)
- [Convert a callable with Closure::fromCallable()](#closure-from-callable)
- [First-class callable syntax: function_name(...)](#first-class-callables)
- [The pipe operator |>](#pipe)
- [Callbacks in constant expressions](#closures-in-constant-expressions)
- [Recursion with Closure::getCurrent()](#recursive-closures)
- [Trailing commas in calls and declarations](#trailing-commas)
- [Readable numeric literals](#numeric-literals)
- [Grouped namespace imports](#group-use)
- [Temporarily binding a closure with call()](#closure-call)
- [Expressions in static local initializers](#static-initializers)
- [exit() and die() behave more like functions](#exit-callable)
- [Type casts in constant expressions](#casts-in-constant-expressions)
- [Attributes on global constants](#global-constant-attributes)

<a id="null-coalescing"></a>
## A default value with ??

**PHP 7.0+ | Core**

Use `??` when a missing value and `null` both mean “use the default”. Existing `0`, `false` and an empty string are preserved. This is useful for configuration and optional array fields.

```php
$config = ['limit' => 0, 'label' => null];

var_dump( $config['limit'] ?? 20 );
var_dump( $config['label'] ?? 'Untitled' );
var_dump( $config['missing'] ?? 'Fallback' );
# int(0), string(8) "Untitled", string(8) "Fallback"
```

Unlike `?:`, this does not replace every false-like value. It also does not distinguish an absent key from a key explicitly containing `null`; use `array_key_exists()` when that distinction matters. Parenthesize the coalescing expression when combining it with concatenation.

[PHP documentation](https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce)

<a id="null-coalescing-assignment"></a>
## Fill missing configuration with ??=

**PHP 7.4+ | Core**

`??=` writes the default back to the variable or array entry. Use it when later code should see a normalized configuration, rather than only computing a temporary fallback.

```php
$config = ['retries' => 0];
$config['timeout'] ??= 30;
$config['retries'] ??= 3;

echo $config['timeout'], ':', $config['retries'];
# 30:0
```

An entry containing `null` is replaced too. The right side is evaluated only if an assignment is necessary, so it can contain a more expensive default calculation.

[PHP documentation](https://www.php.net/manual/en/language.operators.assignment.php)

<a id="spaceship"></a>
## Sorting with the spaceship operator <=>

**PHP 7.0+ | Core**

A comparison callback for sorting must return a negative number, zero or a positive number. `<=>` produces exactly that shape: `-1`, `0` or `1`. This removes a chain of conditions from a comparator.

```php
$rows = [['score' => 8], ['score' => 2], ['score' => 5]];

usort( $rows, function( array $left, array $right ): int
{
	return $left['score'] <=> $right['score'];
} );

echo implode( ',', array_column($rows, 'score') );
# 2,5,8
```

Swap the operands for descending order. Normalize the compared data first if types can differ: `<=>` follows PHP comparison rules and is not a strict equality operator. Returning only `true` or `false` is not a proper three-way comparator.

[PHP documentation](https://www.php.net/manual/en/language.operators.comparison.php)

<a id="arrow-functions"></a>
## Arrow functions and captured values

**PHP 7.4+ | Core**

`fn` is useful for a short transformation containing one expression. Variables used from the surrounding scope are captured by value when the function is created; no explicit `use` list is needed.

```php
$factor = 10;
$multiply = fn( int $value ): int => $value * $factor;
$factor = 20;

echo $multiply( 3 );
# 30
```

Use a normal closure for multiple statements or `use( &$value )` when an outer variable must be updated. Capturing an object by value still refers to the same object; it does not create a deep copy. `static fn` also avoids automatically binding `$this`.

[PHP documentation](https://www.php.net/manual/en/functions.arrow.php)

<a id="named-arguments"></a>
## Named arguments

**PHP 8.0+ | Core**

Parameter names let a call explain its own meaning and skip optional parameters. This is especially useful for several boolean options or functions with many defaults.

```php
function describe_item( string $name, bool $active = true, int $limit = 20 ): string
{
	return $name . ':' . $limit;
}

echo describe_item( 'Report', limit: 5 );
# Report:5
```

Positional arguments must come before named ones. Parameter names now form part of the callable interface: renaming `$limit` can break callers that use `limit:`. Prefer meaningful stable names in public functions.

[PHP documentation](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

<a id="unpacking-named-arguments"></a>
## String keys become named arguments

**PHP 8.0+ | Core**

When an array is unpacked into a call, string keys are treated as parameter names. This also matters when migrating `call_user_func_array()` code to PHP 8: an associative array is no longer just a positional list.

```php
function format_range( int $start, int $end ): string
{
	return $start . '-' . $end;
}

$arguments = ['end' => 9, 'start' => 3];
echo format_range( ...$arguments );
# 3-9
```

Unknown names or duplicate assignments to one parameter produce an error. If keys are arbitrary labels and positional order is intended, deliberately use `array_values()` first. Do not unpack unchecked request data directly into an application function.

[PHP documentation](https://www.php.net/manual/en/function.call-user-func-array.php)

<a id="named-after-unpacking"></a>
## Named arguments after unpacking

**PHP 8.1+ | Core**

A call can unpack a prepared list and then set a remaining parameter by name. This is useful when one caller needs an explicit option in addition to shared positional arguments.

```php
function join_parts( string $left, string $right, string $separator = '/' ): string
{
	return $left . $separator . $right;
}

$parts = ['docs', 'types'];
echo join_parts( ...$parts, separator: ':' );
# docs:types
```

The later named argument must not overwrite a parameter already supplied by the unpacked array. This is an argument feature, separate from unpacking arrays into another array.

[PHP documentation](https://www.php.net/manual/en/functions.arguments.php)

<a id="match"></a>
## match returns a value and compares strictly

**PHP 8.0+ | Core**

`match` is convenient when a small set of inputs determines one result. Each selected arm is an expression, and the subject is compared using strict identity, so `1` and `'1'` can lead to different results.

```php
$status = '1';
$label = match( $status )
{
	1 => 'numeric',
	'1' => 'text',
	2, 3 => 'other known state',
	default => 'unknown',
};

echo $label;
# text
```

There is no fall-through or `break`. Without a matching arm or `default`, PHP throws `UnhandledMatchError`. A missing default can be intentional when every enum case should be handled. Replacing a `switch` requires checking its previous loose-comparison behavior.

[PHP documentation](https://www.php.net/manual/en/control-structures.match.php)

<a id="nullsafe"></a>
## A nullable object chain with ?->

**PHP 8.0+ | Core**

`?->` stops an object access chain when its left operand is `null`. It is a compact way to express “this related object may legitimately be absent”.

```php
class Profile
{
	public function city(): string
	{
		return 'Kyiv';
	}
}

$profile = null;
echo $profile?->city() ?? 'Not provided';
# Not provided
```

Apply `?->` at every nullable step. It does not validate unknown property names, convert `false` into `null`, or hide exceptions thrown by a real method. It is read-only: assigning through a nullsafe chain is not supported. Required relationships should fail explicitly instead of silently disappearing.

[PHP documentation](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.nullsafe)

<a id="throw-expression"></a>
## throw inside an expression

**PHP 8.0+ | Core**

A missing required value can lead directly to an exception in the expression that reads it. Keep this for short checks whose meaning remains obvious.

```php
$config = ['token' => 'local-example'];
$token = $config['token'] ?? throw new InvalidArgumentException( 'Token is required' );

echo $token;
# local-example
```

The check above accepts an empty string because `??` only tests absence and `null`. Validate the contents separately if an empty token is invalid. Use a normal `if` block when validation needs several steps or a more useful error message.

[PHP documentation](https://www.php.net/exceptions)

<a id="closure-from-callable"></a>
## Convert a callable with Closure::fromCallable()

**PHP 7.1+ | Core**

`Closure::fromCallable()` turns an existing callable into a `Closure` and checks that it is callable in the current scope. This is useful when a callback arrives as a function name or method array but the receiving API stores a closure.

```php
function add_prefix( string $name ): string
{
	return 'note:' . $name;
}

$format = Closure::fromCallable( 'add_prefix' );
echo $format( 'types' ), PHP_EOL;
var_dump( $format instanceof Closure );
# note:types
# bool(true)
```

An invalid or inaccessible callable throws `TypeError` at conversion time. The resulting closure keeps the callable’s visibility context; it does not make an arbitrary private method publicly accessible. From PHP 8.1, [first-class callable syntax](#first-class-callables) offers the same semantics for a callable written directly in code.

[PHP documentation](https://www.php.net/manual/en/closure.fromcallable.php)

<a id="first-class-callables"></a>
## First-class callable syntax: function_name(...)

**PHP 8.1+ | Core**

The literal `...` creates a `Closure` instead of calling the function. Use it when passing a known function or method as a callback; the callable is resolved at creation time.

```php
$normalize = trim(...);
$values = array_map( $normalize, [' one ', ' two '] );

echo implode( ',', $values );
# one,two
```

`$service->handle(...)` also captures the method in the current visibility scope. This syntax is not argument unpacking: there is no array after the dots. It cannot be combined with `?->` or used as `new ClassName(...)` to create a constructor callback.

[PHP documentation](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)

<a id="pipe"></a>
## The pipe operator |>

**PHP 8.5+ | Core**

A pipe passes the value on its left to the callable on its right, then evaluates to the returned result. A sequence of small transformations can be read in execution order.

```php
$slug = ' PHP Notes '
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $text ): string => str_replace(' ', '-', $text));

echo $slug;
# php-notes
```

The callable must accept the piped value as its argument. Use a small closure to adapt functions requiring additional arguments or a different argument position. Pipes execute synchronously, do not handle errors for you and do not make a long transformation automatically more readable.

[PHP documentation](https://www.php.net/manual/en/language.operators.functional.php)

<a id="closures-in-constant-expressions"></a>
## Callbacks in constant expressions

**PHP 8.5+ | Core**

First-class callables and restricted static closures can now appear where PHP requires a constant expression, including parameter defaults and constants. A callback can have a useful default without a nullable parameter and an extra initialization branch.

```php
function normalize_name( string $name, Closure $normalizer = trim(...) ): string
{
	return $normalizer( $name );
}

echo normalize_name( ' Ada ' );
# Ada
```

This does not permit arbitrary function calls inside constants. The expression creates the callable; its body runs later when it is invoked. Closures in constant expressions must be static and cannot capture outer variables with `use`.

[PHP documentation](https://wiki.php.net/rfc/closures_in_const_expr)

<a id="recursive-closures"></a>
## Recursion with Closure::getCurrent()

**PHP 8.5+ | Core**

A closure can obtain its own currently executing instance. This avoids capturing the variable that will hold it by reference merely to call itself recursively.

```php
$factorial = static function( int $value ): int
{
	if( $value < 0 )
		throw new InvalidArgumentException( 'Expected a non-negative integer' );

	if( $value <= 1 )
		return 1;

	$current = Closure::getCurrent();
	return $value * $current( $value - 1 );
};

echo $factorial( 5 );
# 120
```

Calling `Closure::getCurrent()` outside a closure throws `Error`. It simplifies the reference, not the algorithm: a deep recursion can still exhaust resources, and factorial values quickly exceed the integer range.

[PHP documentation](https://www.php.net/manual/en/closure.getcurrent.php)

<a id="trailing-commas"></a>
## Trailing commas in calls and declarations

**PHP 7.3+ | Core**

PHP 7.3 accepts a trailing comma in a function or method call. PHP 8.0 also accepts it in parameter declarations and closure `use` lists. This makes adding a line to a multiline list produce a smaller diff.

```php
# This call syntax works from PHP 7.3.
echo implode(
	',',
	['one', 'two'],
);
# one,two
```

A trailing comma does not pass an extra argument. The version boundary depends on where the comma appears: a call and a function declaration did not gain support in the same release.

[PHP documentation](https://www.php.net/manual/en/functions.arguments.php)

<a id="numeric-literals"></a>
## Readable numeric literals

**PHP 7.4+ | Core**

Numeric separators make a limit or bit mask easier to read without changing its value. PHP 8.1 adds explicit `0o` octal notation, so an octal value no longer needs an easily overlooked leading zero.

```php
$max_bytes = 10_485_760;
$mask = 0b1111_0000;

echo $max_bytes, ':', $mask;
# 10485760:240
```

For PHP 8.1+, `0o644` means the same as `0644`, which is decimal `420`. Separators belong between digits; they are source syntax, not formatting understood by every string-to-number conversion.

[PHP documentation](https://www.php.net/manual/en/language.types.integer.php)

<a id="group-use"></a>
## Grouped namespace imports

**PHP 7.0+ | Core**

Several names from one namespace can share a common prefix in a `use` declaration. This is useful when a file uses several related application classes.

```php
use App\Report\{CsvWriter, JsonWriter};

echo CsvWriter::class, PHP_EOL;
echo JsonWriter::class;
# App\Report\CsvWriter
# App\Report\JsonWriter
```

The example prints class names, so it does not need the classes to be instantiated. An import does not load or create a class by itself. Avoid making one enormous group just to reduce the number of `use` lines.

[PHP documentation](https://www.php.net/manual/en/language.namespaces.importing.php)

<a id="closure-call"></a>
## Temporarily binding a closure with call()

**PHP 7.0+ | Core**

`Closure::call()` invokes a closure with a temporary `$this` object and its class scope. This can be useful in controlled inspection code, where creating a permanently rebound closure would be unnecessary.

```php
class Counter
{
	private $value = 7;
}

$read = function(): int
{
	return $this->value;
};

echo $read->call( new Counter() );
# 7
```

A static closure cannot be bound to `$this`. Treat access to private state as a deliberate technique for tools or tests, not a replacement for the normal public interface of an application object.

[PHP documentation](https://www.php.net/manual/en/closure.call.php)

<a id="static-initializers"></a>
## Expressions in static local initializers

**PHP 8.3+ | Core**

A function-local static variable can now be initialized by a function call or another dynamic expression. Initialization happens when execution first reaches it; later calls reuse the stored value.

```php
function next_number(): int
{
	static $number = strlen( 'seed' );
	return ++$number;
}

echo next_number(), ':', next_number();
# 5:6
```

This is convenient for small local caches. It does not create a cache shared across separate PHP requests. In a long-running worker the same function state can live much longer, so request-specific data needs an explicit lifetime policy.

[PHP documentation](https://wiki.php.net/rfc/arbitrary_static_variable_initializers)

<a id="exit-callable"></a>
## exit() and die() behave more like functions

**PHP 8.4+ | Core**

`exit()` can now be used as a callable, and its argument follows normal type rules including `strict_types`. An integer is a process exit status; a string is printed before execution terminates.

```php
$terminate = exit(...);

var_dump( $terminate instanceof Closure );
# bool(true)
# Calling $terminate(0) would end this script.
```

The example obtains the callable without terminating the process. Existing code passing an array or another invalid type must be fixed: PHP now throws `TypeError` instead of relying on the older conversion behavior. `die()` is the alias and has the same rules.

[PHP documentation](https://www.php.net/manual/en/migration84.incompatible.php)

<a id="casts-in-constant-expressions"></a>
## Type casts in constant expressions

**PHP 8.5+ | Core**

Explicit type casts are now allowed in constant expressions. A computed constant can state its intended representation directly instead of relying on a separately prepared literal.

```php
const WHOLE_SECONDS = (int)3.9;

echo WHOLE_SECONDS;
# 3
```

This changes which expressions can be declared, not the conversion rules: converting a float to int truncates its fractional part. A cast is not input validation, and an already-correct literal such as `const RETRIES = 3` does not need one.

[PHP documentation](https://www.php.net/manual/en/language.constants.syntax.php)

<a id="global-constant-attributes"></a>
## Attributes on global constants

**PHP 8.5+ | Core**

Attributes can now be attached to non-class constants declared using `const`. A practical built-in use is deprecating an old configuration constant while documenting its replacement.

```php
#[Deprecated( 'Use DEFAULT_PAGE_SIZE' )]
const OLD_PAGE_SIZE = 20;
const DEFAULT_PAGE_SIZE = 20;

echo DEFAULT_PAGE_SIZE;
# 20
```

Reading `OLD_PAGE_SIZE` would emit the deprecation diagnostic. This syntax does not apply to constants created with `define()`. For custom attributes, allow `Attribute::TARGET_CONSTANT` and read the metadata through reflection; PHP does not invent behavior for application-defined metadata.

[PHP documentation](https://www.php.net/manual/en/migration85.new-features.php)
