# Types

[EN](../en/02-types.md) | [UK](../uk/02-types.md) | [RU](../ru/02-types.md)

[Contents](../../README.md)

- [Parameter and return types](#scalar-types)
- [Keep types strict with `strict_types`](#strict-types)
- [Allow `null` with `?T`](#nullable)
- [Do something without returning a value: `void`](#void)
- [Accept iterable values and objects](#iterable-object)
- [Give a property a type](#typed-properties)
- [Refine types in a child class](#variance)
- [Allow one of several types: `A|B`](#union)
- [Accept any value with `mixed`](#mixed)
- [Return the right child class with `static`](#static-return)
- [Require both types with `A&B`](#intersection)
- [A function that does not return: `never`](#never)
- [Combine requirements and alternatives: `(A&B)|C`](#dnf)
- [Return exactly `true`, `false` or `null`](#literal-types)
- [Give a class constant a type](#typed-constants)
- [What PHP checks and what PHPDoc adds](#native-type-limits)

<a id="scalar-types"></a>
## Parameter and return types

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/migration70.new-features.php)

The declaration tells us what to pass in and what comes back. Here the price is a `float`, the quantity is an `int` and the result is a `float`. PHP checks those types itself, so repeating them in PHPDoc does not add the same runtime check.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

The scalar types are `int`, `float`, `string` and `bool`. Whether PHP converts a value to the declared type depends on `strict_types`.

<a id="strict-types"></a>
## Keep types strict with `strict_types`

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

The string `'3'` looks like a number, but it is still a string. Turn on `strict_types` at the start of the file if PHP should not silently accept it for an `int` parameter. This call then throws `TypeError`.

<!-- example: fragment -->
```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

double_count( '3' ); # TypeError
```

Argument checks follow the file making the call. Return-value checks follow the file declaring the function. One conversion is still allowed: an `int` can be passed where a `float` is expected.

<a id="nullable"></a>
## Allow `null` with `?T`

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

The name can be a string, or there may be no name. `?string` explicitly allows both. The `= null` default does a separate job: it lets callers leave the argument out entirely.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name(); # Guest
```

That is why calling without a name prints `Guest`. From PHP 8.0, `string|null` is another way to write the type. The old implicit form `string $name = null` is deprecated from PHP 8.4.

<a id="void"></a>
## Do something without returning a value: `void`

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/language.types.void.php)

This function only prints a status; callers do not need a value back. That is what `void` says. If the status is hidden, a plain `return;` lets the function finish early.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}
```

`return null;` is not allowed, because that explicitly returns a value too.

<a id="iterable-object"></a>
## Accept iterable values and objects

**PHP 7.1 / 7.2+** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

If a function only needs `foreach`, it does not have to demand an array. From PHP 7.1, `iterable` accepts arrays and `Traversable` objects, including generators. The sum here does not depend on how the values are stored.

```php
function sum_values( iterable $values ): int
{
	$total = 0;

	foreach( $values as $value )
		$total += $value;

	return $total;
}

echo sum_values( [2, 3, 5] ); # 10
```

`iterable` does not check the types of its elements. The separate `object` type, added in PHP 7.2, accepts any object without requiring a particular class.

<a id="typed-properties"></a>
## Give a property a type

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.properties.php)

A property can declare its type directly. Declaring it and giving it a value are separate steps, though. Here `id` has no value yet, while `email` already holds `null`. The `?` alone does not assign `null`.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();
var_dump( isset($profile->id), $profile->email ); # false, null
```

Reading `id` before assigning it throws `Error`. `isset()` is safe, but returns `false` for both an uninitialized property and `null`. Properties cannot use the `callable` type; use `Closure` when storing a closure.

<a id="variance"></a>
## Refine types in a child class

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.variance.php)

An overriding method can accept more kinds of input and return a more specific result. These changes are called parameter contravariance and return-type covariance. Here we accept any `object` instead of only `stdClass`, but promise to return a `stdClass`.

```php
interface Factory
{
	public function create( stdClass $data ): object;
}

class CustomFactory implements Factory
{
	public function create( object $data ): stdClass
	{
		return new stdClass();
	}
}
```

Anything accepted by the original method is still accepted by the new one. PHP 7.2 already allowed removing a parameter type entirely. Ordinary properties that can be both read and written do not have that flexibility: their type must stay invariant.

<a id="union"></a>
## Allow one of several types: `A|B`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.type-system.php)

Use `|` to list the allowed alternatives. A position lookup, for example, returns an integer or `false` when nothing is found. `int|false` says that directly.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

echo find_position( 'php', 'p' ); # 0
```

The position found here is zero, which is a successful result. Use `!== false` to distinguish it from failure. `false` is allowed in a union from PHP 8.0 and as a standalone type from PHP 8.2.

<a id="mixed"></a>
## Accept any value with `mixed`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.mixed.php)

Some functions really do need to accept anything, such as a value inspector or a general-purpose container. `mixed` makes that explicit: every PHP value is accepted, including `null`.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

There is no need to add `null` again. `?mixed` and `mixed|null` are invalid because `mixed` already includes it.

<a id="static-return"></a>
## Return the right child class with `static`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

The creation method is declared in `Record`, but we call it through `UserRecord`. A `static` return type follows the class used for the call, so the child does not need its own method just to describe the result correctly.

```php
class Record
{
	public static function create(): static
	{
		return new static();
	}
}

class UserRecord extends Record {}

echo get_class( UserRecord::create() ); # UserRecord
```

`self` instead refers to the class declaring the method. From PHP 8.5, an override in a `final` class can replace a `static` return type with `self` or that class’s name.

<a id="intersection"></a>
## Require both types with `A&B`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

Sometimes we need both capabilities, not either one. `Countable&Iterator` requires an object that supports iterator methods and lets us count its elements.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

`ArrayIterator` meets both requirements. Intersections work with object types; scalar combinations such as `int&string` are not allowed.

<a id="never"></a>
## A function that does not return: `never`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.never.php)

Use `never` when a function always throws, exits the process or keeps looping forever. It tells callers that normal execution will not continue at the next line after the call.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}
```

Every call in this example throws. Even a bare `return;` is forbidden, and reaching the end of the function would throw `TypeError`.

<a id="dnf"></a>
## Combine requirements and alternatives: `(A&B)|C`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/migration82.new-features.php)

Combine `&` and `|` when a parameter can be an object with both required capabilities or a different alternative. This form is called DNF. Here we accept an object that is both `Countable` and `Iterator`, or `null` when there is no collection.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Parentheses around the `&` group are required. You cannot rearrange the groups freely: PHP does not accept `A&(B|C)`.

<a id="literal-types"></a>
## Return exactly `true`, `false` or `null`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/language.types.singleton.php)

A return type can allow one specific value instead of any `bool`. For example, `true` fits an operation that returns `true` on success and throws on failure.

```php
function confirm_ready(): true
{
	return true;
}

function missing_value(): null
{
	return null;
}
```

If both `true` and `false` are allowed, write `bool`; `true|false` is not accepted. A `null` return type still returns a particular value, so it is different from `void`.

<a id="typed-constants"></a>
## Give a class constant a type

**PHP 8.3+** | [PHP](https://www.php.net/manual/en/language.oop5.constants.php)

A class constant can declare a type too. If a child class overrides it, the new declaration must stay type-compatible. That keeps the expected kind of value consistent through inheritance.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}
```

This also works for constants in interfaces, traits and enums. An ordinary global `const` cannot declare its type this way.

<a id="native-type-limits"></a>
## What PHP checks and what PHPDoc adds

**PHP 7.0-8.5 | Background** | [PHP](https://www.php.net/manual/en/language.types.declarations.php)

An `array` declaration only tells PHP to expect an array. It does not check the keys or the types of the elements. PHPDoc such as `list<User>` helps an editor or static analyzer understand that it contains a list of users, but PHP itself does not enforce that annotation.

```php
/** @param list<User> $users */
function take_users( array $users ) {}
```

The same applies to `string`: it may still contain an invalid email, an untrusted path or unsafe HTML. Types catch some mistakes; the contents still need checking where the value is used.
