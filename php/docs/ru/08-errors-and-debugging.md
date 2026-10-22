# Ошибки и отладка

[EN](../en/08-errors-and-debugging.md) | [UK](../uk/08-errors-and-debugging.md) | [RU](../ru/08-errors-and-debugging.md)

[Содержание](../../README.ru.md)

**На этой странице**

- [`Throwable` объединяет `Exception` и `Error`](#throwable)
- [Один `catch` для нескольких типов исключений](#multi-catch)
- [`catch` без неиспользуемой переменной](#catch-without-variable)
- [`ValueError` и `TypeError`](#value-error)
- [Исключения из `__toString()`](#to-string-exceptions)
- [Идентификатор ресурса через `get_resource_id()`](#resource-id)
- [Понятные типы во время выполнения через `get_debug_type()`](#debug-types)
- [Как понять ошибку регулярного выражения](#regex-errors)
- [Как отличить отсутствующую группу от пустого совпадения](#unmatched-groups)
- [Свой callback замены для каждого шаблона](#regex-callback-map)
- [Скрытие секретных аргументов в стеке](#sensitive-parameter)
- [Пометка старого API через `#[Deprecated]`](#deprecated-attribute)
- [Выявление проигнорированного результата через `#[NoDiscard]`](#nodiscard)
- [Проверка текущих обработчиков ошибок и исключений](#current-handlers)
- [Стек вызовов для фатальных ошибок](#fatal-backtraces)
- [Чтение PHP-кода через `PhpToken`](#php-token)

<a id="throwable"></a>
## `Throwable` объединяет `Exception` и `Error`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.throwable.php)

`Throwable` охватывает иерархии `Exception` и ошибок движка `Error`; перехват только `Exception` пропускает `TypeError`.

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

Обычные предупреждения не становятся исключениями автоматически, а некоторые фатальные состояния остаются невосстановимыми.

<a id="multi-catch"></a>
## Один `catch` для нескольких типов исключений

**PHP 7.1+ | Ядро** | [Документация PHP](https://www.php.net/exceptions)

`catch` может перечислять несколько типов исключений через `|`.

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

Выполняется только первый подходящий обработчик, поэтому конкретные типы ставьте перед более общими родительскими.

<a id="catch-without-variable"></a>
## `catch` без неиспользуемой переменной

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/exceptions)

Переменная в объявлении `catch` необязательна, если обработчику не нужен объект исключения.

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
## `ValueError` и `TypeError`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.valueerror.php)

`TypeError` сообщает о нарушении контракта типов, а `ValueError` о допустимом типе с некорректным значением.

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

Это зависит от API: некоторые функции всё ещё сообщают о неудаче через `false` или другой документированный результат.

<a id="to-string-exceptions"></a>
## Исключения из `__toString()`

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

С PHP 7.4 исключения из `__toString()` можно перехватывать обычным `catch`.

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

Более ранние версии считали исключение при таком преобразовании фатальной ошибкой.

<a id="resource-id"></a>
## Идентификатор ресурса через `get_resource_id()`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.get-resource-id.php)

`get_resource_id()` явно возвращает целый идентификатор ресурса вместо `(int)$resource`.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

$resource_id = get_resource_id( $stream );
fclose( $stream );
```

Он идентифицирует ресурс в текущем процессе, а не между запусками, и не является файловым дескриптором операционной системы.

<a id="debug-types"></a>
## Понятные типы во время выполнения через `get_debug_type()`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.get-debug-type.php)

`get_debug_type()` возвращает диагностические имена типов: `int`, имя класса или вид ресурса.

```php
$type = get_debug_type( 42 ); # int
$class_name = get_debug_type( new DateTimeImmutable() ); # DateTimeImmutable
```

Функция описывает значение, а не проверяет его.

<a id="regex-errors"></a>
## Как понять ошибку регулярного выражения

**PHP 8.0+ | PCRE** | [Документация PHP](https://www.php.net/manual/en/function.preg-last-error-msg.php)

`preg_last_error_msg()` объясняет последнюю ошибку PCRE; `preg_match()` возвращает `1`, `0` или `false` для совпадения, отсутствия совпадения или ошибки.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Читайте ошибку до следующего выполнения регулярного выражения, которое заменит её состояние; некорректный шаблон также может вызвать предупреждение.

<a id="unmatched-groups"></a>
## Как отличить отсутствующую группу от пустого совпадения

**PHP 7.2+ | PCRE** | [Документация PHP](https://www.php.net/manual/en/function.preg-match.php)

`PREG_UNMATCHED_AS_NULL` различает группу, которая не участвовала (`null`), и пустое совпадение (`''`).

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );
var_dump( $matches['digits'], $matches['tail'] ); # null, ''
```

С PHP 7.4 конечные группы без совпадения также включаются как `null`; флаг не заменяет проверку общего результата поиска.

<a id="regex-callback-map"></a>
## Свой callback замены для каждого шаблона

**PHP 7.0+ | PCRE** | [Документация PHP](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

`preg_replace_callback_array()` сопоставляет шаблоны с callback-функциями и применяет правила в порядке массива, поэтому последующие правила видят предыдущие замены.

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

Ошибка выполнения регулярного выражения возвращает `null`, а некорректный шаблон может вызвать предупреждение; необязательный параметр `flags` требует PHP 7.4.

<a id="sensitive-parameter"></a>
## Скрытие секретных аргументов в стеке

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.sensitiveparameter.php)

`#[SensitiveParameter]` заменяет аргумент на `SensitiveParameterValue`, когда аргументы попадают в стек вызовов.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}
```

Атрибут не скрывает явно выведенные значения или значения в сообщениях исключений.

<a id="deprecated-attribute"></a>
## Пометка старого API через `#[Deprecated]`

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.deprecated.php)

`#[Deprecated]` вызывает `E_USER_DEPRECATED` для функций, методов и констант класса; PHP 8.5 добавляет traits и глобальные объявления `const`.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}
```

`since` обозначает версию или дату вашего API, а сообщение не останавливает выполнение.

<a id="nodiscard"></a>
## Выявление проигнорированного результата через `#[NoDiscard]`

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.nodiscard.php)

`#[NoDiscard]` предупреждает, когда результат вызова отброшен; `(void)` явно подавляет предупреждение и также требует PHP 8.5.

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

Атрибут не наследуется автоматически переопределяющими реализациями.

<a id="current-handlers"></a>
## Проверка текущих обработчиков ошибок и исключений

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.get-error-handler.php) | [`get_exception_handler()`](https://www.php.net/manual/en/function.get-exception-handler.php)

`get_error_handler()` и `get_exception_handler()` читают зарегистрированный `callable` без изменения стеков обработчиков.

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

`null` означает отсутствие своего обработчика; `get_error_handler()` не возвращает зарегистрированную маску уровней.

<a id="fatal-backtraces"></a>
## Стек вызовов для фатальных ошибок

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.error-get-last.php)

`fatal_error_backtraces` управляет стеком вызовов фатальных ошибок, а `error_get_last()` возвращает `trace`, когда он доступен.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];
```

Это не делает фатальные ошибки доступными для перехвата; неперехваченные исключения имеют собственный стек и не добавляют этот элемент.

<a id="php-token"></a>
## Чтение PHP-кода через `PhpToken`

**PHP 8.0+ | tokenizer** | [Документация PHP](https://www.php.net/manual/en/class.phptoken.php)

`PhpToken::tokenize()` возвращает однотипные объекты токенов с `id`, `text`, `line` и байтовой позицией `pos`.

```php
$source = '<?php echo 42;';
foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Токенизация не выполняет код и не создаёт полного синтаксического дерева.
