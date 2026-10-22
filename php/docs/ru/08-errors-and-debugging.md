# Ошибки и отладка

[English](../en/08-errors-and-debugging.md) | [Українська](../uk/08-errors-and-debugging.md) | [Русский](../ru/08-errors-and-debugging.md)

[Содержание](../../README.ru.md)

Хорошая диагностика различает некорректные входные данные, ошибку в коде и сбой во время выполнения. Здесь показаны средства языка без превращения каждой ожидаемой ситуации в исключение. Запускайте каждый PHP-пример отдельно.

**На этой странице**

- [Throwable объединяет Exception и Error](#throwable)
- [Один catch для нескольких типов исключений](#multi-catch)
- [catch без неиспользуемой переменной](#catch-without-variable)
- [ValueError и TypeError](#value-error)
- [Исключения из __toString()](#to-string-exceptions)
- [Идентификатор ресурса через get_resource_id()](#resource-id)
- [Понятные типы во время выполнения через get_debug_type()](#debug-types)
- [Как понять ошибку регулярного выражения](#regex-errors)
- [Как отличить отсутствующую группу от пустого совпадения](#unmatched-groups)
- [Свой callback замены для каждого шаблона](#regex-callback-map)
- [Скрытие секретных аргументов в стеке](#sensitive-parameter)
- [Пометка старого API через #[Deprecated]](#deprecated-attribute)
- [Выявление проигнорированного результата через #[NoDiscard]](#nodiscard)
- [Проверка текущих обработчиков ошибок и исключений](#current-handlers)
- [Стек вызовов для фатальных ошибок](#fatal-backtraces)
- [Чтение PHP-кода через PhpToken](#php-token)

<a id="throwable"></a>
## Throwable объединяет Exception и Error

**PHP 7.0+ | Ядро**

PHP 7 вводит отдельную иерархию `Error` для многих ошибок движка. И `Error`, и `Exception` реализуют `Throwable`. На границе, где нужно обработать оба вида, можно перехватить `Throwable`; перехват только `Exception` не охватывает `TypeError`.

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

Это не механизм перехвата любой диагностики PHP. Обычные предупреждения не становятся исключениями автоматически, а после некоторых фатальных ситуаций работу невозможно продолжить. Для конкретного восстановления перехватывайте более точный тип; широкую границу оставляйте для журналирования или подходящего ответа о сбое операции.

[Документация PHP](https://www.php.net/manual/en/class.throwable.php)

<a id="multi-catch"></a>
## Один catch для нескольких типов исключений

**PHP 7.1+ | Ядро**

`catch` может содержать несколько типов, разделённых `|`. Это уместно, когда разные сбои действительно требуют одинакового действия восстановления, вместо копирования блока для каждого класса.

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

Это правило выбора исключения, а не объединённый тип результата. Более точные блоки `catch` должны стоять перед широким родительским типом, если оба присутствуют: исключение обрабатывает только первый подходящий блок.

[Документация PHP](https://www.php.net/exceptions)

<a id="catch-without-variable"></a>
## catch без неиспользуемой переменной

**PHP 8.0+ | Ядро**

Переменная исключения необязательна. Убирайте её, когда обработчик только выбирает запасное действие и действительно не нуждается в сообщении, стеке или предыдущем исключении.

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

Отсутствие переменной не должно становиться поводом скрывать неожиданные сбои. Если для разбора нужны подробности, оставьте переменную и запишите полезную диагностику в журнал приложения.

[Документация PHP](https://www.php.net/exceptions)

<a id="value-error"></a>
## ValueError и TypeError

**PHP 8.0+ | Ядро**

`TypeError` означает нарушение требований к типу. `ValueError` означает значение подходящего типа, которое выходит за допустимые границы функции, например пустой массив там, где нужно выбрать элемент.

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

Не распространяйте это на все API: некоторые функции по-прежнему возвращают `false` или другое документированное значение ошибки. Читайте контракт конкретной функции. Для ожидаемых входных данных проверка до вызова может дать более понятное сообщение.

[Документация PHP](https://www.php.net/manual/en/class.valueerror.php)

<a id="to-string-exceptions"></a>
## Исключения из __toString()

**PHP 7.4+ | Ядро**

Неудачное преобразование в строку теперь может выбросить исключение, которое вызывающий код обычно перехватит. До PHP 7.4 исключение из `__toString()` вызывало фатальную ошибку.

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

Преобразование всё ещё должно быть предсказуемым и недорогим. Его можно косвенно вызвать склеиванием строк, журналированием или выводом, поэтому сетевые запросы или значительные побочные действия внутри усложняют поиск ошибок.

[Документация PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

<a id="resource-id"></a>
## Идентификатор ресурса через get_resource_id()

**PHP 8.0+ | Ядро**

`get_resource_id()` явно возвращает целый идентификатор ресурса. Она заменяет менее понятное приведение `(int)$resource`, когда нужно связать потоки с диагностической информацией.

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

Числовой ID различается между запусками. Используйте его только для идентификации ресурсов в текущем процессе: это не постоянный идентификатор приложения и не файловый дескриптор операционной системы. Пример закрывает временный поток в `finally`; `tmpfile()` также удаляет временный файл при закрытии. Для объектов существуют другие средства идентичности.

[Документация PHP](https://www.php.net/manual/en/function.get-resource-id.php)

<a id="debug-types"></a>
## Понятные типы во время выполнения через get_debug_type()

**PHP 8.0+ | Ядро**

`get_debug_type()` возвращает удобные для диагностики имена: `int`, `bool`, имя класса или вид ресурса. Не нужно вручную сочетать `gettype()` и `get_class()`.

```php
foreach( [42, false, null, new DateTimeImmutable('2025-01-01')] as $value )
	echo get_debug_type( $value ), PHP_EOL;
# int
# bool
# null
# DateTimeImmutable
```

Используйте её для описания отклонённого значения без вывода самого значения. Это диагностическое имя, а не замена `instanceof`, типов в сигнатурах или явной проверки.

[Документация PHP](https://www.php.net/manual/en/function.get-debug-type.php)

<a id="regex-errors"></a>
## Как понять ошибку регулярного выражения

**PHP 8.0+ | PCRE**

`preg_match()` имеет три значимых результата: `1` означает совпадение, `0` его отсутствие, а `false` ошибку выполнения. `preg_last_error_msg()` объясняет последнюю ошибку PCRE без собственной таблицы числовых кодов.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Читайте ошибку сразу после неудачного вызова: другое регулярное выражение может изменить это состояние. В примере шаблон правильный, но входные данные не являются корректным UTF-8. Некорректный шаблон является отдельной проблемой и также может создавать предупреждение.

[Документация PHP](https://www.php.net/manual/en/function.preg-last-error-msg.php)

<a id="unmatched-groups"></a>
## Как отличить отсутствующую группу от пустого совпадения

**PHP 7.2+ | PCRE**

`PREG_UNMATCHED_AS_NULL` представляет необязательную группу, которая не участвовала в совпадении, как `null`. Группа, которая участвовала и совпала с пустой строкой, остаётся `''`. Это полезно, когда пустое и отсутствующее поля имеют разный смысл.

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );

var_dump( $matches['digits'] );
var_dump( $matches['tail'] );
# NULL
# string(0) ""
```

В этом примере весь шаблон всё равно совпал. Флаг не заменяет проверку результата `preg_match()` на `1`, `0` или `false`. С PHP 7.4 конечные группы без совпадения тоже включаются как `null`; прежние версии могли пропускать такие конечные элементы даже с этим флагом.

[Документация PHP](https://www.php.net/manual/en/function.preg-match.php)

<a id="regex-callback-map"></a>
## Свой callback замены для каждого шаблона

**PHP 7.0+ | PCRE**

`preg_replace_callback_array()` принимает карту шаблонов и callback-функций. Каждое правило может вычислять собственную замену, поэтому связанные преобразования текста остаются вместе без большой функции, угадывающей шаблон совпадения.

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

Правила выполняются в порядке массива, поэтому следующий шаблон видит текст, изменённый предыдущим правилом. Шаблоны должны контролироваться приложением. Ошибка выполнения регулярного выражения возвращает `null`, а не `false`; некорректный шаблон может создать предупреждение. Необязательный параметр `flags` добавлен в PHP 7.4, позже самой функции.

[Документация PHP](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

<a id="sensitive-parameter"></a>
## Скрытие секретных аргументов в стеке

**PHP 8.2+ | Ядро**

Пометьте параметр с секретом атрибутом `#[SensitiveParameter]`. Если стек содержит аргументы, PHP заменит этот аргумент объектом `SensitiveParameterValue`. Это помогает не выводить пароли и токены в обычном текстовом стеке.

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

Это не шифрование и не общий фильтр журналов. Явный вывод пароля или добавление его в сообщение исключения всё ещё раскрывает секрет. Временная INI-настройка только делает демонстрацию видимой; после неё прежнее значение восстанавливается.

[Документация PHP](https://www.php.net/manual/en/class.sensitiveparameter.php)

<a id="deprecated-attribute"></a>
## Пометка старого API через #[Deprecated]

**PHP 8.4+ | Ядро**

Встроенный атрибут помечает функции, методы и константы класса как устаревшие. Их использование создаёт `E_USER_DEPRECATED`. PHP 8.5 добавляет поддержку для traits и глобальных констант, объявленных через `const`.

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

`since` является версией или датой вашего API, а не обязательно версией PHP. В сообщении укажите полезную замену. Временный обработчик выше делает результат предсказуемым; пометка устаревания не мешает выполнению тела функции.

[Документация PHP](https://www.php.net/manual/en/class.deprecated.php)

<a id="nodiscard"></a>
## Выявление проигнорированного результата через #[NoDiscard]

**PHP 8.5+ | Ядро**

Некоторые функции возвращают информацию, которую вызывающий код должен проверить, например результат валидации или новое неизменяемое значение. `#[NoDiscard]` создаёт предупреждение, если результат вызова отброшен.

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

`(void)` является явным решением проигнорировать результат и убирает предупреждение; это приведение также требует PHP 8.5. Присваивание результата не доказывает, что вызывающий код использовал его осмысленно. Переопределённым реализациям нужен собственный атрибут: объявление в интерфейсе не навязывает его автоматически.

[Документация PHP](https://www.php.net/manual/en/class.nodiscard.php)

<a id="current-handlers"></a>
## Проверка текущих обработчиков ошибок и исключений

**PHP 8.5+ | Ядро**

`get_error_handler()` и `get_exception_handler()` возвращают текущий зарегистрированный callable без временной замены. Это помогает диагностике и интеграционному коду проверять состояние процесса без установки фиктивного обработчика.

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

Результат `null` означает, что собственный обработчик этого вида не установлен. Getter возвращает callable, а не маску уровней из `set_error_handler()`. Для временной установки используйте соответствующий `restore_*_handler()` в `finally`, чтобы восстановить прежний стек обработчиков.

[Документация PHP](https://www.php.net/manual/en/function.get-error-handler.php) | [get_exception_handler()](https://www.php.net/manual/en/function.get-exception-handler.php)

<a id="fatal-backtraces"></a>
## Стек вызовов для фатальных ошибок

**PHP 8.5+ | Ядро**

Фатальные сбои, например исчерпанное время выполнения, могут содержать стек вызовов, которым управляет `fatal_error_backtraces`. В PHP 8.5 `error_get_last()` также возвращает поле `trace`, если такой стек фатальной ошибки доступен.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];

echo count( $trace );
# 0 in a fresh process with no preceding error
```

Пример только читает диагностику и намеренно не исчерпывает память и не задерживает скрипт. Стек помогает разбору, но не делает фатальную ошибку доступной для перехвата. Неперехваченное исключение имеет собственный стек и не добавляет это поле `trace` в `error_get_last()`. Не выводите внутреннюю диагностику в публичный ответ.

[Документация PHP](https://www.php.net/manual/en/function.error-get-last.php)

<a id="php-token"></a>
## Чтение PHP-кода через PhpToken

**PHP 8.0+ | tokenizer**

`PhpToken::tokenize()` представляет каждый лексический токен объектом одинаковой формы: `id`, `text`, `line` и байтовая позиция `pos`. С ним удобнее работать, чем со смесью массивов и односимвольных строк от `token_get_all()`.

```php
$source = '<?php echo 42;';

foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Это полезно для инструментов проверки кода и небольших преобразований исходного текста. Токенизация не выполняет этот код. Токены не являются полным синтаксическим деревом, поэтому сложный рефакторинг всё ещё требует парсера и учёта областей видимости.

[Документация PHP](https://www.php.net/manual/en/class.phptoken.php)
