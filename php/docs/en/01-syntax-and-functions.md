# Syntax and functions

[EN](../en/01-syntax-and-functions.md) | [UK](../uk/01-syntax-and-functions.md) | [RU](../ru/01-syntax-and-functions.md)

[Contents](../../README.md)

**On this page**

- [A fallback value with `??`](#null-coalescing)
- [Fill in settings with `??=`](#null-coalescing-assignment)
- [Sort with `<=>`](#spaceship)
- [Short functions with `fn`](#arrow-functions)
- [Pass arguments by name](#named-arguments)
- [Argument names from array keys](#unpacking-named-arguments)
- [Add a named argument after unpacking](#named-after-unpacking)
- [Choose a value with `match`](#match)
- [Read an object chain with `?->`](#nullsafe)
- [Throw directly from an expression](#throw-expression)
- [Make a closure with `Closure::fromCallable()`](#closure-from-callable)
- [Get a function with `function_name(...)`](#first-class-callables)
- [Process a value step by step with `|>`](#pipe)
- [Use a function as a default value](#closures-in-constant-expressions)
- [Let a closure call itself](#recursive-closures)
- [A comma after the last argument](#trailing-commas)
- [Make long numbers easier to read](#numeric-literals)
- [Group related `use` imports](#group-use)
- [Call a closure with a chosen `$this`](#closure-call)
- [Initialize a local `static` value on first use](#static-initializers)
- [Pass `exit()` around as a function](#exit-callable)
- [Cast a value when declaring a constant](#casts-in-constant-expressions)
- [Add an attribute to a global constant](#global-constant-attributes)

<a id="null-coalescing"></a>
## A fallback value with `??`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce)

If the key is missing or holds `null`, take the fallback. Keep zero, `false` and an empty string as they are; those can be perfectly valid values.

```php
$config = ['limit' => 0, 'label' => null];
$limit = $config['limit'] ?? 20;
$label = $config['label'] ?? 'Untitled';

echo $limit, ':', $label; # 0:Untitled
```

Here `limit` stays at zero, while `label` falls back to `Untitled`. Use `array_key_exists()` if a missing key and a key holding `null` mean different things. When combining `??` with string concatenation, put parentheses around the part you want evaluated together.

<a id="null-coalescing-assignment"></a>
## Fill in settings with `??=`

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.assignment.php)

Use the fallback and write it back in one step. This is handy for filling gaps in a settings array without a separate `if` for each key.

```php
$config = ['retries' => 0];
$config['timeout'] ??= 30;
$config['retries'] ??= 3;

echo $config['timeout'], ':', $config['retries']; # 30:0
```

`timeout` gets `30`, while `retries` stays at zero. PHP only evaluates the right side when it needs to assign it: the key is missing or its value is `null`.

<a id="spaceship"></a>
## Sort with `<=>`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.comparison.php)

A sorting callback needs to say whether one value is smaller, equal or larger. `<=>` returns the required `-1`, `0` or `1` without a chain of conditions.

```php
$rows = [['score' => 8], ['score' => 2], ['score' => 5]];
usort( $rows, function( array $left, array $right ): int
{
	return $left['score'] <=> $right['score'];
} );

echo implode( ',', array_column($rows, 'score') ); # 2,5,8
```

The rows here end up in ascending `score` order. Swap `$left` and `$right` to reverse it. This uses PHP comparison rules rather than strict `===`, so decide how to handle mixed types before sorting them.

<a id="arrow-functions"></a>
## Short functions with `fn`

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arrow.php)

When a function only needs one expression, `fn` keeps it compact. Outer variables are available without `use`, using the values they had when the function was created.

```php
$factor = 10;
$multiply = fn( int $value ): int => $value * $factor;
$factor = 20;
echo $multiply( 3 ); # 30
```

Changing `$factor` to `20` afterwards does not change this result: it is still `30`. Objects are not cloned, though; the function still has the same object instance. Use a regular closure when you need to capture a variable by reference, and `static fn` when you do not need a bound `$this`.

<a id="named-arguments"></a>
## Pass arguments by name

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

Name the parameter you want to change and skip the optional ones before it. Here we set `limit` and leave `separator` at its default.

```php
function describe_item( string $name, string $separator = ':', int $limit = 20 ): string
{
	return $name . $separator . $limit;
}

$label = describe_item( 'Report', limit: 5 );

echo $label; # Report:5
```

Regular positional arguments must come before named ones. Parameter names now matter to callers too: rename `limit` in the function, and an existing call using `limit:` will break.

<a id="unpacking-named-arguments"></a>
## Argument names from array keys

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/function.call-user-func-array.php)

When an array is unpacked with `...`, string keys become argument names. That is why `end` can appear before `start` in this array and the function still receives `start = 3` and `end = 9`. The same rule applies to `call_user_func_array()`.

```php
function format_range( int $start, int $end ): string
{
	return $start . '-' . $end;
}

$arguments = ['end' => 9, 'start' => 3];
echo format_range( ...$arguments ); # 3-9
```

An unknown name or a parameter supplied twice causes an error. If the keys are just labels and you want positional arguments, remove the keys with `array_values()` first.

<a id="named-after-unpacking"></a>
## Add a named argument after unpacking

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arguments.php)

Take some arguments from an array and supply another one by name. Here `left` and `right` come from `$parts`, while the separator is written directly in the call.

```php
function join_parts( string $left, string $right, string $separator = '/' ): string
{
	return $left . $separator . $right;
}

$parts = ['docs', 'types'];
echo join_parts( ...$parts, separator: ':' ); # docs:types
```

You cannot supply the same parameter twice. If the array already supplies `separator`, adding `separator:` will cause an error.

<a id="match"></a>
## Choose a value with `match`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/control-structures.match.php)

`match` is useful when one value determines another. Its comparisons are strict: the string `'1'` and the integer `1` are different cases. The selected arm returns a value without falling through to another arm.

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

That gives us `text` here. If nothing matches and there is no `default`, PHP throws `UnhandledMatchError`. Keep that in mind when replacing `switch`, which uses loose comparisons.

<a id="nullsafe"></a>
## Read an object chain with `?->`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.nullsafe)

The profile may be missing, and so may its address. `?->` lets us follow that chain without a separate `if` at each step. On reaching `null`, it stops and returns `null`, so `??` can supply the text to display.

```php
$profile = null;
$city = $profile?->address?->city ?? 'Not provided';

echo $city; # Not provided
```

Use `?->` at each step where the object can actually be `null`. It supports reading, not writing properties. It does not hide invalid access on an existing object or exceptions from methods you call.

<a id="throw-expression"></a>
## Throw directly from an expression

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/exceptions)

If the value is required, put `throw` on the right of `??`. The example has a token, so nothing is thrown. A missing `token` key or a `null` value would throw the exception instead.

```php
$config = ['token' => 'local-example'];
$token = $config['token'] ?? throw new InvalidArgumentException( 'Token is required' );
```

An empty string still passes this check. We are checking that a value is present, not that it is suitable for the next operation.

<a id="closure-from-callable"></a>
## Make a closure with `Closure::fromCallable()`

**PHP 7.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/closure.fromcallable.php)

Turn a function or an accessible method into a `Closure`, store it in a variable and call it later. Here `add_prefix` becomes the function we call through `$format`.

```php
function add_prefix( string $name ): string
{
	return 'note:' . $name;
}

$format = Closure::fromCallable( 'add_prefix' );

echo $format( 'types' ); # note:types
```

Access is checked when the closure is created. An invalid callable or one that is not accessible here throws `TypeError`. From PHP 8.1, [first-class callable syntax](#first-class-callables) gives you a shorter way to do the same thing.

<a id="first-class-callables"></a>
## Get a function with `function_name(...)`

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)

Three dots with no arguments mean: get this function instead of calling it. The result is a `Closure` you can pass to something like `array_map()`. For methods, PHP checks access when creating the callable and keeps that access for later calls.

```php
$normalize = trim(...);
$values = array_map( $normalize, [' one ', ' two '] );

echo implode( ',', $values ); # one,two
```

This is different from unpacking an array of arguments. You cannot combine it with `?->` or use `new ClassName(...)` to make a constructor callable.

<a id="pipe"></a>
## Process a value step by step with `|>`

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.operators.functional.php)

Write a sequence of operations from top to bottom instead of nesting function calls. `|>` passes the value on its left to the function on its right, then sends that result to the next step. Here we trim the edges, change the case and replace the space in the middle.

```php
$slug = ' PHP Notes '
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $text ): string => str_replace(' ', '-', $text));

echo $slug; # php-notes
```

The function on the right receives one argument. For extra parameters or a different argument position, wrap the call in a closure, as with `str_replace()` here. The steps run in sequence, synchronously.

<a id="closures-in-constant-expressions"></a>
## Use a function as a default value

**PHP 8.5+ | Core** | [PHP documentation](https://wiki.php.net/rfc/closures_in_const_expr)

A default value or constant can now hold a first-class callable or a permitted static closure. Here `trim(...)` supplies a ready-to-use handler, so a normal call to `normalize_name()` does not need to pass one.

```php
function normalize_name( string $name, Closure $normalizer = trim(...) ): string
{
	return $normalizer( $name );
}

echo normalize_name( ' Ada ' ); # Ada
```

The declaration creates the callback; it does not execute it. Closures used here have restrictions: they must be `static` and cannot capture outer variables with `use`.

<a id="recursive-closures"></a>
## Let a closure call itself

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/closure.getcurrent.php)

A closure has no regular function name, but it can get itself through `Closure::getCurrent()` when it needs recursion. There is no need to capture `$factorial` by reference beforehand.

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

Each call here multiplies its number by the next call’s result until it reaches `1`. Calling `Closure::getCurrent()` outside an executing closure throws `Error`.

<a id="trailing-commas"></a>
## A comma after the last argument

**PHP 7.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/functions.arguments.php)

You can leave the last comma in place. That makes adding lines and reading Git diffs easier. Calls allow it from PHP 7.3; parameter declarations and closure `use` lists allow it from PHP 8.0.

```php
echo implode(
	',',
	['one', 'two'],
);
# one,two
```

The comma does not create an extra argument. `implode()` still receives two arguments here.

<a id="numeric-literals"></a>
## Make long numbers easier to read

**PHP 7.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.types.integer.php)

It is easy to miss a zero in a long number. From PHP 7.4, `_` can separate digits without changing the value. PHP 8.1 also adds the explicit octal form `0o644` alongside `0644`; both mean decimal `420`.

```php
$max_bytes = 10_485_760;
$mask = 0b1111_0000;
```

This is notation for numbers in source code. A string containing these separators is not automatically a valid format for converting to a number.

<a id="group-use"></a>
## Group related `use` imports

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.namespaces.importing.php)

When several classes share a namespace, write the common part once. Put only the different names inside the braces.

```php
use App\Report\{CsvWriter, JsonWriter};

$writers = [CsvWriter::class, JsonWriter::class];
```

`use` only shortens names in this file. It does not load the classes or create objects by itself.

<a id="closure-call"></a>
## Call a closure with a chosen `$this`

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/closure.call.php)

`call()` gives a closure an object to use as `$this` for one call. It also gives the closure that object’s class scope, which is why it can read the private `value` here.

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

The binding lasts for this call. It does not work with a static closure, because a static closure cannot have `$this`.

<a id="static-initializers"></a>
## Initialize a local `static` value on first use

**PHP 8.3+ | Core** | [PHP documentation](https://wiki.php.net/rfc/arbitrary_static_variable_initializers)

The initial value of a local `static` variable can now come from an expression or function call. PHP evaluates it when execution first reaches the declaration, not on every call to the function.

```php
function next_number(): int
{
	static $number = strlen( 'seed' );
	return ++$number;
}

echo next_number(), ':', next_number(); # 5:6
```

Here the length of `seed` is calculated once, then the counter keeps increasing: `5:6`. The value survives calls during the current PHP execution. This does not create a shared cache across separate requests.

<a id="exit-callable"></a>
## Pass `exit()` around as a function

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration84.incompatible.php)

`exit()` and its alias `die()` can now be used where a callable is expected. In the example, `exit(...)` only creates a closure; it does not stop execution on that line.

```php
$terminate = exit(...);
$is_closure = $terminate instanceof Closure;
```

Argument types now follow normal function rules, including `strict_types`. An integer sets the exit status, while a string is printed before exiting. An invalid argument type now throws `TypeError`.

<a id="casts-in-constant-expressions"></a>
## Cast a value when declaring a constant

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.constants.syntax.php)

A constant expression can now include an explicit type cast. Here we keep the integer part of `3.9` and store `3`.

```php
const WHOLE_SECONDS = (int)3.9;

echo WHOLE_SECONDS; # 3
```

The conversion rules themselves have not changed. `(int)` discards the fractional part rather than rounding to the nearest integer.

<a id="global-constant-attributes"></a>
## Add an attribute to a global constant

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration85.new-features.php)

Attributes can now go before a global `const` declaration too. For example, mark an old constant name with `#[Deprecated]` so using it points callers towards its replacement.

```php
#[Deprecated( 'Use DEFAULT_PAGE_SIZE' )]
const OLD_PAGE_SIZE = 20;
const DEFAULT_PAGE_SIZE = 20;
```

Reading `OLD_PAGE_SIZE` emits a deprecation diagnostic. Constants created with `define()` do not support this attribute syntax.
