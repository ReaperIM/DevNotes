# Помилки та налагодження

[EN](../en/08-errors-and-debugging.md) | [UK](../uk/08-errors-and-debugging.md) | [RU](../ru/08-errors-and-debugging.md)

[Зміст](../../README.uk.md)

Розбираємося, що саме зламалося, як перехопити потрібну помилку та які дані залишити для налагодження. Окремо дивимося, як не загубити результат перевірки й не показати пароль у стеку викликів.

**На цій сторінці**

- [`Throwable` об’єднує `Exception` та `Error`](#throwable)
- [Один `catch` для кількох типів винятків](#multi-catch)
- [`catch` без невикористаної змінної](#catch-without-variable)
- [`ValueError` і `TypeError`](#value-error)
- [Винятки з `__toString()`](#to-string-exceptions)
- [Ідентифікатор ресурсу через `get_resource_id()`](#resource-id)
- [Як дізнатися тип значення через `get_debug_type()`](#debug-types)
- [Як зрозуміти помилку регулярного виразу](#regex-errors)
- [Як відрізнити відсутню групу від порожнього збігу](#unmatched-groups)
- [Власний callback заміни для кожного шаблону](#regex-callback-map)
- [Приховування секретних аргументів у стеку](#sensitive-parameter)
- [Позначення старого API через `#[Deprecated]`](#deprecated-attribute)
- [Попередження, якщо результат функції загубили](#nodiscard)
- [Які обробники помилок зараз установлені](#current-handlers)
- [Стек викликів для фатальних помилок](#fatal-backtraces)
- [Читання PHP-коду через `PhpToken`](#php-token)

<a id="throwable"></a>
## `Throwable` об’єднує `Exception` та `Error`

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.throwable.php)

Якщо ловити лише `Exception`, помилка типу може пройти повз обробник. У `Exception` і помилок рушія `Error` спільний інтерфейс `Throwable`, тому через нього можна перехопити обидва варіанти. Тут передаємо масив замість рядка, отримуємо `TypeError` і потрапляємо в `catch`.

```php
try
{
	strlen( [] );
}
catch( Throwable $error )
{
	echo get_class( $error ); # TypeError
}
```

Звичайне попередження саме собою винятком не стає. І можливість перехопити `Error` не означає, що після будь-якого збою можна продовжити роботу: деякі фатальні стани залишаються невідновними.

<a id="multi-catch"></a>
## Один `catch` для кількох типів винятків

**PHP 7.1+ | Ядро** | [Документація PHP](https://www.php.net/exceptions)

Якщо кілька винятків обробляються однаково, перелічуємо їх через `|` в одному `catch`. Не потрібно копіювати той самий обробник для кожного типу. У прикладі повідомлення виведеться і для `InvalidArgumentException`, і для `RuntimeException`.

```php
try
{
	throw new InvalidArgumentException( 'Invalid format' );
}
catch( InvalidArgumentException | RuntimeException $error )
{
	echo $error->getMessage(); # Invalid format
}
```

Коли блоків `catch` кілька, спрацьовує перший відповідний. Тому вузькі типи ставимо раніше за загальні: інакше батьківський тип перехопить виняток до точнішого обробника.

<a id="catch-without-variable"></a>
## `catch` без невикористаної змінної

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/exceptions)

Якщо потрібен лише факт помилки, а її повідомлення та решту даних не використовуємо, змінну в `catch` можна прибрати. Тут достатньо знати, що ділили на нуль, і вивести запасний результат.

```php
try
{
	intdiv( 10, 0 );
}
catch( DivisionByZeroError )
{
	echo 'No ratio'; # No ratio
}
```

Якщо пізніше знадобиться записати подробиці помилки в журнал, повернемо змінну в `catch`. Поки вона не потрібна, оголошувати її лише заради оголошення немає сенсу.

<a id="value-error"></a>
## `ValueError` і `TypeError`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.valueerror.php)

`TypeError` означає, що передали невідповідний тип. У `ValueError` тип підходить, але саме значення використати не можна. У прикладі масив передано правильно, тільки він порожній, і вибрати з нього випадковий елемент неможливо.

```php
try
{
	array_rand( [] );
}
catch( ValueError $error )
{
	echo $error->getMessage();
}
```

Це не загальне правило для всіх функцій. У конкретної функції помилка все ще може повертатися як `false` або інше спеціальне значення, тому спосіб перевірки дивимося в її документації.

<a id="to-string-exceptions"></a>
## Винятки з `__toString()`

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

Перетворення об'єкта на рядок теж може завершитися помилкою. З PHP 7.4 виняток із `__toString()` можна перехопити звичайним `catch`. Тут перетворення відбувається на `echo`, а замість рядка метод викидає `RuntimeException`.

```php
class ReportLabel
{
	public function __toString(): string
	{
		throw new RuntimeException( 'Label is unavailable' );
	}
}

try
{
	echo new ReportLabel();
}
catch( RuntimeException $error )
{
	echo $error->getMessage();
}
# Label is unavailable
```

У попередніх версіях такий виняток призводив до фатальної помилки. Якщо код має працювати на старій версії PHP, цю різницю потрібно враховувати окремо.

<a id="resource-id"></a>
## Ідентифікатор ресурсу через `get_resource_id()`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.get-resource-id.php)

Щоб розрізняти відкриті ресурси під час налагодження або в журналі, можна отримати їхній числовий ідентифікатор через `get_resource_id()`. Це зрозуміліше, ніж приводити ресурс до `(int)`. У прикладі спочатку перевіряємо, чи відкрився тимчасовий потік, і лише потім беремо ідентифікатор.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

$resource_id = get_resource_id( $stream );
fclose( $stream );
```

Цей номер має сенс у поточному процесі. Зберігати його для наступного запуску не варто. І з файловим дескриптором операційної системи він не збігається за призначенням.

<a id="debug-types"></a>
## Як дізнатися тип значення через `get_debug_type()`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.get-debug-type.php)

Коли в повідомленні про помилку потрібен зрозумілий тип значення, беремо `get_debug_type()`. Для числа отримаємо `int`, для об'єкта назву його класу, для ресурсу його вид. У прикладі не потрібно окремо перевіряти, число перед нами чи об'єкт.

```php
$type = get_debug_type( 42 ); # int
$class_name = get_debug_type( new DateTimeImmutable() ); # DateTimeImmutable
```

Функція лише описує значення. Якщо треба дозволити чи заборонити конкретний тип, сам собою цей рядок такої перевірки не робить.

<a id="regex-errors"></a>
## Як зрозуміти помилку регулярного виразу

**PHP 8.0+ | PCRE** | [Документація PHP](https://www.php.net/manual/en/function.preg-last-error-msg.php)

У `preg_match()` три різні результати: `1` означає збіг, `0` його відсутність, а `false` помилку. У прикладі передаємо пошкоджений UTF-8 із прапорцем `u`, тому отримуємо саме помилку. `preg_last_error_msg()` пояснює її текстом.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Забираємо повідомлення до наступного виклику регулярного виразу, інакше він перезапише останню помилку. Неправильний сам шаблон теж може викликати попередження, це окремий привід перевірити його запис.

<a id="unmatched-groups"></a>
## Як відрізнити відсутню групу від порожнього збігу

**PHP 7.2+ | PCRE** | [Документація PHP](https://www.php.net/manual/en/function.preg-match.php)

Порожній знайдений рядок і група, яка взагалі не брала участі в збігу, це різні випадки. `PREG_UNMATCHED_AS_NULL` дозволяє їх розрізнити. У прикладі група `digits` не спрацювала й стала `null`, а `tail` успішно збіглася з порожнім рядком `''`.

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );
var_dump( $matches['digits'], $matches['tail'] ); # null, ''
```

З PHP 7.4 у результат також потрапляють кінцеві групи без збігу, зі значенням `null`. Сам прапорець не каже, чи вдалося виконати пошук: результат `preg_match()` усе одно перевіряємо окремо.

<a id="regex-callback-map"></a>
## Власний callback заміни для кожного шаблону

**PHP 7.0+ | PCRE** | [Документація PHP](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

Якщо для кожного шаблону своя заміна, можна зібрати їх у масив для `preg_replace_callback_array()`. Ключем буде регулярний вираз, значенням функція заміни. Тут спочатку підставляємо ім'я, потім подвоюємо знайдені числа.

```php
$result = preg_replace_callback_array( [
	'/\{name\}/' => static function( array $matches ): string
	{
		return 'Ada';
	},
	'/\d+/' => static function( array $matches ): string
	{
		return (string)((int)$matches[0] * 2);
	},
], '{name}: 7' );

if( $result === null )
	throw new RuntimeException( 'Replacement failed' );

echo $result; # Ada: 14
```

Правила виконуються в порядку масиву, тому наступний шаблон уже бачить попередні заміни. У разі помилки виконання отримаємо `null`, а неправильний шаблон може ще й видати попередження. Необов'язковий параметр `flags` доступний з PHP 7.4.

<a id="sensitive-parameter"></a>
## Приховування секретних аргументів у стеку

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.sensitiveparameter.php)

Пароль в аргументах функції може випадково потрапити в стек викликів. Якщо позначити параметр атрибутом `#[SensitiveParameter]`, PHP підставить у стек об'єкт `SensitiveParameterValue` замість самого значення. Так пароль не опиниться в цьому місці діагностичного виведення.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}
```

Якщо самостійно записати пароль у повідомлення винятку або вивести його в журнал, атрибут це не приховає. Він захищає саме значення аргументу в стеку викликів.

<a id="deprecated-attribute"></a>
## Позначення старого API через `#[Deprecated]`

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.deprecated.php)

Коли стару функцію поки залишаємо, але вже хочемо попередити про заміну, можна поставити `#[Deprecated]`. Під час використання PHP видасть `E_USER_DEPRECATED`. Атрибут підходить для функцій, методів і констант класу, а з PHP 8.5 ще для traits і глобальних оголошень `const`.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}
```

У `since` вказуємо версію чи дату нашого API, а не версію PHP. Саме попередження виконання не зупиняє: стара функція продовжує працювати, доки ми її не видалимо.

<a id="nodiscard"></a>
## Попередження, якщо результат функції загубили

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.nodiscard.php)

Якщо результат функції не можна непомітно загубити, ставимо `#[NoDiscard]`. Наприклад, викликати перевірку імені й нічого не зробити з її `bool` часто означає помилку в коді виклику. PHP попередить про такий виклик. У прикладі перший результат зберігаємо, а другий явно відкидаємо через `(void)`.

```php
#[NoDiscard]
function validate_name( string $name ): bool
{
	return trim( $name ) !== '';
}

$is_valid = validate_name( 'Ada' );
var_dump( $is_valid ); # bool(true)
(void)validate_name( ' ' );
```

`(void)` тут означає, що результат відкинуто навмисно, і прибирає попередження. Саме це приведення теж доступне лише з PHP 8.5. Якщо метод перевизначили, атрибут автоматично в нову реалізацію не переноситься.

<a id="current-handlers"></a>
## Які обробники помилок зараз установлені

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.get-error-handler.php) | [`get_exception_handler()`](https://www.php.net/manual/en/function.get-exception-handler.php)

Якщо треба дізнатися, хто зараз обробляє помилки й неперехоплені винятки, використовуємо `get_error_handler()` і `get_exception_handler()`. Вони повертають зареєстрований `callable`, нічого не встановлюючи замість нього. У прикладі перевіряємо, що це саме наші функції, і в `finally` повертаємо попередні обробники.

```php
$error_handler = static function( int $level, string $message ): bool
{
	return false;
};
$exception_handler = static function( Throwable $error ): void {};

set_error_handler( $error_handler );
set_exception_handler( $exception_handler );

try
{
	var_dump( get_error_handler() === $error_handler );
	var_dump( get_exception_handler() === $exception_handler );
}
finally
{
	restore_exception_handler();
	restore_error_handler();
}
# bool(true)
# bool(true)
```

Якщо свого обробника немає, отримаємо `null`. Маску рівнів помилок, яку передавали в `set_error_handler()`, функція `get_error_handler()` не повертає.

<a id="fatal-backtraces"></a>
## Стек викликів для фатальних помилок

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.error-get-last.php)

Для розбору фатальної помилки корисно бачити не лише рядок збою, а й шлях викликів до нього. Налаштування `fatal_error_backtraces` керує таким стеком, а `error_get_last()` повертає його в полі `trace`, коли він доступний. Якщо поля немає, у прикладі беремо порожній масив.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];
```

Це додаткова діагностика, а не можливість перехопити фатальну помилку через `catch`. У неперехоплених винятків уже є власний стек, і це поле для них не додається.

<a id="php-token"></a>
## Читання PHP-коду через `PhpToken`

**PHP 8.0+ | tokenizer** | [Документація PHP](https://www.php.net/manual/en/class.phptoken.php)

Якщо треба розібрати PHP-код на окремі елементи, не виконуючи його, є `PhpToken::tokenize()`. Кожен токен містить вид елемента `id`, сам текст `text`, рядок `line` і байтову позицію `pos`. У прикладі шукаємо оператор `echo` й виводимо, на якому рядку він зустрівся.

```php
# Це рядок із кодом: розбираємо його, не виконуючи.
$source = '<?php echo 42;';
foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Токени допомагають розбирати вихідний текст, але ще не описують усю будову програми. Повного синтаксичного дерева цей метод не будує й сам код не запускає.
