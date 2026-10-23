# Типи

[EN](../en/02-types.md) | [UK](../uk/02-types.md) | [RU](../ru/02-types.md)

[Зміст](../../README.uk.md)

- [Типи параметрів і результату](#scalar-types)
- [Не підміняємо типи через `strict_types`](#strict-types)
- [Дозволяємо `null` через `?T`](#nullable)
- [Виконуємо дію без результату: `void`](#void)
- [Приймаємо значення для перебору й об’єкти](#iterable-object)
- [Указуємо тип властивості](#typed-properties)
- [Уточнюємо типи в нащадку](#variance)
- [Дозволяємо один із типів: `A|B`](#union)
- [Приймаємо будь-яке значення через `mixed`](#mixed)
- [Повертаємо об’єкт потрібного нащадка: `static`](#static-return)
- [Вимагаємо одразу обидва типи: `A&B`](#intersection)
- [Після цієї функції не продовжуємо: `never`](#never)
- [Поєднуємо вимоги й варіанти: `(A&B)|C`](#dnf)
- [Повертаємо саме `true`, `false` або `null`](#literal-types)
- [Указуємо тип константи класу](#typed-constants)
- [Що перевіряє PHP, а що залишаємо PHPDoc](#native-type-limits)

<a id="scalar-types"></a>
## Типи параметрів і результату

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/migration70.new-features.php)

З оголошення функції одразу видно, що їй передати й що отримати назад. Тут ціна має бути `float`, кількість `int`, а результат `float`. PHP сам перевіряє ці типи, тому повторювати їх у PHPDoc заради тієї самої перевірки не треба.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

Для звичайних значень є `int`, `float`, `string` і `bool`. Чи буде PHP підлаштовувати значення під указаний тип, залежить від `strict_types`.

<a id="strict-types"></a>
## Не підміняємо типи через `strict_types`

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

Рядок `'3'` схожий на число, але все ще залишається рядком. Якщо не хочемо, щоб PHP мовчки прийняв його замість `int`, вмикаємо `strict_types` на початку файлу. У цьому прикладі виклик завершиться `TypeError`.

<!-- example: fragment -->
```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

double_count( '3' ); # TypeError
```

Для аргументів важливий файл, із якого викликаємо функцію. Для поверненого значення важливий файл, де її оголошено. Окреме дозволене перетворення залишається: `int` можна передати туди, де потрібен `float`.

<a id="nullable"></a>
## Дозволяємо `null` через `?T`

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Ім’я може бути рядком, а може бути відсутнім. Запис `?string` прямо дозволяє обидва випадки. А `= null` вирішує інше завдання: дозволяє взагалі не передавати цей аргумент під час виклику.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name(); # Guest
```

Тому виклик без імені виводить `Guest`. Із PHP 8.0 той самий тип можна записати як `string|null`. Старий неявний запис `string $name = null` краще замінити: із PHP 8.4 він застарів.

<a id="void"></a>
## Виконуємо дію без результату: `void`

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/language.types.void.php)

Ця функція лише виводить статус, отримувати від неї значення не потрібно. Саме це й позначає `void`. Якщо статус прихований, звичайний `return;` дозволяє вийти раніше.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}
```

Повернути `null` через `return null;` уже не можна: це теж явне повернення значення.

<a id="iterable-object"></a>
## Приймаємо значення для перебору й об’єкти

**PHP 7.1 / 7.2+** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

Якщо функції потрібен лише `foreach`, можна не вимагати саме масив. Тип `iterable` із PHP 7.1 приймає і масиви, і `Traversable`, зокрема генератори. У прикладі підсумовування не залежить від конкретного способу зберігання.

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

Типи елементів `iterable` не перевіряє. А окремий тип `object` із PHP 7.2 означає, що підходить будь-який об’єкт, без прив’язки до одного класу.

<a id="typed-properties"></a>
## Указуємо тип властивості

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.properties.php)

Тип можна написати просто біля властивості. Але оголосити властивість і записати в неї значення не те саме. У прикладі `id` ще не має значення, а `email` уже має `null`. Сам знак `?` значення `null` не присвоює.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();
var_dump( isset($profile->id), $profile->email ); # false, null
```

Якщо прочитати `id` до присвоєння, отримаємо `Error`. Перевірити через `isset()` можна безпечно, але він поверне `false` і для такого стану, і для `null`. Тип `callable` у властивості не підтримується; якщо зберігаємо замикання, є `Closure`.

<a id="variance"></a>
## Уточнюємо типи в нащадку

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.variance.php)

Метод нащадка може прийняти більше варіантів на вході, а повернути конкретніший результат. Це називають контраваріантністю параметрів і коваріантністю результату. Тут замість одного `stdClass` дозволяємо будь-який `object`, зате на виході обіцяємо саме `stdClass`.

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

Це зберігає зміст початкового оголошення: усе, що підходило старому методу, підходить і новому. Із PHP 7.2 можна було просто прибрати тип параметра. Для звичайних властивостей, які і читають, і записують, такої свободи немає: їхній тип залишається тим самим.

<a id="union"></a>
## Дозволяємо один із типів: `A|B`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.type-system.php)

Через `|` перелічуємо дозволені варіанти. Наприклад, пошук позиції повертає або число, або `false`, якщо нічого не знайдено. Запис `int|false` прямо це показує.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

echo find_position( 'php', 'p' ); # 0
```

У прикладі знайдена позиція дорівнює нулю, це успішний результат. Відрізняємо його від помилки через `!== false`. У складі такого типу `false` дозволений із PHP 8.0, а як самостійний тип із PHP 8.2.

<a id="mixed"></a>
## Приймаємо будь-яке значення через `mixed`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.mixed.php)

Іноді функція справді має приймати будь-що. Наприклад, показати тип переданого значення або зберегти його в універсальному контейнері. Для цього є `mixed`: він приймає будь-яке значення PHP, зокрема `null`.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

Додавати `null` ще раз не треба. Записи `?mixed` і `mixed|null` недопустимі, він уже входить у `mixed`.

<a id="static-return"></a>
## Повертаємо об’єкт потрібного нащадка: `static`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

Метод створення оголошено в `Record`, але викликаємо його через `UserRecord`. Тип результату `static` враховує саме клас виклику. Тому нащадку не треба переписувати метод лише заради правильного типу результату.

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

`self`, на відміну від `static`, указує на клас, де метод оголошено. Із PHP 8.5 під час перевизначення у `final`-класі результат `static` можна замінити на `self` або ім’я цього класу.

<a id="intersection"></a>
## Вимагаємо одразу обидва типи: `A&B`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

Іноді потрібен не один із варіантів, а об’єкт із двома можливостями одразу. Запис `Countable&Iterator` означає: об’єкт має і перебиратися через методи ітератора, і дозволяти порахувати елементи.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

`ArrayIterator` відповідає обом вимогам. Таке поєднання вимог працює для об’єктів; скласти скалярні типи як `int&string` не можна.

<a id="never"></a>
## Після цієї функції не продовжуємо: `never`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.never.php)

Якщо функція завжди викидає виняток, завершує процес або залишається в нескінченному циклі, указуємо `never`. Так одразу видно, що звичайного повернення до наступного рядка коду виклику не буде.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}
```

У прикладі будь-який виклик завершиться винятком. Навіть порожній `return;` тут заборонений, а якщо виконання все ж дійде до кінця функції, PHP викине `TypeError`.

<a id="dnf"></a>
## Поєднуємо вимоги й варіанти: `(A&B)|C`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/migration82.new-features.php)

Можна поєднати `&` і `|`: прийняти об’єкт із двома потрібними можливостями або зовсім інший варіант. Це називають DNF. У прикладі потрібні одночасно `Countable` і `Iterator`, а якщо колекції немає, дозволений `null`.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Групу з `&` обов’язково беремо в дужки. Довільно переставляти такі групи не можна: запис `A&(B|C)` PHP не приймає.

<a id="literal-types"></a>
## Повертаємо саме `true`, `false` або `null`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/language.types.singleton.php)

Тип результату може дозволяти не будь-який `bool`, а лише одне значення. Наприклад, `true` підходить для операції, яка в разі успіху повертає `true`, а в разі помилки викидає виняток.

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

Якщо підходять і `true`, і `false`, пишемо `bool`: запис `true|false` не дозволений. Окремий тип `null` теж повертає конкретне значення, тому це не те саме, що `void`.

<a id="typed-constants"></a>
## Указуємо тип константи класу

**PHP 8.3+** | [PHP](https://www.php.net/manual/en/language.oop5.constants.php)

Для константи класу теж можна закріпити тип. Якщо нащадок перевизначить таку константу, новий тип має залишатися сумісним. Це допомагає зберегти очікуване значення під час успадкування.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}
```

Те саме працює для констант інтерфейсів, трейтів та enum. Для звичайної глобальної `const` так записати тип не можна.

<a id="native-type-limits"></a>
## Що перевіряє PHP, а що залишаємо PHPDoc

**PHP 7.0-8.5 | Контекст** | [PHP](https://www.php.net/manual/en/language.types.declarations.php)

Тип `array` каже PHP лише те, що передано масив. Які в ньому ключі й елементи, він за таким оголошенням не перевіряє. Уточнення `list<User>` у PHPDoc допомагає редактору й статичному аналізатору зрозуміти, що всередині список користувачів, але сам PHP цей запис не виконує.

```php
/** @param list<User> $users */
function take_users( array $users ) {}
```

Так само зі `string`: рядок може виявитися неправильним email, чужим шляхом або небезпечним HTML. Типи допомагають відсіяти частину помилок, а вміст усе одно перевіряємо там, де використовуємо значення.
