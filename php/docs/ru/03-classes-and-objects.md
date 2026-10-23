# Классы и объекты

[EN](../en/03-classes-and-objects.md) | [UK](../uk/03-classes-and-objects.md) | [RU](../ru/03-classes-and-objects.md)

[Содержание](../../README.ru.md)

**На этой странице**

- [Создаём класс прямо на месте](#anonymous-classes)
- [Объявляем свойства прямо в конструкторе](#constructor-promotion)
- [Объект как запасной аргумент](#new-initializers)
- [Добавляем к методу описание через атрибут](#attributes)
- [Принимаем строку или объект с `__toString()`](#stringable)
- [Задаём допустимые состояния через enum](#enums)
- [Берём имя или значение enum для константы](#enum-constants)
- [Записываем свойство один раз через `readonly`](#readonly-properties)
- [Делаем все свойства класса `readonly`](#readonly-classes)
- [Отделяем вложенный объект при клонировании](#readonly-cloning)
- [Создаём анонимный `readonly`-класс](#readonly-anonymous)
- [Закрываем константы и запрещаем переопределение](#constant-visibility-final)
- [Читаем константу по имени из переменной](#dynamic-constants)
- [Храним константы вместе с кодом трейта](#trait-constants)
- [Запрещаем наследнику менять метод трейта](#trait-final)
- [Проверяем, что метод действительно переопределён](#override)
- [Обрабатываем чтение и запись свойства](#property-hooks)
- [Проверяем значение перед записью в свойство](#setter-validation)
- [Читать можно всем, менять только классу](#asymmetric-visibility)
- [Требуем свойство через интерфейс](#interface-properties)
- [Готовим объект только при первом обращении](#lazy-objects)
- [Вызываем метод сразу после `new`](#new-chaining)
- [Проверяем переопределение свойства](#override-properties)
- [Ограничиваем запись в статическое свойство](#static-visibility)
- [Добавляем `final` к свойству в конструкторе](#final-promotion)
- [Копируем объект и меняем выбранные свойства](#clone-with)
- [Не создаём свойства случайно](#dynamic-properties)

<a id="anonymous-classes"></a>
## Создаём класс прямо на месте

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.anonymous.php)

Если небольшой объект нужен только здесь, класс можно объявить сразу после `new`, не придумывая ему отдельное имя. Получившийся объект используем как обычно.

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

У такого класса тоже могут быть родитель через `extends` и интерфейсы через `implements`. Отсутствие имени не лишает его обычных возможностей класса.

<a id="constructor-promotion"></a>
## Объявляем свойства прямо в конструкторе

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

Не обязательно отдельно объявлять свойство, принимать параметр и присваивать одно другому. Если у параметра конструктора есть `public`, `protected` или `private`, PHP сам создаст свойство и запишет в него переданное значение.

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

В примере `id` и `name` уже доступны у объекта сразу после `new User()`. Присваивание происходит до выполнения тела конструктора. Обычные параметры без объявления свойства тоже можно оставить рядом.

<a id="new-initializers"></a>
## Объект как запасной аргумент

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration81.new-features.php)

Если вызывающий код не передал объект, можно создать его прямо в значении по умолчанию. Здесь без второго аргумента функция получит новый `Prefix` и добавит префикс `note`.

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

Когда используется этот запасной аргумент, объект создаётся для конкретного вызова, а не один на все вызовы. `new` также разрешён в аргументах атрибутов, начальных значениях локальных `static`-переменных и глобальных константах. Для начального значения обычного свойства такая запись всё ещё не подходит.

<a id="attributes"></a>
## Добавляем к методу описание через атрибут

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.attributes.overview.php)

Атрибут хранит дополнительные сведения рядом с объявлением. В примере у `export()` записано описание, а через `ReflectionMethod` мы находим его и получаем объект `Description`. Само наличие атрибута метод не запускает.

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

`TARGET_METHOD` разрешает наш атрибут только у методов. Повторять его разрешает отдельный флаг `IS_REPEATABLE`; эти правила проверяются при `newInstance()`.

В PHP 8.5 появился `#[DelayedTargetValidation]`: он позволяет отложить проверку места установки встроенного атрибута или атрибута расширения до создания его объекта через Reflection. Ещё есть [атрибуты для сообщений об ошибках и устаревании](08-errors-and-debugging.md#deprecated-attribute) и [атрибуты у глобальных констант](01-syntax-and-functions.md#global-constant-attributes).

<a id="stringable"></a>
## Принимаем строку или объект с `__toString()`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.stringable.php)

Объект с `__toString()` умеет превращаться в строку и автоматически подходит под интерфейс `Stringable`. Тип `string|Stringable` позволяет функции принимать и обычный текст, и такой объект.

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

Внутри явно получаем строку через `(string)`. При строгой типизации один `string` сам по себе объект `Stringable` не принимает. И преобразование тоже может завершиться ошибкой: `__toString()` [может выбросить исключение](08-errors-and-debugging.md#to-string-exceptions).

<a id="enums"></a>
## Задаём допустимые состояния через enum

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.enumerations.backed.php)

Если у статуса есть только несколько допустимых вариантов, их удобно собрать в enum. Каждый `case` здесь отдельный именованный объект. Вариант с базовым `string` или `int` называют backed enum: у него есть уникальное `value` для хранения, а `name` есть у любого варианта.

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

`from()` ищет вариант по значению и выбрасывает `ValueError`, если не нашёл. `tryFrom()` в таком случае отдаёт `null`, но неподходящий тип аргумента всё ещё может вызвать `TypeError`. `cases()` возвращает все варианты. У enum без базового типа нет ни `value`, ни методов `from()` и `tryFrom()`.

<a id="enum-constants"></a>
## Берём имя или значение enum для константы

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration82.new-features.php)

Не обязательно повторять строку из enum вручную. При объявлении константы можно прочитать `name` нужного варианта, а у backed enum ещё и `value`.

```php
enum Format: string
{
	case Json = 'json';
}

const DEFAULT_FORMAT = Format::Json->value;
const DEFAULT_CASE_NAME = Format::Json->name;

echo DEFAULT_CASE_NAME, ':', DEFAULT_FORMAT; # Json:json
```

В примере имя `Json` и значение `json` берутся из одного варианта. Это отдельная возможность enum, а не общее разрешение читать любые свойства объектов или вызывать методы в константах.

<a id="readonly-properties"></a>
## Записываем свойство один раз через `readonly`

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

Типизированное нестатическое `readonly`-свойство можно заполнить один раз. После этого присвоить ему другое значение нельзя. Но если внутри объект, его собственные свойства менять по-прежнему можно, поэтому счётчик в примере спокойно станет равен `2`.

```php
class Snapshot
{
	public function __construct( public readonly stdClass $data ) {}
}

$snapshot = new Snapshot( (object)['count' => 1] );
$snapshot->data->count++;

echo $snapshot->data->count; # 2
```

Для числа запрет касается и `++`, для массива нельзя менять его элементы через это свойство. Объект внутри не становится неизменяемым автоматически.

До PHP 8.4 впервые записать значение мог только объявивший свойство класс. С PHP 8.4 по умолчанию действует `protected(set)`, то есть это доступно и наследнику, если право записи не сужено явно.

<a id="readonly-classes"></a>
## Делаем все свойства класса `readonly`

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

Если все свойства объекта должны заполняться один раз, можно поставить `readonly` перед всем классом. У `Point` не нужно отдельно повторять его у `x` и `y`.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y; # 3:4
```

Все свойства должны иметь тип. Статические и динамически добавленные свойства здесь запрещены, а наследование должно соблюдать правила `readonly`-классов. Вложенные объекты при этом всё ещё могут меняться, как и у отдельного `readonly`-свойства.

<a id="readonly-cloning"></a>
## Отделяем вложенный объект при клонировании

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration83.new-features.php)

Обычный `clone` копирует внешний объект, но вложенная дата остаётся общей. Если хотим менять дату копии отдельно, клонируем и её в `__clone()`. С PHP 8.3 в этот момент разрешено заново заполнить `readonly`-свойство.

```php
readonly class Schedule
{
	public function __construct( public DateTime $date ) {}

	public function __clone(): void
	{
		# копируем дату отдельно, чтобы не менять её у оригинала
		$this->date = clone $this->date;
	}
}

$original = new Schedule( new DateTime('2025-01-01') );
$copy = clone $original;
$copy->date->modify( '+1 day' );

echo $original->date->format( 'Y-m-d' ), ':', $copy->date->format( 'Y-m-d' );
# 2025-01-01:2025-01-02
```

Поэтому изменение даты у `$copy` не трогает `$original`. Разрешение действует именно во время клонирования. Остальные вложенные объекты, которые явно не скопировали, при обычном клонировании останутся общими.

<a id="readonly-anonymous"></a>
## Создаём анонимный `readonly`-класс

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration83.new-features.php)

Один небольшой объект без отдельного имени тоже можно сделать `readonly`. В примере `id` заполняется в конструкторе, после этого присвоить ему другое значение нельзя.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id; # 7
```

Правила те же, что у именованного `readonly`-класса: свойства с типами, а вложенные объекты не становятся неизменяемыми сами по себе.

<a id="constant-visibility-final"></a>
## Закрываем константы и запрещаем переопределение

**PHP 7.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.constants.php)

С PHP 7.1 у константы класса можно указать доступ. В примере `INTERNAL_FACTOR` оставляем для самого класса, а `PAGE_SIZE` разрешаем читать снаружи.

```php
class Limits
{
	private const INTERNAL_FACTOR = 2;
	public const PAGE_SIZE = 20;
}

echo Limits::PAGE_SIZE; # 20
```

С PHP 8.1 запись `final public const PAGE_SIZE = 20;` ещё и запрещает наследникам переобъявлять эту константу. А [тип константы класса](02-types.md#typed-constants) можно указать с PHP 8.3, это отдельная возможность.

<a id="dynamic-constants"></a>
## Читаем константу по имени из переменной

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.constants.php)

Имя константы может прийти из переменной: `Format::{$name}` прочитает нужную константу класса. В примере `$name` содержит `JSON`, поэтому получаем `application/json`.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
$mime = Format::{$name};

echo $mime; # application/json
```

Права доступа остаются в силе, неизвестное имя вызовет ошибку. Для имени самого класса объекта с PHP 8.0 есть другая запись: `$object::class`.

<a id="trait-constants"></a>
## Храним константы вместе с кодом трейта

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Трейт может принести классу не только методы и свойства, но и константы. Читаем их через класс, который подключил трейт: здесь это `Exporter::FORMAT`. Если такое имя уже объявлено, определения должны быть совместимыми.

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

Ещё изменение трейтов в PHP 8.3: если наследник сам повторно подключает трейт, статические свойства этого трейта у него хранятся отдельно от родительских. Это важно, когда рассчитываем на общий статический счётчик или кеш.

<a id="trait-final"></a>
## Запрещаем наследнику менять метод трейта

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Иногда метод трейта должен остаться как есть только в конкретном классе. При подключении можно пометить его `as final`, не меняя сам трейт.

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

Метод `identifier()` по-прежнему доступен у `Report`, но наследник `Report` уже не сможет его переопределить.

<a id="override"></a>
## Проверяем, что метод действительно переопределён

**PHP 8.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.override.php)

Опечатка в имени метода может незаметно создать новый метод вместо переопределения старого. `#[Override]` просит PHP проверить это при компиляции: должен найтись метод родителя или интерфейса, который мы реализуем.

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

Здесь `format()` есть в интерфейсе, поэтому проверка проходит. К `__construct()` этот атрибут не применяется. Для [переопределения свойств](#override-properties) он тоже работает, начиная с PHP 8.5.

<a id="property-hooks"></a>
## Обрабатываем чтение и запись свойства

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

У свойства могут быть свои `get` и `set`, их называют hooks. Так можно очистить значение при записи или вычислить его при чтении. В примере `name` хранит имя без пробелов по краям, а `display_name` каждый раз получает вариант в верхнем регистре.

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

Короткий `set =>` сам сохраняет результат выражения. У `display_name` отдельного сохранённого значения нет, это виртуальное свойство. Поскольку у него есть только `get`, записать в него нельзя. Hooks не поддерживаются у статических свойств и не сочетаются с `readonly`.

<a id="setter-validation"></a>
## Проверяем значение перед записью в свойство

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Если в `set` нужен не один расчёт, а несколько действий, пишем обычный блок. В отличие от короткого `set =>`, здесь нужно самим записать принятое значение в свойство.

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

Присваивание `$this->quantity = $value` внутри этого обработчика пишет прямо в хранилище и не вызывает `set` повторно. Если значение отрицательное, исключение происходит до присваивания, поэтому старое количество остаётся на месте.

<a id="asymmetric-visibility"></a>
## Читать можно всем, менять только классу

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

Запись `public private(set)` разделяет права: читать заголовок можно снаружи, а присваивать новое значение только внутри объявившего его класса. Поэтому переименование идёт через `rename()`, где заодно убираем пробелы.

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

Это не `readonly`: сам класс может менять значение сколько нужно. Право записи не может быть шире права чтения. `private(set)` также делает свойство `final`. Получение ссылки и изменение элементов массива тоже считаются записью и требуют соответствующего доступа.

<a id="interface-properties"></a>
## Требуем свойство через интерфейс

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.interfaces.php)

Интерфейс может потребовать не только методы, но и доступное свойство. `{ get; }` означает, что его можно читать, а `{ get; set; }` требует ещё и запись. В примере любой `HasTitle` должен давать прочитать строковый `title`.

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

Выполнить требование можно обычным свойством, как у `Article`, или через hooks. Типы при этом должны оставаться совместимыми по правилам их изменения. Само требование `get` не запрещает классу разрешить запись тоже: интерфейс задаёт нужный минимум.

<a id="lazy-objects"></a>
## Готовим объект только при первом обращении

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

Через Reflection можно отложить заполнение объекта до обращения к его состоянию. Это полезно, если объект создали, но его данные могут вообще не понадобиться. У ghost заполняется тот же объект, а proxy получает отдельный настоящий объект из переданной функции.

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

По выводу видно порядок: сначала `created`, а строки `initialize:...` появляются только при чтении `title`. Proxy и созданный для него объект остаются разными экземплярами. Возможность рассчитана на пользовательские классы и `stdClass`, произвольный внутренний класс PHP так отложить нельзя.

<a id="new-chaining"></a>
## Вызываем метод сразу после `new`

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration84.new-features.php)

Создали объект и сразу взяли результат его метода, без отдельной переменной и внешних скобок вокруг `new`. Скобки самого конструктора при такой записи нужны.

```php
$date = new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );

echo $date; # 2025-01-02
```

Старая запись `(new ClassName(...))->method()` тоже остаётся правильной, переписывать её только из-за версии не обязательно.

<a id="override-properties"></a>
## Проверяем переопределение свойства

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.override.php)

В PHP 8.5 `#[Override]` работает и у свойств. Если рассчитываем заменить свойство родителя или выполнить требование интерфейса, атрибут проверит наличие такого объявления при компиляции.

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

В примере `Report` меняет чтение уже существующего `label`. Атрибут не отменяет остальные правила: несовместимый тип или переопределение `final`-свойства всё равно запрещены.

<a id="static-visibility"></a>
## Ограничиваем запись в статическое свойство

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.visibility.php)

Разделять права чтения и записи теперь можно и у статического свойства. Счётчик `calls` виден снаружи, но увеличивать его может только сам `Counter`.

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

Так ограничиваем доступ к значению, а не меняем срок его хранения. Статическое свойство всё ещё нельзя объявить `readonly`.

<a id="final-promotion"></a>
## Добавляем `final` к свойству в конструкторе

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.final.php)

С PHP 8.4 свойство можно защитить от повторного объявления в наследнике через `final`. В PHP 8.5 это разрешили и для свойств, объявленных прямо в параметрах конструктора.

```php
class Ticket
{
	public function __construct( final public string $code ) {}
}

$ticket = new Ticket( 'A' );
$ticket->code = 'B';

echo $ticket->code; # B
```

`final` не запрещает менять само значение: в примере `code` меняется с `A` на `B`. Он запрещает наследнику заново объявить это свойство. Для запрета повторного присваивания нужен `readonly`.

<a id="clone-with"></a>
## Копируем объект и меняем выбранные свойства

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.cloning.php)

Через `clone( $object, $changes )` можно сразу получить копию с нужными изменениями. Сначала выполняется `__clone()`, затем присваиваются перечисленные свойства. В примере оригинальный цвет остаётся с `alpha = 255`, а копия получает `128`.

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

Права записи всё ещё проверяются, поэтому менять `readonly` так можно только из разрешённой области класса. Конструктор повторно не вызывается: проверку диапазона в `with_alpha()` оставляем сами. Вложенные объекты без отдельного клонирования по-прежнему остаются общими.

<a id="dynamic-properties"></a>
## Не создаём свойства случайно

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)

Запись в необъявленное свойство обычного класса с PHP 8.2 выдаёт сообщение об устаревании. Это заодно помогает заметить опечатки в именах. Нужное свойство лучше объявить явно, как `name` у `User`.

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

Для свободного набора полей есть `stdClass`, как у `$metadata`. Исключения также есть у классов с `#[AllowDynamicProperties]` и их наследников. Обработчик `__set()` может принимать такие присваивания сам, но создание нового необъявленного свойства внутри него всё ещё может вызвать то же сообщение.
