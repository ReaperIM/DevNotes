# Classes and objects

[English](../en/03-classes-and-objects.md) | [Українська](../uk/03-classes-and-objects.md) | [Русский](../ru/03-classes-and-objects.md)

[Contents](../../README.md)

From local anonymous implementations to enums, readonly values, hooks and lazy initialization. Each example is independent. Use the version labels to distinguish the original feature from later improvements mentioned in the text.

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

**PHP 7.0+ | Core**

An anonymous class creates an object whose implementation is needed locally. It can implement an interface or extend another class, making it useful for a small test double or a callback object with state.

```php
interface LabelProvider
{
	public function label(): string;
}

$provider = new class( 'draft' ) implements LabelProvider
{
	private $status;

	public function __construct( string $status )
	{
		$this->status = $status;
	}

	public function label(): string
	{
		return strtoupper( $this->status );
	}
};

echo $provider->label();
# DRAFT
```

Keep a reusable domain object in a named class. Anonymous does not mean untyped or isolated: the object still follows inheritance, visibility and interface rules.

[PHP documentation](https://www.php.net/manual/en/language.oop5.anonymous.php)

<a id="constructor-promotion"></a>
## Declare properties in the constructor

**PHP 8.0+ | Core**

A constructor parameter with a visibility modifier declares a property and assigns the argument to it. This removes the repeated property declaration and `$this->name = $name` assignment for simple data objects.

```php
class User
{
	public function __construct(
		public int $id,
		public string $name,
	) {}
}

$user = new User( 7, 'Ada' );
echo $user->id, ':', $user->name;
# 7:Ada
```

You may mix promoted and ordinary parameters. The assignments happen before the constructor body runs, so that body can validate or normalize the stored values. A public property is still public; promotion alone does not provide immutability or validation.

[PHP documentation](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

<a id="new-initializers"></a>
## Objects as default parameter values

**PHP 8.1+ | Core**

A default parameter can construct an object with `new`. This is useful when a dependency has a small sensible default and callers should still be able to supply another instance.

```php
class Prefix
{
	public function __construct( public string $value = 'note' ) {}
}

function label( string $text, Prefix $prefix = new Prefix() ): string
{
	return $prefix->value . ':' . $text;
}

echo label( 'types' );
# note:types
```

When the default is used, the object is evaluated for that call; it is not one shared mutable default for every call. PHP 8.1 also permits `new` in attribute arguments, local static initializers and global constants, with syntax restrictions. An ordinary property declaration still cannot use `new` as its default.

[PHP documentation](https://www.php.net/manual/en/migration81.new-features.php)

<a id="attributes"></a>
## Attributes and reading their metadata

**PHP 8.0+ | Core**

Attributes attach structured metadata to declarations. Define an attribute class, place it on a supported declaration, then read it through reflection. Application-defined attributes only do something when application code interprets them.

```php
#[Attribute( Attribute::TARGET_METHOD )]
class Description
{
	public function __construct( public string $text ) {}
}

class Report
{
	#[Description( 'Exports a report' )]
	public function export(): string
	{
		return 'report';
	}
}

$method = new ReflectionMethod( Report::class, 'export' );
$attributes = $method->getAttributes( Description::class );
$description = $attributes[0]->newInstance();

echo $description->text;
# Exports a report
```

Use target flags and `Attribute::IS_REPEATABLE` deliberately. `newInstance()` constructs the attribute and validates its usage; a metadata marker alone does not enforce permissions. PHP 8.5 adds `#[DelayedTargetValidation]` for advanced attribute tooling: it defers invalid-target checks for built-in or extension attributes until reflection instantiates them. See also [diagnostic attributes](08-errors-and-debugging.md#deprecated-attribute) and [global constant attributes](01-syntax-and-functions.md#global-constant-attributes).

[PHP documentation](https://www.php.net/manual/en/language.attributes.overview.php)

<a id="stringable"></a>
## The Stringable interface

**PHP 8.0+ | Core**

An object with `__toString()` implicitly implements `Stringable`. A union such as `string|Stringable` can express that an API accepts either a plain string or an object with a string representation.

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

`Stringable` is not the same type as `string`, especially with strict typing. The explicit cast above is the boundary where the representation is requested. Conversion may throw; see [exceptions from __toString()](08-errors-and-debugging.md#to-string-exceptions).

[PHP documentation](https://www.php.net/manual/en/class.stringable.php)

<a id="enums"></a>
## Enums for a closed set of states

**PHP 8.1+ | Core**

An enum defines a finite set of named objects. A backed enum adds a unique `string` or `int` value for storage or external data. Application code can use a typed state while the database stores a small scalar.

```php
enum Status: string
{
	case Draft = 'draft';
	case Published = 'published';
}

$status = Status::from( 'published' );
echo $status->name, ':', $status->value, PHP_EOL;
var_dump( Status::tryFrom('missing') );
echo count( Status::cases() );
# Published:published
# NULL
# 2
```

`from()` throws `ValueError` for an unknown backing value; `tryFrom()` returns `null`. Invalid argument types can still cause `TypeError`, so `tryFrom()` is not a universal validator. A case is an object: `Status::Draft !== 'draft'`. Use `->value` only at the scalar boundary. Pure enums have cases and `->name`, but no backing `->value`, `from()` or `tryFrom()`.

[PHP documentation](https://www.php.net/manual/en/language.enumerations.backed.php)

<a id="enum-constants"></a>
## Enum properties in constant expressions

**PHP 8.2+ | Core**

The `name` and `value` of an enum case can be read in constant expressions. This avoids repeating the scalar spelling beside the enum declaration.

```php
enum Format: string
{
	case Json = 'json';
}

const DEFAULT_FORMAT = Format::Json->value;
const DEFAULT_CASE_NAME = Format::Json->name;

echo DEFAULT_CASE_NAME, ':', DEFAULT_FORMAT;
# Json:json
```

A pure enum has no `value` property. These expressions read the built-in enum metadata; they do not allow arbitrary object property access or method execution in constants.

[PHP documentation](https://www.php.net/manual/en/migration82.new-features.php)

<a id="readonly-properties"></a>
## Readonly properties and shallow immutability

**PHP 8.1+ | Core**

A `readonly` property can be initialized once and cannot normally be reassigned or modified afterward. It is useful for identity and value objects, but an object stored in that property can still have mutable state.

```php
class Snapshot
{
	public function __construct( public readonly stdClass $data ) {}
}

$data = (object)['count' => 1];
$snapshot = new Snapshot( $data );
$snapshot->data->count++;

echo $snapshot->data->count;
# 2
```

The property must be typed and cannot be static. Incrementing a readonly scalar or modifying an array entry is forbidden; changing the contents of a referenced object is a different operation. Before PHP 8.4 initialization was implicitly private to the declaring class; from PHP 8.4 the default is `protected(set)`, allowing initialization from child scope unless narrowed explicitly.

[PHP documentation](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

<a id="readonly-classes"></a>
## Readonly classes

**PHP 8.2+ | Core**

A readonly class applies readonly rules to all its instance properties. This keeps a data object concise and prevents a new property from accidentally being mutable.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y;
# 3:4
```

Properties must be typed; static and dynamic properties are unavailable. Inheritance must respect readonly class rules. As with one readonly property, nested mutable objects do not become immutable automatically.

[PHP documentation](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

<a id="readonly-cloning"></a>
## Reinitialize readonly properties during __clone()

**PHP 8.3+ | Core**

During cloning, readonly properties may be reinitialized so that a copied object can receive its own nested objects. This solves a common shallow-copy problem without making the original property generally writable.

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

This is a controlled cloning-time exception to the normal write restriction, not permission to repeatedly update the property. Cloning remains shallow unless you explicitly clone the nested objects that require independent state.

[PHP documentation](https://www.php.net/manual/en/migration83.new-features.php)

<a id="readonly-anonymous"></a>
## Readonly anonymous classes

**PHP 8.3+ | Core**

A local anonymous object can now follow the same readonly rules as a named readonly class. This is useful for a small immutable value that does not need a reusable public class name.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id;
# 7
```

This adds readonly to anonymous classes; ordinary anonymous classes have existed since PHP 7.0. The restrictions on typed properties and nested mutable state still apply.

[PHP documentation](https://www.php.net/manual/en/migration83.new-features.php)

<a id="constant-visibility-final"></a>
## Visibility and final class constants

**PHP 7.1+ | Core**

PHP 7.1 allows class constants to be public, protected or private. PHP 8.1 adds `final` constants, preventing a subclass from replacing a value that belongs to the parent’s contract.

```php
class Limits
{
	private const INTERNAL_FACTOR = 2;
	public const PAGE_SIZE = 20;

	public static function batch_size(): int
	{
		return self::PAGE_SIZE * self::INTERNAL_FACTOR;
	}
}

echo Limits::batch_size();
# 40
```

For PHP 8.1+, the declaration can be `final public const PAGE_SIZE = 20;` if overriding it would violate the design. Visibility controls access; final controls redeclaration. [Typed class constants](02-types.md#typed-constants) are a separate PHP 8.3 addition.

[PHP documentation](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="dynamic-constants"></a>
## A class constant selected by name

**PHP 8.3+ | Core**

`ClassName::{$name}` reads a class constant using a variable name. It avoids building a `ClassName::CONSTANT` string for `constant()` when the class is already known.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
echo Format::{$name};
# application/json
```

The same visibility checks still apply, and an unknown constant produces an error. An external name should come from an allowed set. Since PHP 8.0, `$object::class` also obtains an object’s class name without a separate `get_class()` call.

[PHP documentation](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="trait-constants"></a>
## Constants declared in traits

**PHP 8.2+ | Core**

A trait can declare constants alongside the behavior that uses them. This is useful when a shared implementation has a small fixed option or protocol value.

```php
trait HasFormat
{
	public const FORMAT = 'json';

	public function format(): string
	{
		return self::FORMAT;
	}
}

class Exporter
{
	use HasFormat;
}

echo (new Exporter())->format();
# json
```

Access constants through the class using the trait. Conflicting declarations must satisfy PHP’s compatibility rules. A related PHP 8.3 change affects static trait properties: when a child explicitly uses the same trait again, its static property is separate from the parent’s; do not assume both counters share storage.

[PHP documentation](https://www.php.net/manual/en/language.oop5.traits.php)

<a id="trait-final"></a>
## Import a trait method as final

**PHP 8.3+ | Core**

A class can mark an imported trait method as final in the adaptation block. The shared trait remains reusable, while this class prevents its children from overriding the imported behavior.

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

echo (new Report())->identifier();
# report
```

This constrains overriding in subclasses; it does not make the entire class final or freeze object properties. Use it for behavior that is intentionally fixed by the class contract.

[PHP documentation](https://www.php.net/manual/en/language.oop5.traits.php)

<a id="override"></a>
## Check overridden methods with #[Override]

**PHP 8.3+ | Core**

The attribute records that a method is intended to replace an inherited method or implement an interface method. A typo or an upstream rename then causes a compile-time error instead of silently creating an unrelated method.

```php
class Exporter
{
	public function format(): string
	{
		return 'text';
	}
}

class JsonExporter extends Exporter
{
	#[Override]
	public function format(): string
	{
		return 'json';
	}
}

echo (new JsonExporter())->format();
# json
```

The attribute verifies the existence of the overridden declaration, not the correctness of its implementation. It cannot be applied to `__construct()`. [Property overrides](#override-properties) gain the same annotation in PHP 8.5.

[PHP documentation](https://www.php.net/manual/en/class.override.php)

<a id="property-hooks"></a>
## Property hooks: backed and virtual values

**PHP 8.4+ | Core**

A property can customize reading with `get` and writing with `set`. A backed property stores a value; a virtual property computes its value through hooks without its own storage.

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
echo $contact->name, ':', $contact->display_name;
# Ada:ADA
```

The expression form of `set` automatically stores its result in the backing value. A virtual get-only property is not writable. Hooks are available for non-static properties and cannot be combined with `readonly`. Put business operations involving several objects in methods; a property accessor should remain easy to understand.

[PHP documentation](https://www.php.net/manual/en/language.oop5.property-hooks.php)

<a id="setter-validation"></a>
## A full setter must assign the backing value

**PHP 8.4+ | Core**

A block-form `set` hook can validate input before storing it. Unlike the expression form, the block must explicitly assign the accepted value to the property.

```php
class Product
{
	public int $quantity = 0
	{
		set
		{
			if( $value < 0 )
				throw new InvalidArgumentException( 'Quantity cannot be negative' );

			$this->quantity = $value;
		}
	}
}

$product = new Product();
$product->quantity = 5;

try
{
	$product->quantity = -1;
}
catch( InvalidArgumentException )
{
	echo $product->quantity;
}
# 5
```

Within this hook, `$this->quantity` accesses the backing storage rather than recursively calling the same hook. Validation runs before the assignment, so a rejected update leaves the previous value intact. A property default is not a substitute for validating later writes.

[PHP documentation](https://www.php.net/manual/en/language.oop5.property-hooks.php)

<a id="asymmetric-visibility"></a>
## Different visibility for reading and writing

**PHP 8.4+ | Core**

`public private(set)` lets outside code read a property while only the declaring class can write it. It keeps a simple public read interface without making all assignments public.

```php
class Article
{
	public private(set) string $title;

	public function __construct( string $title )
	{
		$this->title = $title;
	}

	public function rename( string $title ): void
	{
		$this->title = trim( $title );
	}
}

$article = new Article( 'Draft' );
$article->rename( ' Published ' );
echo $article->title;
# Published
```

The write visibility cannot be wider than the read visibility. `private(set)` also makes the property final, so a child cannot redeclare it to bypass the restriction. It is not readonly: the permitted class may write again. Obtaining a reference or modifying an array element also follows write visibility.

[PHP documentation](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

<a id="interface-properties"></a>
## Property contracts in interfaces

**PHP 8.4+ | Core**

An interface can require a readable or writable public property using hook-shaped declarations. The implementation may use a normal property or hooks as long as the required operations are available.

```php
interface HasTitle
{
	public string $title { get; }
}

class Article implements HasTitle
{
	public function __construct( public string $title ) {}
}

function title_of( HasTitle $item ): string
{
	return $item->title;
}

echo title_of( new Article('Notes') );
# Notes
```

A `get` requirement does not force the implementation to forbid writes; it only promises reading. Similarly, `{ get; set; }` requires both operations. Respect the type-variance rules when refining a contract.

[PHP documentation](https://www.php.net/manual/en/language.oop5.interfaces.php)

<a id="lazy-objects"></a>
## Lazy ghosts and lazy proxies

**PHP 8.4+ | Core**

Reflection can create an object whose initialization waits until its state is used. A lazy ghost is initialized in place; a lazy proxy asks a factory for a separate real instance and forwards state operations to it.

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

This delays work; it does not run it in parallel. Proxy identity differs from its real instance, which matters for identity maps and strict object comparisons. The mechanism targets user-defined classes and `stdClass`, not arbitrary internal classes. Use it where deferred initialization solves a real lifetime or loading problem.

[PHP documentation](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

<a id="new-chaining"></a>
## Call a method directly after new

**PHP 8.4+ | Core**

An object created with constructor parentheses can be used immediately without wrapping the whole `new` expression in another pair of parentheses. It makes a short create-and-use expression lighter.

```php
echo new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );
# 2025-01-02
```

The older `(new DateTimeImmutable(...))->format(...)` form remains valid. Keep a variable when several later steps need the object or when the one-liner obscures an important lifecycle.

[PHP documentation](https://www.php.net/manual/en/migration84.new-features.php)

<a id="override-properties"></a>
## Check property overrides with #[Override]

**PHP 8.5+ | Core**

The override check extends from methods to properties. This is especially useful for a virtual property whose inherited computation is being specialized.

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

echo new Report()->label;
# report
```

If the expected parent or interface property disappears, the declaration produces a compile-time error. Other rules still apply: the attribute cannot make an incompatible or final property override legal.

[PHP documentation](https://www.php.net/manual/en/class.override.php)

<a id="static-visibility"></a>
## Asymmetric visibility for static properties

**PHP 8.5+ | Core**

Static properties can also have narrower write visibility. A shared counter can be observed externally while updates stay inside the class that owns the operation.

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
echo Counter::$calls;
# 1
```

This controls access, not request isolation or concurrency. In long-running processes, static state survives successive operations. Readonly static properties are a different concept and remain unsupported.

[PHP documentation](https://www.php.net/manual/en/language.oop5.visibility.php)

<a id="final-promotion"></a>
## Final promoted properties

**PHP 8.5+ | Core**

Final properties were introduced in PHP 8.4; PHP 8.5 allows the modifier directly on a promoted constructor property. It prevents a child from redeclaring the property.

```php
class Ticket
{
	public function __construct( final public string $code ) {}
}

$ticket = new Ticket( 'A' );
$ticket->code = 'B';
echo $ticket->code;
# B
```

Final does not mean readonly: the public property above can still be reassigned. Choose final for inheritance restrictions and readonly for value-write restrictions; they solve different problems.

[PHP documentation](https://www.php.net/manual/en/language.oop5.final.php)

<a id="clone-with"></a>
## Clone an object with selected property changes

**PHP 8.5+ | Core**

The function form `clone( $object, $changes )` creates a copy and applies a map of property values. It is useful for a `with_*()` method returning an updated readonly value object.

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

Overrides happen after `__clone()` and respect property write visibility. Place readonly updates in an allowed class scope. The constructor is not rerun, so validate changed values before cloning; constructor validation alone is insufficient. Unlisted nested objects still follow ordinary shallow-clone rules.

[PHP documentation](https://www.php.net/manual/en/language.oop5.cloning.php)

<a id="dynamic-properties"></a>
## Dynamic properties are deprecated

**PHP 8.2+ | Core**

Creating an undeclared property on an ordinary class now emits a deprecation diagnostic. This catches misspellings and makes the object’s actual shape easier to inspect.

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

Declare known fields. For intentionally arbitrary data use an array or `stdClass`. Exceptions include classes explicitly allowing dynamic properties with `#[AllowDynamicProperties]`, their children, and classes using `__set()`; an implementation that creates undeclared properties inside `__set()` can still trigger the diagnostic. Do not add the attribute everywhere just to hide typos.

[PHP documentation](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)
