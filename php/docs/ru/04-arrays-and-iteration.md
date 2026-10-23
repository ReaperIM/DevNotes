# Массивы и обход данных

[EN](../en/04-arrays-and-iteration.md) | [UK](../uk/04-arrays-and-iteration.md) | [RU](../ru/04-arrays-and-iteration.md)

[Содержание](../../README.ru.md)

Массивы PHP сохраняют порядок добавления элементов. Как забрать нужные поля, собрать несколько массивов и пройти данные без лишних копий. Заодно разбираемся с ключами: они влияют и на слияние, и на то, что в итоге попадёт в JSON.

- [Раскладываем массив по переменным](#destructuring)
- [Забираем нужные поля по ключам](#keyed-destructuring)
- [Меняем исходный элемент через ссылку](#reference-destructuring)
- [Перебираем массивы и итераторы одинаково](#iterable)
- [Отдаём значения из нескольких источников через `yield from`](#generator-delegation)
- [Получаем итог работы генератора](#generator-return)
- [Собираем массив через `...`](#unpacking)
- [Склеиваем наборы, даже если наборов нет](#merge-empty)
- [Берём первый и последний ключ](#boundary-keys)
- [Проверяем, можно ли вызвать `count()`](#countable)
- [Проверяем список перед выводом в JSON](#list-arrays)
- [Ищем первое значение по условию](#predicate-search)
- [Проверяем хотя бы один элемент или все сразу](#predicate-checks)
- [Берём первое или последнее значение](#boundary-values)
- [Сохраняем порядок при равных значениях](#stable-sorting)

<a id="destructuring"></a>
## Раскладываем массив по переменным

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Вместо нескольких обращений по индексу можно сразу разложить массив в нужные переменные. Это называется деструктуризацией. Пустое место между запятыми пропускает элемент, а в `foreach` такая запись разбирает каждую строку по ходу обхода.

```php
[$id, , $name] = [7, true, 'Ada']; # $id: 7, $name: 'Ada'

foreach( [[7, 'Ada'], [9, 'Linus']] as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

PHP ожидает, что указанные позиции существуют. Если элемента нет, получим предупреждение и `null`, поэтому сама эта запись не заменяет проверку входных данных.

<a id="keyed-destructuring"></a>
## Забираем нужные поля по ключам

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.list.php)

Когда у полей есть имена, можно сразу указать, какой ключ в какую переменную забрать. Порядок полей в исходном массиве уже не важен, лишние поля просто не берём.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row; # 7, 'Ada'
```

На одном уровне нельзя смешать запись по ключам и запись только по позициям. Если поле необязательное, сначала подставляем для него значение по умолчанию, иначе получим предупреждение об отсутствующем ключе.

<a id="reference-destructuring"></a>
## Меняем исходный элемент через ссылку

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/migration73.new-features.php)

Знак `&` оставляет переменную связанной с элементом массива. Поэтому присваивание `$x = 15` меняет и первый элемент `$point`, а не только отдельную копию значения.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15; # в $point теперь [15, 20]
unset( $x );
```

Когда связь больше не нужна, убираем её через `unset($x)`. Сам элемент массива при этом остаётся на месте.

<a id="iterable"></a>
## Перебираем массивы и итераторы одинаково

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

Если функция просто проходит по значениям через `foreach`, ей не обязательно требовать массив. `iterable` принимает массивы и объекты `Traversable`, в том числе генераторы. Поэтому `sum_values()` может так же посчитать сумму из `ArrayIterator`.

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

Но `iterable` не обещает доступ по индексу, возможность вызвать `count()` или пройти данные ещё раз. Типы самих элементов он тоже не проверяет. С PHP 8.2 это имя для типа `array|Traversable`.

<a id="generator-delegation"></a>
## Отдаём значения из нескольких источников через `yield from`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/language.generators.syntax.php)

`yield from` позволяет генератору отдавать значения другого массива или перебираемого объекта. Здесь сначала идут `10` и `11`, затем `12` и `13`. Промежуточный общий массив не нужен, значения поступают по мере обхода.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}
# одинаковые ключи не должны затереть значения из первого массива
$ids = iterator_to_array( page_ids(), false ); # [10, 11, 12, 13]
```

Ключи при этом сохраняются. У обоих массивов в примере они начинаются с нуля, поэтому в `iterator_to_array()` передаём `false`: нам нужны все значения, а не перезапись по одинаковым ключам. Для нового обхода уже завершённого генератора создаём новый экземпляр.

<a id="generator-return"></a>
## Получаем итог работы генератора

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/generator.getreturn.php)

У генератора есть два разных результата: значения, которые он постепенно отдаёт через `yield`, и итог из `return`. Итог читаем отдельно через `getReturn()`, когда генератор уже закончил работу.

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

Здесь в `$ids` попадёт `[10]`, а в `$count` число `1`. Если вызвать `getReturn()` до нормального завершения, получим исключение. Обычный `break` из `foreach` сам по себе не доводит генератор до его `return`.

<a id="unpacking"></a>
## Собираем массив через `...`

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

Через `...` можно вставить в новый массив элементы другого массива или `Traversable`. Числовые ключи при этом пересчитываются с нуля: старые `8` и `9` в первом примере не сохранятся. Строковые ключи поддерживаются с PHP 8.1.

```php
$items = [...[8 => 'a', 9 => 'b'], 'c']; # ['a', 'b', 'c']

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]]; # timeout: 30
```

Если строковый ключ встречается повторно, остаётся более позднее значение, поэтому `timeout` станет `30`. У объединения через `+` другие правила: числовые ключи сохраняются, а при совпадении ключа остаётся значение слева.

<a id="merge-empty"></a>
## Склеиваем наборы, даже если наборов нет

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-merge.php)

Список массивов может оказаться пустым. С PHP 7.4 для этого не нужна отдельная ветка: `array_merge()` и `array_merge_recursive()` разрешено вызвать без аргументов. Поэтому распаковка `...$batches` работает в обоих случаях.

```php
$batches = [];
$items = array_merge( ...$batches ); # []
$batches = [[10, 20], [30]];
$items = array_merge( ...$batches ); # [10, 20, 30]
```

Обычный `array_merge()` пересчитывает числовые ключи. Рекурсивный вариант ведёт себя иначе при одинаковых строковых ключах: собирает их значения в массив, а не просто заменяет старое новым.

<a id="boundary-keys"></a>
## Берём первый и последний ключ

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-key-first.php) | [PHP](https://www.php.net/manual/en/function.array-key-last.php)

`array_key_first()` и `array_key_last()` берут ключи по порядку элементов, а не по величине чисел. Поэтому первый ключ здесь `40`, последний `10`. Внутренний указатель массива эти функции не двигают.

```php
$queue = [40 => 'first', 10 => 'last'];
$first_key = array_key_first( $queue ); # 40
$last_key = array_key_last( $queue ); # 10
$missing = array_key_first( [] ); # null
```

У пустого массива получим `null`. Ключ `0` при этом совершенно допустим, поэтому наличие результата проверяем через `!== null`, а не обычным `if($key)`.

<a id="countable"></a>
## Проверяем, можно ли вызвать `count()`

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.is-countable.php)

Не всё, что можно перебрать, можно сразу посчитать. `is_countable()` проверяет, что перед нами массив или объект `Countable`, и после этого можно вызвать `count()`.

```php
$values = new ArrayObject( [10, 20] );
if( is_countable($values) )
	$count = count( $values ); # 2
```

В примере `ArrayObject` подходит, получаем `2`. Если в PHP 8 передать `count()` неподходящее значение, вместо количества будет `TypeError`.

<a id="list-arrays"></a>
## Проверяем список перед выводом в JSON

**PHP 8.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-is-list.php)

Для списка нужны целые ключи подряд от нуля: `0, 1, 2`. Это проверяет `array_is_list()`; пустой массив тоже считается списком. После удаления среднего элемента в примере остаётся дырка, и JSON уже получится объектом, а не массивом.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
$is_list = array_is_list( $ids ); # false
$object_json = json_encode( $ids ); # {"0":10,"2":30}
$list_json = json_encode( array_values($ids) ); # [10,30]
```

`array_values()` собирает ключи заново, после него получаем JSON-массив `[10,30]`. Такая же ситуация бывает после `array_filter()`: он сохраняет исходные ключи.

<a id="predicate-search"></a>
## Ищем первое значение по условию

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-find.php) | [PHP](https://www.php.net/manual/en/function.array-find-key.php)

`array_find()` отдаёт первое значение, которое прошло проверку, а `array_find_key()` отдаёт его ключ. В примере первым подходящим результатом будет `12` у `Linus`; до `Grace` поиск уже не дойдёт.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 ); # 12
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 ); # Linus
```

Функция проверки получает сначала значение, затем ключ. Если ничего не нашлось, обе функции вернут `null`. Когда само искомое значение тоже может быть `null`, надёжнее искать ключ и проверять его через `!== null`.

<a id="predicate-checks"></a>
## Проверяем хотя бы один элемент или все сразу

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-any.php) | [PHP](https://www.php.net/manual/en/function.array-all.php)

`array_any()` отвечает, есть ли хотя бы один подходящий элемент. `array_all()` проверяет, подходят ли все. Здесь несовершеннолетний есть, поэтому первая проверка даёт `true`, а утверждение «все взрослые» уже `false`.

```php
$ages = [18, 22, 16];
$has_minor = array_any( $ages, fn( int $age ): bool => $age < 18 ); # true
$all_adults = array_all( $ages, fn( int $age ): bool => $age >= 18 ); # false
$any_empty = array_any( [], fn( $value ): bool => true ); # false
$all_empty = array_all( [], fn( $value ): bool => false ); # true
```

Обе функции останавливаются, как только ответ известен, поэтому не рассчитываем на вызов проверки для каждого элемента. У пустого массива `array_any()` возвращает `false`, а `array_all()` возвращает `true`. Если по смыслу массив ещё и должен быть непустым, это проверяем отдельно.

<a id="boundary-values"></a>
## Берём первое или последнее значение

**PHP 8.5+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-first.php) | [PHP](https://www.php.net/manual/en/function.array-last.php)

Если нужен сам элемент, а не его ключ, есть `array_first()` и `array_last()`. Они идут по порядку массива и не двигают его внутренний указатель.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
$first = array_first( $stages ); # Waiting
$last = array_last( $stages ); # Finished
$missing = array_last( [] ); # null
```

В примере получаем `Waiting` и `Finished`. Пустой массив вернёт `null`, но такой же результат будет, если крайний элемент сам содержит `null`. По одному результату эти случаи не различить.

<a id="stable-sorting"></a>
## Сохраняем порядок при равных значениях

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.usort.php)

С PHP 8.0 сортировка стабильна: если сравнение считает два элемента равными, они остаются в прежнем порядке друг относительно друга. Поэтому после сортировки по `score` `Ada` всё ещё идёт перед `Linus`.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
$names = array_column( $rows, 'name' ); # ['Grace', 'Ada', 'Linus']
```

Функция сравнения должна возвращать целое число: меньше нуля, ноль или больше нуля. `usort()` при этом пересчитывает ключи. Если нужно сохранить связь с исходными ключами, есть `uasort()`.
