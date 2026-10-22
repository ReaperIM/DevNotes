# Помилки та налагодження

[English](../en/08-errors-and-debugging.md) | [Українська](../uk/08-errors-and-debugging.md) | [Русский](../ru/08-errors-and-debugging.md)

[Зміст](../../README.uk.md)

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

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.throwable.php)

`Throwable` охоплює ієрархії `Exception` та помилок рушія `Error`; перехоплення лише `Exception` пропускає `TypeError`.

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

Звичайні попередження не стають винятками автоматично, а деякі фатальні стани залишаються невідновними.

<a id="multi-catch"></a>
## Один catch для кількох типів винятків

**PHP 7.1+ | Ядро** | [Документація PHP](https://www.php.net/exceptions)

`catch` може перелічувати кілька типів винятків через `|`.

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

Виконується лише перший відповідний обробник, тому конкретні типи ставте перед загальнішими батьківськими.

<a id="catch-without-variable"></a>
## catch без невикористаної змінної

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/exceptions)

Змінна в оголошенні `catch` необов’язкова, якщо обробнику не потрібен об’єкт винятку.

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

<a id="value-error"></a>
## ValueError і TypeError

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.valueerror.php)

`TypeError` повідомляє про порушення контракту типів, а `ValueError` про допустимий тип із некоректним значенням.

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

Це залежить від API: деякі функції все ще повідомляють про невдачу через `false` або інший задокументований результат.

<a id="to-string-exceptions"></a>
## Винятки з __toString()

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

З PHP 7.4 винятки з `__toString()` можна перехоплювати звичайним `catch`.

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

Раніші версії вважали виняток під час цього перетворення фатальною помилкою.

<a id="resource-id"></a>
## Ідентифікатор ресурсу через get_resource_id()

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.get-resource-id.php)

`get_resource_id()` явно повертає цілий ідентифікатор ресурсу замість `(int)$resource`.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

$resource_id = get_resource_id( $stream );
fclose( $stream );
```

Він ідентифікує ресурс у поточному процесі, а не між запусками, і не є файловим дескриптором операційної системи.

<a id="debug-types"></a>
## Зрозумілі типи під час виконання через get_debug_type()

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.get-debug-type.php)

`get_debug_type()` повертає діагностичні назви типів: `int`, назву класу або вид ресурсу.

```php
$type = get_debug_type( 42 ); # int
$class_name = get_debug_type( new DateTimeImmutable() ); # DateTimeImmutable
```

Функція описує значення, а не перевіряє його.

<a id="regex-errors"></a>
## Як зрозуміти помилку регулярного виразу

**PHP 8.0+ | PCRE** | [Документація PHP](https://www.php.net/manual/en/function.preg-last-error-msg.php)

`preg_last_error_msg()` пояснює останню помилку PCRE; `preg_match()` повертає `1`, `0` або `false` для збігу, відсутності збігу чи помилки.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Читайте помилку до наступного виконання регулярного виразу, яке замінить її стан; некоректний шаблон також може спричинити попередження.

<a id="unmatched-groups"></a>
## Як відрізнити відсутню групу від порожнього збігу

**PHP 7.2+ | PCRE** | [Документація PHP](https://www.php.net/manual/en/function.preg-match.php)

`PREG_UNMATCHED_AS_NULL` розрізняє групу, яка не брала участі (`null`), і порожній збіг (`''`).

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );
var_dump( $matches['digits'], $matches['tail'] ); # null, ''
```

З PHP 7.4 кінцеві групи без збігу також включаються як `null`; прапорець не замінює перевірку загального результату пошуку.

<a id="regex-callback-map"></a>
## Власний callback заміни для кожного шаблону

**PHP 7.0+ | PCRE** | [Документація PHP](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

`preg_replace_callback_array()` зіставляє шаблони з callback-функціями та застосовує правила в порядку масиву, тому пізніші правила бачать попередні заміни.

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

Помилка виконання регулярного виразу повертає `null`, а некоректний шаблон може спричинити попередження; необов’язковий параметр `flags` потребує PHP 7.4.

<a id="sensitive-parameter"></a>
## Приховування секретних аргументів у стеку

**PHP 8.2+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.sensitiveparameter.php)

`#[SensitiveParameter]` замінює аргумент на `SensitiveParameterValue`, коли аргументи потрапляють у стек викликів.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}
```

Атрибут не приховує явно виведені значення або значення в повідомленнях винятків.

<a id="deprecated-attribute"></a>
## Позначення старого API через #[Deprecated]

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.deprecated.php)

`#[Deprecated]` спричиняє `E_USER_DEPRECATED` для функцій, методів і констант класу; PHP 8.5 додає traits та глобальні оголошення `const`.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}
```

`since` позначає версію або дату вашого API, а повідомлення не зупиняє виконання.

<a id="nodiscard"></a>
## Виявлення проігнорованого результату через #[NoDiscard]

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/class.nodiscard.php)

`#[NoDiscard]` попереджає, коли результат виклику відкинуто; `(void)` явно пригнічує попередження та також потребує PHP 8.5.

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

Атрибут не успадковується автоматично перевизначеними реалізаціями.

<a id="current-handlers"></a>
## Перевірка поточних обробників помилок і винятків

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.get-error-handler.php) | [get_exception_handler()](https://www.php.net/manual/en/function.get-exception-handler.php)

`get_error_handler()` і `get_exception_handler()` читають зареєстрований callable без зміни стеків обробників.

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

`null` означає відсутність власного обробника; `get_error_handler()` не повертає зареєстровану маску рівнів.

<a id="fatal-backtraces"></a>
## Стек викликів для фатальних помилок

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.error-get-last.php)

`fatal_error_backtraces` керує стеком викликів фатальних помилок, а `error_get_last()` повертає `trace`, коли він доступний.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];
```

Це не робить фатальні помилки доступними для перехоплення; неперехоплені винятки мають власний стек і не додають цей елемент.

<a id="php-token"></a>
## Читання PHP-коду через PhpToken

**PHP 8.0+ | tokenizer** | [Документація PHP](https://www.php.net/manual/en/class.phptoken.php)

`PhpToken::tokenize()` повертає однотипні об’єкти токенів із `id`, `text`, `line` та байтовою позицією `pos`.

```php
$source = '<?php echo 42;';
foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Токенізація не виконує код і не створює повного синтаксичного дерева.
