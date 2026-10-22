# Массивы и обход данных

[EN](../en/04-arrays-and-iteration.md) | [UK](../uk/04-arrays-and-iteration.md) | [RU](../ru/04-arrays-and-iteration.md)

[Содержание](../../README.ru.md)

Массивы PHP сохраняют порядок добавления элементов. Ниже заметки о ключах, распаковке массивов и обходе данных.

- [Разбор массива по переменным](#destructuring)
- [Чтение выбранных именованных полей](#keyed-destructuring)
- [Разбор по ссылке для изменения оригинала](#reference-destructuring)
- [Массивы и итераторы через `iterable`](#iterable)
- [Объединение потоков через `yield from`](#generator-delegation)
- [Итоговый результат генератора](#generator-return)
- [Создание массивов через `...` и повторяющиеся ключи](#unpacking)
- [Слияние нуля или более массивов](#merge-empty)
- [Первый и последний ключ без изменения указателя](#boundary-keys)
- [Проверка возможности `count()`](#countable)
- [Распознавание списков и форма JSON-массива](#list-arrays)
- [Поиск первого подходящего значения или ключа](#predicate-search)
- [Проверка любого или всех элементов](#predicate-checks)
- [Прямое чтение первого или последнего значения](#boundary-values)
- [Стабильная сортировка сохраняет порядок равных элементов](#stable-sorting)

<a id="destructuring"></a>

## Разбор массива по переменным

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Деструктуризация разбирает массив по переменным без отдельных обращений по индексу. Она работает и в `foreach`; пустая позиция позволяет пропустить элемент.

```php
[$id, , $name] = [7, true, 'Ada']; # $id: 7, $name: 'Ada'

foreach( [[7, 'Ada'], [9, 'Linus']] as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

Отсутствующий индекс вызывает предупреждение и даёт `null`; это не проверка структуры записи.

<a id="keyed-destructuring"></a>

## Чтение выбранных именованных полей

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.list.php)

Деструктуризация по ключам выбирает поля независимо от их порядка.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row; # 7, 'Ada'
```

На одном уровне нельзя смешивать элементы с ключами и без них; для необязательных ключей нужны значения по умолчанию.

<a id="reference-destructuring"></a>

## Разбор по ссылке для изменения оригинала

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/migration73.new-features.php)

Ссылки в деструктуризации связывают переменные с элементами исходного массива.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15; # $point is [15, 20]
unset( $x );
```

`unset($x)` разрывает связь, не удаляя элемент массива.

<a id="iterable"></a>

## Массивы и итераторы через `iterable`

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

`iterable` принимает массивы и объекты `Traversable`, включая генераторы. Этот контракт подходит, когда функции нужен обход, а не операции именно с массивом.

```php
function sum_values( iterable $values ): int
{
	$total = 0;

	foreach( $values as $value )
		$total += $value;

	return $total;
}

$total = sum_values( new ArrayIterator([2, 3, 5]) ); # 10
```

Он не гарантирует типов элементов, `count()`, доступа по индексу или повторного обхода; с 8.2 это псевдоним `array|Traversable`.

<a id="generator-delegation"></a>

## Объединение потоков через `yield from`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/language.generators.syntax.php)

`yield from` делегирует обход другому итерируемому значению, объединяя источники без промежуточного массива. Значения создаются по мере обхода генератора.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}
$ids = iterator_to_array( page_ids(), false ); # [10, 11, 12, 13]
```

Ключи сохраняются: дубликаты перезаписываются в `iterator_to_array()`, если второй аргумент не `false`; для нового обхода завершённого генератора нужно создать новый экземпляр.

<a id="generator-return"></a>

## Итоговый результат генератора

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/generator.getreturn.php)

`Generator::getReturn()` получает результат `return` отдельно от значений `yield`.

```php
function processed_ids(): Generator
{
	yield 10;
	return 1;
}
$stream = processed_ids();
$ids = iterator_to_array( $stream ); # [10]
$count = $stream->getReturn(); # 1
```

До нормального завершения он выбрасывает исключение; выход из `foreach` через `break` не выполняет `return` генератора.

<a id="unpacking"></a>

## Создание массивов через `...` и повторяющиеся ключи

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

Распаковка массивов принимает массивы и `Traversable`; целые ключи перенумеровываются, строковые поддерживаются с 8.1.

```php
$items = [...[8 => 'a', 9 => 'b'], 'c']; # ['a', 'b', 'c']

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]]; # timeout: 30
```

Для строковых ключей побеждает позднее значение; объединение через `+` сохраняет целые ключи и значения слева.

<a id="merge-empty"></a>

## Слияние нуля или более массивов

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-merge.php)

С 7.4 `array_merge()` и `array_merge_recursive()` принимают ноль аргументов. Поэтому динамический список наборов можно распаковать и тогда, когда он пуст.

```php
$batches = [];
$items = array_merge( ...$batches ); # []
$batches = [[10, 20], [30]];
$items = array_merge( ...$batches ); # [10, 20, 30]
```

`array_merge()` перенумеровывает целые ключи; рекурсивное слияние превращает значения с общим строковым ключом в массив вместо замены.

<a id="boundary-keys"></a>

## Первый и последний ключ без изменения указателя

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-key-first.php) | [PHP](https://www.php.net/manual/en/function.array-key-last.php)

`array_key_first()` и `array_key_last()` следуют порядку обхода и не меняют внутренний указатель.

```php
$queue = [40 => 'first', 10 => 'last'];
$first_key = array_key_first( $queue ); # 40
$last_key = array_key_last( $queue ); # 10
$missing = array_key_first( [] ); # null
```

Пустой массив даёт `null`; ключ `0` допустим, поэтому нужен `!== null`, а не проверка истинности.

<a id="countable"></a>

## Проверка возможности `count()`

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.is-countable.php)

`is_countable()` распознаёт массивы и объекты `Countable` перед вызовом `count()`.

```php
$values = new ArrayObject( [10, 20] );
if( is_countable($values) )
	$count = count( $values ); # 2
```

Итерируемое значение не обязательно имеет размер; в PHP 8 `count()` для неподходящего значения выбрасывает `TypeError`.

<a id="list-arrays"></a>

## Распознавание списков и форма JSON-массива

**PHP 8.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-is-list.php)

`array_is_list()` проверяет последовательные целые ключи от нуля; пустой массив является списком.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
$is_list = array_is_list( $ids ); # false
$object_json = json_encode( $ids ); # {"0":10,"2":30}
$list_json = json_encode( array_values($ids) ); # [10,30]
```

`array_filter()` сохраняет ключи; используйте `array_values()`, когда результат должен кодироваться как массив JSON.

<a id="predicate-search"></a>

## Поиск первого подходящего значения или ключа

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-find.php) | [PHP](https://www.php.net/manual/en/function.array-find-key.php)

`array_find()` возвращает первое подходящее значение, а `array_find_key()` его ключ. Обе функции останавливаются на совпадении; предикат получает значение, затем ключ.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 ); # 12
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 ); # Linus
```

Обе функции возвращают `null` при отсутствии совпадения; если само значение может быть `null`, проверяйте ключ через `!== null`.

<a id="predicate-checks"></a>

## Проверка любого или всех элементов

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-any.php) | [PHP](https://www.php.net/manual/en/function.array-all.php)

`array_any()` проверяет, подходит ли хотя бы один элемент, а `array_all()` требует соответствия всех. Обход прекращается, как только результат известен.

```php
$ages = [18, 22, 16];
$has_minor = array_any( $ages, fn( int $age ): bool => $age < 18 ); # true
$all_adults = array_all( $ages, fn( int $age ): bool => $age >= 18 ); # false
$any_empty = array_any( [], fn( $value ): bool => true ); # false
$all_empty = array_all( [], fn( $value ): bool => false ); # true
```

`array_all([])` даёт `true`; непустоту проверяют отдельно, а вызов предиката для каждого элемента не гарантирован.

<a id="boundary-values"></a>

## Прямое чтение первого или последнего значения

**PHP 8.5+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-first.php) | [PHP](https://www.php.net/manual/en/function.array-last.php)

`array_first()` и `array_last()` читают значения в порядке обхода без изменения внутреннего указателя.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
$first = array_first( $stages ); # Waiting
$last = array_last( $stages ); # Finished
$missing = array_last( [] ); # null
```

И пустой массив, и крайний элемент со значением `null` возвращают `null`.

<a id="stable-sorting"></a>

## Стабильная сортировка сохраняет порядок равных элементов

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.usort.php)

С 8.0 сортировка стабильна: равные по сравнению элементы сохраняют взаимный порядок.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
$names = array_column( $rows, 'name' ); # ['Grace', 'Ada', 'Linus']
```

Компаратор должен возвращать отрицательное целое, ноль или положительное целое; `usort()` перенумеровывает ключи, `uasort()` сохраняет их связи.
