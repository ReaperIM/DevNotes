# Classes and objects

[EN](../en/03-classes-and-objects.md) | [UK](../uk/03-classes-and-objects.md) | [RU](../ru/03-classes-and-objects.md)

[Contents](../../README.md)

**On this page**

- [Create a class right where it is needed](#anonymous-classes)
- [Declare properties in the constructor](#constructor-promotion)
- [Use an object as a default argument](#new-initializers)
- [Attach a description with an attribute](#attributes)
- [Accept a string or an object with `__toString()`](#stringable)
- [Define allowed states with an enum](#enums)
- [Use an enum name or value in a constant](#enum-constants)
- [Assign a property once with `readonly`](#readonly-properties)
- [Make every property `readonly`](#readonly-classes)
- [Separate a nested object when cloning](#readonly-cloning)
- [Create an anonymous `readonly` class](#readonly-anonymous)
- [Control access to constants and prevent overrides](#constant-visibility-final)
- [Read a constant using a name from a variable](#dynamic-constants)
- [Keep constants with the trait’s code](#trait-constants)
- [Prevent a child from overriding a trait method](#trait-final)
- [Check that a method really overrides something](#override)
- [Handle property reads and writes](#property-hooks)
- [Check a value before storing it](#setter-validation)
- [Let everyone read, but only the class write](#asymmetric-visibility)
- [Require a property through an interface](#interface-properties)
- [Initialize an object only when it is needed](#lazy-objects)
- [Call a method directly after `new`](#new-chaining)
- [Check that a property overrides something](#override-properties)
- [Restrict writes to a static property](#static-visibility)
- [Add `final` to a promoted property](#final-promotion)
- [Clone an object and change selected properties](#clone-with)
- [Avoid creating properties by accident](#dynamic-properties)

<a id="anonymous-classes"></a>
## Create a class right where it is needed

**PHP 7.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.anonymous.php)

If a small object is only needed here, declare its class directly after `new` without giving it a separate name. Use the resulting object as usual.

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

An anonymous class can still have a parent through `extends` and implement interfaces through `implements`. It has the usual class capabilities even without a name.

<a id="constructor-promotion"></a>
## Declare properties in the constructor

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

You do not need to declare a property, accept a parameter and assign one to the other separately. Add `public`, `protected` or `private` to a constructor parameter, and PHP creates the property and assigns the argument for you.

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

Here `id` and `name` are ready on the object after construction. The assignments happen before the constructor body runs. Ordinary parameters that do not declare properties can sit alongside them.

<a id="new-initializers"></a>
## Use an object as a default argument

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration81.new-features.php)

If the caller does not supply an object, create one directly in the default value. Without a second argument, this function gets a new `Prefix` and adds `note` to the label.

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

When the default is used, an object is created for that call rather than shared by all calls. `new` is also allowed in attribute arguments, local `static` initializers and global constants. Ordinary property defaults still cannot use it.

<a id="attributes"></a>
## Attach a description with an attribute

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.attributes.overview.php)

An attribute keeps extra information next to a declaration. Here `export()` has a description attached to it. `ReflectionMethod` finds that attribute and creates its `Description` object. Attaching the attribute does not run the method.

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

`TARGET_METHOD` allows our attribute only on methods. The separate `IS_REPEATABLE` flag allows repeated uses; `newInstance()` checks these rules.

PHP 8.5 adds `#[DelayedTargetValidation]` to postpone invalid-target checks for built-in or extension attributes until Reflection creates their objects. See also [diagnostic attributes](08-errors-and-debugging.md#deprecated-attribute) and [attributes on global constants](01-syntax-and-functions.md#global-constant-attributes).

<a id="stringable"></a>
## Accept a string or an object with `__toString()`

**PHP 8.0+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.stringable.php)

An object with `__toString()` knows how to turn itself into text and automatically implements `Stringable`. `string|Stringable` lets the function accept either regular text or one of these objects.

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

We explicitly convert it with `(string)` inside the function. With strict typing, a `string` parameter alone does not accept a `Stringable` object. Conversion can fail too: `__toString()` [may throw an exception](08-errors-and-debugging.md#to-string-exceptions).

<a id="enums"></a>
## Define allowed states with an enum

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.enumerations.backed.php)

An enum is useful when a status has a fixed set of allowed states. Each `case` is a named object. A backed enum also gives every case a unique `string` or `int` value for storage through `value`; every enum case has a `name`.

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

`from()` finds a case by value and throws `ValueError` if none matches. `tryFrom()` returns `null` for that case, but an invalid argument type can still throw `TypeError`. `cases()` returns all cases. A pure enum has no backing `value` or `from()` and `tryFrom()` methods.

<a id="enum-constants"></a>
## Use an enum name or value in a constant

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration82.new-features.php)

You do not have to repeat the enum’s string by hand. A constant expression can read a case’s `name` and, for a backed enum, its `value` too.

```php
enum Format: string
{
	case Json = 'json';
}

const DEFAULT_FORMAT = Format::Json->value;
const DEFAULT_CASE_NAME = Format::Json->name;

echo DEFAULT_CASE_NAME, ':', DEFAULT_FORMAT; # Json:json
```

Here `Json` and `json` come from the same case. This is specific to enums, not general permission to read arbitrary object properties or call methods in constants.

<a id="readonly-properties"></a>
## Assign a property once with `readonly`

**PHP 8.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

A typed, non-static `readonly` property can be initialized once. After that, you cannot assign a different value to it. But an object stored inside can still change its own properties, which is why the counter here reaches `2`.

```php
class Snapshot
{
	public function __construct( public readonly stdClass $data ) {}
}

$snapshot = new Snapshot( (object)['count' => 1] );
$snapshot->data->count++;

echo $snapshot->data->count; # 2
```

For a number, even `++` is forbidden. Array entries cannot be changed through the property either. An object inside does not automatically become immutable.

Before PHP 8.4, only the declaring class could initialize the property. From PHP 8.4 the default is `protected(set)`, so child classes can initialize it too unless write access is explicitly narrowed.

<a id="readonly-classes"></a>
## Make every property `readonly`

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

If every property should be initialized only once, put `readonly` on the class. `Point` then does not need to repeat it on `x` and `y`.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y; # 3:4
```

Every property must have a type. Static and dynamic properties are forbidden, and inheritance must follow the rules for `readonly` classes. Nested objects can still change, just as they can inside a single `readonly` property.

<a id="readonly-cloning"></a>
## Separate a nested object when cloning

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration83.new-features.php)

A normal `clone` copies the outer object but leaves the nested date shared. To change the copy’s date independently, clone it inside `__clone()` too. From PHP 8.3, a `readonly` property can be reinitialized at this point.

```php
readonly class Schedule
{
	public function __construct( public DateTime $date ) {}

	public function __clone(): void
	{
		# give the copy its own date so changes do not reach the original
		$this->date = clone $this->date;
	}
}

$original = new Schedule( new DateTime('2025-01-01') );
$copy = clone $original;
$copy->date->modify( '+1 day' );

echo $original->date->format( 'Y-m-d' ), ':', $copy->date->format( 'Y-m-d' );
# 2025-01-01:2025-01-02
```

Changing the date on `$copy` therefore leaves `$original` alone. This permission applies during cloning. Any other nested objects that were not explicitly copied still remain shared by a normal shallow clone.

<a id="readonly-anonymous"></a>
## Create an anonymous `readonly` class

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration83.new-features.php)

A small object without a named class can be `readonly` too. Here `id` is initialized in the constructor and cannot be assigned another value afterwards.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id; # 7
```

The same rules apply as for named `readonly` classes: properties need types, and nested objects do not become immutable by themselves.

<a id="constant-visibility-final"></a>
## Control access to constants and prevent overrides

**PHP 7.1+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.constants.php)

From PHP 7.1, class constants can declare visibility. Here `INTERNAL_FACTOR` stays inside the class, while `PAGE_SIZE` is readable from outside.

```php
class Limits
{
	private const INTERNAL_FACTOR = 2;
	public const PAGE_SIZE = 20;
}

echo Limits::PAGE_SIZE; # 20
```

From PHP 8.1, `final public const PAGE_SIZE = 20;` also prevents child classes from redeclaring the constant. [A type on the class constant](02-types.md#typed-constants) is a separate feature added in PHP 8.3.

<a id="dynamic-constants"></a>
## Read a constant using a name from a variable

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.constants.php)

The constant name can come from a variable: `Format::{$name}` reads the selected class constant. Here `$name` contains `JSON`, so we get `application/json`.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
$mime = Format::{$name};

echo $mime; # application/json
```

Visibility still applies, and an unknown name causes an error. To get the object’s class name instead, `$object::class` has been available since PHP 8.0.

<a id="trait-constants"></a>
## Keep constants with the trait’s code

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.traits.php)

A trait can bring constants into a class alongside methods and properties. Read them through the class using the trait, here `Exporter::FORMAT`. If the name is already declared, the declarations must be compatible.

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

Another trait change in PHP 8.3: a child class that explicitly uses the trait again gets its own static trait properties, separate from its parent’s. Keep that in mind if you expect a shared static counter or cache.

<a id="trait-final"></a>
## Prevent a child from overriding a trait method

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.traits.php)

Sometimes a trait method needs to stay fixed in one particular class. Mark it `as final` when using the trait without changing the trait itself.

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

`Report` still has its `identifier()` method, but a child of `Report` can no longer override it.

<a id="override"></a>
## Check that a method really overrides something

**PHP 8.3+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.override.php)

A typo in a method name can quietly create a new method instead of overriding the old one. `#[Override]` asks PHP to check this at compile time: a matching method must exist in a parent class or interface.

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

`format()` exists in the interface here, so the check passes. The attribute cannot be used on `__construct()`. It also supports [property overrides](#override-properties) from PHP 8.5.

<a id="property-hooks"></a>
## Handle property reads and writes

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.property-hooks.php)

A property can have its own `get` and `set` hooks. Use them to clean up a value on assignment or calculate it when read. Here `name` stores the trimmed name, while `display_name` calculates the uppercase version each time it is read.

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

The short `set =>` form stores the expression’s result for you. `display_name` has no separately stored value; it is a virtual property. Since it only has `get`, it cannot be written. Hooks cannot be static or combined with `readonly`.

<a id="setter-validation"></a>
## Check a value before storing it

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Use a block-form `set` when you need several steps rather than one expression. Unlike the short `set =>` form, this form needs an explicit assignment to store the accepted value.

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

Inside its own hook, `$this->quantity = $value` writes directly to the stored property without calling `set` again. A negative value throws before the assignment, leaving the previous quantity unchanged.

<a id="asymmetric-visibility"></a>
## Let everyone read, but only the class write

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

`public private(set)` separates the two permissions: outside code can read the title, but only the declaring class can assign it. Renaming therefore goes through `rename()`, where we also trim the text.

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

This is not `readonly`; the class can change the value repeatedly. Write visibility cannot be wider than read visibility. `private(set)` also makes the property `final`. Taking a reference and changing array entries require write access too.

<a id="interface-properties"></a>
## Require a property through an interface

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.interfaces.php)

An interface can require an accessible property as well as methods. `{ get; }` requires reading; `{ get; set; }` requires writing too. Here every `HasTitle` must provide a readable string `title`.

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

A regular property, as in `Article`, or property hooks can meet the requirement. Their types must obey the relevant variance rules. Requiring `get` does not prevent the class from allowing writes too; the interface sets the minimum capability.

<a id="lazy-objects"></a>
## Initialize an object only when it is needed

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

Reflection can delay initializing an object until its state is used. This helps when an object is created but its data may never be needed. A ghost initializes the same object in place; a proxy gets a separate real object from the supplied function.

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

The output shows the order: `created` comes first, and `initialize:...` appears only when we read `title`. A proxy and the real object it obtains are different instances. This supports user-defined classes and `stdClass`, not arbitrary internal PHP classes.

<a id="new-chaining"></a>
## Call a method directly after `new`

**PHP 8.4+ | Core** | [PHP documentation](https://www.php.net/manual/en/migration84.new-features.php)

Create an object and immediately use its method’s result, without a temporary variable or outer parentheses around `new`. The constructor’s own parentheses are required for this form.

```php
$date = new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );

echo $date; # 2025-01-02
```

The older `(new ClassName(...))->method()` form is still valid; there is no need to replace it just because the newer form exists.

<a id="override-properties"></a>
## Check that a property overrides something

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/class.override.php)

From PHP 8.5, `#[Override]` works on properties too. If a property is intended to replace one from a parent or meet an interface requirement, the attribute checks that declaration exists at compile time.

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

Here `Report` changes how the existing `label` is read. The attribute does not bypass other rules: incompatible types and overriding `final` properties are still forbidden.

<a id="static-visibility"></a>
## Restrict writes to a static property

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.visibility.php)

Static properties can now separate read and write access too. Outside code can read `calls`, but only `Counter` can update it.

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

This controls access to the value, not how long it is stored. Static properties still cannot be declared `readonly`.

<a id="final-promotion"></a>
## Add `final` to a promoted property

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.final.php)

PHP 8.4 added `final` properties to prevent redeclaration in child classes. PHP 8.5 also allows it on properties declared directly in constructor parameters.

```php
class Ticket
{
	public function __construct( final public string $code ) {}
}

$ticket = new Ticket( 'A' );
$ticket->code = 'B';

echo $ticket->code; # B
```

`final` does not freeze the value: `code` changes from `A` to `B` here. It stops a child class from redeclaring the property. `readonly` is the modifier that restricts reassignment.

<a id="clone-with"></a>
## Clone an object and change selected properties

**PHP 8.5+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.cloning.php)

`clone( $object, $changes )` gives you a copy with selected changes in one step. `__clone()` runs first, then the listed properties are assigned. Here the original color keeps `alpha = 255` while the copy gets `128`.

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

Write visibility still applies, so changing `readonly` properties this way requires a permitted class scope. The constructor does not run again; `with_alpha()` needs its own range check. Nested objects remain shared unless they are cloned separately.

<a id="dynamic-properties"></a>
## Avoid creating properties by accident

**PHP 8.2+ | Core** | [PHP documentation](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)

Writing an undeclared property on an ordinary class emits a deprecation diagnostic from PHP 8.2. This also helps catch misspelled names. Declare the property you need explicitly, as with `User::$name`.

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

For a free-form set of fields, use `stdClass`, as `$metadata` does here. Classes marked `#[AllowDynamicProperties]` and their children are also exempt. `__set()` can handle assignments itself, but creating an undeclared property inside that handler can still emit the diagnostic.
