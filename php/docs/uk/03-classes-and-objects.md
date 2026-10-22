# Класи та об’єкти

[EN](../en/03-classes-and-objects.md) | [UK](../uk/03-classes-and-objects.md) | [RU](../ru/03-classes-and-objects.md)

[Зміст](../../README.uk.md)

**На цій сторінці**

- [Анонімні класи](#anonymous-classes)
- [Оголошення властивостей у конструкторі](#constructor-promotion)
- [Об’єкти як значення параметрів за замовчуванням](#new-initializers)
- [Атрибути та читання їхніх метаданих](#attributes)
- [Інтерфейс `Stringable`](#stringable)
- [Enum для закритого набору станів](#enums)
- [Властивості enum у константних виразах](#enum-constants)
- [`Readonly`-властивості та поверхнева незмінюваність](#readonly-properties)
- [`Readonly`-класи](#readonly-classes)
- [Повторна ініціалізація `readonly` у `__clone()`](#readonly-cloning)
- [Анонімні `readonly`-класи](#readonly-anonymous)
- [Видимість і `final` для констант класу](#constant-visibility-final)
- [Вибір константи класу за назвою](#dynamic-constants)
- [Константи, оголошені у traits](#trait-constants)
- [Імпорт методу trait як `final`](#trait-final)
- [Перевірка перевизначених методів через `#[Override]`](#override)
- [Обробники властивостей: збережені та віртуальні значення](#property-hooks)
- [Повний setter має записувати значення властивості](#setter-validation)
- [Різні права для читання та запису](#asymmetric-visibility)
- [Контракти властивостей в інтерфейсах](#interface-properties)
- [Відкладені об’єкти: ghost і proxy](#lazy-objects)
- [Виклик методу одразу після `new`](#new-chaining)
- [Перевірка перевизначення властивостей через `#[Override]`](#override-properties)
- [Асиметрична видимість статичних властивостей](#static-visibility)
- [`Final` для властивостей у параметрах конструктора](#final-promotion)
- [Клонування об’єкта зі зміною вибраних властивостей](#clone-with)
- [Динамічні властивості застаріли](#dynamic-properties)

<a id="anonymous-classes"></a>
## Анонімні класи

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.anonymous.php)

Анонімні класи створюють локальні реалізації та можуть розширювати клас або реалізовувати інтерфейс.

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
## Оголошення властивостей у конструкторі

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

Параметр конструктора з модифікатором видимості оголошує та ініціалізує властивість.

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

Можна поєднувати звичайні параметри та параметри-властивості; присвоєння відбувається до тіла конструктора.

<a id="new-initializers"></a>
## Об’єкти як значення параметрів за замовчуванням

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration81.new-features.php)

`new` дозволений у значеннях параметрів за замовчуванням, аргументах атрибутів, локальних статичних ініціалізаторах і глобальних константах.

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

Використане значення параметра за замовчуванням створює об’єкт для цього виклику; звичайні властивості все ще не можуть мати `new` як початкове значення.

<a id="attributes"></a>
## Атрибути та читання їхніх метаданих

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.attributes.overview.php)

Атрибути задають метадані для рефлексії; прапорці цілі та `IS_REPEATABLE` обмежують використання, яке перевіряє `newInstance()`.

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

PHP 8.5 додає `#[DelayedTargetValidation]`, що відкладає перевірку некоректної цілі вбудованих атрибутів або атрибутів розширень до створення екземпляра через рефлексію; див. також [діагностичні атрибути](08-errors-and-debugging.md#deprecated-attribute) та [глобальні константи](01-syntax-and-functions.md#global-constant-attributes).

<a id="stringable"></a>
## Інтерфейс `Stringable`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.stringable.php)

Клас із `__toString()` неявно реалізує `Stringable`; `string|Stringable` приймає обидва представлення.

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

`Stringable` не є `string` при строгій типізації, а перетворення [може кинути виняток](08-errors-and-debugging.md#to-string-exceptions).

<a id="enums"></a>
## Enum для закритого набору станів

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.enumerations.backed.php)

Enum оголошує іменовані об’єкти-варіанти; backed enum додає унікальні значення `int` або `string` через `->value`, а всі варіанти мають `->name`.

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

`from()` кидає `ValueError` для невідомого значення, а `tryFrom()` повертає `null`, проте некоректний тип усе ще може спричинити `TypeError`; чисті enum не мають базового значення та методів перетворення.

<a id="enum-constants"></a>
## Властивості enum у константних виразах

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration82.new-features.php)

`name` варіанта enum та `value` backed-варіанта можна читати в константних виразах.

```php
enum Format: string
{
	case Json = 'json';
}

const DEFAULT_FORMAT = Format::Json->value;
const DEFAULT_CASE_NAME = Format::Json->name;

echo DEFAULT_CASE_NAME, ':', DEFAULT_FORMAT; # Json:json
```

Це не дозволяє довільне читання властивостей об’єктів або виклики методів у константах.

<a id="readonly-properties"></a>
## `Readonly`-властивості та поверхнева незмінюваність

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

Типізована нестатична `readonly`-властивість допускає одноразову ініціалізацію, але збережений у ній об’єкт може лишатися змінюваним.

```php
class Snapshot
{
	public function __construct( public readonly stdClass $data ) {}
}

$snapshot = new Snapshot( (object)['count' => 1] );
$snapshot->data->count++;

echo $snapshot->data->count; # 2
```

До PHP 8.4 ініціалізація була неявно приватною для класу оголошення; з 8.4 типово діє `protected(set)`, якщо доступ не звужено, а інкремент скаляра та запис елементів масиву залишаються забороненими.

<a id="readonly-classes"></a>
## `Readonly`-класи

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

Клас `readonly` робить усі властивості екземпляра `readonly`.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y; # 3:4
```

Властивості мають бути типізованими, статичні та динамічні властивості заборонені, успадкування підкоряється правилам `readonly`, а вкладені об’єкти все ще можуть бути змінюваними.

<a id="readonly-cloning"></a>
## Повторна ініціалізація `readonly` у `__clone()`

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration83.new-features.php)

`__clone()` може повторно ініціалізувати `readonly`-властивості, щоб відокремити вкладені об’єкти копії.

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

Це обмежений виняток на час клонування; інші вкладені об’єкти залишаються спільними при звичайному поверхневому клонуванні.

<a id="readonly-anonymous"></a>
## Анонімні `readonly`-класи

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration83.new-features.php)

Анонімні класи також можуть бути `readonly`.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id; # 7
```

Для них діють обмеження типізованих властивостей і поверхневої незмінюваності іменованих `readonly`-класів.

<a id="constant-visibility-final"></a>
## Видимість і `final` для констант класу

**PHP 7.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.constants.php)

PHP 7.1 додає видимість констант класу; PHP 8.1 додає оголошення на зразок `final public const PAGE_SIZE = 20;`, що забороняють перевизначення в нащадках.

```php
class Limits
{
	private const INTERNAL_FACTOR = 2;
	public const PAGE_SIZE = 20;
}

echo Limits::PAGE_SIZE; # 20
```

[Типізовані константи класу](02-types.md#typed-constants) з’являються окремо в PHP 8.3.

<a id="dynamic-constants"></a>
## Вибір константи класу за назвою

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.constants.php)

`ClassName::{$name}` читає константу класу за змінною назвою; `$object::class` повертає назву класу об’єкта з PHP 8.0.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
$mime = Format::{$name};

echo $mime; # application/json
```

Видимість константи зберігається, а невідома назва спричиняє помилку.

<a id="trait-constants"></a>
## Константи, оголошені у traits

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Trait може оголошувати константи, доступні через клас, що його використовує; конфліктні оголошення мають бути сумісними.

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

З PHP 8.3 нащадок, який явно повторно використовує trait, має окреме від батька сховище статичних властивостей trait.

<a id="trait-final"></a>
## Імпорт методу trait як `final`

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Адаптація trait може імпортувати метод як `final`.

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

Це забороняє перевизначення методу в нащадках класу, що використовує trait, без зміни самого trait.

<a id="override"></a>
## Перевірка перевизначених методів через `#[Override]`

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.override.php)

`#[Override]` перевіряє під час компіляції, що метод перевизначає успадковане оголошення або реалізує метод інтерфейсу.

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

Атрибут недоступний для `__construct()`; [перевизначення властивостей](#override-properties) підтримується з PHP 8.5.

<a id="property-hooks"></a>
## Обробники властивостей: збережені та віртуальні значення

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Обробники властивостей змінюють читання та запис: `name` зберігає значення, а `display_name` є віртуальною.

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

Вираз `set` автоматично зберігає результат; віртуальна властивість лише з `get` недоступна для запису, а обробники не можуть бути статичними або поєднуватися з `readonly`.

<a id="setter-validation"></a>
## Повний setter має записувати значення властивості

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Блоковий обробник `set` має явно записувати прийняте значення.

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

Усередині власного обробника `$this->quantity` звертається до сховища без рекурсії; відмова до присвоєння зберігає попереднє значення.

<a id="asymmetric-visibility"></a>
## Різні права для читання та запису

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

`public private(set)` дозволяє публічне читання та повторний запис усередині класу оголошення.

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

Видимість запису не може бути ширшою за читання, `private(set)` робить властивість `final`, а посилання та зміни елементів масиву також потребують права запису.

<a id="interface-properties"></a>
## Контракти властивостей в інтерфейсах

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.interfaces.php)

Інтерфейси можуть вимагати публічного читання або запису властивості через `{ get; }` чи `{ get; set; }`.

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

Контракт може виконати звичайна властивість або обробники з дотриманням правил варіантності типів. Вимога `get` не забороняє реалізації дозволяти запис.

<a id="lazy-objects"></a>
## Відкладені об’єкти: ghost і proxy

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

Через Reflection можна відкласти ініціалізацію об’єкта до звернення до його стану. Ghost ініціалізується на місці, а proxy отримує окремий реальний екземпляр із фабрики.

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

Proxy та створений ним об’єкт є різними екземплярами; механізм підтримує користувацькі класи та `stdClass`, а не довільні внутрішні класи.

<a id="new-chaining"></a>
## Виклик методу одразу після `new`

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration84.new-features.php)

Після виразу `new` з дужками конструктора можна одразу звертатися до членів об’єкта.

```php
$date = new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );

echo $date; # 2025-01-02
```

Старий синтаксис `(new ClassName(...))->method()` залишається коректним.

<a id="override-properties"></a>
## Перевірка перевизначення властивостей через `#[Override]`

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.override.php)

`#[Override]` також перевіряє успадковані властивості або властивості інтерфейсу під час компіляції.

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

Атрибут не дозволяє несумісне перевизначення чи перевизначення `final`-властивості.

<a id="static-visibility"></a>
## Асиметрична видимість статичних властивостей

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.visibility.php)

Статичні властивості можуть мати вужчу видимість запису.

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

Це керує доступом, а не часом життя стану; статичні `readonly`-властивості залишаються недоступними.

<a id="final-promotion"></a>
## `Final` для властивостей у параметрах конструктора

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.final.php)

PHP 8.5 дозволяє `final` для властивостей у параметрах конструктора, розширюючи `final`-властивості з PHP 8.4.

```php
class Ticket
{
	public function __construct( final public string $code ) {}
}

$ticket = new Ticket( 'A' );
$ticket->code = 'B';

echo $ticket->code; # B
```

Модифікатор забороняє повторне оголошення в нащадках, але не забороняє присвоєння, як `readonly`.

<a id="clone-with"></a>
## Клонування об’єкта зі зміною вибраних властивостей

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.cloning.php)

`clone( $object, $changes )` створює копію та змінює властивості після `__clone()`.

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

Зміни підкоряються видимості запису, тому оновлення `readonly` потребують дозволеної області класу. Конструктор не викликається повторно: перевіряйте зміни явно; решта вкладених об’єктів зберігають поведінку поверхневого клонування.

<a id="dynamic-properties"></a>
## Динамічні властивості застаріли

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)

Створення неоголошеної властивості звичайного класу спричиняє повідомлення про застарілу можливість.

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

Винятки охоплюють `stdClass`, класи з `#[AllowDynamicProperties]` та їхніх нащадків і обробку через `__set()`, але створення неоголошеної властивості всередині `__set()` усе ще може спричинити повідомлення.
