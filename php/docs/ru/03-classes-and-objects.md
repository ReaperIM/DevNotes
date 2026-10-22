# Классы и объекты

[English](../en/03-classes-and-objects.md) | [Українська](../uk/03-classes-and-objects.md) | [Русский](../ru/03-classes-and-objects.md)

[Содержание](../../README.ru.md)

От локальных анонимных реализаций до enum, readonly-значений, обработчиков свойств и отложенной инициализации. Каждый пример независим. По пометкам версий отличайте исходную возможность от более поздних улучшений, указанных в тексте.

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

**PHP 7.0+ | Ядро**

Анонимный класс создаёт объект, реализация которого нужна локально. Он может реализовывать интерфейс или расширять другой класс, поэтому удобен для небольшой тестовой подмены или объекта обратного вызова с состоянием.

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

Для повторно используемого предметного объекта лучше именованный класс. Анонимность не означает отсутствие типов или изоляцию: объект всё равно подчиняется правилам наследования, видимости и интерфейсов.

[Документация PHP](https://www.php.net/manual/en/language.oop5.anonymous.php)

<a id="constructor-promotion"></a>
## Объявление свойств в конструкторе

**PHP 8.0+ | Ядро**

Параметр конструктора с модификатором видимости объявляет свойство и присваивает ей аргумент. Это убирает повторное объявление свойства и `$this->name = $name` в простых объектах данных.

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

Можно сочетать продвинутые и обычные параметры. Присваивания происходят до выполнения тела конструктора, поэтому в нём можно проверять или нормализовать сохранённые значения. Публичное свойство остаётся публичным: само продвижение не добавляет неизменяемости или проверок.

[Документация PHP](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

<a id="new-initializers"></a>
## Объекты как значения параметров по умолчанию

**PHP 8.1+ | Ядро**

Параметр по умолчанию может создать объект через `new`. Это удобно, когда у зависимости есть небольшое разумное начальное значение, но вызывающий код может передать другой экземпляр.

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

Когда используется значение по умолчанию, объект вычисляется для этого вызова, а не является одним общим изменяемым экземпляром для всех вызовов. PHP 8.1 также разрешает `new` в аргументах атрибутов, локальных статических инициализаторах и глобальных константах с синтаксическими ограничениями. Обычное объявление свойства всё ещё не может иметь `new` как начальное значение.

[Документация PHP](https://www.php.net/manual/en/migration81.new-features.php)

<a id="attributes"></a>
## Атрибуты и чтение их метаданных

**PHP 8.0+ | Ядро**

Атрибуты добавляют структурированные метаданные к объявлениям. Определите класс атрибута, разместите его на разрешённом объявлении и прочитайте через reflection. Собственные атрибуты действуют только тогда, когда код приложения их интерпретирует.

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

Осознанно выбирайте допустимые цели и `Attribute::IS_REPEATABLE`. `newInstance()` создаёт атрибут и проверяет его использование; сама метка метаданных не проверяет права доступа. PHP 8.5 добавляет `#[DelayedTargetValidation]` для более сложных инструментов: проверка неправильной цели встроенных атрибутов или атрибутов расширений откладывается до создания через reflection. Смотрите также [диагностические атрибуты](08-errors-and-debugging.md#deprecated-attribute) и [атрибуты глобальных констант](01-syntax-and-functions.md#global-constant-attributes).

[Документация PHP](https://www.php.net/manual/en/language.attributes.overview.php)

<a id="stringable"></a>
## Интерфейс Stringable

**PHP 8.0+ | Ядро**

Объект с `__toString()` неявно реализует `Stringable`. Объединение `string|Stringable` позволяет указать, что API принимает обычную строку или объект со строковым представлением.

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

`Stringable` не является тем же типом, что `string`, особенно при строгой типизации. Явное приведение выше является границей, где запрашивается строковое представление. Преобразование может выбросить исключение; смотрите [исключения из __toString()](08-errors-and-debugging.md#to-string-exceptions).

[Документация PHP](https://www.php.net/manual/en/class.stringable.php)

<a id="enums"></a>
## Enum для закрытого набора состояний

**PHP 8.1+ | Ядро**

Enum определяет конечный набор именованных объектов. Backed enum добавляет уникальное значение `string` или `int` для хранения или внешних данных. Код может работать с типизированным состоянием, а база хранить небольшое скалярное значение.

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

`from()` выбрасывает `ValueError` для неизвестного значения, а `tryFrom()` возвращает `null`. Некорректный тип аргумента всё ещё может вызвать `TypeError`, поэтому `tryFrom()` не является универсальным валидатором. Случай enum является объектом: `Status::Draft !== 'draft'`. Используйте `->value` только на скалярной границе. Pure enum имеет случаи и `->name`, но не имеет `->value`, `from()` или `tryFrom()`.

[Документация PHP](https://www.php.net/manual/en/language.enumerations.backed.php)

<a id="enum-constants"></a>
## Свойства enum в константных выражениях

**PHP 8.2+ | Ядро**

Поля `name` и `value` случая enum можно читать в константных выражениях. Это позволяет не дублировать скалярную запись рядом с объявлением enum.

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

Pure enum не имеет свойства `value`. Такие выражения читают встроенные метаданные enum, а не разрешают произвольный доступ к свойствам объектов или выполнение методов в константах.

[Документация PHP](https://www.php.net/manual/en/migration82.new-features.php)

<a id="readonly-properties"></a>
## Readonly-свойства и поверхностная неизменяемость

**PHP 8.1+ | Ядро**

`readonly`-свойство можно инициализировать один раз, после чего его обычно нельзя переприсвоить или изменить. Это полезно для идентичности и объектов-значений, но объект внутри такого свойства всё ещё может иметь изменяемое состояние.

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

Свойство должно быть типизированным и не может быть статическим. Увеличение readonly-скаляра или изменение элемента его массива запрещены; изменение содержимого объекта по ссылке является другой операцией. До PHP 8.4 инициализация неявно была приватной для объявившего класса; с PHP 8.4 по умолчанию действует `protected(set)`, разрешающая инициализацию из дочерней области, если права явно не сужены.

[Документация PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

<a id="readonly-classes"></a>
## Readonly-классы

**PHP 8.2+ | Ядро**

Readonly-класс применяет правила readonly ко всем свойствам экземпляра. Это сокращает объект данных и защищает от случайно изменяемого нового свойства.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y;
# 3:4
```

Свойства должны быть типизированными; статические и динамические свойства недоступны. Наследование должно соблюдать правила readonly-классов. Как и с отдельным readonly-свойством, вложенные изменяемые объекты не становятся неизменяемыми автоматически.

[Документация PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

<a id="readonly-cloning"></a>
## Повторная инициализация readonly в __clone()

**PHP 8.3+ | Ядро**

При клонировании readonly-свойства можно повторно инициализировать, чтобы копия получила собственные вложенные объекты. Это решает распространённую проблему поверхностной копии без общего разрешения изменять исходное свойство.

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

Это контролируемое исключение из запрета записи на время клонирования, а не разрешение многократно обновлять свойство. Клонирование остаётся поверхностным, если явно не клонировать вложенные объекты, которым нужно независимое состояние.

[Документация PHP](https://www.php.net/manual/en/migration83.new-features.php)

<a id="readonly-anonymous"></a>
## Анонимные readonly-классы

**PHP 8.3+ | Ядро**

Локальный анонимный объект теперь может соблюдать те же readonly-правила, что и именованный класс. Это удобно для небольшого неизменяемого значения, которому не нужно повторно используемое публичное имя класса.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id;
# 7
```

Это добавляет readonly к анонимным классам; обычные анонимные классы существуют с PHP 7.0. Ограничения на типизированные свойства и вложенное изменяемое состояние сохраняются.

[Документация PHP](https://www.php.net/manual/en/migration83.new-features.php)

<a id="constant-visibility-final"></a>
## Видимость и final для констант класса

**PHP 7.1+ | Ядро**

PHP 7.1 позволяет задавать для констант класса public, protected или private. PHP 8.1 добавляет `final`, запрещающий дочернему классу заменять значение из контракта родительского класса.

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

В PHP 8.1+ можно объявить `final public const PAGE_SIZE = 20;`, если переопределение нарушило бы замысел. Видимость управляет доступом, а final переопределением. [Типизированные константы класса](02-types.md#typed-constants) являются отдельным дополнением PHP 8.3.

[Документация PHP](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="dynamic-constants"></a>
## Выбор константы класса по имени

**PHP 8.3+ | Ядро**

`ClassName::{$name}` читает константу класса по имени из переменной. Это убирает построение строки `ClassName::CONSTANT` для `constant()`, когда класс уже известен.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
echo Format::{$name};
# application/json
```

Правила видимости остаются в силе, а неизвестная константа вызывает ошибку. Внешнее имя нужно выбирать из разрешённого набора. С PHP 8.0 `$object::class` также позволяет получить имя класса объекта без отдельного вызова `get_class()`.

[Документация PHP](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="trait-constants"></a>
## Константы, объявленные в traits

**PHP 8.2+ | Ядро**

Trait может объявлять константы рядом с поведением, которое их использует. Это удобно, когда общая реализация имеет небольшую постоянную опцию или значение протокола.

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

Обращайтесь к константам через класс, использующий trait. Конфликтующие объявления должны соответствовать правилам совместимости PHP. Связанное изменение PHP 8.3 касается статических свойств trait: если дочерний класс явно использует тот же trait повторно, его статическое свойство отделяется от родительского; не предполагайте, что оба счётчика общие.

[Документация PHP](https://www.php.net/manual/en/language.oop5.traits.php)

<a id="trait-final"></a>
## Импорт метода trait как final

**PHP 8.3+ | Ядро**

Класс может пометить импортированный метод trait как final в блоке адаптации. Общий trait остаётся пригодным для других классов, а этот класс запрещает потомкам переопределять импортированное поведение.

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

Это ограничивает переопределение в потомках, а не делает весь класс final и не фиксирует свойства объекта. Используйте для поведения, которое контракт класса намеренно делает неизменным.

[Документация PHP](https://www.php.net/manual/en/language.oop5.traits.php)

<a id="override"></a>
## Проверка переопределённых методов через #[Override]

**PHP 8.3+ | Ядро**

Атрибут указывает, что метод должен заменять унаследованный метод или реализовывать метод интерфейса. Опечатка или переименование в родительском API тогда вызовет ошибку компиляции, а не незаметно создаст посторонний метод.

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

Атрибут проверяет существование переопределяемого объявления, а не правильность реализации. Его нельзя применять к `__construct()`. [Переопределение свойств](#override-properties) получает такую же пометку в PHP 8.5.

[Документация PHP](https://www.php.net/manual/en/class.override.php)

<a id="property-hooks"></a>
## Обработчики свойств: хранимые и виртуальные значения

**PHP 8.4+ | Ядро**

Свойство может менять поведение чтения через `get` и записи через `set`. Backed-свойство хранит значение, а виртуальное вычисляет его через обработчики без собственного хранилища.

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

Сокращённый `set` автоматически записывает результат выражения в хранимое значение. Виртуальное свойство только с `get` недоступно для записи. Обработчики доступны для нестатических свойств и не сочетаются с `readonly`. Предметные операции над несколькими объектами оставляйте методами; доступ к свойству должен быть понятным.

[Документация PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

<a id="setter-validation"></a>
## Полный setter должен записывать значение свойства

**PHP 8.4+ | Ядро**

Блочный обработчик `set` может проверить данные перед сохранением. В отличие от сокращённого выражения, блок должен явно присвоить принятое значение свойству.

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

Внутри этого обработчика `$this->quantity` обращается к хранилищу, а не рекурсивно вызывает тот же обработчик. Проверка происходит до присваивания, поэтому отклонённое обновление оставляет прежнее значение. Начальное значение свойства не заменяет проверку дальнейших записей.

[Документация PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

<a id="asymmetric-visibility"></a>
## Разные права для чтения и записи

**PHP 8.4+ | Ядро**

`public private(set)` позволяет внешнему коду читать свойство, но записывать его может только объявивший класс. Это сохраняет простой публичный интерфейс чтения без общего разрешения на присваивание.

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

Права записи не могут быть шире прав чтения. `private(set)` также делает свойство final, поэтому потомок не может объявить его заново для обхода ограничения. Это не readonly: разрешённый класс может записывать повторно. Получение ссылки или изменение элемента массива также подчиняются правам записи.

[Документация PHP](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

<a id="interface-properties"></a>
## Контракты свойств в интерфейсах

**PHP 8.4+ | Ядро**

Интерфейс может требовать публичное свойство для чтения или записи с помощью объявлений в форме обработчиков. Реализация может использовать обычное свойство или обработчики, если нужные операции доступны.

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

Требование `get` не заставляет реализацию запретить запись, а лишь гарантирует чтение. Соответственно, `{ get; set; }` требует обеих операций. Уточняя контракт, соблюдайте правила вариантности типов.

[Документация PHP](https://www.php.net/manual/en/language.oop5.interfaces.php)

<a id="lazy-objects"></a>
## Отложенные объекты: ghost и proxy

**PHP 8.4+ | Ядро**

Reflection может создать объект, инициализация которого отложена до использования состояния. Lazy ghost инициализируется на месте, а lazy proxy получает от фабрики отдельный настоящий экземпляр и передаёт ему операции с состоянием.

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

Это откладывает работу, а не выполняет её параллельно. Proxy и его настоящий экземпляр имеют разную идентичность, что важно для таблиц объектов и строгого сравнения. Механизм предназначен для пользовательских классов и `stdClass`, а не произвольных внутренних классов. Используйте его, когда отложенная инициализация решает реальную проблему загрузки или времени жизни.

[Документация PHP](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

<a id="new-chaining"></a>
## Вызов метода сразу после new

**PHP 8.4+ | Ядро**

Объект, созданный со скобками конструктора, можно использовать сразу без дополнительных скобок вокруг всего выражения `new`. Это облегчает короткую запись «создать и использовать».

```php
echo new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );
# 2025-01-02
```

Прежняя запись `(new DateTimeImmutable(...))->format(...)` остаётся правильной. Оставляйте переменную, если объект нужен в нескольких следующих шагах или однострочная запись скрывает важный жизненный цикл.

[Документация PHP](https://www.php.net/manual/en/migration84.new-features.php)

<a id="override-properties"></a>
## Проверка переопределения свойств через #[Override]

**PHP 8.5+ | Ядро**

Проверка переопределения распространяется с методов на свойства. Это особенно полезно для виртуального свойства, унаследованное вычисление которого уточняется.

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

Если ожидаемое свойство родительского класса или интерфейса исчезает, объявление вызывает ошибку компиляции. Остальные правила сохраняются: атрибут не делает несовместимое переопределение или переопределение final-свойства допустимым.

[Документация PHP](https://www.php.net/manual/en/class.override.php)

<a id="static-visibility"></a>
## Асимметричная видимость статических свойств

**PHP 8.5+ | Ядро**

Статические свойства тоже могут иметь более узкие права записи. Общий счётчик можно читать снаружи, тогда как обновления остаются в классе, отвечающем за операцию.

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

Это управляет доступом, а не изоляцией запросов или параллельностью. В долгоживущих процессах статическое состояние переживает последовательные операции. Readonly для статических свойств является другой идеей и остаётся неподдерживаемым.

[Документация PHP](https://www.php.net/manual/en/language.oop5.visibility.php)

<a id="final-promotion"></a>
## Final для свойств в параметрах конструктора

**PHP 8.5+ | Ядро**

Final-свойства появились в PHP 8.4; PHP 8.5 разрешает указывать модификатор прямо у свойства, объявленного параметром конструктора. Он запрещает потомку заново объявлять это свойство.

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

Final не означает readonly: приведённое публичное свойство всё ещё можно переприсваивать. Выбирайте final для ограничения наследования, а readonly для ограничения записи значения; это разные задачи.

[Документация PHP](https://www.php.net/manual/en/language.oop5.final.php)

<a id="clone-with"></a>
## Клонирование объекта с изменением выбранных свойств

**PHP 8.5+ | Ядро**

Функциональная форма `clone( $object, $changes )` создаёт копию и применяет карту значений свойств. Это удобно для метода `with_*()`, возвращающего обновлённый readonly-объект-значение.

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

Замены происходят после `__clone()` и соблюдают права записи свойств. Обновления readonly размещайте в разрешённой области класса. Конструктор повторно не выполняется, поэтому проверяйте изменённые значения до клонирования: проверки только в конструкторе недостаточно. Неуказанные вложенные объекты сохраняют обычные правила поверхностной копии.

[Документация PHP](https://www.php.net/manual/en/language.oop5.cloning.php)

<a id="dynamic-properties"></a>
## Динамические свойства устарели

**PHP 8.2+ | Ядро**

Создание необъявленного свойства в обычном классе теперь создаёт сообщение об устаревании. Это выявляет ошибки в именах и делает настоящую структуру объекта понятнее.

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

Объявляйте известные поля. Для намеренно произвольных данных используйте массив или `stdClass`. Исключения охватывают классы с `#[AllowDynamicProperties]`, их потомков и классы с `__set()`; реализация, создающая необъявленные свойства внутри `__set()`, всё ещё может вызывать это сообщение. Не добавляйте атрибут везде только для скрытия ошибок в именах.

[Документация PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)
