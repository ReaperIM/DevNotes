# Массивы и обход данных

[English](../en/04-arrays-and-iteration.md) | [Українська](../uk/04-arrays-and-iteration.md) | [Русский](../ru/04-arrays-and-iteration.md)

[Содержание](../../README.ru.md)

Массивы PHP являются упорядоченными картами. Списки, записи и таблицы поиска по ID имеют один тип, но разные правила использования. Примеры объясняют ключи, постепенную обработку и отсутствующие значения. Каждый пример независим.

- [Разбор строки массива на переменные](#destructuring)
- [Чтение выбранных именованных полей](#keyed-destructuring)
- [Разбор по ссылке для изменения оригинала](#reference-destructuring)
- [Массивы и итераторы через `iterable`](#iterable)
- [Объединение потоков через yield from](#generator-delegation)
- [Итоговый результат генератора](#generator-return)
- [Создание массивов через ... и повторяющиеся ключи](#unpacking)
- [Слияние нуля или более массивов](#merge-empty)
- [Первый и последний ключ без изменения указателя](#boundary-keys)
- [Проверка возможности `count()`](#countable)
- [Распознавание списков и форма JSON-массива](#list-arrays)
- [Поиск первого подходящего значения или ключа](#predicate-search)
- [Проверка любого или всех элементов](#predicate-checks)
- [Прямое чтение первого или последнего значения](#boundary-values)
- [Стабильная сортировка сохраняет порядок равных элементов](#stable-sorting)

<a id="destructuring"></a>

## Разбор строки массива на переменные

**PHP 7.1+ | Ядро**

Короткая запись разбора позволяет назвать поля без повторных обращений к индексам одной строки. Она работает и внутри foreach. Это удобно для фиксированных наборов, например ID и имени.

```php
$rows = [[7, 'Ada'], [9, 'Linus']];

foreach( $rows as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

**Важно:** Это присваивание, а не проверка схемы. Отсутствующий индекс в PHP 8 вызывает предупреждение и даёт null. Сначала проверяйте внешние данные; позицию можно пропустить: [$first, , $third].

[Документация PHP: destructuring](https://www.php.net/manual/en/migration71.new-features.php)

<a id="keyed-destructuring"></a>

## Чтение выбранных именованных полей

**PHP 7.1+ | Ядро**

Разбор ассоциативного массива показывает, какие ключи нужны. Их порядок не важен, ведь PHP ищет значения по именам. Запись похожа на выбор столбцов из записи.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row;
echo $user_id . ': ' . $user_name . PHP_EOL; # 7: Ada
```

**Важно:** Не смешивайте именованные и позиционные элементы на одном уровне. Для необязательных полей отдельно задавайте значения по умолчанию.

[Документация PHP: list()](https://www.php.net/manual/en/function.list.php)

<a id="reference-destructuring"></a>

## Разбор по ссылке для изменения оригинала

**PHP 7.3+ | Ядро**

Ссылка внутри разбора связывает переменную с элементом исходного массива. Используйте её для намеренного изменения оригинала; обычное чтение не требует ссылок.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15;
unset( $x );
echo json_encode( $point ) . PHP_EOL; # [15,20]
```

**Важно:** Связь сохраняется после присваивания. unset($x) разрывает её для переменной, но не удаляет элемент массива.

[Документация PHP: PHP 7.3](https://www.php.net/manual/en/migration73.new-features.php)

<a id="iterable"></a>

## Массивы и итераторы через `iterable`

**PHP 7.1+ | Ядро**

Используйте `iterable`, если функции достаточно foreach без специальных операций массивов. Тип принимает массивы и объекты `Traversable`, включая генераторы. Вызывающий код может передавать значения постепенно, не создавая полный массив.

```php
function sum_values( iterable $values ): int
{
	$total = 0;

	foreach( $values as $value )
		$total += $value;

	return $total;
}

echo sum_values( new ArrayIterator([2, 3, 5]) ) . PHP_EOL; # 10
```

**Важно:** `iterable` не гарантирует `count()`, доступ по индексу, повторный обход или типы элементов. В PHP 8.2 он стал псевдонимом array|`Traversable`.

[Документация PHP: iterable](https://www.php.net/manual/en/language.types.iterable.php)

<a id="generator-delegation"></a>

## Объединение потоков через yield from

**PHP 7.0+ | Ядро**

yield from передаёт значения из другого `iterable`. Генератор работает по мере запроса значений, поэтому не нужно хранить все промежуточные результаты. Это удобно для объединения нескольких источников данных.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}

$ids = iterator_to_array( page_ids(), false );
echo json_encode( $ids ) . PHP_EOL; # [10,11,12,13]
```

**Важно:** Делегирование сохраняет ключи. Повторяющиеся ключи могут перезаписать элементы в `iterator_to_array()`; для списка передавайте false. Генератор расходуется при работе и не является автоматически многоразовым.

[Документация PHP: generator syntax](https://www.php.net/manual/en/language.generators.syntax.php)

<a id="generator-return"></a>

## Итоговый результат генератора

**PHP 7.0+ | Ядро**

Генератор может выдавать промежуточные значения и отдельно возвращать итог. `getReturn()` читает этот результат после завершения, отделяя его от элементов потока.

```php
function processed_ids(): Generator
{
	yield 10;
	yield 20;
	return 2;
}

$stream = processed_ids();

foreach( $stream as $id )
	echo $id . ' ';

echo 'count=' . $stream->getReturn() . PHP_EOL; # 10 20 count=2
```

**Важно:** `getReturn()` выбрасывает исключение до нормального завершения. Досрочная остановка foreach не заставляет генератор дойти до return.

[Документация PHP: Generator::getReturn()](https://www.php.net/manual/en/generator.getreturn.php)

<a id="unpacking"></a>

## Создание массивов через ... и повторяющиеся ключи

**PHP 7.4+ | Ядро**

Распаковка вставляет массив или `Traversable` в выражение массива. Целочисленные ключи перенумеровываются. Строковые ключи поддерживаются с PHP 8.1; более позднее значение заменяет предыдущее с тем же строковым ключом.

```php
$first = [8 => 'a', 9 => 'b'];
echo json_encode( [...$first, 'c'] ) . PHP_EOL; # ["a","b","c"]

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]];
echo $config['timeout'] . PHP_EOL; # 30
```

**Важно:** Пример со строковыми ключами требует PHP 8.1. Объединение через + имеет другие правила: существующие ключи слева побеждают, а целочисленные ключи сохраняются.

[Документация PHP: array unpacking](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

<a id="merge-empty"></a>

## Слияние нуля или более массивов

**PHP 7.4+ | Ядро**

С PHP 7.4 `array_merge()` и `array_merge_recursive()` можно вызывать без аргументов. Поэтому динамический список пакетов можно распаковать даже тогда, когда объединять нечего.

```php
$batches = [];
echo json_encode( array_merge(...$batches) ) . PHP_EOL; # []

$batches = [[10, 20], [30]];
echo json_encode( array_merge(...$batches) ) . PHP_EOL; # [10,20,30]
```

**Важно:** `array_merge()` перенумеровывает целочисленные ключи. `array_merge_recursive()` собирает значения повторных строковых ключей в массивы, что часто ломает переопределение настроек; это не просто более глубокое слияние.

[Документация PHP: array_merge()](https://www.php.net/manual/en/function.array-merge.php)

<a id="boundary-keys"></a>

## Первый и последний ключ без изменения указателя

**PHP 7.3+ | Ядро**

`array_key_first()` и `array_key_last()` возвращают ключи в порядке обхода. Они не меняют внутренний указатель, в отличие от `reset()` и `end()`. Это удобно, когда нужен именно ключ.

```php
$queue = [40 => 'first', 10 => 'last'];
var_dump( array_key_first($queue), array_key_last($queue) );
# int(40)
# int(10)
var_dump( array_key_first([]) ); # NULL
```

**Важно:** Первый ключ не означает наименьший числовой. Для пустого массива возвращается null; ключ 0 допустим, его нельзя терять из-за проверки истинности.

[Документация PHP: array_key_first()](https://www.php.net/manual/en/function.array-key-first.php) | [Документация PHP: array_key_last()](https://www.php.net/manual/en/function.array-key-last.php)

<a id="countable"></a>

## Проверка возможности `count()`

**PHP 7.3+ | Ядро**

`is_countable()` распознаёт массивы и объекты `Countable`. Она прямо отвечает на вопрос перед `count()`, который в PHP 8 выбрасывает `TypeError` для неподходящих значений.

```php
$values = new ArrayObject( [10, 20] );

if( is_countable($values) )
	echo count( $values ) . PHP_EOL; # 2

var_dump( is_countable(null) ); # bool(false)
```

**Важно:** `iterable` не обязательно является countable. Генератор можно обходить, но обычно нельзя посчитать без его расходования.

[Документация PHP: is_countable()](https://www.php.net/manual/en/function.is-countable.php)

<a id="list-arrays"></a>

## Распознавание списков и форма JSON-массива

**PHP 8.1+ | Ядро**

`array_is_list()` проверяет последовательные целочисленные ключи от нуля. Это проверка структуры, а не типов элементов. Она полезна, когда API ожидает JSON-массив, а не объект.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
var_dump( array_is_list($ids) ); # bool(false)
echo json_encode( $ids ) . PHP_EOL; # {"0":10,"2":30}
echo json_encode( array_values($ids) ) . PHP_EOL; # [10,30]
```

**Важно:** Пустой массив является списком. `array_filter()` сохраняет ключи. Осознанно используйте `array_values()` для сплошных JSON-массивов; сохраняйте ключи, если они идентифицируют записи.

[Документация PHP: array_is_list()](https://www.php.net/manual/en/function.array-is-list.php)

<a id="predicate-search"></a>

## Поиск первого подходящего значения или ключа

**PHP 8.4+ | Ядро**

`array_find()` возвращает первое значение, соответствующее условию; `array_find_key()` возвращает его ключ. Обе функции останавливаются на первом совпадении без создания промежуточного отфильтрованного массива.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 );
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 );
echo $name . ': ' . $score . PHP_EOL; # Linus: 12
```

**Важно:** Условие получает сначала значение, затем ключ. Отсутствие совпадения даёт null. Если найденное значение само может быть null, используйте `array_find_key()` и проверяйте результат через !== null.

[Документация PHP: array_find()](https://www.php.net/manual/en/function.array-find.php) | [Документация PHP: array_find_key()](https://www.php.net/manual/en/function.array-find-key.php)

<a id="predicate-checks"></a>

## Проверка любого или всех элементов

**PHP 8.4+ | Ядро**

`array_any()` проверяет наличие хотя бы одного подходящего элемента; `array_all()` требует соответствия всех элементов. Обход прекращается, как только ответ известен, поэтому последующие вызовы могут не произойти.

```php
$ages = [18, 22, 16];
var_dump( array_any($ages, fn( int $age ): bool => $age < 18) ); # bool(true)
var_dump( array_all($ages, fn( int $age ): bool => $age >= 18) ); # bool(false)
var_dump( array_any([], fn( $value ): bool => true) ); # bool(false)
var_dump( array_all([], fn( $value ): bool => false) ); # bool(true)
```

**Важно:** all([]) даёт true, ведь нет элемента, нарушающего условие. При необходимости отдельно проверяйте непустоту. В условии не должно быть обязательных побочных действий для каждого элемента.

[Документация PHP: array_any()](https://www.php.net/manual/en/function.array-any.php) | [Документация PHP: array_all()](https://www.php.net/manual/en/function.array-all.php)

<a id="boundary-values"></a>

## Прямое чтение первого или последнего значения

**PHP 8.5+ | Ядро**

`array_first()` и `array_last()` читают крайние значения без изменения внутреннего указателя. Они используют порядок обхода, в том числе в ассоциативных массивах. Если нужно лишь значение, дополнительный поиск ключа излишен.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
echo array_first( $stages ) . PHP_EOL; # Waiting
echo array_last( $stages ) . PHP_EOL; # Finished
var_dump( array_last([]) ); # NULL
```

**Важно:** Пустой массив и крайний элемент с null оба дают null. Если это важно, отдельно проверяйте пустоту массива.

[Документация PHP: array_first()](https://www.php.net/manual/en/function.array-first.php) | [Документация PHP: array_last()](https://www.php.net/manual/en/function.array-last.php)

<a id="stable-sorting"></a>

## Стабильная сортировка сохраняет порядок равных элементов

**PHP 8.0+ | Ядро**

С PHP 8.0 сортировка сохраняет исходный взаимный порядок элементов, сравнивающихся как равные. Уже установленный дополнительный порядок может сохраниться после сортировки по другому полю.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
echo implode( ', ', array_column($rows, 'name') ) . PHP_EOL; # Grace, Ada, Linus
```

**Важно:** Возвращайте из функции сравнения отрицательное целое число, ноль или положительное целое число. bool не выражает все три случая. `usort()` перенумеровывает ключи, `uasort()` сохраняет их связи.

[Документация PHP: usort()](https://www.php.net/manual/en/function.usort.php)
