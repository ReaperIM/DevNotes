# Types

[English](../en/02-types.md) | [Українська](../uk/02-types.md) | [Русский](../ru/02-types.md)

[Contents](../../README.md)

- [Scalar parameters and return types](#scalar-types)
- [strict_types](#strict-types)
- [Nullable types](#nullable)
- [void](#void)
- [iterable / object](#iterable-object)
- [Typed properties](#typed-properties)
- [Covariance / contravariance](#variance)
- [Union: A|B](#union)
- [mixed](#mixed)
- [Return type static](#static-return)
- [Intersection: A&B](#intersection)
- [never](#never)
- [DNF: (A&B)|C](#dnf)
- [Standalone true / false / null](#literal-types)
- [Typed constants](#typed-constants)
- [PHPDoc vs native types](#native-type-limits)

<a id="scalar-types"></a>
## Scalar parameters and return types

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/migration70.new-features.php)

Scalar parameter and return types make a function's contract visible in its signature. PHP checks values at the call boundary; a separate PHPDoc type is no longer needed for this.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

The scalar names are `int`, `float`, `string` and `bool`; conversion rules depend on `strict_types`.

<a id="strict-types"></a>
## strict_types

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

Use `strict_types` when a numeric `string` should not silently satisfy an `int` parameter. Place the declaration at the beginning of the PHP file.

<!-- example: fragment -->
```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

double_count( '3' ); # TypeError
```

Argument strictness follows the caller's file; `return` checks follow the defining file. `int` is still accepted for `float`.

<a id="nullable"></a>
## Nullable types

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

`?T` describes a value that may be absent: for example, an optional name or an unsuccessful lookup. The `= null` default separately allows the argument to be omitted.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name(); # Guest
```

`string|null` is another spelling from 8.0. The implicit form `string $name = null` is deprecated from 8.4.

<a id="void"></a>
## void

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/language.types.void.php)

`void` marks an action with no useful `return` value. An early `return` without a value is allowed, but `return null` is not.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}
```

<a id="iterable-object"></a>
## iterable / object

**PHP 7.1 / 7.2+** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

`iterable` (7.1) lets the same function accept an `array` or `Traversable`, including a generator. It is useful when the function only needs to iterate, rather than access `array` indexes. `object` (7.2) accepts any `object`.

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

`iterable` does not constrain element types.

<a id="typed-properties"></a>
## Typed properties

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.properties.php)

A property can declare its own type. A typed property without an initial value remains uninitialized; even a nullable declaration does not automatically assign `null`.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();
var_dump( isset($profile->id), $profile->email ); # false, null
```

Reading id before assignment throws `Error`. `isset()` is safe but does not distinguish uninitialized from `null`. `callable` is not a property type; use `Closure` for a stored closure.

<a id="variance"></a>
## Covariance / contravariance

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.variance.php)

An overriding method may accept a broader input type and `return` a more specific one. Here the implementation accepts any `object` instead of only `stdClass`, while promising `stdClass` instead of a general `object`.

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

7.2 already allowed removing a parameter type. Ordinary read/write properties remain invariant.

<a id="union"></a>
## Union: A|B

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.type-system.php)

When a function genuinely returns more than one kind of value, list the alternatives in its return type. `int|false` matches a position lookup where zero is a valid successful result.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

echo find_position( 'php', 'p' ); # 0
```

`false` is allowed in unions from 8.0, standalone from 8.2. Check failure with !== `false` rather than discarding position zero.

<a id="mixed"></a>
## mixed

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.mixed.php)

`mixed` explicitly accepts every PHP value, including `null`. It fits a generic formatter or container whose contract really does not narrow the input.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

`mixed` already includes `null`, so `?mixed` and `mixed|null` are invalid.

<a id="static-return"></a>
## Return type static

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

`static` follows the class used by the call, while `self` refers to the class declaring the method. This keeps an inherited factory's return type precise for its child classes.

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

8.5: an override in a final class may replace `static` with `self` or the final class name.

<a id="intersection"></a>
## Intersection: A&B

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

A&B requires one `object` to satisfy both contracts. `Countable&Iterator` is useful when a function needs both iteration methods and the item count.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

Scalar intersections such as `int&string` are invalid.

<a id="never"></a>
## never

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.never.php)

`never` says the function cannot `return` normally: it throws, exits or loops indefinitely. It makes an abort path explicit without pretending it returns an ordinary value.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}
```

Even a bare `return` is forbidden; reaching the end throws `TypeError`.

<a id="dnf"></a>
## DNF: (A&B)|C

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/migration82.new-features.php)

DNF combines intersections with alternatives. This signature accepts an `object` that is both `Countable` and `Iterator`, or `null` when there is no collection.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Parentheses around intersection groups are required. `A&(B|C)` is not accepted.

<a id="literal-types"></a>
## Standalone true / false / null

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/language.types.singleton.php)

A return type can promise exactly `true`, `false` or `null`. For example, `true` is useful for an operation that returns success and throws on failure.

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

`true|false` is invalid; use `bool`. `null` is a returned value, unlike `void`.

<a id="typed-constants"></a>
## Typed constants

**PHP 8.3+** | [PHP](https://www.php.net/manual/en/language.oop5.constants.php)

Class constants can declare a type as part of an inherited contract. A child override must remain type-compatible. This also applies to interface, trait and enum constants.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}
```

Ordinary global `const` does not support this type declaration.

<a id="native-type-limits"></a>
## PHPDoc vs native types

**PHP 7.0-8.5 | Background** | [PHP](https://www.php.net/manual/en/language.types.declarations.php)

An `array` declaration checks the container type, not its keys or element types. PHPDoc such as `list<User>` adds useful detail for an IDE or static analyzer, but PHP itself does not enforce that annotation. Likewise, `string` alone does not guarantee a valid email, a trusted path or safe HTML; those checks belong to the application.

```php
/** @param list<User> $users */
function take_users( array $users ) {}
```
