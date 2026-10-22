# Types and contracts

[English](../en/02-types.md) | [Українська](../uk/02-types.md) | [Русский](../ru/02-types.md)

[Contents](../../README.md) | [PHP 7.0-8.5](VERSIONS.md) | [Migration](MIGRATION.md)

From PHP 7 scalar declarations to typed constants and precise contracts. Each section explains what PHP checks and what your application still needs to validate. Version labels describe feature availability, not a recommendation to run an old interpreter.

## Contents

- [Scalar parameters and return types](#scalar-types)
- [strict_types: control scalar coercion](#strict-types)
- [Nullable types](#nullable)
- [void: finish without a result](#void)
- [iterable and object](#iterable-object)
- [Typed properties and uninitialized state](#typed-properties)
- [Covariance and contravariance](#variance)
- [Union types: one of several alternatives](#union)
- [mixed: an explicitly unrestricted value](#mixed)
- [static return type](#static-return)
- [Intersection types: all contracts at once](#intersection)
- [never: no normal return](#never)
- [DNF: intersections plus alternatives](#dnf)
- [Standalone true, false and null](#literal-types)
- [Typed class constants](#typed-constants)
- [What a native type does not validate](#native-type-limits)

<a id="scalar-types"></a>
## Scalar parameters and return types

**PHP 7.0+**

Put the contract in the signature: a price is a float, a quantity is an integer, and the result is a float. PHP checks values at the function boundary. This saves repetitive checks inside ordinary application code.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

The scalar names are int, float, string and bool. Use bool, not boolean. A type describes the kind of value; it does not establish that a quantity is positive or a string is a valid email address. Those are separate validation rules.

[PHP documentation](https://www.php.net/manual/en/migration70.new-features.php)

<a id="strict-types"></a>
## strict_types: control scalar coercion

**PHP 7.0+**

By default PHP can convert a compatible scalar argument, such as a numeric string, to the declared type. Enable strict mode when an accidental conversion would hide a programming mistake. The declaration must be at the beginning of the PHP file.

```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

try
{
	double_count( '3' );
}
catch( TypeError $error )
{
	echo 'Expected an integer';
}
```

The caller's file determines strictness for scalar arguments of calls made from that file. Return-value checks follow the file defining the function. An int is still accepted for a float parameter. strict_types is not input validation and does not change === or make every internal operation strict.

[PHP documentation](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

<a id="nullable"></a>
## Nullable types

**PHP 7.1+**

Use ?T when a value can be either T or null. A lookup returning no record is a natural example. Making a parameter nullable does not make that parameter optional.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name();          # Guest
echo display_name( '' );      # empty string
echo display_name( 'Olena' ); # Olena
```

The = null default allows omitting the argument. Without it, the caller must still pass a value, even if that value is null. From PHP 8.0, string|null is another spelling. Implicit nullable declarations such as string $name = null are deprecated in PHP 8.4; write nullability explicitly.

[PHP documentation](https://www.php.net/manual/en/migration71.new-features.php)

<a id="void"></a>
## void: finish without a result

**PHP 7.1+**

void says a function performs an action and does not return a useful value. A bare return is allowed for an early exit. return null is not allowed.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}

print_status( true ); # Ready
```

Choose void for an ordinary operation that can complete normally. Choose never when normal completion is impossible. Neither type can describe values stored in a property.

[PHP documentation](https://www.php.net/manual/en/language.types.void.php)

<a id="iterable-object"></a>
## iterable and object

**PHP 7.1 / 7.2+**

iterable (7.1) accepts an array or an object implementing Traversable, so a function can process both a ready-made list and a generator. object (7.2) accepts any object. Prefer a specific interface when the function needs specific methods.

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

iterable does not validate element types. See [arrays and iteration](04-arrays-and-iteration.md) for generators and keyed streams. object suits genuinely generic tools, not every domain interface.

[PHP documentation](https://www.php.net/manual/en/language.types.iterable.php)

<a id="typed-properties"></a>
## Typed properties and uninitialized state

**PHP 7.4+**

A property can carry its own type. An uninitialized typed property is different from a property containing null. Even a nullable property needs an explicit initial value before it can be read.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();

var_dump( isset($profile->id) ); # false
var_dump( $profile->email );    # NULL

$profile->id = 42;
echo $profile->id;              # 42
```

Reading id before assignment would throw Error. isset() is safe, but does not distinguish uninitialized from null; ReflectionProperty::isInitialized() can. callable is not a permitted property type; Closure is often appropriate for a stored callback.

[PHP documentation](https://www.php.net/manual/en/language.oop5.properties.php)

<a id="variance"></a>
## Covariance and contravariance

**PHP 7.4+**

An overriding method can promise a more specific result and accept a more general argument. The child object must remain usable wherever the parent contract was expected.

```php
class Animal {}
class Cat extends Animal {}

interface Shelter
{
	public function adopt( Cat $candidate ): Animal;
}

class CatShelter implements Shelter
{
	public function adopt( Animal $candidate ): Cat
	{
		return new Cat();
	}
}

echo get_class( (new CatShelter())->adopt(new Cat()) ); # Cat
```

Full support arrived in 7.4; 7.2 already permitted removing a parameter type in an override. Reversing these changes can narrow accepted input or weaken promised output. Ordinary readable and writable properties are generally invariant; property hooks introduce special cases.

[PHP documentation](https://www.php.net/manual/en/language.oop5.variance.php)

<a id="union"></a>
## Union types: one of several alternatives

**PHP 8.0+**

A union uses | to list accepted alternatives. It is useful when an API really has more than one kind of result, such as an integer on success or false on failure.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

$position = find_position( 'php', 'p' );

if( $position !== false )
	echo $position; # 0 is a successful position
```

The union is a runtime contract, unlike a PHPDoc note. Do not turn every uncertain parameter into a large union: normalize external input when the operation needs one clear type. false was allowed inside unions in 8.0; standalone false came later.

[PHP documentation](https://www.php.net/manual/en/language.types.type-system.php)

<a id="mixed"></a>
## mixed: an explicitly unrestricted value

**PHP 8.0+**

mixed explicitly permits every PHP value, including null. A generic container or a function forwarding an unknown value can use it honestly. It does not say what operations are safe on the value.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

mixed already includes null, so ?mixed and mixed|null are invalid. It does not validate decoded JSON or make array-key access safe. Use a narrower type as soon as the actual contract is known.

[PHP documentation](https://www.php.net/manual/en/language.types.mixed.php)

<a id="static-return"></a>
## static return type

**PHP 8.0+**

self refers to the class declaring the method; static follows the class used by the call. This makes inherited named constructors and fluent APIs more precise.

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

static is a return type, not a property or parameter type. Return an instance compatible with the called class. In 8.5 an overriding method in a final class may narrow a parent's static return to self or that final class.

[PHP documentation](https://www.php.net/manual/en/language.types.relative-class-types.php)

<a id="intersection"></a>
## Intersection types: all contracts at once

**PHP 8.1+**

An intersection uses & and requires one object to satisfy every listed class or interface type. Countable&Iterator is useful when code needs both iteration methods and a count.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

It means one object with both capabilities, not either capability or a pair of objects. Scalar intersections such as int&string are invalid. Combining an intersection with null or another alternative needs DNF syntax from 8.2.

[PHP documentation](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

<a id="never"></a>
## never: no normal return

**PHP 8.1+**

A never function must throw, exit, or continue indefinitely. This makes an abort operation visible to readers and analysis tools. Catching its exception outside the function is still possible.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}

try
{
	reject_request();
}
catch( RuntimeException $error )
{
	echo $error->getMessage(); # Request rejected
}
```

A bare return is forbidden too. Reaching the end causes TypeError. Do not use never merely because a function usually fails; a normal successful path needs a normal return type.

[PHP documentation](https://www.php.net/manual/en/language.types.never.php)

<a id="dnf"></a>
## DNF: intersections plus alternatives

**PHP 8.2+**

DNF allows a union of complete intersection groups. A value can be an object that is both Countable and Iterator, or it can be null.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Parentheses around intersection groups are required. Arbitrary arrangements such as A&(B|C) are not accepted. When a contract becomes complicated, a suitable interface can be clearer than continually extending a type expression.

[PHP documentation](https://www.php.net/manual/en/migration82.new-features.php)

<a id="literal-types"></a>
## Standalone true, false and null

**PHP 8.2+**

A literal type promises exactly one value. It suits narrow contracts and compatible overrides; bool remains the right choice for an ordinary yes/no result.

```php
function confirm_ready(): true
{
	return true;
}

function missing_value(): null
{
	return null;
}

var_dump( confirm_ready() ); # bool(true)
var_dump( missing_value() ); # NULL
```

false and null participated in suitable unions from 8.0; 8.2 makes them standalone and adds true. true|false is invalid: write bool. null is a return value, whereas void says there is no useful return value.

[PHP documentation](https://www.php.net/manual/en/language.types.singleton.php)

<a id="typed-constants"></a>
## Typed class constants

**PHP 8.3+**

Constants can express a type as well as a value. This helps contracts inherited by other classes: an override must remain type-compatible.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}

echo Limits::PAGE_SIZE; # 50
```

This applies to class, interface, trait and enum constants. It does not add type-declaration syntax to an ordinary global const. An array constant's native type still does not describe every element.

[PHP documentation](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="native-type-limits"></a>
## What a native type does not validate

**PHP 7.0-8.5 | Background**

array only promises an array, not id/name keys, a list shape or User elements. string does not mean an email, trusted path or safe HTML. Keep shape and business validation close to the boundary where external data enters your code.

```php
function require_positive_id( array $input ): int
{
	$id = $input['id'] ?? null;

	if( !is_int($id) || $id <= 0 )
		throw new InvalidArgumentException( 'Positive integer id required' );

	return $id;
}

echo require_positive_id( ['id' => 42] ); # 42
```

PHPDoc such as `list<User>` helps an IDE or static analyzer, but PHP 7.0-8.5 does not enforce it at runtime. Decide deliberately whether a numeric string from HTTP should be accepted and converted, or rejected. Avoid a cast that silently turns malformed input into a usable-looking zero.

[PHP documentation](https://www.php.net/manual/en/language.types.declarations.php)

[Contents](../../README.md)
