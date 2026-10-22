# Синтаксис и функции

[EN](../en/01-syntax-and-functions.md) | [UK](../uk/01-syntax-and-functions.md) | [RU](../ru/01-syntax-and-functions.md)

[Содержание](../../README.ru.md)

**На этой странице**

- [Значение по умолчанию через `??`](#null-coalescing)
- [Дополнение настроек через `??=`](#null-coalescing-assignment)
- [Сортировка оператором `<=>`](#spaceship)
- [Стрелочные функции и захваченные значения](#arrow-functions)
- [Именованные аргументы](#named-arguments)
- [Строковые ключи становятся именованными аргументами](#unpacking-named-arguments)
- [Именованные аргументы после распаковки](#named-after-unpacking)
- [`match` возвращает значение и сравнивает строго](#match)
- [Цепочка необязательных объектов через `?->`](#nullsafe)
- [`throw` внутри выражения](#throw-expression)
- [Преобразование callable через `Closure::fromCallable()`](#closure-from-callable)
- [Синтаксис ссылки на функцию: `function_name(...)`](#first-class-callables)
- [Оператор последовательной обработки `|>`](#pipe)
- [Callback в константных выражениях](#closures-in-constant-expressions)
- [Рекурсия через `Closure::getCurrent()`](#recursive-closures)
- [Завершающая запятая в вызовах и объявлениях](#trailing-commas)
- [Читаемые числовые литералы](#numeric-literals)
- [Группировка импортов пространства имён](#group-use)
- [Временная привязка замыкания через `call()`](#closure-call)
- [Выражения в инициализаторах статических локальных переменных](#static-initializers)
- [`exit()` и `die()` ведут себя ближе к обычным функциям](#exit-callable)
- [Приведение типа в константных выражениях](#casts-in-constant-expressions)
- [Атрибуты глобальных констант](#global-constant-attributes)

<a id="null-coalescing"></a>
## Значение по умолчанию через `??`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce)

`??` подставляет значение для отсутствующих или `null`-значений, сохраняя `0`, `false` и `''`.

```php
$config = ['limit' => 0, 'label' => null];
$limit = $config['limit'] ?? 20;
$label = $config['label'] ?? 'Untitled';

echo $limit, ':', $label; # 0:Untitled
```

`array_key_exists()` различает отсутствующий ключ и `null`; при сочетании `??` со склеиванием строк ставьте скобки.

<a id="null-coalescing-assignment"></a>
## Дополнение настроек через `??=`

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.assignment.php)

`??=` записывает значение по умолчанию, если текущее отсутствует или равно `null`.

```php
$config = ['retries' => 0];
$config['timeout'] ??= 30;
$config['retries'] ??= 3;

echo $config['timeout'], ':', $config['retries']; # 30:0
```

Правая часть вычисляется только при необходимости присваивания; существующие нулевые значения сохраняются.

<a id="spaceship"></a>
## Сортировка оператором `<=>`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.comparison.php)

`<=>` возвращает `-1`, `0` или `1` для сортировки; перестановка операндов меняет порядок на обратный.

```php
$rows = [['score' => 8], ['score' => 2], ['score' => 5]];
usort( $rows, function( array $left, array $right ): int
{
	return $left['score'] <=> $right['score'];
} );

echo implode( ',', array_column($rows, 'score') ); # 2,5,8
```

Оператор использует правила сравнения PHP, а не строгое тождество, поэтому смешанные типы могут требовать нормализации.

<a id="arrow-functions"></a>
## Стрелочные функции и захваченные значения

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arrow.php)

`fn` объявляет замыкание с одним выражением и захватывает внешние переменные по значению.

```php
$factor = 10;
$multiply = fn( int $value ): int => $value * $factor;
$factor = 20;
echo $multiply( 3 ); # 30
```

Объекты остаются теми же экземплярами; для захвата по ссылке нужно обычное замыкание, а `static fn` не привязывает `$this`.

<a id="named-arguments"></a>
## Именованные аргументы

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

Именованные аргументы выбирают параметры по имени и позволяют пропускать необязательные.

```php
function describe_item( string $name, string $separator = ':', int $limit = 20 ): string
{
	return $name . $separator . $limit;
}

$label = describe_item( 'Report', limit: 5 );

echo $label; # Report:5
```

Позиционные аргументы должны идти первыми, а переименование параметра может сломать вызовы с его именем.

<a id="unpacking-named-arguments"></a>
## Строковые ключи становятся именованными аргументами

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.call-user-func-array.php)

Строковые ключи распакованного массива аргументов становятся именами параметров, в том числе в `call_user_func_array()`.

```php
function format_range( int $start, int $end ): string
{
	return $start . '-' . $end;
}

$arguments = ['end' => 9, 'start' => 3];
echo format_range( ...$arguments ); # 3-9
```

Неизвестные имена и повторная передача параметра вызывают ошибки; для позиционного порядка с произвольными ключами используйте `array_values()`.

<a id="named-after-unpacking"></a>
## Именованные аргументы после распаковки

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arguments.php)

Именованные аргументы могут идти после распаковки аргументов.

```php
function join_parts( string $left, string $right, string $separator = '/' ): string
{
	return $left . $separator . $right;
}

$parts = ['docs', 'types'];
echo join_parts( ...$parts, separator: ':' ); # docs:types
```

Они не могут повторно передавать параметр, уже заполненный распакованным массивом.

<a id="match"></a>
## `match` возвращает значение и сравнивает строго

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/control-structures.match.php)

`match` возвращает результат выражения, сравнивает строго и не переходит в следующую ветку.

```php
$status = '1';
$label = match( $status )
{
	1 => 'numeric',
	'1' => 'text',
	2, 3 => 'known',
	default => 'unknown',
};

echo $label; # text
```

Без подходящей ветки или `default` возникает `UnhandledMatchError`; это отличается от нестрогих сравнений в `switch`.

<a id="nullsafe"></a>
## Цепочка необязательных объектов через `?->`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.nullsafe)

`?->` останавливает цепочку доступа к объектам, когда левый операнд равен `null`; отмечайте каждый шаг, на котором возможен `null`.

```php
$profile = null;
$city = $profile?->address?->city ?? 'Not provided';

echo $city; # Not provided
```

Доступен только для чтения и не скрывает некорректные обращения или исключения в ненулевых объектах.

<a id="throw-expression"></a>
## `throw` внутри выражения

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/exceptions)

`throw` может быть частью выражения, в том числе запасным вариантом в `??`.

```php
$config = ['token' => 'local-example'];
$token = $config['token'] ?? throw new InvalidArgumentException( 'Token is required' );
```

Здесь пустая строка всё ещё допустима, поскольку `??` проверяет только отсутствие и `null`.

<a id="closure-from-callable"></a>
## Преобразование callable через `Closure::fromCallable()`

**PHP 7.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/closure.fromcallable.php)

`Closure::fromCallable()` создаёт замыкание и проверяет доступность callable в текущей области.

```php
function add_prefix( string $name ): string
{
	return 'note:' . $name;
}

$format = Closure::fromCallable( 'add_prefix' );

echo $format( 'types' ); # note:types
```

Некорректный или недоступный callable вызывает `TypeError`; [синтаксис ссылки на функцию](#first-class-callables) имеет ту же семантику с PHP 8.1.

<a id="first-class-callables"></a>
## Синтаксис ссылки на функцию: `function_name(...)`

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)

`function_name(...)` создаёт `Closure` и проверяет доступ при создании; callable метода сохраняет эту область видимости.

```php
$normalize = trim(...);
$values = array_map( $normalize, [' one ', ' two '] );

echo implode( ',', $values ); # one,two
```

Это не распаковка аргументов; синтаксис не сочетается с `?->` или `new ClassName(...)`.

<a id="pipe"></a>
## Оператор последовательной обработки `|>`

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.functional.php)

`|>` передаёт левое значение callable справа и возвращает результат вызова.

```php
$slug = ' PHP Notes '
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $text ): string => str_replace(' ', '-', $text));

echo $slug; # php-notes
```

Дополнительные аргументы или другую позицию аргумента задавайте через замыкание; цепочка выполняется синхронно.

<a id="closures-in-constant-expressions"></a>
## Callback в константных выражениях

**PHP 8.5+ | Ядро** | [Документация PHP](https://wiki.php.net/rfc/closures_in_const_expr)

Ссылки на функции и ограниченные статические замыкания разрешены в константных выражениях, включая значения по умолчанию и константы.

```php
function normalize_name( string $name, Closure $normalizer = trim(...) ): string
{
	return $normalizer( $name );
}

echo normalize_name( ' Ada ' ); # Ada
```

Выражение создаёт callback, а не выполняет его; замыкание должно быть статическим и не может захватывать переменные через `use`.

<a id="recursive-closures"></a>
## Рекурсия через `Closure::getCurrent()`

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/closure.getcurrent.php)

`Closure::getCurrent()` возвращает текущее замыкание для рекурсии без захвата его переменной по ссылке.

```php
$factorial = static function( int $value ): int
{
	if( $value < 0 )
		throw new InvalidArgumentException( 'Expected a non-negative integer' );

	if( $value <= 1 )
		return 1;

	$current = Closure::getCurrent();
	return $value * $current( $value - 1 );
};

echo $factorial( 5 ); # 120
```

Вызов вне замыкания вызывает `Error`.

<a id="trailing-commas"></a>
## Завершающая запятая в вызовах и объявлениях

**PHP 7.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arguments.php)

Завершающая запятая поддерживается в вызовах с PHP 7.3, а в объявлениях параметров и списках `use` замыканий с PHP 8.0.

```php
echo implode(
	',',
	['one', 'two'],
);
# one,two
```

Она не добавляет ещё один аргумент.

<a id="numeric-literals"></a>
## Читаемые числовые литералы

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.types.integer.php)

PHP 7.4 разрешает разделители `_` между цифрами; PHP 8.1 добавляет явную восьмеричную запись `0o644`, равную `0644` (десятичное `420`).

```php
$max_bytes = 10_485_760;
$mask = 0b1111_0000;
```

Разделители являются синтаксисом кода, а не общим форматом преобразования строк в числа.

<a id="group-use"></a>
## Группировка импортов пространства имён

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.namespaces.importing.php)

Групповой импорт использует общий префикс пространства имён для нескольких имён.

```php
use App\Report\{CsvWriter, JsonWriter};

$writers = [CsvWriter::class, JsonWriter::class];
```

Импорт имени не загружает класс и не создаёт его экземпляр.

<a id="closure-call"></a>
## Временная привязка замыкания через `call()`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/closure.call.php)

`Closure::call()` вызывает замыкание с временным объектом `$this` и его областью класса.

```php
class Counter
{
	private $value = 7;
}

$read = function(): int
{
	return $this->value;
};
echo $read->call( new Counter() ); # 7
```

Статическое замыкание нельзя привязать к `$this`.

<a id="static-initializers"></a>
## Выражения в инициализаторах статических локальных переменных

**PHP 8.3+ | Ядро** | [Документация PHP](https://wiki.php.net/rfc/arbitrary_static_variable_initializers)

Инициализаторы локальных статических переменных могут содержать динамические выражения, вычисляемые при первом достижении объявления.

```php
function next_number(): int
{
	static $number = strlen( 'seed' );
	return ++$number;
}

echo next_number(), ':', next_number(); # 5:6
```

Значение сохраняется между вызовами функции в текущем процессе; между отдельными PHP-запросами общего кеша нет.

<a id="exit-callable"></a>
## `exit()` и `die()` ведут себя ближе к обычным функциям

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration84.incompatible.php)

`exit()` и его псевдоним `die()` поддерживают callable и обычные правила типов аргументов, включая `strict_types`.

```php
$terminate = exit(...);
$is_closure = $terminate instanceof Closure;
```

Целое число задаёт код выхода, строка выводится; некорректные типы аргументов теперь вызывают `TypeError`.

<a id="casts-in-constant-expressions"></a>
## Приведение типа в константных выражениях

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.constants.syntax.php)

Явные приведения типов разрешены в константных выражениях.

```php
const WHOLE_SECONDS = (int)3.9;

echo WHOLE_SECONDS; # 3
```

Правила преобразования не изменились: `(int)3.9` отбрасывает дробную часть и даёт `3`.

<a id="global-constant-attributes"></a>
## Атрибуты глобальных констант

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration85.new-features.php)

Атрибуты можно добавлять к глобальным константам, объявленным через `const`; чтение этой устаревшей константы вызывает диагностическое сообщение.

```php
#[Deprecated( 'Use DEFAULT_PAGE_SIZE' )]
const OLD_PAGE_SIZE = 20;
const DEFAULT_PAGE_SIZE = 20;
```

Константы, созданные через `define()`, не поддерживают этот синтаксис.
