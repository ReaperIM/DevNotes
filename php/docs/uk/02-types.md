# Типи та контракти

[English](../en/02-types.md) | [Українська](../uk/02-types.md) | [Русский](../ru/02-types.md)

[Зміст](../../README.uk.md) | [PHP 7.0-8.5](VERSIONS.md) | [Оновлення коду](MIGRATION.md)

Від скалярних оголошень PHP 7 до типізованих констант і точних контрактів. Кожен розділ пояснює, що перевіряє PHP і що залишається перевіряти застосунку. Версії позначають доступність можливостей, а не рекомендацію запускати старий інтерпретатор.

## Зміст

- [Скалярні параметри й тип результату](#scalar-types)
- [strict_types: керування перетворенням типів](#strict-types)
- [Nullable-типи](#nullable)
- [void: завершення без результату](#void)
- [iterable та object](#iterable-object)
- [Типізовані властивості та неініціалізований стан](#typed-properties)
- [Коваріантність і контраваріантність](#variance)
- [Union-типи: один із кількох варіантів](#union)
- [mixed: явно довільне значення](#mixed)
- [Тип результату static](#static-return)
- [Intersection-типи: усі контракти одночасно](#intersection)
- [never: без звичайного повернення](#never)
- [DNF: перетини та альтернативи](#dnf)
- [Окремі типи true, false та null](#literal-types)
- [Типізовані константи класу](#typed-constants)
- [Що нативний тип не перевіряє](#native-type-limits)

<a id="scalar-types"></a>
## Скалярні параметри й тип результату

**PHP 7.0+**

Контракт видно із сигнатури: ціна є дробовим числом, кількість є цілим, результат теж дробовий. PHP перевіряє значення на межі функції. Це прибирає повторювані перевірки всередині звичайного прикладного коду.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

Скалярні типи записують як int, float, string і bool. Пишіть bool, а не boolean. Тип визначає вид значення, але не гарантує, що кількість додатна або рядок містить правильну адресу пошти. Це окремі правила перевірки.

[Документація PHP](https://www.php.net/manual/en/migration70.new-features.php)

<a id="strict-types"></a>
## strict_types: керування перетворенням типів

**PHP 7.0+**

За замовчуванням PHP може перетворити сумісний скалярний аргумент, наприклад числовий рядок, на оголошений тип. Суворий режим доречний, коли випадкове перетворення приховає помилку в коді. Директиву ставлять на початку PHP-файлу.

```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

try
{
	double_count( '3' );
}
catch( TypeError $error )
{
	echo 'Expected an integer';
}
```

Суворість скалярних аргументів визначає файл, з якого викликають функцію. Перевірка результату залежить від файлу з її оголошенням. Для параметра float можна передати int. strict_types не замінює перевірку вхідних даних, не змінює === і не робить усі внутрішні операції суворими.

[Документація PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

<a id="nullable"></a>
## Nullable-типи

**PHP 7.1+**

Використовуйте ?T, якщо значенням може бути T або null. Наприклад, пошук може не знайти запис. Nullable-параметр не стає необов'язковим автоматично.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name();          # Guest
echo display_name( '' );      # empty string
echo display_name( 'Olena' ); # Olena
```

Значення за замовчуванням = null дозволяє пропустити аргумент. Без нього значення все одно треба передати, навіть якщо це null. Від PHP 8.0 можна писати string|null. Неявний nullable-запис string $name = null застарів у PHP 8.4; позначайте null явно.

[Документація PHP](https://www.php.net/manual/en/migration71.new-features.php)

<a id="void"></a>
## void: завершення без результату

**PHP 7.1+**

void означає, що функція виконує дію й не повертає корисного значення. Для дострокового виходу можна написати return без значення. return null не дозволено.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}

print_status( true ); # Ready
```

Вибирайте void для звичайної операції, яка може нормально завершитися. never потрібен, коли нормальне завершення неможливе. Ці типи не описують значення властивостей.

[Документація PHP](https://www.php.net/manual/en/language.types.void.php)

<a id="iterable-object"></a>
## iterable та object

**PHP 7.1 / 7.2+**

iterable (7.1) приймає масив або об'єкт Traversable: функція працюватиме і з готовим списком, і з генератором. object (7.2) приймає будь-який об'єкт. Якщо потрібні конкретні методи, краще вказати відповідний інтерфейс.

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

iterable не перевіряє тип кожного елемента. Генератори й потоки з ключами розглянуто в [главі про масиви та ітерацію](04-arrays-and-iteration.md). object доречний в універсальних інструментах, а не замість кожного предметного інтерфейсу.

[Документація PHP](https://www.php.net/manual/en/language.types.iterable.php)

<a id="typed-properties"></a>
## Типізовані властивості та неініціалізований стан

**PHP 7.4+**

Властивість може мати власний тип. Неініціалізована типізована властивість відрізняється від властивості зі значенням null. Навіть nullable-властивості потрібне початкове значення перед читанням.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();

var_dump( isset($profile->id) ); # false
var_dump( $profile->email );    # NULL

$profile->id = 42;
echo $profile->id;              # 42
```

Читання id до присвоєння спричинить Error. isset() безпечний, але не відрізняє неініціалізовану властивість від null; для цього є ReflectionProperty::isInitialized(). callable не дозволений як тип властивості; для збереженого замикання часто підходить Closure.

[Документація PHP](https://www.php.net/manual/en/language.oop5.properties.php)

<a id="variance"></a>
## Коваріантність і контраваріантність

**PHP 7.4+**

Перевизначений метод може обіцяти конкретніший результат і приймати загальніший аргумент. Об'єкт нащадка має залишатися придатним усюди, де очікується батьківський контракт.

```php
class Animal {}
class Cat extends Animal {}

interface Shelter
{
	public function adopt( Cat $candidate ): Animal;
}

class CatShelter implements Shelter
{
	public function adopt( Animal $candidate ): Cat
	{
		return new Cat();
	}
}

echo get_class( (new CatShelter())->adopt(new Cat()) ); # Cat
```

Повна підтримка з'явилася у 7.4; версія 7.2 вже дозволяла прибрати тип параметра в перевизначенні. Зворотна зміна може звузити допустимі аргументи або послабити обіцяний результат. Звичайні властивості з читанням і записом переважно інваріантні; для property hooks є окремі правила.

[Документація PHP](https://www.php.net/manual/en/language.oop5.variance.php)

<a id="union"></a>
## Union-типи: один із кількох варіантів

**PHP 8.0+**

Об'єднання типів записують через |. Воно доречне, коли API справді має кілька видів результату, наприклад ціле число в разі успіху або false в разі невдачі.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

$position = find_position( 'php', 'p' );

if( $position !== false )
	echo $position; # 0 is a successful position
```

Це контракт, який перевіряється під час виконання, на відміну від PHPDoc. Не перетворюйте кожен невизначений параметр на велике об'єднання: якщо операції потрібен один тип, нормалізуйте зовнішні дані. false у складі union дозволений від 8.0; окремий тип false з'явився пізніше.

[Документація PHP](https://www.php.net/manual/en/language.types.type-system.php)

<a id="mixed"></a>
## mixed: явно довільне значення

**PHP 8.0+**

mixed явно дозволяє будь-яке значення PHP, зокрема null. Це доречно для універсального контейнера або передавання невідомого значення далі. Тип не пояснює, які операції над значенням безпечні.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

mixed вже містить null, тому ?mixed і mixed|null недопустимі. Він не перевіряє декодований JSON і не робить читання ключів масиву безпечним. Щойно контракт зрозумілий, використовуйте точніший тип.

[Документація PHP](https://www.php.net/manual/en/language.types.mixed.php)

<a id="static-return"></a>
## Тип результату static

**PHP 8.0+**

self позначає клас, у якому оголошено метод; static враховує клас виклику. Це дозволяє точніше описати успадковані іменовані конструктори та ланцюжки методів.

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

static можна вказати для результату, але не для параметра чи властивості. Потрібно повернути об'єкт, сумісний із класом виклику. У 8.5 перевизначений метод final-класу може замінити батьківський результат static на self або назву цього final-класу.

[Документація PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

<a id="intersection"></a>
## Intersection-типи: усі контракти одночасно

**PHP 8.1+**

Перетин записують через &: один об'єкт має відповідати всім переліченим класам або інтерфейсам. Countable&Iterator доречний, якщо потрібні і методи ітерації, і кількість елементів.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

Це один об'єкт з обома можливостями, а не будь-яка з них і не пара об'єктів. Скалярні перетини int&string недопустимі. Для поєднання перетину з null або іншим варіантом потрібен DNF-синтаксис від 8.2.

[Документація PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

<a id="never"></a>
## never: без звичайного повернення

**PHP 8.1+**

Функція never має викинути виняток, завершити процес або працювати нескінченно. Такий контракт пояснює операцію переривання читачеві й аналізатору. Її виняток можна перехопити зовні.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}

try
{
	reject_request();
}
catch( RuntimeException $error )
{
	echo $error->getMessage(); # Request rejected
}
```

Навіть return без значення заборонений. Досягнення кінця функції спричиняє TypeError. Не використовуйте never лише тому, що функція часто завершується помилкою: звичайний успішний шлях потребує іншого типу.

[Документація PHP](https://www.php.net/manual/en/language.types.never.php)

<a id="dnf"></a>
## DNF: перетини та альтернативи

**PHP 8.2+**

DNF дозволяє об'єднати цілі групи перетинів. Значенням може бути об'єкт, який одночасно є Countable та Iterator, або null.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Дужки навколо груп перетину обов'язкові. Довільні вирази на кшталт A&(B|C) не підтримуються. Якщо контракт стає складним, відповідний інтерфейс часто зрозуміліший за дедалі довший вираз типу.

[Документація PHP](https://www.php.net/manual/en/migration82.new-features.php)

<a id="literal-types"></a>
## Окремі типи true, false та null

**PHP 8.2+**

Літеральний тип обіцяє рівно одне значення. Він доречний для вузьких контрактів і сумісного перевизначення; для звичайного результату так/ні підходить bool.

```php
function confirm_ready(): true
{
	return true;
}

function missing_value(): null
{
	return null;
}

var_dump( confirm_ready() ); # bool(true)
var_dump( missing_value() ); # NULL
```

false і null входили до відповідних union-типів від 8.0; у 8.2 вони стали окремими типами та з'явився true. true|false недопустимий: пишіть bool. null є значенням результату, а void означає відсутність корисного результату.

[Документація PHP](https://www.php.net/manual/en/language.types.singleton.php)

<a id="typed-constants"></a>
## Типізовані константи класу

**PHP 8.3+**

Константи можуть мати тип разом зі значенням. Це корисно для успадкованих контрактів: перевизначення має залишатися сумісним за типом.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}

echo Limits::PAGE_SIZE; # 50
```

Це стосується констант класів, інтерфейсів, трейтів та enum. Звичайна глобальна const від цього не отримує синтаксис оголошення типу. Тип array також не описує кожен елемент масиву.

[Документація PHP](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="native-type-limits"></a>
## Що нативний тип не перевіряє

**PHP 7.0-8.5 | Контекст**

array обіцяє лише масив, а не ключі id/name, список чи елементи User. string не означає адресу пошти, довірений шлях або безпечний HTML. Перевіряйте структуру та предметні правила там, де зовнішні дані потрапляють у код.

```php
function require_positive_id( array $input ): int
{
	$id = $input['id'] ?? null;

	if( !is_int($id) || $id <= 0 )
		throw new InvalidArgumentException( 'Positive integer id required' );

	return $id;
}

echo require_positive_id( ['id' => 42] ); # 42
```

PHPDoc на кшталт `list<User>` допомагає IDE чи статичному аналізатору, але PHP 7.0-8.5 не перевіряє його під час виконання. Свідомо вирішуйте, чи приймати числовий рядок з HTTP з перетворенням, чи відхиляти. Приведення типу не має непомітно робити з некоректних даних правдоподібний нуль.

[Документація PHP](https://www.php.net/manual/en/language.types.declarations.php)

[Зміст](../../README.uk.md)
