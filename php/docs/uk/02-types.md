# Типи

[EN](../en/02-types.md) | [UK](../uk/02-types.md) | [RU](../ru/02-types.md)

[Зміст](../../README.uk.md)

- [Скалярні параметри й результат](#scalar-types)
- [`strict_types`](#strict-types)
- [Nullable-типи](#nullable)
- [`void`](#void)
- [`iterable` / `object`](#iterable-object)
- [Типізовані властивості](#typed-properties)
- [Коваріантність / контраваріантність](#variance)
- [Union: `A|B`](#union)
- [`mixed`](#mixed)
- [Тип результату `static`](#static-return)
- [Intersection: `A&B`](#intersection)
- [`never`](#never)
- [DNF: `(A&B)|C`](#dnf)
- [Окремі `true` / `false` / `null`](#literal-types)
- [Типізовані константи](#typed-constants)
- [PHPDoc і нативні типи](#native-type-limits)

<a id="scalar-types"></a>
## Скалярні параметри й результат

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/migration70.new-features.php)

Типи скалярних параметрів і результату видно просто в сигнатурі. PHP перевіряє значення під час виклику; окремо дублювати ці типи у PHPDoc вже не потрібно.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

Скалярні типи: `int`, `float`, `string` і `bool`; правила перетворення залежать від `strict_types`.

<a id="strict-types"></a>
## `strict_types`

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

`strict_types` доречний, коли числовий рядок не повинен непомітно задовольняти параметр `int`. Директиву ставлять на початку PHP-файлу.

<!-- example: fragment -->
```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

double_count( '3' ); # TypeError
```

Суворість аргументів залежить від файлу виклику, перевірка результату від файлу оголошення. `int` усе ще дозволений для `float`.

<a id="nullable"></a>
## Nullable-типи

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

`?T` описує значення, яке може бути відсутнім: наприклад, необов'язкове ім'я чи невдалий пошук. Значення `= null` окремо дозволяє пропустити аргумент.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name(); # Guest
```

`string|null` є іншим записом від 8.0. Неявний варіант `string $name = null` застарів від 8.4.

<a id="void"></a>
## `void`

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/language.types.void.php)

`void` позначає дію без корисного результату. Достроковий `return` без значення дозволений, але `return null` ні.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}
```

<a id="iterable-object"></a>
## `iterable` / `object`

**PHP 7.1 / 7.2+** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

`iterable` (7.1) дозволяє передати в ту саму функцію масив або `Traversable`, зокрема генератор. Це зручно, коли потрібен лише обхід, а не доступ за індексами масиву. `object` (7.2) приймає будь-який об'єкт.

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

`iterable` не обмежує типи елементів.

<a id="typed-properties"></a>
## Типізовані властивості

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.properties.php)

Властивість може мати власний тип. Без початкового значення типізована властивість залишається неініціалізованою; навіть nullable-оголошення не присвоює `null` автоматично.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();
var_dump( isset($profile->id), $profile->email ); # false, null
```

Читання `id` до присвоєння спричиняє `Error`. `isset()` безпечний, але не відрізняє неініціалізований стан від `null`. `callable` не є типом властивості; для збереженого замикання є `Closure`.

<a id="variance"></a>
## Коваріантність / контраваріантність

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.variance.php)

Перевизначений метод може приймати ширший вхідний тип і повертати конкретніший. Тут реалізація приймає будь-який `object` замість лише `stdClass`, але обіцяє `stdClass` замість загального `object`.

```php
interface Factory
{
	public function create( stdClass $data ): object;
}

class CustomFactory implements Factory
{
	public function create( object $data ): stdClass
	{
		return new stdClass();
	}
}
```

7.2 вже дозволяв прибрати тип параметра. Звичайні властивості з читанням і записом залишаються інваріантними.

<a id="union"></a>
## Union: `A|B`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.type-system.php)

Якщо функція справді повертає кілька видів значення, їх можна перелічити в типі результату. `int|false` підходить для пошуку позиції, де нуль є правильним успішним результатом.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

echo find_position( 'php', 'p' ); # 0
```

`false` дозволений у union від 8.0, окремо від 8.2. Перевіряйте невдачу через `!== false`, щоб не втратити нульову позицію.

<a id="mixed"></a>
## `mixed`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.mixed.php)

`mixed` явно приймає будь-яке значення PHP, зокрема `null`. Він підходить універсальному форматувальнику чи контейнеру, контракт якого справді не звужує вхідний тип.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

`mixed` вже містить `null`, тому `?mixed` і `mixed|null` недопустимі.

<a id="static-return"></a>
## Тип результату `static`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

`static` враховує клас виклику, а `self` позначає клас оголошення методу. Тому успадкована фабрика може точно описати результат і для нащадків.

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

8.5: перевизначення у `final`-класі може замінити `static` на `self` або назву цього класу.

<a id="intersection"></a>
## Intersection: `A&B`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

`A&B` вимагає, щоб один об'єкт відповідав обом контрактам. `Countable&Iterator` доречний, коли функції потрібні методи ітерації та кількість елементів.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

Скалярні перетини `int&string` недопустимі.

<a id="never"></a>
## `never`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.never.php)

`never` означає, що функція не повертається звичайним шляхом: вона викидає виняток, завершує процес або працює нескінченно. Так шлях переривання видно прямо із сигнатури.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}
```

Навіть `return` без значення заборонений; досягнення кінця спричиняє `TypeError`.

<a id="dnf"></a>
## DNF: `(A&B)|C`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/migration82.new-features.php)

DNF поєднує перетини з альтернативами. Ця сигнатура приймає об'єкт, який одночасно є `Countable` та `Iterator`, або `null`, якщо колекції немає.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Дужки навколо груп перетину обов'язкові. `A&(B|C)` не підтримується.

<a id="literal-types"></a>
## Окремі `true` / `false` / `null`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/language.types.singleton.php)

Тип результату може обіцяти рівно `true`, `false` або `null`. Наприклад, `true` доречний для операції, яка повертає успіх, а в разі помилки викидає виняток.

```php
function confirm_ready(): true
{
	return true;
}

function missing_value(): null
{
	return null;
}
```

`true|false` недопустимий; використовуйте `bool`. `null` є значенням результату, на відміну від `void`.

<a id="typed-constants"></a>
## Типізовані константи

**PHP 8.3+** | [PHP](https://www.php.net/manual/en/language.oop5.constants.php)

Константи класу можуть мати тип як частину успадкованого контракту. Перевизначення в нащадку має залишатися сумісним за типом. Це також стосується констант інтерфейсів, трейтів та enum.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}
```

Звичайна глобальна `const` не підтримує таке оголошення типу.

<a id="native-type-limits"></a>
## PHPDoc і нативні типи

**PHP 7.0-8.5 | Контекст** | [PHP](https://www.php.net/manual/en/language.types.declarations.php)

Оголошення `array` перевіряє тип контейнера, а не його ключі чи типи елементів. PHPDoc на кшталт `list<User>` уточнює контракт для IDE чи статичного аналізатора, але сам PHP цю анотацію не перевіряє. Так само `string` не гарантує коректну email-адресу, довірений шлях чи безпечний HTML; ці перевірки виконує застосунок.

```php
/** @param list<User> $users */
function take_users( array $users ) {}
```
