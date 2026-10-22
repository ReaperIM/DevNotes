# Syntax and functions

[EN](../en/01-syntax-and-functions.md) | [UK](../uk/01-syntax-and-functions.md) | [RU](../ru/01-syntax-and-functions.md)

[Contents](../../README.md)

**On this page**

- [A default value with `??`](#null-coalescing)
- [Fill missing configuration with `??=`](#null-coalescing-assignment)
- [Sorting with the spaceship operator `<=>`](#spaceship)
- [Arrow functions and captured values](#arrow-functions)
- [Named arguments](#named-arguments)
- [String keys become named arguments](#unpacking-named-arguments)
- [Named arguments after unpacking](#named-after-unpacking)
- [`match` returns a value and compares strictly](#match)
- [A nullable object chain with `?->`](#nullsafe)
- [`throw` inside an expression](#throw-expression)
- [Convert a callable with `Closure::fromCallable()`](#closure-from-callable)
- [First-class callable syntax: `function_name(...)`](#first-class-callables)
- [The pipe operator `|>`](#pipe)
- [Callbacks in constant expressions](#closures-in-constant-expressions)
- [Recursion with `Closure::getCurrent()`](#recursive-closures)
- [Trailing commas in calls and declarations](#trailing-commas)
- [Readable numeric literals](#numeric-literals)
- [Grouped namespace imports](#group-use)
- [Temporarily binding a closure with `call()`](#closure-call)
- [Expressions in static local initializers](#static-initializers)
- [`exit()` and `die()` behave more like functions](#exit-callable)
- [Type casts in constant expressions](#casts-in-constant-expressions)
- [Attributes on global constants](#global-constant-attributes)

<a id="null-coalescing"></a>
## A default value with `??`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce)

`??` supplies a fallback for missing or `null` values while preserving `0`, `false` and `''`.

```php
$config = ['limit' => 0, 'label' => null];
$limit = $config['limit'] ?? 20;
$label = $config['label'] ?? 'Untitled';

echo $limit, ':', $label; # 0:Untitled
```

Use `array_key_exists()` to distinguish a missing key from `null`; parenthesize `??` when combining it with concatenation.

<a id="null-coalescing-assignment"></a>
## Fill missing configuration with `??=`

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.assignment.php)

`??=` assigns a default when the current value is missing or `null`.

```php
$config = ['retries' => 0];
$config['timeout'] ??= 30;
$config['retries'] ??= 3;

echo $config['timeout'], ':', $config['retries']; # 30:0
```

The right side is evaluated only when assignment is needed; existing zero values remain unchanged.

<a id="spaceship"></a>
## Sorting with the spaceship operator `<=>`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.comparison.php)

`<=>` returns `-1`, `0` or `1` for sorting callbacks; reversing operands reverses the order.

```php
$rows = [['score' => 8], ['score' => 2], ['score' => 5]];
usort( $rows, function( array $left, array $right ): int
{
	return $left['score'] <=> $right['score'];
} );

echo implode( ',', array_column($rows, 'score') ); # 2,5,8
```

It follows PHP comparison rules rather than strict identity, so mixed types may need normalization.

<a id="arrow-functions"></a>
## Arrow functions and captured values

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arrow.php)

`fn` defines a single-expression closure and captures outer variables by value.

```php
$factor = 10;
$multiply = fn( int $value ): int => $value * $factor;
$factor = 20;
echo $multiply( 3 ); # 30
```

Objects still refer to the same instance; use a normal closure for reference capture and `static fn` to avoid binding `$this`.

<a id="named-arguments"></a>
## Named arguments

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

Named arguments select parameters by name and allow skipping optional ones.

```php
function describe_item( string $name, string $separator = ':', int $limit = 20 ): string
{
	return $name . $separator . $limit;
}

$label = describe_item( 'Report', limit: 5 );

echo $label; # Report:5
```

Positional arguments must come first, and renaming a parameter can break callers using its name.

<a id="unpacking-named-arguments"></a>
## String keys become named arguments

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.call-user-func-array.php)

String keys in unpacked argument arrays become parameter names, including in `call_user_func_array()`.

```php
function format_range( int $start, int $end ): string
{
	return $start . '-' . $end;
}

$arguments = ['end' => 9, 'start' => 3];
echo format_range( ...$arguments ); # 3-9
```

Unknown names and duplicate assignments cause errors; use `array_values()` when keys are labels and positional order is intended.

<a id="named-after-unpacking"></a>
## Named arguments after unpacking

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arguments.php)

Named arguments may follow argument unpacking.

```php
function join_parts( string $left, string $right, string $separator = '/' ): string
{
	return $left . $separator . $right;
}

$parts = ['docs', 'types'];
echo join_parts( ...$parts, separator: ':' ); # docs:types
```

They must not overwrite a parameter already supplied by the unpacked array.

<a id="match"></a>
## `match` returns a value and compares strictly

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/control-structures.match.php)

`match` returns an expression result, compares with strict identity and has no fall-through.

```php
$status = '1';
$label = match( $status )
{
	1 => 'numeric',
	'1' => 'text',
	2, 3 => 'known',
	default => 'unknown',
};

echo $label; # text
```

Without a matching arm or `default`, it throws `UnhandledMatchError`; this differs from the loose comparisons in `switch`.

<a id="nullsafe"></a>
## A nullable object chain with `?->`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.nullsafe)

`?->` short-circuits an object chain when its left operand is `null`; mark every nullable step.

```php
$profile = null;
$city = $profile?->address?->city ?? 'Not provided';

echo $city; # Not provided
```

It supports reads only and does not suppress invalid accesses or exceptions on non-null objects.

<a id="throw-expression"></a>
## `throw` inside an expression

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/exceptions)

`throw` can appear inside an expression, including a `??` fallback.

```php
$config = ['token' => 'local-example'];
$token = $config['token'] ?? throw new InvalidArgumentException( 'Token is required' );
```

Here an empty string is still accepted because `??` only checks absence and `null`.

<a id="closure-from-callable"></a>
## Convert a callable with `Closure::fromCallable()`

**PHP 7.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/closure.fromcallable.php)

`Closure::fromCallable()` creates a closure and checks callable access in the current scope.

```php
function add_prefix( string $name ): string
{
	return 'note:' . $name;
}

$format = Closure::fromCallable( 'add_prefix' );

echo $format( 'types' ); # note:types
```

Invalid or inaccessible callables throw `TypeError`; [first-class callable syntax](#first-class-callables) provides the same semantics from PHP 8.1.

<a id="first-class-callables"></a>
## First-class callable syntax: `function_name(...)`

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)

`function_name(...)` creates a `Closure` and resolves access at creation time; method callables preserve that visibility scope.

```php
$normalize = trim(...);
$values = array_map( $normalize, [' one ', ' two '] );

echo implode( ',', $values ); # one,two
```

This is not argument unpacking and cannot be combined with `?->` or `new ClassName(...)`.

<a id="pipe"></a>
## The pipe operator `|>`

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.functional.php)

`|>` passes its left value to the callable on the right and returns that call’s result.

```php
$slug = ' PHP Notes '
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $text ): string => str_replace(' ', '-', $text));

echo $slug; # php-notes
```

Adapt additional arguments or another argument position with a closure; the chain executes synchronously.

<a id="closures-in-constant-expressions"></a>
## Callbacks in constant expressions

**PHP 8.5+ | Core** | [PHP documentation](https://wiki.php.net/rfc/closures_in_const_expr)

First-class callables and restricted static closures are allowed in constant expressions, including defaults and constants.

```php
function normalize_name( string $name, Closure $normalizer = trim(...) ): string
{
	return $normalizer( $name );
}

echo normalize_name( ' Ada ' ); # Ada
```

They create callbacks rather than execute them; closures must be static and cannot capture variables with `use`.

<a id="recursive-closures"></a>
## Recursion with `Closure::getCurrent()`

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/closure.getcurrent.php)

`Closure::getCurrent()` obtains the executing closure for recursion without capturing its variable by reference.

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

echo $factorial( 5 ); # 120
```

Calling it outside a closure throws `Error`.

<a id="trailing-commas"></a>
## Trailing commas in calls and declarations

**PHP 7.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arguments.php)

Trailing commas are supported in calls from PHP 7.3, and in parameter declarations and closure `use` lists from PHP 8.0.

```php
echo implode(
	',',
	['one', 'two'],
);
# one,two
```

They do not add another argument.

<a id="numeric-literals"></a>
## Readable numeric literals

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.types.integer.php)

PHP 7.4 permits `_` separators between digits; PHP 8.1 adds explicit octal notation such as `0o644`, equivalent to `0644` (decimal `420`).

```php
$max_bytes = 10_485_760;
$mask = 0b1111_0000;
```

Separators are source syntax, not a general string-to-number format.

<a id="group-use"></a>
## Grouped namespace imports

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.namespaces.importing.php)

Grouped imports share a namespace prefix across several names.

```php
use App\Report\{CsvWriter, JsonWriter};

$writers = [CsvWriter::class, JsonWriter::class];
```

Importing a name does not load or instantiate its class.

<a id="closure-call"></a>
## Temporarily binding a closure with `call()`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/closure.call.php)

`Closure::call()` invokes a closure with a temporary `$this` object and its class scope.

```php
class Counter
{
	private $value = 7;
}

$read = function(): int
{
	return $this->value;
};
echo $read->call( new Counter() ); # 7
```

A static closure cannot be bound to `$this`.

<a id="static-initializers"></a>
## Expressions in static local initializers

**PHP 8.3+ | Core** | [PHP documentation](https://wiki.php.net/rfc/arbitrary_static_variable_initializers)

Function-local static initializers may use dynamic expressions, evaluated when execution first reaches the declaration.

```php
function next_number(): int
{
	static $number = strlen( 'seed' );
	return ++$number;
}

echo next_number(), ':', next_number(); # 5:6
```

Later function calls reuse the stored value in the current process; it is not a cache shared across separate PHP requests.

<a id="exit-callable"></a>
## `exit()` and `die()` behave more like functions

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration84.incompatible.php)

`exit()` and its alias `die()` support callables and normal argument type rules, including `strict_types`.

```php
$terminate = exit(...);
$is_closure = $terminate instanceof Closure;
```

An integer is an exit status and a string is printed; invalid argument types now throw `TypeError`.

<a id="casts-in-constant-expressions"></a>
## Type casts in constant expressions

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.constants.syntax.php)

Explicit type casts are allowed in constant expressions.

```php
const WHOLE_SECONDS = (int)3.9;

echo WHOLE_SECONDS; # 3
```

Conversion rules are unchanged: `(int)3.9` truncates to `3`.

<a id="global-constant-attributes"></a>
## Attributes on global constants

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration85.new-features.php)

Attributes may annotate global constants declared with `const`; reading this deprecated constant emits a diagnostic.

```php
#[Deprecated( 'Use DEFAULT_PAGE_SIZE' )]
const OLD_PAGE_SIZE = 20;
const DEFAULT_PAGE_SIZE = 20;
```

Constants created with `define()` do not support this syntax.
