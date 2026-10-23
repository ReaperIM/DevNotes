# Класи та об’єкти

[EN](../en/03-classes-and-objects.md) | [UK](../uk/03-classes-and-objects.md) | [RU](../ru/03-classes-and-objects.md)

[Зміст](../../README.uk.md)

**На цій сторінці**

- [Створюємо клас просто на місці](#anonymous-classes)
- [Оголошуємо властивості просто в конструкторі](#constructor-promotion)
- [Об’єкт як запасний аргумент](#new-initializers)
- [Додаємо до методу опис через атрибут](#attributes)
- [Приймаємо рядок або об’єкт із `__toString()`](#stringable)
- [Задаємо дозволені стани через enum](#enums)
- [Беремо ім’я або значення enum для константи](#enum-constants)
- [Записуємо властивість один раз через `readonly`](#readonly-properties)
- [Робимо всі властивості класу `readonly`](#readonly-classes)
- [Відокремлюємо вкладений об’єкт під час клонування](#readonly-cloning)
- [Створюємо анонімний `readonly`-клас](#readonly-anonymous)
- [Закриваємо константи й забороняємо перевизначення](#constant-visibility-final)
- [Читаємо константу за ім’ям зі змінної](#dynamic-constants)
- [Зберігаємо константи разом із кодом трейту](#trait-constants)
- [Забороняємо нащадку змінювати метод трейту](#trait-final)
- [Перевіряємо, що метод справді перевизначено](#override)
- [Обробляємо читання й запис властивості](#property-hooks)
- [Перевіряємо значення перед записом у властивість](#setter-validation)
- [Читати можна всім, змінювати лише класу](#asymmetric-visibility)
- [Вимагаємо властивість через інтерфейс](#interface-properties)
- [Готуємо об’єкт лише під час першого звернення](#lazy-objects)
- [Викликаємо метод одразу після `new`](#new-chaining)
- [Перевіряємо перевизначення властивості](#override-properties)
- [Обмежуємо запис у статичну властивість](#static-visibility)
- [Додаємо `final` до властивості в конструкторі](#final-promotion)
- [Копіюємо об’єкт і змінюємо вибрані властивості](#clone-with)
- [Не створюємо властивості випадково](#dynamic-properties)

<a id="anonymous-classes"></a>
## Створюємо клас просто на місці

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.anonymous.php)

Якщо невеликий об’єкт потрібен лише тут, клас можна оголосити одразу після `new`, не вигадуючи йому окремого імені. Отриманий об’єкт використовуємо як звичайно.

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

Такий клас теж може мати батька через `extends` та інтерфейси через `implements`. Відсутність імені не забирає в нього звичайних можливостей класу.

<a id="constructor-promotion"></a>
## Оголошуємо властивості просто в конструкторі

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

Не обов’язково окремо оголошувати властивість, приймати параметр і присвоювати одне іншому. Якщо параметр конструктора має `public`, `protected` чи `private`, PHP сам створить властивість і запише в неї передане значення.

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

У прикладі `id` і `name` уже доступні в об’єкта одразу після `new User()`. Присвоєння відбувається до виконання тіла конструктора. Звичайні параметри без оголошення властивості теж можна залишити поруч.

<a id="new-initializers"></a>
## Об’єкт як запасний аргумент

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration81.new-features.php)

Якщо код виклику не передав об’єкт, можна створити його просто у значенні за замовчуванням. Тут без другого аргументу функція отримає новий `Prefix` і додасть префікс `note`.

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

Коли використовується цей запасний аргумент, об’єкт створюється для конкретного виклику, а не один на всі виклики. `new` також дозволений в аргументах атрибутів, початкових значеннях локальних `static`-змінних і глобальних константах. Для початкового значення звичайної властивості такий запис іще не підходить.

<a id="attributes"></a>
## Додаємо до методу опис через атрибут

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.attributes.overview.php)

Атрибут зберігає додаткові відомості поруч з оголошенням. У прикладі для `export()` записано опис, а через `ReflectionMethod` ми знаходимо його й отримуємо об’єкт `Description`. Сама наявність атрибута метод не запускає.

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

`TARGET_METHOD` дозволяє наш атрибут лише біля методів. Повторювати його дозволяє окремий прапорець `IS_REPEATABLE`; ці правила перевіряються під час `newInstance()`.

У PHP 8.5 з’явився `#[DelayedTargetValidation]`: він дозволяє відкласти перевірку місця встановлення вбудованого атрибута або атрибута розширення до створення його об’єкта через Reflection. Ще є [атрибути для повідомлень про помилки й застарілість](08-errors-and-debugging.md#deprecated-attribute) та [атрибути глобальних констант](01-syntax-and-functions.md#global-constant-attributes).

<a id="stringable"></a>
## Приймаємо рядок або об’єкт із `__toString()`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.stringable.php)

Об’єкт із `__toString()` уміє перетворюватися на рядок і автоматично відповідає інтерфейсу `Stringable`. Тип `string|Stringable` дозволяє функції приймати і звичайний текст, і такий об’єкт.

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

Усередині явно отримуємо рядок через `(string)`. За строгої типізації сам `string` об’єкт `Stringable` не приймає. І перетворення теж може завершитися помилкою: `__toString()` [може викинути виняток](08-errors-and-debugging.md#to-string-exceptions).

<a id="enums"></a>
## Задаємо дозволені стани через enum

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.enumerations.backed.php)

Якщо статус має лише кілька дозволених варіантів, їх зручно зібрати в enum. Кожен `case` тут окремий іменований об’єкт. Варіант із базовим `string` чи `int` називають backed enum: він має унікальне `value` для зберігання, а `name` є в будь-якого варіанта.

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

`from()` шукає варіант за значенням і викидає `ValueError`, якщо не знайшов. `tryFrom()` у такому разі віддає `null`, але непридатний тип аргументу все ще може спричинити `TypeError`. `cases()` повертає всі варіанти. В enum без базового типу немає ні `value`, ні методів `from()` і `tryFrom()`.

<a id="enum-constants"></a>
## Беремо ім’я або значення enum для константи

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration82.new-features.php)

Не обов’язково повторювати рядок з enum вручну. Під час оголошення константи можна прочитати `name` потрібного варіанта, а в backed enum ще й `value`.

```php
enum Format: string
{
	case Json = 'json';
}

const DEFAULT_FORMAT = Format::Json->value;
const DEFAULT_CASE_NAME = Format::Json->name;

echo DEFAULT_CASE_NAME, ':', DEFAULT_FORMAT; # Json:json
```

У прикладі ім’я `Json` і значення `json` беруться з одного варіанта. Це окрема можливість enum, а не загальний дозвіл читати будь-які властивості об’єктів чи викликати методи в константах.

<a id="readonly-properties"></a>
## Записуємо властивість один раз через `readonly`

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

Типізовану нестатичну `readonly`-властивість можна заповнити один раз. Після цього присвоїти їй інше значення не можна. Але якщо всередині об’єкт, його власні властивості змінювати все ще можна, тому лічильник у прикладі спокійно стане рівним `2`.

```php
class Snapshot
{
	public function __construct( public readonly stdClass $data ) {}
}

$snapshot = new Snapshot( (object)['count' => 1] );
$snapshot->data->count++;

echo $snapshot->data->count; # 2
```

Для числа заборона стосується й `++`, для масиву не можна змінювати його елементи через цю властивість. Об’єкт усередині не стає незмінним автоматично.

До PHP 8.4 вперше записати значення міг лише клас, який оголосив властивість. Із PHP 8.4 за замовчуванням діє `protected(set)`, тобто це доступно й нащадку, якщо право запису не звужене явно.

<a id="readonly-classes"></a>
## Робимо всі властивості класу `readonly`

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

Якщо всі властивості об’єкта мають заповнюватися один раз, можна поставити `readonly` перед усім класом. У `Point` не треба окремо повторювати його біля `x` та `y`.

```php
readonly class Point
{
	public function __construct( public int $x, public int $y ) {}
}

$point = new Point( 3, 4 );
echo $point->x, ':', $point->y; # 3:4
```

Усі властивості мають мати тип. Статичні й динамічно додані властивості тут заборонені, а успадкування має дотримуватися правил `readonly`-класів. Вкладені об’єкти при цьому все ще можуть змінюватися, як і в окремої `readonly`-властивості.

<a id="readonly-cloning"></a>
## Відокремлюємо вкладений об’єкт під час клонування

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration83.new-features.php)

Звичайний `clone` копіює зовнішній об’єкт, але вкладена дата залишається спільною. Якщо хочемо змінювати дату копії окремо, клонуємо і її в `__clone()`. Із PHP 8.3 у цей момент дозволено заново заповнити `readonly`-властивість.

```php
readonly class Schedule
{
	public function __construct( public DateTime $date ) {}

	public function __clone(): void
	{
		# копіюємо дату окремо, щоб не змінювати її в оригіналу
		$this->date = clone $this->date;
	}
}

$original = new Schedule( new DateTime('2025-01-01') );
$copy = clone $original;
$copy->date->modify( '+1 day' );

echo $original->date->format( 'Y-m-d' ), ':', $copy->date->format( 'Y-m-d' );
# 2025-01-01:2025-01-02
```

Тому зміна дати в `$copy` не зачіпає `$original`. Дозвіл діє саме під час клонування. Інші вкладені об’єкти, які явно не скопіювали, за звичайного клонування залишаться спільними.

<a id="readonly-anonymous"></a>
## Створюємо анонімний `readonly`-клас

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration83.new-features.php)

Один невеликий об’єкт без окремого імені теж можна зробити `readonly`. У прикладі `id` заповнюється в конструкторі, після цього присвоїти йому інше значення не можна.

```php
$value = new readonly class( 7 )
{
	public function __construct( public int $id ) {}
};

echo $value->id; # 7
```

Правила ті самі, що в іменованого `readonly`-класу: властивості з типами, а вкладені об’єкти не стають незмінними самі по собі.

<a id="constant-visibility-final"></a>
## Закриваємо константи й забороняємо перевизначення

**PHP 7.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.constants.php)

Із PHP 7.1 для константи класу можна вказати доступ. У прикладі `INTERNAL_FACTOR` залишаємо для самого класу, а `PAGE_SIZE` дозволяємо читати ззовні.

```php
class Limits
{
	private const INTERNAL_FACTOR = 2;
	public const PAGE_SIZE = 20;
}

echo Limits::PAGE_SIZE; # 20
```

Із PHP 8.1 запис `final public const PAGE_SIZE = 20;` ще й забороняє нащадкам перевизначати цю константу. А [тип константи класу](02-types.md#typed-constants) можна вказати з PHP 8.3, це окрема можливість.

<a id="dynamic-constants"></a>
## Читаємо константу за ім’ям зі змінної

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.constants.php)

Ім’я константи може надійти зі змінної: `Format::{$name}` прочитає потрібну константу класу. У прикладі `$name` містить `JSON`, тому отримуємо `application/json`.

```php
class Format
{
	public const JSON = 'application/json';
}

$name = 'JSON';
$mime = Format::{$name};

echo $mime; # application/json
```

Права доступу залишаються чинними, невідоме ім’я спричинить помилку. Для імені самого класу об’єкта з PHP 8.0 є інший запис: `$object::class`.

<a id="trait-constants"></a>
## Зберігаємо константи разом із кодом трейту

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Трейт може додати класу не лише методи й властивості, а й константи. Читаємо їх через клас, який підключив трейт: тут це `Exporter::FORMAT`. Якщо таке ім’я вже оголошено, визначення мають бути сумісними.

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

Ще одна зміна трейтів у PHP 8.3: якщо нащадок сам повторно підключає трейт, статичні властивості цього трейту в нього зберігаються окремо від батьківських. Це важливо, коли розраховуємо на спільний статичний лічильник чи кеш.

<a id="trait-final"></a>
## Забороняємо нащадку змінювати метод трейту

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.traits.php)

Іноді метод трейту має залишитися як є лише в конкретному класі. Під час підключення можна позначити його `as final`, не змінюючи сам трейт.

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

Метод `identifier()` так само доступний у `Report`, але нащадок `Report` уже не зможе його перевизначити.

<a id="override"></a>
## Перевіряємо, що метод справді перевизначено

**PHP 8.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.override.php)

Помилка в імені методу може непомітно створити новий метод замість перевизначення старого. `#[Override]` просить PHP перевірити це під час компіляції: має знайтися метод батька або інтерфейсу, який ми реалізуємо.

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

Тут `format()` є в інтерфейсі, тому перевірка проходить. До `__construct()` цей атрибут не застосовується. Для [перевизначення властивостей](#override-properties) він теж працює, починаючи з PHP 8.5.

<a id="property-hooks"></a>
## Обробляємо читання й запис властивості

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Властивість може мати власні `get` і `set`, їх називають hooks. Так можна очистити значення під час запису або обчислити його під час читання. У прикладі `name` зберігає ім’я без пробілів із країв, а `display_name` щоразу отримує варіант у верхньому регістрі.

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

Короткий `set =>` сам зберігає результат виразу. У `display_name` окремого збереженого значення немає, це віртуальна властивість. Оскільки в неї є лише `get`, записати в неї не можна. Hooks не підтримуються для статичних властивостей і не поєднуються з `readonly`.

<a id="setter-validation"></a>
## Перевіряємо значення перед записом у властивість

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.property-hooks.php)

Якщо в `set` потрібне не одне обчислення, а кілька дій, пишемо звичайний блок. На відміну від короткого `set =>`, тут треба самим записати прийняте значення у властивість.

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

Присвоєння `$this->quantity = $value` всередині цього обробника пише просто в сховище й не викликає `set` повторно. Якщо значення від’ємне, виняток виникає до присвоєння, тому стара кількість залишається на місці.

<a id="asymmetric-visibility"></a>
## Читати можна всім, змінювати лише класу

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.visibility.php#language.oop5.visibility-members)

Запис `public private(set)` розділяє права: читати заголовок можна ззовні, а присвоювати нове значення лише всередині класу, який його оголосив. Тому перейменування йде через `rename()`, де заодно прибираємо пробіли.

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

Це не `readonly`: сам клас може змінювати значення скільки потрібно. Право запису не може бути ширшим за право читання. `private(set)` також робить властивість `final`. Отримання посилання й зміна елементів масиву теж вважаються записом і потребують відповідного доступу.

<a id="interface-properties"></a>
## Вимагаємо властивість через інтерфейс

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.interfaces.php)

Інтерфейс може вимагати не лише методи, а й доступну властивість. `{ get; }` означає, що її можна читати, а `{ get; set; }` вимагає ще й запис. У прикладі будь-який `HasTitle` має давати прочитати рядковий `title`.

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

Виконати вимогу можна звичайною властивістю, як в `Article`, або через hooks. Типи при цьому мають залишатися сумісними за правилами їх зміни. Сама вимога `get` не забороняє класу дозволити запис теж: інтерфейс задає потрібний мінімум.

<a id="lazy-objects"></a>
## Готуємо об’єкт лише під час першого звернення

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.lazy-objects.php)

Через Reflection можна відкласти заповнення об’єкта до звернення до його стану. Це корисно, якщо об’єкт створили, але його дані можуть узагалі не знадобитися. У ghost заповнюється той самий об’єкт, а proxy отримує окремий справжній об’єкт із переданої функції.

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

За виведенням видно порядок: спочатку `created`, а рядки `initialize:...` з’являються лише під час читання `title`. Proxy й створений для нього об’єкт залишаються різними екземплярами. Можливість розрахована на користувацькі класи та `stdClass`, довільний внутрішній клас PHP так відкласти не можна.

<a id="new-chaining"></a>
## Викликаємо метод одразу після `new`

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration84.new-features.php)

Створили об’єкт і одразу взяли результат його методу, без окремої змінної та зовнішніх дужок навколо `new`. Дужки самого конструктора за такого запису потрібні.

```php
$date = new DateTimeImmutable( '2025-01-02' )->format( 'Y-m-d' );

echo $date; # 2025-01-02
```

Старий запис `(new ClassName(...))->method()` теж залишається правильним, переписувати його лише через версію не обов’язково.

<a id="override-properties"></a>
## Перевіряємо перевизначення властивості

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.override.php)

У PHP 8.5 `#[Override]` працює і для властивостей. Якщо розраховуємо замінити властивість батька або виконати вимогу інтерфейсу, атрибут перевірить наявність такого оголошення під час компіляції.

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

У прикладі `Report` змінює читання вже наявного `label`. Атрибут не скасовує решту правил: несумісний тип або перевизначення `final`-властивості все одно заборонені.

<a id="static-visibility"></a>
## Обмежуємо запис у статичну властивість

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.visibility.php)

Розділяти права читання й запису тепер можна і для статичної властивості. Лічильник `calls` видно ззовні, але збільшувати його може лише сам `Counter`.

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

Так обмежуємо доступ до значення, а не змінюємо строк його зберігання. Статичну властивість усе ще не можна оголосити `readonly`.

<a id="final-promotion"></a>
## Додаємо `final` до властивості в конструкторі

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.final.php)

Із PHP 8.4 властивість можна захистити від повторного оголошення в нащадку через `final`. У PHP 8.5 це дозволили й для властивостей, оголошених просто в параметрах конструктора.

```php
class Ticket
{
	public function __construct( final public string $code ) {}
}

$ticket = new Ticket( 'A' );
$ticket->code = 'B';

echo $ticket->code; # B
```

`final` не забороняє змінювати саме значення: у прикладі `code` змінюється з `A` на `B`. Він забороняє нащадку заново оголосити цю властивість. Для заборони повторного присвоєння потрібен `readonly`.

<a id="clone-with"></a>
## Копіюємо об’єкт і змінюємо вибрані властивості

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.cloning.php)

Через `clone( $object, $changes )` можна одразу отримати копію з потрібними змінами. Спочатку виконується `__clone()`, потім присвоюються перелічені властивості. У прикладі оригінальний колір залишається з `alpha = 255`, а копія отримує `128`.

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

Права запису все ще перевіряються, тому змінювати `readonly` так можна лише з дозволеної області класу. Конструктор повторно не викликається: перевірку діапазону у `with_alpha()` залишаємо самі. Вкладені об’єкти без окремого клонування так само залишаються спільними.

<a id="dynamic-properties"></a>
## Не створюємо властивості випадково

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.dynamic-properties)

Запис у неоголошену властивість звичайного класу з PHP 8.2 видає повідомлення про застарілість. Це заодно допомагає помітити помилки в іменах. Потрібну властивість краще оголосити явно, як `name` у `User`.

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

Для вільного набору полів є `stdClass`, як у `$metadata`. Винятки також є для класів із `#[AllowDynamicProperties]` та їхніх нащадків. Обробник `__set()` може приймати такі присвоєння сам, але створення нової неоголошеної властивості всередині нього все ще може спричинити те саме повідомлення.
