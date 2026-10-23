# Ошибки и отладка

[EN](../en/08-errors-and-debugging.md) | [UK](../uk/08-errors-and-debugging.md) | [RU](../ru/08-errors-and-debugging.md)

[Содержание](../../README.ru.md)

Разбираемся, что именно сломалось, как поймать нужную ошибку и какие данные оставить для отладки. Отдельно смотрим, как не потерять результат проверки и не засветить пароль в стеке вызовов.

**На этой странице**

- [`Throwable` объединяет `Exception` и `Error`](#throwable)
- [Один `catch` для нескольких типов исключений](#multi-catch)
- [`catch` без неиспользуемой переменной](#catch-without-variable)
- [`ValueError` и `TypeError`](#value-error)
- [Исключения из `__toString()`](#to-string-exceptions)
- [Идентификатор ресурса через `get_resource_id()`](#resource-id)
- [Как узнать тип значения через `get_debug_type()`](#debug-types)
- [Как понять ошибку регулярного выражения](#regex-errors)
- [Как отличить отсутствующую группу от пустого совпадения](#unmatched-groups)
- [Свой callback замены для каждого шаблона](#regex-callback-map)
- [Скрытие секретных аргументов в стеке](#sensitive-parameter)
- [Пометка старого API через `#[Deprecated]`](#deprecated-attribute)
- [Предупреждение, если результат функции потеряли](#nodiscard)
- [Какие обработчики ошибок сейчас установлены](#current-handlers)
- [Стек вызовов для фатальных ошибок](#fatal-backtraces)
- [Чтение PHP-кода через `PhpToken`](#php-token)

<a id="throwable"></a>
## `Throwable` объединяет `Exception` и `Error`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.throwable.php)

Если ловить только `Exception`, ошибка типа может пройти мимо обработчика. У `Exception` и ошибок движка `Error` общий интерфейс `Throwable`, поэтому через него можно поймать оба варианта. Здесь передаём массив вместо строки, получаем `TypeError` и попадаем в `catch`.

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

Обычное предупреждение само по себе исключением не становится. И возможность поймать `Error` не означает, что после любого сбоя можно продолжить работу: некоторые фатальные состояния остаются невосстановимыми.

<a id="multi-catch"></a>
## Один `catch` для нескольких типов исключений

**PHP 7.1+ | Ядро** | [Документация PHP](https://www.php.net/exceptions)

Если несколько исключений обрабатываются одинаково, перечисляем их через `|` в одном `catch`. Не нужно копировать один и тот же обработчик для каждого типа. В примере сообщение выведется и для `InvalidArgumentException`, и для `RuntimeException`.

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

Когда блоков `catch` несколько, срабатывает первый подходящий. Поэтому узкие типы ставим раньше общих: иначе родительский тип перехватит исключение до более точного обработчика.

<a id="catch-without-variable"></a>
## `catch` без неиспользуемой переменной

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/exceptions)

Если нужен только факт ошибки, а её сообщение и остальные данные не используем, переменную в `catch` можно убрать. Здесь нам достаточно знать, что делили на ноль, и вывести запасной результат.

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

Если позже понадобится записать подробности ошибки в журнал, вернём переменную в `catch`. Пока она не нужна, держать её ради самого объявления нет смысла.

<a id="value-error"></a>
## `ValueError` и `TypeError`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.valueerror.php)

`TypeError` означает, что передали неподходящий тип. У `ValueError` тип подходит, но само значение использовать нельзя. В примере массив передан правильно, только он пустой, и выбрать из него случайный элемент невозможно.

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

Это не общее правило для всех функций. У конкретной функции ошибка всё ещё может возвращаться как `false` или другое специальное значение, поэтому способ проверки смотрим в её документации.

<a id="to-string-exceptions"></a>
## Исключения из `__toString()`

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.tostring)

Преобразование объекта в строку тоже может завершиться ошибкой. С PHP 7.4 исключение из `__toString()` можно поймать обычным `catch`. Здесь преобразование происходит на `echo`, а вместо строки метод выбрасывает `RuntimeException`.

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

В более ранних версиях такое исключение приводило к фатальной ошибке. Если код должен работать на старой версии PHP, эту разницу нужно учитывать отдельно.

<a id="resource-id"></a>
## Идентификатор ресурса через `get_resource_id()`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.get-resource-id.php)

Чтобы отличать открытые ресурсы в отладке или журнале, можно получить их числовой идентификатор через `get_resource_id()`. Это понятнее, чем приводить ресурс к `(int)`. В примере сначала проверяем, открылся ли временный поток, и только потом берём его идентификатор.

```php
$stream = tmpfile();

if( $stream === false )
	throw new RuntimeException( 'Cannot create a temporary stream' );

$resource_id = get_resource_id( $stream );
fclose( $stream );
```

Этот номер имеет смысл в текущем процессе. Сохранять его для следующего запуска не стоит. И с файловым дескриптором операционной системы он не совпадает по назначению.

<a id="debug-types"></a>
## Как узнать тип значения через `get_debug_type()`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.get-debug-type.php)

Когда в сообщении об ошибке нужен понятный тип значения, берём `get_debug_type()`. Для числа получим `int`, для объекта имя его класса, для ресурса его вид. В примере не нужно отдельно проверять, число перед нами или объект.

```php
$type = get_debug_type( 42 ); # int
$class_name = get_debug_type( new DateTimeImmutable() ); # DateTimeImmutable
```

Функция только описывает значение. Если нужно разрешить или запретить конкретный тип, сама по себе эта строка такую проверку не делает.

<a id="regex-errors"></a>
## Как понять ошибку регулярного выражения

**PHP 8.0+ | PCRE** | [Документация PHP](https://www.php.net/manual/en/function.preg-last-error-msg.php)

У `preg_match()` три разных результата: `1` означает совпадение, `0` его отсутствие, а `false` ошибку. В примере передаём повреждённый UTF-8 с флагом `u`, поэтому получаем именно ошибку. `preg_last_error_msg()` объясняет её текстом.

```php
$result = preg_match( '/./u', "\xFF" );

if( $result === false )
	echo preg_last_error_msg();
# Malformed UTF-8 characters, possibly incorrectly encoded
```

Забираем сообщение до следующего вызова регулярного выражения, иначе он перезапишет последнюю ошибку. Неверный сам шаблон тоже может вызвать предупреждение, это отдельный повод проверить его запись.

<a id="unmatched-groups"></a>
## Как отличить отсутствующую группу от пустого совпадения

**PHP 7.2+ | PCRE** | [Документация PHP](https://www.php.net/manual/en/function.preg-match.php)

Пустая найденная строка и группа, которая вообще не участвовала в совпадении, это разные случаи. `PREG_UNMATCHED_AS_NULL` позволяет их различить. В примере группа `digits` не сработала и стала `null`, а `tail` успешно совпала с пустой строкой `''`.

```php
preg_match( '/^(?<digits>\d+)?(?<tail>[a-z]*)$/', '', $matches, PREG_UNMATCHED_AS_NULL );
var_dump( $matches['digits'], $matches['tail'] ); # null, ''
```

С PHP 7.4 в результат также попадают конечные группы без совпадения, со значением `null`. Сам флаг не говорит, удалось ли выполнить поиск: результат `preg_match()` всё равно проверяем отдельно.

<a id="regex-callback-map"></a>
## Свой callback замены для каждого шаблона

**PHP 7.0+ | PCRE** | [Документация PHP](https://www.php.net/manual/en/function.preg-replace-callback-array.php)

Если для каждого шаблона своя замена, можно собрать их в массив для `preg_replace_callback_array()`. Ключом будет регулярное выражение, значением функция замены. Здесь сначала подставляем имя, потом удваиваем найденные числа.

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

Правила выполняются в порядке массива, поэтому следующий шаблон уже видит предыдущие замены. При ошибке выполнения получим `null`, а неверный шаблон может ещё и выдать предупреждение. Необязательный параметр `flags` доступен с PHP 7.4.

<a id="sensitive-parameter"></a>
## Скрытие секретных аргументов в стеке

**PHP 8.2+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.sensitiveparameter.php)

Пароль в аргументах функции может случайно попасть в стек вызовов. Если пометить параметр атрибутом `#[SensitiveParameter]`, PHP подставит в стек объект `SensitiveParameterValue` вместо самого значения. Так пароль не окажется в этом месте диагностического вывода.

```php
function authenticate( #[SensitiveParameter] string $password ): void
{
	throw new RuntimeException( 'Authentication failed' );
}
```

Если самостоятельно записать пароль в сообщение исключения или вывести его в журнал, атрибут это не скроет. Он защищает именно значение аргумента в стеке вызовов.

<a id="deprecated-attribute"></a>
## Пометка старого API через `#[Deprecated]`

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.deprecated.php)

Когда старую функцию пока оставляем, но уже хотим предупредить о замене, можно поставить `#[Deprecated]`. При использовании PHP выдаст `E_USER_DEPRECATED`. Атрибут подходит для функций, методов и констант класса, а с PHP 8.5 ещё для traits и глобальных объявлений `const`.

```php
#[Deprecated( message: 'Use current_name()', since: '2.0' )]
function old_name(): string
{
	return 'report';
}
```

В `since` указываем версию или дату нашего API, а не версию PHP. Само предупреждение выполнение не останавливает: старая функция продолжает работать, пока мы её не удалим.

<a id="nodiscard"></a>
## Предупреждение, если результат функции потеряли

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/class.nodiscard.php)

Если результат функции нельзя незаметно потерять, ставим `#[NoDiscard]`. Например, вызвать проверку имени и ничего не сделать с её `bool` часто означает ошибку в вызывающем коде. PHP предупредит о таком вызове. В примере первый результат сохраняем, а второй явно отбрасываем через `(void)`.

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

`(void)` здесь означает, что результат отброшен намеренно, и подавляет предупреждение. Само это приведение тоже доступно только с PHP 8.5. Если метод переопределили, атрибут автоматически в новую реализацию не переносится.

<a id="current-handlers"></a>
## Какие обработчики ошибок сейчас установлены

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.get-error-handler.php) | [`get_exception_handler()`](https://www.php.net/manual/en/function.get-exception-handler.php)

Если нужно узнать, кто сейчас обрабатывает ошибки и неперехваченные исключения, используем `get_error_handler()` и `get_exception_handler()`. Они возвращают зарегистрированный `callable`, ничего не устанавливая взамен. В примере проверяем, что это именно наши функции, и в `finally` возвращаем прежние обработчики.

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

Если своего обработчика нет, получим `null`. Маску уровней ошибок, которую передавали в `set_error_handler()`, функция `get_error_handler()` не возвращает.

<a id="fatal-backtraces"></a>
## Стек вызовов для фатальных ошибок

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.error-get-last.php)

Для разбора фатальной ошибки полезно видеть не только строку сбоя, но и путь вызовов до неё. Настройка `fatal_error_backtraces` управляет таким стеком, а `error_get_last()` возвращает его в поле `trace`, когда он доступен. Если поля нет, в примере берём пустой массив.

```php
$last_error = error_get_last();
$trace = $last_error['trace'] ?? [];
```

Это дополнительная диагностика, а не возможность поймать фатальную ошибку через `catch`. У неперехваченных исключений уже есть собственный стек, и это поле для них не добавляется.

<a id="php-token"></a>
## Чтение PHP-кода через `PhpToken`

**PHP 8.0+ | tokenizer** | [Документация PHP](https://www.php.net/manual/en/class.phptoken.php)

Если нужно разобрать PHP-код на отдельные элементы, не выполняя его, есть `PhpToken::tokenize()`. Каждый токен содержит вид элемента `id`, сам текст `text`, строку `line` и байтовую позицию `pos`. В примере ищем оператор `echo` и выводим, на какой строке он встретился.

```php
# Это строка с кодом: разбираем её, не выполняя.
$source = '<?php echo 42;';
foreach( PhpToken::tokenize($source) as $token )
{
	if( $token->is(T_ECHO) )
		echo $token->text, ':', $token->line;
}
# echo:1
```

Токены помогают разбирать исходный текст, но ещё не описывают всё устройство программы. Полного синтаксического дерева этот метод не строит и сам код не запускает.
