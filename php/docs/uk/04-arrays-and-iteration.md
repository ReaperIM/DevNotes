# Масиви та обхід даних

[EN](../en/04-arrays-and-iteration.md) | [UK](../uk/04-arrays-and-iteration.md) | [RU](../ru/04-arrays-and-iteration.md)

[Зміст](../../README.uk.md)

Масиви PHP зберігають порядок додавання елементів. Нижче нотатки про ключі, розпакування масивів та обхід даних.

- [Розкладання масиву за змінними](#destructuring)
- [Читання вибраних іменованих полів](#keyed-destructuring)
- [Розкладання за посиланням для зміни оригіналу](#reference-destructuring)
- [Масиви та ітератори через `iterable`](#iterable)
- [Об’єднання потоків через `yield from`](#generator-delegation)
- [Підсумковий результат генератора](#generator-return)
- [Побудова масивів через `...` і повторні ключі](#unpacking)
- [Злиття нуля або більше масивів](#merge-empty)
- [Перший і останній ключ без зміни вказівника](#boundary-keys)
- [Перевірка можливості `count()`](#countable)
- [Розпізнавання списків і форма JSON-масиву](#list-arrays)
- [Пошук першого відповідного значення чи ключа](#predicate-search)
- [Перевірка будь-якого або всіх елементів](#predicate-checks)
- [Безпосереднє читання першого чи останнього значення](#boundary-values)
- [Стабільне сортування зберігає порядок рівних елементів](#stable-sorting)

<a id="destructuring"></a>

## Розкладання масиву за змінними

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Деструктуризація розкладає масив за змінними без окремих звернень за індексом. Вона працює і в `foreach`; порожня позиція дозволяє пропустити елемент.

```php
[$id, , $name] = [7, true, 'Ada']; # $id: 7, $name: 'Ada'

foreach( [[7, 'Ada'], [9, 'Linus']] as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

Відсутній індекс спричиняє попередження та дає `null`; це не перевірка структури запису.

<a id="keyed-destructuring"></a>

## Читання вибраних іменованих полів

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.list.php)

Деструктуризація за ключами вибирає поля незалежно від їхнього порядку.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row; # 7, 'Ada'
```

На одному рівні не можна змішувати елементи з ключами та без них; для необов’язкових ключів потрібні типові значення.

<a id="reference-destructuring"></a>

## Розкладання за посиланням для зміни оригіналу

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/migration73.new-features.php)

Посилання в деструктуризації прив’язують змінні до елементів початкового масиву.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15; # $point is [15, 20]
unset( $x );
```

`unset($x)` розриває зв’язок, не видаляючи елемент масиву.

<a id="iterable"></a>

## Масиви та ітератори через `iterable`

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

`iterable` приймає масиви та об’єкти `Traversable`, зокрема генератори. Цей контракт доречний, коли функції потрібен обхід, а не операції саме з масивом.

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

Він не гарантує типів елементів, `count()`, доступу за індексом чи повторного обходу; з 8.2 це псевдонім `array|Traversable`.

<a id="generator-delegation"></a>

## Об’єднання потоків через `yield from`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/language.generators.syntax.php)

`yield from` делегує обхід іншому ітерованому значенню, поєднуючи джерела без проміжного масиву. Значення створюються в міру обходу генератора.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}
$ids = iterator_to_array( page_ids(), false ); # [10, 11, 12, 13]
```

Ключі зберігаються: дублікати перезаписуються в `iterator_to_array()`, якщо другий аргумент не `false`; генератори не стають повторно придатними автоматично.

<a id="generator-return"></a>

## Підсумковий результат генератора

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/generator.getreturn.php)

`Generator::getReturn()` повертає результат `return` окремо від значень `yield`.

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

До нормального завершення він кидає виняток; вихід із `foreach` через `break` не виконує `return` генератора.

<a id="unpacking"></a>

## Побудова масивів через `...` і повторні ключі

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

Розпакування масивів приймає масиви та `Traversable`; цілі ключі перенумеровуються, рядкові підтримуються з 8.1.

```php
$items = [...[8 => 'a', 9 => 'b'], 'c']; # ['a', 'b', 'c']

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]]; # timeout: 30
```

Для рядкових ключів перемагає пізніше значення; об’єднання через `+` зберігає цілі ключі та значення зліва.

<a id="merge-empty"></a>

## Злиття нуля або більше масивів

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-merge.php)

З 7.4 `array_merge()` та `array_merge_recursive()` приймають нуль аргументів. Тому динамічний список наборів можна розпакувати й тоді, коли він порожній.

```php
$batches = [];
$items = array_merge( ...$batches ); # []
$batches = [[10, 20], [30]];
$items = array_merge( ...$batches ); # [10, 20, 30]
```

`array_merge()` перенумеровує цілі ключі; рекурсивне злиття перетворює значення зі спільним рядковим ключем на масив замість заміни.

<a id="boundary-keys"></a>

## Перший і останній ключ без зміни вказівника

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-key-first.php) | [PHP](https://www.php.net/manual/en/function.array-key-last.php)

`array_key_first()` та `array_key_last()` враховують порядок обходу й не змінюють внутрішній вказівник.

```php
$queue = [40 => 'first', 10 => 'last'];
$first_key = array_key_first( $queue ); # 40
$last_key = array_key_last( $queue ); # 10
$missing = array_key_first( [] ); # null
```

Порожній масив дає `null`; ключ `0` допустимий, тому потрібне `!== null`, а не перевірка істинності.

<a id="countable"></a>

## Перевірка можливості `count()`

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.is-countable.php)

`is_countable()` розпізнає масиви й об’єкти `Countable` перед викликом `count()`.

```php
$values = new ArrayObject( [10, 20] );
if( is_countable($values) )
	$count = count( $values ); # 2
```

Ітероване значення не обов’язково має розмір; у PHP 8 `count()` для непридатного значення кидає `TypeError`.

<a id="list-arrays"></a>

## Розпізнавання списків і форма JSON-масиву

**PHP 8.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-is-list.php)

`array_is_list()` перевіряє послідовні цілі ключі від нуля; порожній масив є списком.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
$is_list = array_is_list( $ids ); # false
$object_json = json_encode( $ids ); # {"0":10,"2":30}
$list_json = json_encode( array_values($ids) ); # [10,30]
```

`array_filter()` зберігає ключі; застосовуйте `array_values()`, коли результат має кодуватися як масив JSON.

<a id="predicate-search"></a>

## Пошук першого відповідного значення чи ключа

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-find.php) | [PHP](https://www.php.net/manual/en/function.array-find-key.php)

`array_find()` повертає перше відповідне значення, а `array_find_key()` його ключ. Обидві функції зупиняються на збігу; предикат отримує значення, потім ключ.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 ); # 12
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 ); # Linus
```

Обидві функції повертають `null` за відсутності збігу; якщо саме значення може бути `null`, перевіряйте ключ через `!== null`.

<a id="predicate-checks"></a>

## Перевірка будь-якого або всіх елементів

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-any.php) | [PHP](https://www.php.net/manual/en/function.array-all.php)

`array_any()` перевіряє, чи відповідає умові хоча б один елемент, а `array_all()` вимагає відповідності всіх. Обхід припиняється, щойно результат відомий.

```php
$ages = [18, 22, 16];
$has_minor = array_any( $ages, fn( int $age ): bool => $age < 18 ); # true
$all_adults = array_all( $ages, fn( int $age ): bool => $age >= 18 ); # false
$any_empty = array_any( [], fn( $value ): bool => true ); # false
$all_empty = array_all( [], fn( $value ): bool => false ); # true
```

`array_all([])` дає `true`; непорожність перевіряється окремо, а виклик предиката для кожного елемента не гарантовано.

<a id="boundary-values"></a>

## Безпосереднє читання першого чи останнього значення

**PHP 8.5+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-first.php) | [PHP](https://www.php.net/manual/en/function.array-last.php)

`array_first()` та `array_last()` читають значення в порядку обходу без зміни внутрішнього вказівника.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
$first = array_first( $stages ); # Waiting
$last = array_last( $stages ); # Finished
$missing = array_last( [] ); # null
```

І порожній масив, і крайній елемент зі значенням `null` повертають `null`.

<a id="stable-sorting"></a>

## Стабільне сортування зберігає порядок рівних елементів

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.usort.php)

З 8.0 сортування стабільне: рівні за порівнянням елементи зберігають взаємний порядок.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
$names = array_column( $rows, 'name' ); # ['Grace', 'Ada', 'Linus']
```

Компаратор має повертати від’ємне ціле, нуль або додатне ціле; `usort()` перенумеровує ключі, `uasort()` зберігає їхні зв’язки.
