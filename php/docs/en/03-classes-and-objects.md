# Classes and objects

[English](../en/03-classes-and-objects.md) | [Українська](../uk/03-classes-and-objects.md) | [Русский](../ru/03-classes-and-objects.md)

[Contents](../../README.md)

**On this page**

- [Anonymous classes](#anonymous-classes)
- [Declare properties in the constructor](#constructor-promotion)
- [Objects as default parameter values](#new-initializers)
- [Attributes and reading their metadata](#attributes)
- [The Stringable interface](#stringable)
- [Enums for a closed set of states](#enums)
- [Enum properties in constant expressions](#enum-constants)
- [Readonly properties and shallow immutability](#readonly-properties)
- [Readonly classes](#readonly-classes)
- [Reinitialize readonly properties during __clone()](#readonly-cloning)
- [Readonly anonymous classes](#readonly-anonymous)
- [Visibility and final class constants](#constant-visibility-final)
- [A class constant selected by name](#dynamic-constants)
- [Constants declared in traits](#trait-constants)
- [Import a trait method as final](#trait-final)
- [Check overridden methods with #[Override]](#override)
- [Property hooks: backed and virtual values](#property-hooks)
- [A full setter must assign the backing value](#setter-validation)
- [Different visibility for reading and writing](#asymmetric-visibility)
- [Property contracts in interfaces](#interface-properties)
- [Lazy ghosts and lazy proxies](#lazy-objects)
- [Call a method directly after new](#new-chaining)
- [Check property overrides with #[Override]](#override-properties)
- [Asymmetric visibility for static properties](#static-visibility)
- [Final promoted properties](#final-promotion)
- [Clone an object with selected property changes](#clone-with)
- [Dynamic properties are deprecated](#dynamic-properties)

<a id="anonymous-classes"></a>
## Anonymous classes

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.anonymous.php)

Anonymous classes create local implementations and can extend a class or implement an interface.

```php
$provider = new class
{
	public function label(): string
	{
		return 'draft';
	}
};

echo $provider->label(); # draft
```

<a id="constructor-promotion"></a>
## Declare properties in the constructor

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

A constructor parameter with visibility declares and initializes a property.

```php
class User
{
	public function __construct(
		public int $id,
		public string $name,
	) {}
}

$user = new User( 7, 'Ada' );
echo $user->id, ':', $user->name; # 7:Ada
```

Promoted and ordinary parameters may coexist; assignment happens before the constructor body.

<a id="new-initializers"></a>
## Objects as default parameter values

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration81.new-features.php)

`new` is allowed in parameter defaults, attribute arguments, local static initializers and global constants.

```php
class Prefix
{
	public function __construct( public string $value = 'note' ) {}
}

function label( string $text, Prefix $prefix = new Prefix() ): string
{
	return $prefix->value . ':' . $text;
}

echo label( 'types' ); # note:types
```

A used parameter default creates an object for that call; ordinary property defaults still cannot use `new`.

<a id="attributes"></a>
## Attributes and reading their metadata

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.attributes.overview.php)

Attributes provide reflection-readable metadata; target flags and `IS_REPEATABLE` constrain usage, checked by `newInstance()`.

```php
#[Attribute( Attribute::TARGET_METHOD )]
class Description
{
	public function __construct( public string $text ) {}
}

class Report
{
	#[Description( 'Exports a report' )]
	public function export(): void {}
}

$method = new ReflectionMethod( Report::class, 'export' );
$description = $method->getAttributes( Description::class )[0]->newInstance();

echo $description->text; # Exports a report
```

PHP 8.5 adds `#[DelayedTargetValidation]`, deferring invalid-target checks for built-in or extension attributes until reflection instantiates them; see also [diagnostic attributes](08-errors-and-debugging.md#deprecated-attribute) and [global constants](01-syntax-and-functions.md#global-constant-attributes).

<a id="stringable"></a>
## The Stringable interface

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.stringable.php)

A class with `__toString()` implicitly implements `Stringable`; `string|Stringable` accepts either representation.

```php
class Label
{
	public function __toString(): string
	{
		return 'draft';
	}
}

function render_label( string|Stringable $value ): string
{
	return (string)$value;
}

echo render_label( new Label() );
# draft
```

`Stringable` is not `string` under strict typing, and conversion [may throw](08-errors-and-debugging.md#to-string-exceptions).

<a id="enums"></a>
## Enums for a closed set of states

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.enumerations.backed.php)

Enums define named case objects; backed enums add unique `int` or `string` values through `->value`, while all cases have `->name`.

```php
enum Status: string
{
	case Draft = 'draft';
	case Published = 'published';
}

$status = Status::from( 'published' );
$missing = Status::tryFrom( 'missing' ); # null
$cases = Status::cases();

echo $status->name, ':', $status->value, PHP_EOL; # Published:published
var_dump( $missing ); # NULL
echo count( $cases ); # 2
```

`from()` throws `ValueError` for unknown values and `tryFrom()` returns `null` but may still throw `TypeError` for invalid types; pure enums have no backing value or conversion methods.

<a id="enum-constants"></a>
## Enum properties in constant expressions

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration82.new-features.php)

Enum case `name` and backed case `value` may be read in constant expressions.

```php
enum Format: string
{
	case Json = 'json';
}

const DEFAULT_FORMAT = Format::Json->value;
const DEFAULT_CASE_NAME = Format::Json->name;

echo DEFAULT_CASE_NAME, ':', DEFAULT_FORMAT; # Json:json
```

This does not allow arbitrary object property reads or method calls in constants.

<a id="readonly-properties"></a>
## Readonly properties and shallow immutability

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

A typed, non-static `readonly` property permits initialization once, but an object stored in it can remain mutable.

```php
class Snapshot
{
	public function __construct( public readonly stdClass $data ) {}
}

$snapshot = new Snapshot( (object)['count' => 1] );
$snapshot->data->count++;

echo $snapshot->data->count; # 2
```

Before PHP 8.4 initialization was implicitly private to the declaring class; from 8.4 the default is `protected(set)` unless narrowed, while scalar increments and array-entry writes remain forbidden.

<a id="readonly-classes"></a>
## Readonly classes

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

A readonly class makes all instance properties readonly.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y; # 3:4
```

Properties must be typed, static and dynamic properties are forbidden, inheritance must obey readonly rules, and nested objects can still be mutable.

<a id="readonly-cloning"></a>
## Reinitialize readonly properties during __clone()

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration83.new-features.php)

`__clone()` may reinitialize readonly properties to detach nested objects in the copy.

```php
readonly class Schedule
{
	public function __construct( public DateTime $date ) {}

	public function __clone(): void
	{
		$this->date = clone $this->date;
	}
}

$original = new Schedule( new DateTime('2025-01-01') );
$copy = clone $original;
$copy->date->modify( '+1 day' );

echo $original->date->format( 'Y-m-d' ), ':', $copy->date->format( 'Y-m-d' );
# 2025-01-01:2025-01-02
```

This is a controlled cloning-time exception; unlisted nested objects remain shared by an ordinary shallow clone.

<a id="readonly-anonymous"></a>
## Readonly anonymous classes

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration83.new-features.php)

Anonymous classes may also be readonly.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id; # 7
```

They retain the typed-property and shallow-immutability restrictions of named readonly classes.

<a id="constant-visibility-final"></a>
## Visibility and final class constants

**PHP 7.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.constants.php)

PHP 7.1 adds class-constant visibility; PHP 8.1 adds declarations such as `final public const PAGE_SIZE = 20;` to forbid redeclaration in children.

```php
class Limits
{
	private const INTERNAL_FACTOR = 2;
	public const PAGE_SIZE = 20;
}

echo Limits::PAGE_SIZE; # 20
```

[Typed class constants](02-types.md#typed-constants) arrive separately in PHP 8.3.

<a id="dynamic-constants"></a>
## A class constant selected by name

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.constants.php)

`ClassName::{$name}` reads a class constant selected by name; `$object::class` has returned an object’s class name since PHP 8.0.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
$mime = Format::{$name};

echo $mime; # application/json
```

Constant visibility still applies, and unknown names produce an error.

<a id="trait-constants"></a>
## Constants declared in traits

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.traits.php)

Traits may declare constants, accessed through the consuming class, with compatibility rules for conflicting declarations.

```php
trait HasFormat
{
	public const FORMAT = 'json';
}

class Exporter
{
	use HasFormat;
}

$format = Exporter::FORMAT;

echo $format; # json
```

Since PHP 8.3, a child explicitly reusing a trait has separate static trait-property storage from its parent.

<a id="trait-final"></a>
## Import a trait method as final

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.traits.php)

A trait adaptation may import a method as `final`.

```php
trait HasIdentifier
{
	public function identifier(): string
	{
		return 'report';
	}
}

class Report
{
	use HasIdentifier
	{
		identifier as final;
	}
}

echo (new Report())->identifier(); # report
```

This prevents overriding that method in subclasses of the consuming class without changing the trait itself.

<a id="override"></a>
## Check overridden methods with #[Override]

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.override.php)

`#[Override]` verifies at compile time that a method overrides an inherited or interface declaration.

```php
interface Exporter
{
	public function format(): string;
}

class JsonExporter implements Exporter
{
	#[Override]
	public function format(): string
	{
		return 'json';
	}
}

echo (new JsonExporter())->format(); # json
```

It cannot annotate `__construct()`; [property overrides](#override-properties) gain support in PHP 8.5.

<a id="property-hooks"></a>
## Property hooks: backed and virtual values

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Property hooks customize reads and writes: `name` is backed, while `display_name` is virtual.

```php
class Contact
{
	public string $name
	{
		set => trim( $value );
	}

	public string $display_name
	{
		get => strtoupper( $this->name );
	}
}

$contact = new Contact();
$contact->name = ' Ada ';
echo $contact->name, ':', $contact->display_name; # Ada:ADA
```

Expression `set` stores its result automatically; virtual get-only properties are unwritable, and hooks cannot be static or combined with `readonly`.

<a id="setter-validation"></a>
## A full setter must assign the backing value

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.property-hooks.php)

A block-form `set` hook must explicitly assign the accepted value.

```php
class Product
{
	public int $quantity = 0
	{
		set
		{
			if( $value < 0 )
				throw new InvalidArgumentException( 'Negative quantity' );

			$this->quantity = $value;
		}
	}
}

$product = new Product();
$product->quantity = 5;
echo $product->quantity; # 5
```

Inside its own hook, `$this->quantity` accesses backing storage without recursion; rejecting before assignment preserves the previous value.

<a id="asymmetric-visibility"></a>
## Different visibility for reading and writing

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

`public private(set)` permits public reading and repeated writes inside the declaring class.

```php
class Article
{
	public function __construct( public private(set) string $title ) {}

	public function rename( string $title ): void
	{
		$this->title = trim( $title );
	}
}

$article = new Article( 'Draft' );
$article->rename( ' Published ' );
echo $article->title; # Published
```

Write visibility cannot exceed read visibility, `private(set)` makes the property final, and references or array-entry changes also require write access.

<a id="interface-properties"></a>
## Property contracts in interfaces

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.interfaces.php)

Interfaces can require public property reads or writes through `{ get; }` or `{ get; set; }`.

```php
interface HasTitle
{
	public string $title { get; }
}

class Article implements HasTitle
{
	public function __construct( public string $title ) {}
}

$article = new Article( 'Notes' );
echo $article->title; # Notes
```

A normal property or hooks may satisfy the contract, subject to type-variance rules. A `get` requirement still allows the implementation to provide writes.

<a id="lazy-objects"></a>
## Lazy ghosts and lazy proxies

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

Reflection can defer an object’s initialization until its state is used. A ghost initializes in place; a proxy obtains a separate real instance from its factory.

```php
class Document
{
	public function __construct( public string $title )
	{
		echo 'initialize:', $title, PHP_EOL;
	}
}

$reflection = new ReflectionClass( Document::class );
$ghost = $reflection->newLazyGhost( static function( Document $document ): void
{
	$document->__construct( 'ghost' );
} );
$proxy = $reflection->newLazyProxy( static function( Document $document ): Document
{
	return new Document( 'proxy' );
} );

echo 'created', PHP_EOL;
echo $ghost->title, PHP_EOL;
echo $proxy->title;
# created
# initialize:ghost
# ghost
# initialize:proxy
# proxy
```

Proxy identity differs from the real instance, and support targets user-defined classes and `stdClass`, not arbitrary internal classes.

<a id="new-chaining"></a>
## Call a method directly after new

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration84.new-features.php)

A `new` expression with constructor parentheses can be followed directly by member access.

```php
$date = new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );

echo $date; # 2025-01-02
```

The older `(new ClassName(...))->method()` syntax remains valid.

<a id="override-properties"></a>
## Check property overrides with #[Override]

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.override.php)

`#[Override]` also verifies inherited or interface properties at compile time.

```php
class Record
{
	public string $label
	{
		get => 'record';
	}
}

class Report extends Record
{
	#[Override]
	public string $label
	{
		get => 'report';
	}
}

echo new Report()->label; # report
```

It does not permit incompatible overrides or overriding a final property.

<a id="static-visibility"></a>
## Asymmetric visibility for static properties

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.visibility.php)

Static properties can have narrower write visibility.

```php
class Counter
{
	public private(set) static int $calls = 0;

	public static function count_call(): void
	{
		self::$calls++;
	}
}

Counter::count_call();
echo Counter::$calls; # 1
```

This controls access rather than state lifetime; readonly static properties remain unsupported.

<a id="final-promotion"></a>
## Final promoted properties

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.final.php)

PHP 8.5 permits `final` on promoted properties, extending final properties introduced in PHP 8.4.

```php
class Ticket
{
	public function __construct( final public string $code ) {}
}

$ticket = new Ticket( 'A' );
$ticket->code = 'B';

echo $ticket->code; # B
```

It prevents child redeclarations but does not forbid reassignment like `readonly` does.

<a id="clone-with"></a>
## Clone an object with selected property changes

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.cloning.php)

`clone( $object, $changes )` creates a copy and applies property overrides after `__clone()`.

```php
readonly class Color
{
	public function __construct( public int $alpha )
	{
		if( $alpha < 0 || $alpha > 255 )
			throw new InvalidArgumentException( 'Alpha must be between 0 and 255' );
	}

	public function with_alpha( int $alpha ): self
	{
		if( $alpha < 0 || $alpha > 255 )
			throw new InvalidArgumentException( 'Alpha must be between 0 and 255' );

		return clone( $this, ['alpha' => $alpha] );
	}
}

$original = new Color( 255 );
$copy = $original->with_alpha( 128 );
echo $original->alpha, ':', $copy->alpha;
# 255:128
```

Overrides respect write visibility, so readonly updates need a permitted class scope. The constructor is not rerun: validate changed values explicitly; other nested objects retain shallow-clone behavior.

<a id="dynamic-properties"></a>
## Dynamic properties are deprecated

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)

Creating an undeclared property on an ordinary class emits a deprecation diagnostic.

```php
class User
{
	public string $name = 'Ada';
}

$user = new User();
$user->name = 'Grace';

$metadata = new stdClass();
$metadata->tag = 'draft';

echo $user->name, ':', $metadata->tag;
# Grace:draft
```

Exceptions include `stdClass`, `#[AllowDynamicProperties]` classes and their children, and `__set()` handling, but creating an undeclared property inside `__set()` can still trigger the diagnostic.
