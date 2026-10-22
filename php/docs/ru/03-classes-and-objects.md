# Классы и объекты

[English](../en/03-classes-and-objects.md) | [Українська](../uk/03-classes-and-objects.md) | [Русский](../ru/03-classes-and-objects.md)

[Содержание](../../README.ru.md)

**На этой странице**

- [Анонимные классы](#anonymous-classes)
- [Объявление свойств в конструкторе](#constructor-promotion)
- [Объекты как значения параметров по умолчанию](#new-initializers)
- [Атрибуты и чтение их метаданных](#attributes)
- [Интерфейс Stringable](#stringable)
- [Enum для закрытого набора состояний](#enums)
- [Свойства enum в константных выражениях](#enum-constants)
- [Readonly-свойства и поверхностная неизменяемость](#readonly-properties)
- [Readonly-классы](#readonly-classes)
- [Повторная инициализация readonly в __clone()](#readonly-cloning)
- [Анонимные readonly-классы](#readonly-anonymous)
- [Видимость и final для констант класса](#constant-visibility-final)
- [Выбор константы класса по имени](#dynamic-constants)
- [Константы, объявленные в traits](#trait-constants)
- [Импорт метода trait как final](#trait-final)
- [Проверка переопределённых методов через #[Override]](#override)
- [Обработчики свойств: хранимые и виртуальные значения](#property-hooks)
- [Полный setter должен записывать значение свойства](#setter-validation)
- [Разные права для чтения и записи](#asymmetric-visibility)
- [Контракты свойств в интерфейсах](#interface-properties)
- [Отложенные объекты: ghost и proxy](#lazy-objects)
- [Вызов метода сразу после new](#new-chaining)
- [Проверка переопределения свойств через #[Override]](#override-properties)
- [Асимметричная видимость статических свойств](#static-visibility)
- [Final для свойств в параметрах конструктора](#final-promotion)
- [Клонирование объекта с изменением выбранных свойств](#clone-with)
- [Динамические свойства устарели](#dynamic-properties)

<a id="anonymous-classes"></a>
## Анонимные классы

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.anonymous.php)

Анонимные классы создают локальные реализации и могут расширять класс или реализовывать интерфейс.

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
## Объявление свойств в конструкторе

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

Параметр конструктора с модификатором видимости объявляет и инициализирует свойство.

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

Обычные параметры можно сочетать с параметрами-свойствами; присваивание происходит до тела конструктора.

<a id="new-initializers"></a>
## Объекты как значения параметров по умолчанию

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration81.new-features.php)

`new` разрешён в значениях параметров по умолчанию, аргументах атрибутов, локальных статических инициализаторах и глобальных константах.

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

Использованное значение параметра по умолчанию создаёт объект для этого вызова; обычные свойства всё ещё не могут иметь `new` как начальное значение.

<a id="attributes"></a>
## Атрибуты и чтение их метаданных

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.attributes.overview.php)

Атрибуты задают метаданные для рефлексии; флаги цели и `IS_REPEATABLE` ограничивают использование, которое проверяет `newInstance()`.

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

PHP 8.5 добавляет `#[DelayedTargetValidation]`, откладывающий проверку некорректной цели встроенных атрибутов или атрибутов расширений до создания экземпляра через рефлексию; см. также [диагностические атрибуты](08-errors-and-debugging.md#deprecated-attribute) и [глобальные константы](01-syntax-and-functions.md#global-constant-attributes).

<a id="stringable"></a>
## Интерфейс Stringable

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.stringable.php)

Класс с `__toString()` неявно реализует `Stringable`; `string|Stringable` принимает оба представления.

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

`Stringable` не является `string` при строгой типизации, а преобразование [может выбросить исключение](08-errors-and-debugging.md#to-string-exceptions).

<a id="enums"></a>
## Enum для закрытого набора состояний

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.enumerations.backed.php)

Enum объявляет именованные объекты-варианты; backed enum добавляет уникальные значения `int` или `string` через `->value`, а все варианты имеют `->name`.

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

`from()` выбрасывает `ValueError` для неизвестного значения, а `tryFrom()` возвращает `null`, однако некорректный тип всё ещё может вызвать `TypeError`; чистые enum не имеют базового значения и методов преобразования.

<a id="enum-constants"></a>
## Свойства enum в константных выражениях

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration82.new-features.php)

`name` варианта enum и `value` backed-варианта можно читать в константных выражениях.

```php
enum Format: string
{
	case Json = 'json';
}

const DEFAULT_FORMAT = Format::Json->value;
const DEFAULT_CASE_NAME = Format::Json->name;

echo DEFAULT_CASE_NAME, ':', DEFAULT_FORMAT; # Json:json
```

Это не разрешает произвольное чтение свойств объектов или вызовы методов в константах.

<a id="readonly-properties"></a>
## Readonly-свойства и поверхностная неизменяемость

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

Типизированное нестатическое `readonly`-свойство допускает однократную инициализацию, но хранящийся в нём объект может оставаться изменяемым.

```php
class Snapshot
{
	public function __construct( public readonly stdClass $data ) {}
}

$snapshot = new Snapshot( (object)['count' => 1] );
$snapshot->data->count++;

echo $snapshot->data->count; # 2
```

До PHP 8.4 инициализация была неявно приватной для объявившего класса; с 8.4 по умолчанию действует `protected(set)`, если доступ не сужен, а инкремент скаляра и запись элементов массива остаются запрещёнными.

<a id="readonly-classes"></a>
## Readonly-классы

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

Класс readonly делает все свойства экземпляра readonly.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y; # 3:4
```

Свойства должны быть типизированными, статические и динамические свойства запрещены, наследование подчиняется правилам readonly, а вложенные объекты всё ещё могут быть изменяемыми.

<a id="readonly-cloning"></a>
## Повторная инициализация readonly в __clone()

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration83.new-features.php)

`__clone()` может повторно инициализировать readonly-свойства, чтобы отделить вложенные объекты копии.

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

Это ограниченное исключение на время клонирования; остальные вложенные объекты остаются общими при обычном поверхностном клонировании.

<a id="readonly-anonymous"></a>
## Анонимные readonly-классы

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration83.new-features.php)

Анонимные классы также могут быть readonly.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id; # 7
```

Для них действуют ограничения типизированных свойств и поверхностной неизменяемости именованных readonly-классов.

<a id="constant-visibility-final"></a>
## Видимость и final для констант класса

**PHP 7.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.constants.php)

PHP 7.1 добавляет видимость констант класса; PHP 8.1 добавляет объявления вида `final public const PAGE_SIZE = 20;`, запрещающие переопределение в потомках.

```php
class Limits
{
	private const INTERNAL_FACTOR = 2;
	public const PAGE_SIZE = 20;
}

echo Limits::PAGE_SIZE; # 20
```

[Типизированные константы класса](02-types.md#typed-constants) появляются отдельно в PHP 8.3.

<a id="dynamic-constants"></a>
## Выбор константы класса по имени

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.constants.php)

`ClassName::{$name}` читает константу класса по переменному имени; `$object::class` возвращает имя класса объекта с PHP 8.0.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
$mime = Format::{$name};

echo $mime; # application/json
```

Видимость константы сохраняется, а неизвестное имя вызывает ошибку.

<a id="trait-constants"></a>
## Константы, объявленные в traits

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Trait может объявлять константы, доступные через использующий его класс; конфликтующие объявления должны быть совместимыми.

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

С PHP 8.3 потомок, явно повторно использующий trait, имеет отдельное от родителя хранилище статических свойств trait.

<a id="trait-final"></a>
## Импорт метода trait как final

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Адаптация trait может импортировать метод как `final`.

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

Это запрещает переопределение метода в потомках использующего trait класса без изменения самого trait.

<a id="override"></a>
## Проверка переопределённых методов через #[Override]

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.override.php)

`#[Override]` проверяет при компиляции, что метод переопределяет унаследованное объявление или реализует метод интерфейса.

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

Атрибут недоступен для `__construct()`; [переопределение свойств](#override-properties) поддерживается с PHP 8.5.

<a id="property-hooks"></a>
## Обработчики свойств: хранимые и виртуальные значения

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Обработчики свойств меняют чтение и запись: `name` хранит значение, а `display_name` является виртуальным.

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

Выражение `set` автоматически сохраняет результат; виртуальное свойство только с `get` недоступно для записи, а обработчики не могут быть статическими или сочетаться с `readonly`.

<a id="setter-validation"></a>
## Полный setter должен записывать значение свойства

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Блочный обработчик `set` должен явно записывать принятое значение.

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

Внутри собственного обработчика `$this->quantity` обращается к хранилищу без рекурсии; отказ до присваивания сохраняет предыдущее значение.

<a id="asymmetric-visibility"></a>
## Разные права для чтения и записи

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

`public private(set)` разрешает публичное чтение и повторную запись внутри объявившего класса.

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

Видимость записи не может быть шире чтения, `private(set)` делает свойство final, а ссылки и изменения элементов массива также требуют права записи.

<a id="interface-properties"></a>
## Контракты свойств в интерфейсах

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.interfaces.php)

Интерфейсы могут требовать публичного чтения или записи свойства через `{ get; }` или `{ get; set; }`.

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

Контракт может выполнить обычное свойство или обработчики с соблюдением правил вариантности типов. Требование `get` не запрещает реализации разрешать запись.

<a id="lazy-objects"></a>
## Отложенные объекты: ghost и proxy

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

Через Reflection можно отложить инициализацию объекта до обращения к его состоянию. Ghost инициализируется на месте, а proxy получает отдельный реальный экземпляр из фабрики.

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

Proxy и созданный им объект являются разными экземплярами; механизм поддерживает пользовательские классы и `stdClass`, а не произвольные внутренние классы.

<a id="new-chaining"></a>
## Вызов метода сразу после new

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration84.new-features.php)

После выражения `new` со скобками конструктора можно сразу обращаться к членам объекта.

```php
$date = new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );

echo $date; # 2025-01-02
```

Старый синтаксис `(new ClassName(...))->method()` остаётся корректным.

<a id="override-properties"></a>
## Проверка переопределения свойств через #[Override]

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.override.php)

`#[Override]` также проверяет унаследованные свойства или свойства интерфейса при компиляции.

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

Атрибут не разрешает несовместимое переопределение или переопределение final-свойства.

<a id="static-visibility"></a>
## Асимметричная видимость статических свойств

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.visibility.php)

Статические свойства могут иметь более узкую видимость записи.

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

Это управляет доступом, а не временем жизни состояния; статические readonly-свойства остаются недоступными.

<a id="final-promotion"></a>
## Final для свойств в параметрах конструктора

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.final.php)

PHP 8.5 разрешает `final` для свойств в параметрах конструктора, расширяя final-свойства из PHP 8.4.

```php
class Ticket
{
	public function __construct( final public string $code ) {}
}

$ticket = new Ticket( 'A' );
$ticket->code = 'B';

echo $ticket->code; # B
```

Модификатор запрещает повторное объявление в потомках, но не запрещает присваивание, как `readonly`.

<a id="clone-with"></a>
## Клонирование объекта с изменением выбранных свойств

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.cloning.php)

`clone( $object, $changes )` создаёт копию и меняет свойства после `__clone()`.

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

Изменения подчиняются видимости записи, поэтому обновления readonly требуют разрешённой области класса. Конструктор не вызывается повторно: проверяйте изменения явно; остальные вложенные объекты сохраняют поведение поверхностного клонирования.

<a id="dynamic-properties"></a>
## Динамические свойства устарели

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)

Создание необъявленного свойства обычного класса вызывает сообщение об устаревшей возможности.

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

Исключения включают `stdClass`, классы с `#[AllowDynamicProperties]` и их потомков и обработку через `__set()`, но создание необъявленного свойства внутри `__set()` всё ещё может вызвать сообщение.
