# Синтаксис и функции

[EN](../en/01-syntax-and-functions.md) | [UK](../uk/01-syntax-and-functions.md) | [RU](../ru/01-syntax-and-functions.md)

[Содержание](../../README.ru.md)

**На этой странице**

- [Запасное значение через `??`](#null-coalescing)
- [Дополняем настройки через `??=`](#null-coalescing-assignment)
- [Сортируем через `<=>`](#spaceship)
- [Короткие функции через `fn`](#arrow-functions)
- [Передаём аргументы по имени](#named-arguments)
- [Имена аргументов из ключей массива](#unpacking-named-arguments)
- [Добавляем аргумент после распаковки](#named-after-unpacking)
- [Выбираем значение через `match`](#match)
- [Читаем цепочку объектов через `?->`](#nullsafe)
- [Выбрасываем исключение прямо в выражении](#throw-expression)
- [Получаем замыкание через `Closure::fromCallable()`](#closure-from-callable)
- [Ссылка на функцию через `function_name(...)`](#first-class-callables)
- [Обрабатываем значение по шагам через `|>`](#pipe)
- [Функция как значение по умолчанию](#closures-in-constant-expressions)
- [Замыкание вызывает само себя](#recursive-closures)
- [Запятая после последнего аргумента](#trailing-commas)
- [Разделяем длинные числа для чтения](#numeric-literals)
- [Собираем похожие `use` в одну строку](#group-use)
- [Вызываем замыкание с нужным `$this`](#closure-call)
- [Готовим значение `static` при первом вызове](#static-initializers)
- [Передаём `exit()` как функцию](#exit-callable)
- [Преобразуем тип при объявлении константы](#casts-in-constant-expressions)
- [Добавляем атрибут к глобальной константе](#global-constant-attributes)

<a id="null-coalescing"></a>
## Запасное значение через `??`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce)

Если ключа нет или там `null`, берём запасное значение. Ноль, `false` и пустую строку не трогаем, с ними всё нормально.

```php
$config = ['limit' => 0, 'label' => null];
$limit = $config['limit'] ?? 20;
$label = $config['label'] ?? 'Untitled';

echo $limit, ':', $label; # 0:Untitled
```

В примере `limit` останется нулём, а вместо `null` в `label` получим `Untitled`. Если нужно различать отсутствующий ключ и ключ со значением `null`, есть `array_key_exists()`. Когда рядом с `??` склеиваем строки, лучше явно поставить скобки вокруг нужной части.

<a id="null-coalescing-assignment"></a>
## Дополняем настройки через `??=`

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.assignment.php)

То же запасное значение, только сразу записываем его на место. Удобно дополнить настройки, не перебирая их через отдельные `if`.

```php
$config = ['retries' => 0];
$config['timeout'] ??= 30;
$config['retries'] ??= 3;

echo $config['timeout'], ':', $config['retries']; # 30:0
```

`timeout` появится со значением `30`, а `retries` останется нулём. Правая часть вычисляется только тогда, когда значение действительно нужно записать: ключа ещё нет или в нём `null`.

<a id="spaceship"></a>
## Сортируем через `<=>`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.comparison.php)

Для сортировки нужно ответить, какое из двух значений меньше, равно другому или больше. `<=>` сразу возвращает нужные `-1`, `0` или `1`, без цепочки условий.

```php
$rows = [['score' => 8], ['score' => 2], ['score' => 5]];
usort( $rows, function( array $left, array $right ): int
{
	return $left['score'] <=> $right['score'];
} );

echo implode( ',', array_column($rows, 'score') ); # 2,5,8
```

В примере строки идут по возрастанию `score`. Поменяем `$left` и `$right` местами, получим обратный порядок. Сравнение здесь обычное для PHP, а не строгое `===`, поэтому у смешанных типов сначала стоит разобраться, что именно сравниваем.

<a id="arrow-functions"></a>
## Короткие функции через `fn`

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arrow.php)

Когда вся функция помещается в одно выражение, её можно записать через `fn`. Внешние переменные доступны сразу, без `use`, но берутся с тем значением, которое было при создании функции.

```php
$factor = 10;
$multiply = fn( int $value ): int => $value * $factor;
$factor = 20;
echo $multiply( 3 ); # 30
```

Поэтому изменение `$factor` на `20` уже не меняет результат: получаем `30`. С объектами копии объекта не появляется, внутри остаётся тот же экземпляр. Если нужна переменная по ссылке, используем обычное замыкание; `static fn` подходит, когда не нужен привязанный `$this`.

<a id="named-arguments"></a>
## Передаём аргументы по имени

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

Можно указать имя нужного параметра и не заполнять все необязательные перед ним. Здесь меняем `limit`, а `separator` оставляем со значением по умолчанию.

```php
function describe_item( string $name, string $separator = ':', int $limit = 20 ): string
{
	return $name . $separator . $limit;
}

$label = describe_item( 'Report', limit: 5 );

echo $label; # Report:5
```

Обычные аргументы по порядку должны стоять раньше именованных. Имена параметров теперь тоже важны для вызывающего кода: переименуем `limit` в функции, и старый вызов с `limit:` сломается.

<a id="unpacking-named-arguments"></a>
## Имена аргументов из ключей массива

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/function.call-user-func-array.php)

При распаковке через `...` строковые ключи становятся именами аргументов. Поэтому `end` и `start` в массиве можно записать в обратном порядке, функция всё равно получит `start = 3` и `end = 9`. Это касается и `call_user_func_array()`.

```php
function format_range( int $start, int $end ): string
{
	return $start . '-' . $end;
}

$arguments = ['end' => 9, 'start' => 3];
echo format_range( ...$arguments ); # 3-9
```

Если имя неизвестно функции или один параметр передан дважды, получим ошибку. Если ключи нужны только самому массиву, а передать значения надо по порядку, сначала убираем ключи через `array_values()`.

<a id="named-after-unpacking"></a>
## Добавляем аргумент после распаковки

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arguments.php)

Часть аргументов можно взять из массива, а оставшийся передать по имени. В примере `left` и `right` приходят из `$parts`, а разделитель дописываем прямо в вызове.

```php
function join_parts( string $left, string $right, string $separator = '/' ): string
{
	return $left . $separator . $right;
}

$parts = ['docs', 'types'];
echo join_parts( ...$parts, separator: ':' ); # docs:types
```

Повторно передать уже заполненный параметр нельзя. Если `separator` тоже придёт из массива, ещё один `separator:` вызовет ошибку.

<a id="match"></a>
## Выбираем значение через `match`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/control-structures.match.php)

`match` удобно использовать, когда по одному значению нужно получить другое. Он сравнивает строго: строка `'1'` и число `1` здесь разные варианты. Выбранная ветка возвращает результат, дальше выполнение по другим веткам не проваливается.

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

Поэтому в примере получаем `text`. Если ни одна ветка не подошла и `default` нет, PHP выбросит `UnhandledMatchError`. При замене `switch` это важно учесть: у него сравнение нестрогое.

<a id="nullsafe"></a>
## Читаем цепочку объектов через `?->`

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.nullsafe)

Профиля может не быть, адреса тоже. Через `?->` можно пройти такую цепочку без отдельного `if` перед каждым шагом: встретили `null`, дальше не идём, результат тоже `null`. После этого `??` подставит текст для вывода.

```php
$profile = null;
$city = $profile?->address?->city ?? 'Not provided';

echo $city; # Not provided
```

`?->` ставим в тех местах, где объект действительно может быть `null`. Через него можно читать, но не записывать свойства. Ошибки в существующем объекте и исключения вызванных методов он не скрывает.

<a id="throw-expression"></a>
## Выбрасываем исключение прямо в выражении

**PHP 8.0+ | Ядро** | [Документация PHP](https://www.php.net/exceptions)

Если без значения продолжать нельзя, справа от `??` можно сразу поставить `throw`. В примере токен есть, поэтому исключения не будет. Без ключа `token` или со значением `null` выполнение остановится на исключении.

```php
$config = ['token' => 'local-example'];
$token = $config['token'] ?? throw new InvalidArgumentException( 'Token is required' );
```

Пустая строка проверку пройдёт. Здесь мы проверяем наличие значения, а не то, подходит ли оно для дальнейшей работы.

<a id="closure-from-callable"></a>
## Получаем замыкание через `Closure::fromCallable()`

**PHP 7.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/closure.fromcallable.php)

Функцию или доступный метод можно превратить в объект `Closure`, сохранить в переменную и вызвать позже. В примере из имени `add_prefix` получаем функцию, которую вызываем через `$format`.

```php
function add_prefix( string $name ): string
{
	return 'note:' . $name;
}

$format = Closure::fromCallable( 'add_prefix' );

echo $format( 'types' ); # note:types
```

PHP проверяет доступ уже при создании замыкания. Если функцию нельзя вызвать из этого места, получим `TypeError`. Начиная с PHP 8.1 то же можно записать короче через [ссылку на функцию](#first-class-callables).

<a id="first-class-callables"></a>
## Ссылка на функцию через `function_name(...)`

**PHP 8.1+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)

Три точки без аргументов здесь означают: взять функцию, а не вызвать её. Получаем `Closure`, который можно передать, например, в `array_map()`. У методов доступ проверяется при создании такой ссылки и сохраняется для её дальнейших вызовов.

```php
$normalize = trim(...);
$values = array_map( $normalize, [' one ', ' two '] );

echo implode( ',', $values ); # one,two
```

Не путаем эту запись с распаковкой массива аргументов. Сочетать её с `?->` или создавать таким способом конструктор через `new ClassName(...)` нельзя.

<a id="pipe"></a>
## Обрабатываем значение по шагам через `|>`

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.operators.functional.php)

Вместо вложенных вызовов можно записать обработку сверху вниз. `|>` передаёт значение слева в функцию справа, а её результат отправляется на следующий шаг. Здесь сначала убираем пробелы по краям, затем меняем регистр и заменяем пробел внутри.

```php
$slug = ' PHP Notes '
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $text ): string => str_replace(' ', '-', $text));

echo $slug; # php-notes
```

Функция справа получает один аргумент. Если нужны дополнительные параметры или значение надо подставить не первым, оборачиваем вызов в замыкание, как сделано с `str_replace()`. Все шаги выполняются последовательно и синхронно.

<a id="closures-in-constant-expressions"></a>
## Функция как значение по умолчанию

**PHP 8.5+ | Ядро** | [Документация PHP](https://wiki.php.net/rfc/closures_in_const_expr)

В значении по умолчанию и в константе теперь можно сохранить ссылку на функцию или допустимое статическое замыкание. Здесь `trim(...)` становится готовым обработчиком, поэтому при обычном вызове `normalize_name()` ничего дополнительно передавать не нужно.

```php
function normalize_name( string $name, Closure $normalizer = trim(...) ): string
{
	return $normalizer( $name );
}

echo normalize_name( ' Ada ' ); # Ada
```

При объявлении обработчик только создаётся, а не вызывается. Для замыканий есть ограничения: они должны быть `static` и не могут забирать внешние переменные через `use`.

<a id="recursive-closures"></a>
## Замыкание вызывает само себя

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/closure.getcurrent.php)

У замыкания нет обычного имени функции, но для рекурсии оно всё равно может получить само себя через `Closure::getCurrent()`. Не нужно заранее захватывать переменную `$factorial` по ссылке.

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

В примере каждый вызов умножает число на результат следующего, пока не дойдёт до `1`. Вне выполняющегося замыкания `Closure::getCurrent()` вызовет `Error`.

<a id="trailing-commas"></a>
## Запятая после последнего аргумента

**PHP 7.3+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/functions.arguments.php)

Последнюю запятую можно оставить: так удобнее добавлять новые строки и смотреть изменения в Git. В вызовах это работает с PHP 7.3, в объявлениях параметров и списках `use` у замыканий с PHP 8.0.

```php
echo implode(
	',',
	['one', 'two'],
);
# one,two
```

Лишнего аргумента из-за запятой не появится. В примере `implode()` всё так же получает два аргумента.

<a id="numeric-literals"></a>
## Разделяем длинные числа для чтения

**PHP 7.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.types.integer.php)

В длинном числе легко потерять один ноль. С PHP 7.4 между цифрами можно ставить `_`: значение не меняется, зато читать легче. С PHP 8.1 восьмеричное число можно явно записать как `0o644` вместо `0644`; оба варианта означают десятичное `420`.

```php
$max_bytes = 10_485_760;
$mask = 0b1111_0000;
```

Это запись чисел в самом коде. Строка с такими разделителями не становится автоматически подходящим форматом для преобразования в число.

<a id="group-use"></a>
## Собираем похожие `use` в одну строку

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.namespaces.importing.php)

Если несколько классов лежат в одном пространстве имён, общий путь можно написать один раз. В фигурных скобках оставляем только разные части имён.

```php
use App\Report\{CsvWriter, JsonWriter};

$writers = [CsvWriter::class, JsonWriter::class];
```

`use` здесь только сокращает имена для этого файла. Сам по себе он не загружает классы и не создаёт объекты.

<a id="closure-call"></a>
## Вызываем замыкание с нужным `$this`

**PHP 7.0+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/closure.call.php)

Через `call()` можно на один вызов дать замыканию объект в качестве `$this`. Вместе с ним замыкание получает доступ из области его класса, поэтому в примере читает приватное `value`.

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

Привязка действует на этот вызов. Со статическим замыканием так не получится: у него не может быть `$this`.

<a id="static-initializers"></a>
## Готовим значение `static` при первом вызове

**PHP 8.3+ | Ядро** | [Документация PHP](https://wiki.php.net/rfc/arbitrary_static_variable_initializers)

Начальное значение локальной `static`-переменной теперь можно вычислить через выражение или вызов функции. Оно вычислится, когда выполнение впервые дойдёт до объявления, а не при каждом вызове функции.

```php
function next_number(): int
{
	static $number = strlen( 'seed' );
	return ++$number;
}

echo next_number(), ':', next_number(); # 5:6
```

Поэтому длина `seed` берётся один раз, а дальше счётчик продолжает расти: `5:6`. Значение хранится между вызовами в текущем выполнении PHP. Общего кеша между отдельными запросами из этого не получается.

<a id="exit-callable"></a>
## Передаём `exit()` как функцию

**PHP 8.4+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration84.incompatible.php)

`exit()` и его другое имя `die()` теперь можно использовать там, где нужна вызываемая функция. В примере `exit(...)` только создаёт замыкание, выполнение на этой строке не завершается.

```php
$terminate = exit(...);
$is_closure = $terminate instanceof Closure;
```

Правила типов тоже стали обычными для функций, включая `strict_types`. Целое число задаёт код завершения, строка выводится перед выходом. Неподходящий тип аргумента теперь вызывает `TypeError`.

<a id="casts-in-constant-expressions"></a>
## Преобразуем тип при объявлении константы

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/language.constants.syntax.php)

В значении константы теперь можно явно преобразовать тип. Здесь сразу оставляем от `3.9` только целую часть и сохраняем `3`.

```php
const WHOLE_SECONDS = (int)3.9;

echo WHOLE_SECONDS; # 3
```

Правила самого преобразования прежние. Запись `(int)` не округляет число до ближайшего целого, а отбрасывает дробную часть.

<a id="global-constant-attributes"></a>
## Добавляем атрибут к глобальной константе

**PHP 8.5+ | Ядро** | [Документация PHP](https://www.php.net/manual/en/migration85.new-features.php)

Атрибуты теперь можно ставить и перед глобальным `const`. Например, помечаем старое имя константы через `#[Deprecated]`, чтобы её использование подсказало, на что перейти.

```php
#[Deprecated( 'Use DEFAULT_PAGE_SIZE' )]
const OLD_PAGE_SIZE = 20;
const DEFAULT_PAGE_SIZE = 20;
```

При чтении `OLD_PAGE_SIZE` PHP выдаст сообщение об устаревании. Для констант, созданных через `define()`, такая запись атрибута не поддерживается.
