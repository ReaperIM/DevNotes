# Помилки та налагодження

[English](../en/08-errors-and-debugging.md) | [Українська](../uk/08-errors-and-debugging.md) | [Русский](../ru/08-errors-and-debugging.md)

[Зміст](../../README.uk.md)

Хороша діагностика розрізняє некоректні вхідні дані, помилку в коді та збій під час виконання. Тут показано засоби мови без перетворення кожної очікуваної ситуації на виняток. Запускайте кожен PHP-приклад окремо.

**На цій сторінці**

- [Throwable об’єднує Exception та Error](#throwable)
- [Один catch для кількох типів винятків](#multi-catch)
- [catch без невикористаної змінної](#catch-without-variable)
- [ValueError і TypeError](#value-error)
- [Винятки з __toString()](#to-string-exceptions)
- [Ідентифікатор ресурсу через get_resource_id()](#resource-id)
- [Зрозумілі типи під час виконання через get_debug_type()](#debug-types)
- [Як зрозуміти помилку регулярного виразу](#regex-errors)
- [Як відрізнити відсутню групу від порожнього збігу](#unmatched-groups)
- [Власний callback заміни для кожного шаблону](#regex-callback-map)
- [Приховування секретних аргументів у стеку](#sensitive-parameter)
- [Позначення старого API через #[Deprecated]](#deprecated-attribute)
- [Виявлення проігнорованого результату через #[NoDiscard]](#nodiscard)
- [Перевірка поточних обробників помилок і винятків](#current-handlers)
- [Стек викликів для фатальних помилок](#fatal-backtraces)
- [Читання PHP-коду через PhpToken](#php-token)

<a id="throwable"></a>
## Throwable об’єднує Exception та Error

**PHP 7.0+ | Ядро**

PHP 7 вводить окрему ієрархію `Error` для багатьох помилок рушія. І `Error`, і `Exception` реалізують `Throwable`. На межі, де потрібно обробити обидва види, можна перехопити `Throwable`; перехоплення лише `Exception` не охоплює `TypeError`.

```php
function double_number( int $value ): int
{
	return $value * 2;
}

try
{
	double_number( [] );
}
catch( Throwable $error )
{
	echo get_class( $error );
}
# TypeError
```

Це не механізм перехоплення будь-якої діагностики PHP. Звичайні попередження не стають винятками автоматично, а після деяких фатальних ситуацій роботу неможливо продовжити. Для конкретного відновлення перехоплюйте точніший тип; широку межу залишайте для журналювання або відповідної відповіді про збій операції.

[Документація PHP](https://www.php.net/manual/en/class.throwable.php)

<a id="multi-catch"></a>
## Один catch для кількох типів винятків

**PHP 7.1+ | Ядро**

`catch` може містити кілька типів, розділених `|`. Це доречно, коли різні збої справді потребують однакової дії відновлення, замість копіювання блоку для кожного класу.

```php
try
{
	throw new InvalidArgumentException( 'Invalid format' );
}
catch( InvalidArgumentException | RuntimeException $error )
{
	echo 'Cannot import: ', $error->getMessage();
}
# Cannot import: Invalid format
```

Це правило вибору винятку, а не об’єднаний тип результату. Точніші блоки `catch` мають стояти перед ширшим батьківським типом, якщо обидва присутні: виняток обробляє лише перший відповідний блок.

[Документація PHP](https://www.php.net/exceptions)

<a id="catch-without-variable"></a>
## catch без невикористаної змінної

**PHP 8.0+ | Ядро**

Змінна винятку необов’язкова. Прибирайте її, коли обробник лише обирає запасну дію і справді не потребує повідомлення, стеку або попереднього винятку.

```php
try
{
	intdiv( 10, 0 );
}
catch( DivisionByZeroError )
{
	echo 'No ratio';
}
# No ratio
```

Відсутність змінної не має ставати приводом приховувати неочікувані збої. Якщо для розбору потрібні подробиці, залиште змінну та запишіть корисну діагностику в журнал застосунку.

[Документація PHP](https://www.php.net/exceptions)

<a id="value-error"></a>
## ValueError і TypeError

**PHP 8.0+ | Ядро**

`TypeError` означає порушення вимог до типу. `ValueError` означає значення прийнятного типу, яке виходить за допустимі межі функції, наприклад порожній масив там, де потрібно вибрати елемент.

```php
try
{
	array_rand( [] );
}
catch( ValueError $error )
{
	echo 'The array must contain an item';
}
# The array must contain an item
```

Не поширюйте це на всі API: деякі функції й далі повертають `false` або інше задокументоване значення помилки. Читайте контракт конкретної функції. Для очікуваних вхідних даних перевірка до виклику може дати зрозуміліше повідомлення.

[Документація PHP](https://www.php.net/manual/en/class.valueerror.php)

<a id="to-string-exceptions"></a>
## Винятки з __toString()

**PHP 7.4+ | Ядро**

Невдале перетворення на рядок тепер може викинути виняток, який викликач звичайно перехопить. До PHP 7.4 виняток із `__toString()` спричиняв фатальну помилку.

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

Перетворення все ще має бути передбачуваним і недорогим. Його можна непрямо викликати склеюванням рядків, журналюванням або виведенням, тому мережеві запити чи значні побічні дії всередині ускладнюють пошук помилок.

[Документація PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

<a id="resource-id"></a>
## Ідентифікатор ресурсу через get_resource_id()

**PHP 8.0+ | Ядро**

`get_resource_id()` явно повертає цілий ідентифікатор ресурсу. Вона замінює менш зрозуміле приведення `(int)$resource`, коли потрібно пов’язати потоки з діагностичною інформацією.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

try
{
	$id = get_resource_id( $stream );
	var_dump( is_int($id) );
	var_dump( $id === get_resource_id($stream) );
}
finally
{
	fclose( $stream );
}
# bool(true)
# bool(true)
```

Числовий ID різниться між запусками. Використовуйте його лише для ідентифікації ресурсів у поточному процесі: це не постійний ідентифікатор застосунку й не файловий дескриптор операційної системи. Приклад закриває тимчасовий потік у `finally`; `tmpfile()` також видаляє тимчасовий файл під час закриття. Для об’єктів існують інші засоби ідентичності.

[Документація PHP](https://www.php.net/manual/en/function.get-resource-id.php)

<a id="debug-types"></a>
## Зрозумілі типи під час виконання через get_debug_type()

**PHP 8.0+ | Ядро**

`get_debug_type()` повертає зручні для діагностики назви: `int`, `bool`, назву класу або вид ресурсу. Не потрібно вручну поєднувати `gettype()` та `get_class()`.

```php
foreach( [42, false, null, new DateTimeImmutable('2025-01-01')] as $value )
	echo get_debug_type( $value ), PHP_EOL;
# int
# bool
# null
# DateTimeImmutable
```

Використовуйте її для опису відхиленого значення без виведення самого значення. Це діагностична назва, а не заміна `instanceof`, типів у сигнатурах або явної перевірки.

[Документація PHP](https://www.php.net/manual/en/function.get-debug-type.php)

<a id="regex-errors"></a>
## Як зрозуміти помилку регулярного виразу

**PHP 8.0+ | PCRE**

`preg_match()` має три змістовні результати: `1` означає збіг, `0` його відсутність, а `false` помилку виконання. `preg_last_error_msg()` пояснює останню помилку PCRE без власної таблиці числових кодів.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Читайте помилку одразу після невдалого виклику: інший регулярний вираз може змінити цей стан. У прикладі шаблон правильний, але вхідні дані не є коректним UTF-8. Некоректний шаблон є окремою проблемою й також може створювати попередження.

[Документація PHP](https://www.php.net/manual/en/function.preg-last-error-msg.php)

<a id="unmatched-groups"></a>
## Як відрізнити відсутню групу від порожнього збігу

**PHP 7.2+ | PCRE**

`PREG_UNMATCHED_AS_NULL` подає необов’язкову групу, яка не брала участі у збігу, як `null`. Група, що взяла участь і збіглася з порожнім рядком, залишається `''`. Це корисно, коли порожнє та відсутнє поля мають різний зміст.

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );

var_dump( $matches['digits'] );
var_dump( $matches['tail'] );
# NULL
# string(0) ""
```

У цьому прикладі весь шаблон усе одно збігся. Прапорець не замінює перевірку результату `preg_match()` на `1`, `0` або `false`. Із PHP 7.4 кінцеві групи без збігу також включаються як `null`; попередні версії могли пропускати такі кінцеві елементи навіть із цим прапорцем.

[Документація PHP](https://www.php.net/manual/en/function.preg-match.php)

<a id="regex-callback-map"></a>
## Власний callback заміни для кожного шаблону

**PHP 7.0+ | PCRE**

`preg_replace_callback_array()` приймає карту шаблонів і callback-функцій. Кожне правило може обчислювати власну заміну, тож пов’язані перетворення тексту залишаються разом без великої функції, що вгадує шаблон збігу.

```php
$rules = [
	'/\{name\}/' => static function( array $matches ): string
	{
		return 'Ada';
	},
	'/\d+/' => static function( array $matches ): string
	{
		return (string)((int)$matches[0] * 2);
	},
];

$result = preg_replace_callback_array( $rules, '{name}: 7' );

if( $result === null )
	throw new RuntimeException( 'Replacement failed' );

echo $result;
# Ada: 14
```

Правила виконуються в порядку масиву, тож наступний шаблон бачить текст, змінений попереднім правилом. Шаблони мають контролюватися застосунком. Помилка виконання регулярного виразу повертає `null`, а не `false`; некоректний шаблон може створити попередження. Необов’язковий параметр `flags` додано в PHP 7.4, пізніше за саму функцію.

[Документація PHP](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

<a id="sensitive-parameter"></a>
## Приховування секретних аргументів у стеку

**PHP 8.2+ | Ядро**

Позначте параметр із секретом атрибутом `#[SensitiveParameter]`. Якщо стек містить аргументи, PHP замінить цей аргумент об’єктом `SensitiveParameterValue`. Це допомагає не виводити паролі й токени у звичайному текстовому стеку.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}

$previous = ini_get( 'zend.exception_ignore_args' );
ini_set( 'zend.exception_ignore_args', '0' );

try
{
	authenticate( 'example-secret' );
}
catch( RuntimeException $error )
{
	$trace = $error->getTrace();
	echo get_debug_type( $trace[0]['args'][0] );
}
finally
{
	ini_set( 'zend.exception_ignore_args', $previous );
}
# SensitiveParameterValue
```

Це не шифрування й не загальний фільтр журналів. Явне виведення пароля або додавання його до повідомлення винятку все ще розкриває секрет. Тимчасова INI-настройка лише робить демонстрацію видимою; після неї попереднє значення відновлюється.

[Документація PHP](https://www.php.net/manual/en/class.sensitiveparameter.php)

<a id="deprecated-attribute"></a>
## Позначення старого API через #[Deprecated]

**PHP 8.4+ | Ядро**

Вбудований атрибут позначає функції, методи й константи класу як застарілі. Їх використання створює `E_USER_DEPRECATED`. PHP 8.5 додає підтримку для traits і глобальних констант, оголошених через `const`.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}

set_error_handler( static function( int $level, string $message ): bool
{
	echo 'Deprecated API', PHP_EOL;
	return true;
}, E_USER_DEPRECATED );

try
{
	echo old_name();
}
finally
{
	restore_error_handler();
}
# Deprecated API
# report
```

`since` є версією або датою вашого API, а не обов’язково версією PHP. У повідомленні вкажіть корисну заміну. Тимчасовий обробник вище робить результат передбачуваним; позначка застарілості не заважає виконанню тіла функції.

[Документація PHP](https://www.php.net/manual/en/class.deprecated.php)

<a id="nodiscard"></a>
## Виявлення проігнорованого результату через #[NoDiscard]

**PHP 8.5+ | Ядро**

Деякі функції повертають інформацію, яку викликач має перевірити, наприклад результат валідації або нове незмінюване значення. `#[NoDiscard]` створює попередження, якщо результат виклику відкинуто.

```php
#[NoDiscard]
function validate_name( string $name ): bool
{
	return trim( $name ) !== '';
}

$is_valid = validate_name( 'Ada' );
var_dump( $is_valid );
(void)validate_name( ' ' );
# bool(true)
```

`(void)` є явним рішенням проігнорувати результат і прибирає попередження; це приведення також потребує PHP 8.5. Присвоєння результату не доводить, що викликач використав його змістовно. Перевизначені реалізації потребують власного атрибута: оголошення в інтерфейсі не нав’язує його автоматично.

[Документація PHP](https://www.php.net/manual/en/class.nodiscard.php)

<a id="current-handlers"></a>
## Перевірка поточних обробників помилок і винятків

**PHP 8.5+ | Ядро**

`get_error_handler()` і `get_exception_handler()` повертають поточний зареєстрований callable без тимчасової заміни. Це допомагає діагностиці й інтеграційному коду перевіряти стан процесу без встановлення фіктивного обробника.

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

Результат `null` означає, що власний обробник цього виду не встановлено. Getter повертає callable, а не маску рівнів із `set_error_handler()`. Для тимчасового встановлення використовуйте відповідний `restore_*_handler()` у `finally`, щоб відновити попередній стек обробників.

[Документація PHP](https://www.php.net/manual/en/function.get-error-handler.php) | [get_exception_handler()](https://www.php.net/manual/en/function.get-exception-handler.php)

<a id="fatal-backtraces"></a>
## Стек викликів для фатальних помилок

**PHP 8.5+ | Ядро**

Фатальні збої, наприклад вичерпаний час виконання, можуть містити стек викликів, яким керує `fatal_error_backtraces`. У PHP 8.5 `error_get_last()` також повертає поле `trace`, якщо такий стек фатальної помилки доступний.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];

echo count( $trace );
# 0 in a fresh process with no preceding error
```

Приклад лише читає діагностику й навмисно не вичерпує пам’ять та не затримує скрипт. Стек допомагає розбору, але не робить фатальну помилку придатною до перехоплення. Неперехоплений виняток має власний стек і не додає це поле `trace` до `error_get_last()`. Не виводьте внутрішню діагностику в публічну відповідь.

[Документація PHP](https://www.php.net/manual/en/function.error-get-last.php)

<a id="php-token"></a>
## Читання PHP-коду через PhpToken

**PHP 8.0+ | tokenizer**

`PhpToken::tokenize()` подає кожен лексичний токен об’єктом однакової форми: `id`, `text`, `line` і байтова позиція `pos`. З ним зручніше працювати, ніж із сумішшю масивів та односимвольних рядків від `token_get_all()`.

```php
$source = '<?php echo 42;';

foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Це корисно для інструментів перевірки коду й невеликих перетворень вихідного тексту. Токенізація не виконує цей код. Токени не є повним синтаксичним деревом, тому складний рефакторинг усе ще потребує парсера та врахування областей видимості.

[Документація PHP](https://www.php.net/manual/en/class.phptoken.php)
