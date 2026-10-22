# Класи та об’єкти

[English](../en/03-classes-and-objects.md) | [Українська](../uk/03-classes-and-objects.md) | [Русский](../ru/03-classes-and-objects.md)

[Зміст](../../README.uk.md)

Від локальних анонімних реалізацій до enum, readonly-значень, обробників властивостей та відкладеної ініціалізації. Кожен приклад незалежний. За позначками версій відрізняйте початкову можливість від пізніших покращень, зазначених у тексті.

**На цій сторінці**

- [Анонімні класи](#anonymous-classes)
- [Оголошення властивостей у конструкторі](#constructor-promotion)
- [Об’єкти як значення параметрів за замовчуванням](#new-initializers)
- [Атрибути та читання їхніх метаданих](#attributes)
- [Інтерфейс Stringable](#stringable)
- [Enum для закритого набору станів](#enums)
- [Властивості enum у константних виразах](#enum-constants)
- [Readonly-властивості та поверхнева незмінюваність](#readonly-properties)
- [Readonly-класи](#readonly-classes)
- [Повторна ініціалізація readonly у __clone()](#readonly-cloning)
- [Анонімні readonly-класи](#readonly-anonymous)
- [Видимість і final для констант класу](#constant-visibility-final)
- [Вибір константи класу за назвою](#dynamic-constants)
- [Константи, оголошені у traits](#trait-constants)
- [Імпорт методу trait як final](#trait-final)
- [Перевірка перевизначених методів через #[Override]](#override)
- [Обробники властивостей: збережені та віртуальні значення](#property-hooks)
- [Повний setter має записувати значення властивості](#setter-validation)
- [Різні права для читання та запису](#asymmetric-visibility)
- [Контракти властивостей в інтерфейсах](#interface-properties)
- [Відкладені об’єкти: ghost і proxy](#lazy-objects)
- [Виклик методу одразу після new](#new-chaining)
- [Перевірка перевизначення властивостей через #[Override]](#override-properties)
- [Асиметрична видимість статичних властивостей](#static-visibility)
- [Final для властивостей у параметрах конструктора](#final-promotion)
- [Клонування об’єкта зі зміною вибраних властивостей](#clone-with)
- [Динамічні властивості застаріли](#dynamic-properties)

<a id="anonymous-classes"></a>
## Анонімні класи

**PHP 7.0+ | Ядро**

Анонімний клас створює об’єкт, реалізація якого потрібна локально. Він може реалізовувати інтерфейс або розширювати інший клас, тому зручний для невеликої тестової підміни чи об’єкта зворотного виклику зі станом.

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

Для повторно використовуваного предметного об’єкта краще іменований клас. Анонімність не означає відсутність типів або ізоляцію: об’єкт усе одно підпорядковується правилам наслідування, видимості та інтерфейсів.

[Документація PHP](https://www.php.net/manual/en/language.oop5.anonymous.php)

<a id="constructor-promotion"></a>
## Оголошення властивостей у конструкторі

**PHP 8.0+ | Ядро**

Параметр конструктора з модифікатором видимості оголошує властивість і присвоює їй аргумент. Це прибирає повторне оголошення властивості та `$this->name = $name` у простих об’єктах даних.

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

Можна поєднувати просунуті та звичайні параметри. Присвоєння відбуваються до виконання тіла конструктора, тож у ньому можна перевіряти або нормалізувати збережені значення. Публічна властивість залишається публічною: саме просування не додає незмінюваності чи перевірок.

[Документація PHP](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

<a id="new-initializers"></a>
## Об’єкти як значення параметрів за замовчуванням

**PHP 8.1+ | Ядро**

Параметр за замовчуванням може створити об’єкт через `new`. Це зручно, коли залежність має невелике доречне початкове значення, але викликач може передати інший екземпляр.

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

Коли використовується значення за замовчуванням, об’єкт обчислюється для цього виклику, а не є одним спільним змінюваним екземпляром для всіх викликів. PHP 8.1 також дозволяє `new` в аргументах атрибутів, локальних статичних ініціалізаторах і глобальних константах із синтаксичними обмеженнями. Звичайне оголошення властивості все ще не може мати `new` як початкове значення.

[Документація PHP](https://www.php.net/manual/en/migration81.new-features.php)

<a id="attributes"></a>
## Атрибути та читання їхніх метаданих

**PHP 8.0+ | Ядро**

Атрибути додають структуровані метадані до оголошень. Визначте клас атрибута, розмістіть його на дозволеному оголошенні та прочитайте через reflection. Власні атрибути діють лише тоді, коли код застосунку їх інтерпретує.

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

Свідомо обирайте дозволені цілі та `Attribute::IS_REPEATABLE`. `newInstance()` створює атрибут і перевіряє його використання; сама мітка метаданих не перевіряє права доступу. PHP 8.5 додає `#[DelayedTargetValidation]` для складніших інструментів: перевірка неправильної цілі вбудованих атрибутів або атрибутів розширень відкладається до створення через reflection. Дивіться також [діагностичні атрибути](08-errors-and-debugging.md#deprecated-attribute) та [атрибути глобальних констант](01-syntax-and-functions.md#global-constant-attributes).

[Документація PHP](https://www.php.net/manual/en/language.attributes.overview.php)

<a id="stringable"></a>
## Інтерфейс Stringable

**PHP 8.0+ | Ядро**

Об’єкт із `__toString()` неявно реалізує `Stringable`. Об’єднання `string|Stringable` дозволяє вказати, що API приймає звичайний рядок або об’єкт із рядковим представленням.

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

`Stringable` не є тим самим типом, що `string`, особливо за строгої типізації. Явне приведення вище є межею, де запитується рядкове представлення. Перетворення може викинути виняток; дивіться [винятки з __toString()](08-errors-and-debugging.md#to-string-exceptions).

[Документація PHP](https://www.php.net/manual/en/class.stringable.php)

<a id="enums"></a>
## Enum для закритого набору станів

**PHP 8.1+ | Ядро**

Enum визначає скінченний набір іменованих об’єктів. Backed enum додає унікальне значення `string` або `int` для зберігання чи зовнішніх даних. Код може працювати з типізованим станом, а база зберігати невелике скалярне значення.

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

`from()` викидає `ValueError` для невідомого значення, а `tryFrom()` повертає `null`. Некоректний тип аргументу все ще може спричинити `TypeError`, тому `tryFrom()` не є універсальним валідатором. Випадок enum є об’єктом: `Status::Draft !== 'draft'`. Використовуйте `->value` лише на скалярній межі. Pure enum має випадки й `->name`, але не має `->value`, `from()` або `tryFrom()`.

[Документація PHP](https://www.php.net/manual/en/language.enumerations.backed.php)

<a id="enum-constants"></a>
## Властивості enum у константних виразах

**PHP 8.2+ | Ядро**

Поля `name` і `value` випадку enum можна читати в константних виразах. Це дозволяє не дублювати скалярний запис поряд з оголошенням enum.

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

Pure enum не має властивості `value`. Такі вирази читають вбудовані метадані enum, а не дозволяють довільний доступ до властивостей об’єктів або виконання методів у константах.

[Документація PHP](https://www.php.net/manual/en/migration82.new-features.php)

<a id="readonly-properties"></a>
## Readonly-властивості та поверхнева незмінюваність

**PHP 8.1+ | Ядро**

`readonly`-властивість можна ініціалізувати один раз, після чого її зазвичай не можна переприсвоїти чи змінити. Це корисно для ідентичності й об’єктів-значень, але об’єкт усередині такої властивості все ще може мати змінюваний стан.

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

Властивість має бути типізованою і не може бути статичною. Збільшення readonly-скаляра або зміна елемента його масиву заборонені; зміна вмісту об’єкта за посиланням є іншою операцією. До PHP 8.4 ініціалізація неявно була приватною для класу оголошення; з PHP 8.4 типовою є `protected(set)`, що дозволяє ініціалізацію з дочірньої області, якщо права явно не звужено.

[Документація PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

<a id="readonly-classes"></a>
## Readonly-класи

**PHP 8.2+ | Ядро**

Readonly-клас застосовує правила readonly до всіх властивостей екземпляра. Це скорочує об’єкт даних і захищає від випадково змінюваної нової властивості.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y;
# 3:4
```

Властивості мають бути типізованими; статичні й динамічні властивості недоступні. Наслідування має дотримуватися правил readonly-класів. Як і з окремою readonly-властивістю, вкладені змінювані об’єкти не стають незмінюваними автоматично.

[Документація PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

<a id="readonly-cloning"></a>
## Повторна ініціалізація readonly у __clone()

**PHP 8.3+ | Ядро**

Під час клонування readonly-властивості можна повторно ініціалізувати, щоб копія отримала власні вкладені об’єкти. Це розв’язує поширену проблему поверхневої копії без загального дозволу змінювати початкову властивість.

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

Це контрольований виняток із заборони запису на час клонування, а не дозвіл багаторазово оновлювати властивість. Клонування залишається поверхневим, якщо явно не клонувати вкладені об’єкти, яким потрібен незалежний стан.

[Документація PHP](https://www.php.net/manual/en/migration83.new-features.php)

<a id="readonly-anonymous"></a>
## Анонімні readonly-класи

**PHP 8.3+ | Ядро**

Локальний анонімний об’єкт тепер може дотримуватися тих самих readonly-правил, що й іменований клас. Це зручно для невеликого незмінюваного значення, якому не потрібна повторно використовувана публічна назва класу.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id;
# 7
```

Це додає readonly до анонімних класів; звичайні анонімні класи існують із PHP 7.0. Обмеження щодо типізованих властивостей і вкладеного змінюваного стану зберігаються.

[Документація PHP](https://www.php.net/manual/en/migration83.new-features.php)

<a id="constant-visibility-final"></a>
## Видимість і final для констант класу

**PHP 7.1+ | Ядро**

PHP 7.1 дозволяє задавати для констант класу public, protected або private. PHP 8.1 додає `final`, що забороняє дочірньому класу замінювати значення з контракту батьківського класу.

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

У PHP 8.1+ можна оголосити `final public const PAGE_SIZE = 20;`, якщо перевизначення порушило б задум. Видимість керує доступом, а final перевизначенням. [Типізовані константи класу](02-types.md#typed-constants) є окремим доповненням PHP 8.3.

[Документація PHP](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="dynamic-constants"></a>
## Вибір константи класу за назвою

**PHP 8.3+ | Ядро**

`ClassName::{$name}` читає константу класу за назвою зі змінної. Це прибирає побудову рядка `ClassName::CONSTANT` для `constant()`, коли клас уже відомий.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
echo Format::{$name};
# application/json
```

Правила видимості залишаються чинними, а невідома константа спричиняє помилку. Зовнішню назву потрібно обирати з дозволеного набору. Із PHP 8.0 `$object::class` також дозволяє отримати назву класу об’єкта без окремого виклику `get_class()`.

[Документація PHP](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="trait-constants"></a>
## Константи, оголошені у traits

**PHP 8.2+ | Ядро**

Trait може оголошувати константи поряд із поведінкою, що їх використовує. Це зручно, коли спільна реалізація має невелику сталу опцію або значення протоколу.

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

Звертайтеся до констант через клас, що використовує trait. Конфліктні оголошення мають відповідати правилам сумісності PHP. Пов’язана зміна PHP 8.3 стосується статичних властивостей trait: якщо дочірній клас явно використовує той самий trait повторно, його статична властивість відокремлюється від батьківської; не припускайте, що обидва лічильники спільні.

[Документація PHP](https://www.php.net/manual/en/language.oop5.traits.php)

<a id="trait-final"></a>
## Імпорт методу trait як final

**PHP 8.3+ | Ядро**

Клас може позначити імпортований метод trait як final у блоці адаптації. Спільний trait залишається придатним для інших класів, а цей клас забороняє нащадкам перевизначати імпортовану поведінку.

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

Це обмежує перевизначення в нащадках, а не робить увесь клас final і не фіксує властивості об’єкта. Використовуйте для поведінки, яку контракт класу навмисно робить незмінною.

[Документація PHP](https://www.php.net/manual/en/language.oop5.traits.php)

<a id="override"></a>
## Перевірка перевизначених методів через #[Override]

**PHP 8.3+ | Ядро**

Атрибут указує, що метод має замінювати успадкований метод або реалізовувати метод інтерфейсу. Друкарська помилка чи перейменування в батьківському API тоді спричинить помилку компіляції, а не непомітно створить сторонній метод.

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

Атрибут перевіряє наявність перевизначеного оголошення, а не правильність реалізації. Його не можна застосувати до `__construct()`. [Перевизначення властивостей](#override-properties) отримує таку саму позначку в PHP 8.5.

[Документація PHP](https://www.php.net/manual/en/class.override.php)

<a id="property-hooks"></a>
## Обробники властивостей: збережені та віртуальні значення

**PHP 8.4+ | Ядро**

Властивість може змінювати поведінку читання через `get` і запису через `set`. Backed-властивість зберігає значення, а віртуальна обчислює його через обробники без власного сховища.

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

Скорочений `set` автоматично записує результат виразу в збережене значення. Віртуальна властивість лише з `get` недоступна для запису. Обробники доступні для нестатичних властивостей і не поєднуються з `readonly`. Предметні операції над кількома об’єктами залишайте методами; доступ до властивості має бути зрозумілим.

[Документація PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

<a id="setter-validation"></a>
## Повний setter має записувати значення властивості

**PHP 8.4+ | Ядро**

Блоковий обробник `set` може перевірити дані перед збереженням. На відміну від скороченого виразу, блок має явно присвоїти прийняте значення властивості.

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

Усередині цього обробника `$this->quantity` звертається до сховища, а не рекурсивно викликає той самий обробник. Перевірка відбувається до присвоєння, тож відхилене оновлення залишає попереднє значення. Початкове значення властивості не замінює перевірку подальших записів.

[Документація PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

<a id="asymmetric-visibility"></a>
## Різні права для читання та запису

**PHP 8.4+ | Ядро**

`public private(set)` дозволяє зовнішньому коду читати властивість, але записувати її може лише клас оголошення. Це зберігає простий публічний інтерфейс читання без загального дозволу на присвоєння.

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

Права запису не можуть бути ширшими за права читання. `private(set)` також робить властивість final, тож нащадок не може оголосити її повторно для обходу обмеження. Це не readonly: дозволений клас може записувати повторно. Отримання посилання або зміна елемента масиву також підпорядковуються правам запису.

[Документація PHP](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

<a id="interface-properties"></a>
## Контракти властивостей в інтерфейсах

**PHP 8.4+ | Ядро**

Інтерфейс може вимагати публічну властивість для читання або запису за допомогою оголошень у формі обробників. Реалізація може використовувати звичайну властивість або обробники, якщо потрібні операції доступні.

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

Вимога `get` не змушує реалізацію заборонити запис, а лише гарантує читання. Відповідно, `{ get; set; }` вимагає обох операцій. Уточнюючи контракт, дотримуйтеся правил варіантності типів.

[Документація PHP](https://www.php.net/manual/en/language.oop5.interfaces.php)

<a id="lazy-objects"></a>
## Відкладені об’єкти: ghost і proxy

**PHP 8.4+ | Ядро**

Reflection може створити об’єкт, ініціалізацію якого відкладено до використання стану. Lazy ghost ініціалізується на місці, а lazy proxy отримує від фабрики окремий справжній екземпляр і передає йому операції зі станом.

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

Це відкладає роботу, а не виконує її паралельно. Proxy та його справжній екземпляр мають різну ідентичність, що важливо для таблиць об’єктів і строгого порівняння. Механізм призначено для користувацьких класів і `stdClass`, а не довільних внутрішніх класів. Використовуйте його, коли відкладена ініціалізація розв’язує реальну проблему завантаження або часу життя.

[Документація PHP](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

<a id="new-chaining"></a>
## Виклик методу одразу після new

**PHP 8.4+ | Ядро**

Об’єкт, створений із дужками конструктора, можна використовувати одразу без додаткових дужок навколо всього виразу `new`. Це полегшує короткий запис «створити й використати».

```php
echo new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );
# 2025-01-02
```

Попередній запис `(new DateTimeImmutable(...))->format(...)` залишається правильним. Залишайте змінну, якщо об’єкт потрібен у кількох наступних кроках або однорядковий запис приховує важливий життєвий цикл.

[Документація PHP](https://www.php.net/manual/en/migration84.new-features.php)

<a id="override-properties"></a>
## Перевірка перевизначення властивостей через #[Override]

**PHP 8.5+ | Ядро**

Перевірка перевизначення поширюється з методів на властивості. Це особливо корисно для віртуальної властивості, успадковане обчислення якої уточнюється.

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

Якщо очікувана властивість батьківського класу чи інтерфейсу зникає, оголошення спричиняє помилку компіляції. Інші правила зберігаються: атрибут не робить несумісне перевизначення або перевизначення final-властивості допустимим.

[Документація PHP](https://www.php.net/manual/en/class.override.php)

<a id="static-visibility"></a>
## Асиметрична видимість статичних властивостей

**PHP 8.5+ | Ядро**

Статичні властивості також можуть мати вужчі права запису. Спільний лічильник можна читати ззовні, тоді як оновлення залишаються в класі, що відповідає за операцію.

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

Це керує доступом, а не ізоляцією запитів або паралельністю. У довготривалих процесах статичний стан переживає послідовні операції. Readonly для статичних властивостей є іншою ідеєю й залишається непідтримуваним.

[Документація PHP](https://www.php.net/manual/en/language.oop5.visibility.php)

<a id="final-promotion"></a>
## Final для властивостей у параметрах конструктора

**PHP 8.5+ | Ядро**

Final-властивості з’явилися в PHP 8.4; PHP 8.5 дозволяє вказувати модифікатор прямо у властивості, оголошеній параметром конструктора. Він забороняє нащадку повторно оголошувати цю властивість.

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

Final не означає readonly: наведену публічну властивість усе ще можна переприсвоювати. Обирайте final для обмеження наслідування, а readonly для обмеження запису значення; це різні задачі.

[Документація PHP](https://www.php.net/manual/en/language.oop5.final.php)

<a id="clone-with"></a>
## Клонування об’єкта зі зміною вибраних властивостей

**PHP 8.5+ | Ядро**

Функціональна форма `clone( $object, $changes )` створює копію та застосовує карту значень властивостей. Це зручно для методу `with_*()`, що повертає оновлений readonly-об’єкт-значення.

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

Заміни відбуваються після `__clone()` і дотримуються прав запису властивостей. Оновлення readonly розміщуйте в дозволеній області класу. Конструктор повторно не виконується, тому перевіряйте змінені значення до клонування: перевірки лише в конструкторі недостатньо. Невказані вкладені об’єкти зберігають звичайні правила поверхневої копії.

[Документація PHP](https://www.php.net/manual/en/language.oop5.cloning.php)

<a id="dynamic-properties"></a>
## Динамічні властивості застаріли

**PHP 8.2+ | Ядро**

Створення неоголошеної властивості у звичайному класі тепер створює повідомлення про застарілість. Це виявляє помилки в назвах і робить реальну структуру об’єкта зрозумілішою.

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

Оголошуйте відомі поля. Для навмисно довільних даних використовуйте масив або `stdClass`. Винятки охоплюють класи з `#[AllowDynamicProperties]`, їхніх нащадків і класи з `__set()`; реалізація, що створює неоголошені властивості всередині `__set()`, усе ще може створювати це повідомлення. Не додавайте атрибут усюди лише для приховування помилок у назвах.

[Документація PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)
