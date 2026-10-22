# Масиви та обхід даних

[English](../en/04-arrays-and-iteration.md) | [Українська](../uk/04-arrays-and-iteration.md) | [Русский](../ru/04-arrays-and-iteration.md)

[Зміст](../../README.uk.md)

Масиви PHP є впорядкованими мапами. Списки, записи й таблиці пошуку за ID мають один тип, але різні правила використання. Приклади пояснюють ключі, поступову обробку та відсутні значення. Кожен приклад незалежний.

- [Розкладання рядка на змінні](#destructuring)
- [Читання вибраних іменованих полів](#keyed-destructuring)
- [Розкладання за посиланням для зміни оригіналу](#reference-destructuring)
- [Масиви та ітератори через `iterable`](#iterable)
- [Об’єднання потоків через yield from](#generator-delegation)
- [Підсумковий результат генератора](#generator-return)
- [Побудова масивів через ... і повторні ключі](#unpacking)
- [Злиття нуля або більше масивів](#merge-empty)
- [Перший і останній ключ без зміни вказівника](#boundary-keys)
- [Перевірка можливості `count()`](#countable)
- [Розпізнавання списків і форма JSON-масиву](#list-arrays)
- [Пошук першого відповідного значення чи ключа](#predicate-search)
- [Перевірка будь-якого або всіх елементів](#predicate-checks)
- [Безпосереднє читання першого чи останнього значення](#boundary-values)
- [Стабільне сортування зберігає порядок рівних елементів](#stable-sorting)

<a id="destructuring"></a>

## Розкладання рядка на змінні

**PHP 7.1+ | Ядро**

Коротке розкладання дає змогу назвати поля без повторного звернення до індексів одного рядка. Воно працює і всередині foreach. Це зручно для фіксованих наборів, наприклад ID та імені.

```php
$rows = [[7, 'Ada'], [9, 'Linus']];

foreach( $rows as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

**Важливо:** Це присвоювання, а не перевірка схеми. Відсутній індекс у PHP 8 спричиняє попередження і дає null. Спочатку перевіряйте зовнішні дані; позицію можна пропустити: [$first, , $third].

[Документація PHP: destructuring](https://www.php.net/manual/en/migration71.new-features.php)

<a id="keyed-destructuring"></a>

## Читання вибраних іменованих полів

**PHP 7.1+ | Ядро**

Асоціативне розкладання показує, які ключі потрібні. Їхній порядок не має значення, адже PHP шукає значення за іменами. Запис схожий на вибір стовпців із запису.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row;
echo $user_id . ': ' . $user_name . PHP_EOL; # 7: Ada
```

**Важливо:** Не змішуйте іменовані та позиційні елементи на одному рівні. Для необов’язкових полів окремо задавайте типові значення.

[Документація PHP: list()](https://www.php.net/manual/en/function.list.php)

<a id="reference-destructuring"></a>

## Розкладання за посиланням для зміни оригіналу

**PHP 7.3+ | Ядро**

Посилання всередині розкладання пов’язує змінну з елементом початкового масиву. Використовуйте його для навмисної зміни оригіналу; звичайне читання не потребує посилань.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15;
unset( $x );
echo json_encode( $point ) . PHP_EOL; # [15,20]
```

**Важливо:** Зв’язок зберігається після присвоювання. unset($x) розриває його для змінної, але не видаляє елемент масиву.

[Документація PHP: PHP 7.3](https://www.php.net/manual/en/migration73.new-features.php)

<a id="iterable"></a>

## Масиви та ітератори через `iterable`

**PHP 7.1+ | Ядро**

Використовуйте `iterable`, якщо функції достатньо foreach без спеціальних операцій масивів. Тип приймає масиви та об’єкти `Traversable`, зокрема генератори. Виклик може передавати значення поступово, не створюючи повного масиву.

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

**Важливо:** `iterable` не гарантує `count()`, доступ за індексом, повторний обхід чи типи елементів. У PHP 8.2 він став псевдонімом array|`Traversable`.

[Документація PHP: iterable](https://www.php.net/manual/en/language.types.iterable.php)

<a id="generator-delegation"></a>

## Об’єднання потоків через yield from

**PHP 7.0+ | Ядро**

yield from передає значення з іншого `iterable`. Генератор працює в міру запиту значень, тому не потрібно зберігати всі проміжні результати. Це зручно для поєднання кількох джерел даних.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}

$ids = iterator_to_array( page_ids(), false );
echo json_encode( $ids ) . PHP_EOL; # [10,11,12,13]
```

**Важливо:** Делегування зберігає ключі. Повторні ключі можуть перезаписати елементи в `iterator_to_array()`; для списку передавайте false. Генератор споживається під час роботи і не є автоматично багаторазовим.

[Документація PHP: generator syntax](https://www.php.net/manual/en/language.generators.syntax.php)

<a id="generator-return"></a>

## Підсумковий результат генератора

**PHP 7.0+ | Ядро**

Генератор може видавати проміжні значення й окремо повертати підсумок. `getReturn()` читає цей результат після завершення, відокремлюючи його від елементів потоку.

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

**Важливо:** `getReturn()` кидає виняток до нормального завершення. Дострокова зупинка foreach не змушує генератор дійти до return.

[Документація PHP: Generator::getReturn()](https://www.php.net/manual/en/generator.getreturn.php)

<a id="unpacking"></a>

## Побудова масивів через ... і повторні ключі

**PHP 7.4+ | Ядро**

Розпакування вставляє масив або `Traversable` у вираз масиву. Цілочислові ключі перенумеровуються. Рядкові ключі підтримуються з PHP 8.1; пізніше значення замінює попереднє з тим самим рядковим ключем.

```php
$first = [8 => 'a', 9 => 'b'];
echo json_encode( [...$first, 'c'] ) . PHP_EOL; # ["a","b","c"]

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]];
echo $config['timeout'] . PHP_EOL; # 30
```

**Важливо:** Приклад із рядковими ключами потребує PHP 8.1. Об’єднання через + має інші правила: наявні ключі зліва перемагають, а цілочислові ключі зберігаються.

[Документація PHP: array unpacking](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

<a id="merge-empty"></a>

## Злиття нуля або більше масивів

**PHP 7.4+ | Ядро**

З PHP 7.4 `array_merge()` та `array_merge_recursive()` можна викликати без аргументів. Тому динамічний список пакетів можна розпакувати навіть тоді, коли об’єднувати нічого.

```php
$batches = [];
echo json_encode( array_merge(...$batches) ) . PHP_EOL; # []

$batches = [[10, 20], [30]];
echo json_encode( array_merge(...$batches) ) . PHP_EOL; # [10,20,30]
```

**Важливо:** `array_merge()` перенумеровує цілочислові ключі. `array_merge_recursive()` збирає значення повторних рядкових ключів у масиви, що часто псує перевизначення налаштувань; це не просто глибше злиття.

[Документація PHP: array_merge()](https://www.php.net/manual/en/function.array-merge.php)

<a id="boundary-keys"></a>

## Перший і останній ключ без зміни вказівника

**PHP 7.3+ | Ядро**

`array_key_first()` та `array_key_last()` повертають ключі в порядку обходу. Вони не змінюють внутрішній вказівник, на відміну від `reset()` та `end()`. Це зручно, коли потрібен саме ключ.

```php
$queue = [40 => 'first', 10 => 'last'];
var_dump( array_key_first($queue), array_key_last($queue) );
# int(40)
# int(10)
var_dump( array_key_first([]) ); # NULL
```

**Важливо:** Перший ключ не означає найменший числовий. Для порожнього масиву повертається null; ключ 0 є допустимим, його не можна втрачати через перевірку істинності.

[Документація PHP: array_key_first()](https://www.php.net/manual/en/function.array-key-first.php) | [Документація PHP: array_key_last()](https://www.php.net/manual/en/function.array-key-last.php)

<a id="countable"></a>

## Перевірка можливості `count()`

**PHP 7.3+ | Ядро**

`is_countable()` розпізнає масиви та об’єкти `Countable`. Вона прямо відповідає на питання перед `count()`, що у PHP 8 кидає `TypeError` для невідповідних значень.

```php
$values = new ArrayObject( [10, 20] );

if( is_countable($values) )
	echo count( $values ) . PHP_EOL; # 2

var_dump( is_countable(null) ); # bool(false)
```

**Важливо:** `iterable` не обов’язково є countable. Генератор можна обходити, але зазвичай не можна порахувати без його споживання.

[Документація PHP: is_countable()](https://www.php.net/manual/en/function.is-countable.php)

<a id="list-arrays"></a>

## Розпізнавання списків і форма JSON-масиву

**PHP 8.1+ | Ядро**

`array_is_list()` перевіряє послідовні цілочислові ключі від нуля. Це перевірка структури, а не типів елементів. Вона корисна, коли API очікує JSON-масив, а не об’єкт.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
var_dump( array_is_list($ids) ); # bool(false)
echo json_encode( $ids ) . PHP_EOL; # {"0":10,"2":30}
echo json_encode( array_values($ids) ) . PHP_EOL; # [10,30]
```

**Важливо:** Порожній масив є списком. `array_filter()` зберігає ключі. Свідомо використовуйте `array_values()` для суцільних JSON-масивів; зберігайте ключі, якщо вони ідентифікують записи.

[Документація PHP: array_is_list()](https://www.php.net/manual/en/function.array-is-list.php)

<a id="predicate-search"></a>

## Пошук першого відповідного значення чи ключа

**PHP 8.4+ | Ядро**

`array_find()` повертає перше значення, що відповідає умові; `array_find_key()` повертає його ключ. Обидві функції зупиняються на першому збігу без створення проміжного відфільтрованого масиву.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 );
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 );
echo $name . ': ' . $score . PHP_EOL; # Linus: 12
```

**Важливо:** Умова отримує спочатку значення, потім ключ. Відсутність збігу дає null. Якщо знайдене значення саме може бути null, використовуйте `array_find_key()` і перевіряйте результат через !== null.

[Документація PHP: array_find()](https://www.php.net/manual/en/function.array-find.php) | [Документація PHP: array_find_key()](https://www.php.net/manual/en/function.array-find-key.php)

<a id="predicate-checks"></a>

## Перевірка будь-якого або всіх елементів

**PHP 8.4+ | Ядро**

`array_any()` перевіряє наявність хоча б одного відповідного елемента; `array_all()` вимагає відповідності всіх елементів. Обхід припиняється, щойно відповідь відома, тому пізніші виклики можуть не відбутися.

```php
$ages = [18, 22, 16];
var_dump( array_any($ages, fn( int $age ): bool => $age < 18) ); # bool(true)
var_dump( array_all($ages, fn( int $age ): bool => $age >= 18) ); # bool(false)
var_dump( array_any([], fn( $value ): bool => true) ); # bool(false)
var_dump( array_all([], fn( $value ): bool => false) ); # bool(true)
```

**Важливо:** all([]) дає true, адже немає елемента, що порушує умову. За потреби окремо перевіряйте непорожність. В умові не повинно бути обов’язкових побічних дій для кожного елемента.

[Документація PHP: array_any()](https://www.php.net/manual/en/function.array-any.php) | [Документація PHP: array_all()](https://www.php.net/manual/en/function.array-all.php)

<a id="boundary-values"></a>

## Безпосереднє читання першого чи останнього значення

**PHP 8.5+ | Ядро**

`array_first()` та `array_last()` читають крайні значення без зміни внутрішнього вказівника. Вони використовують порядок обходу, зокрема в асоціативних масивах. Якщо потрібне лише значення, додатковий пошук ключа зайвий.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
echo array_first( $stages ) . PHP_EOL; # Waiting
echo array_last( $stages ) . PHP_EOL; # Finished
var_dump( array_last([]) ); # NULL
```

**Важливо:** Порожній масив та крайній елемент із null обидва дають null. Якщо це важливо, окремо перевіряйте порожність масиву.

[Документація PHP: array_first()](https://www.php.net/manual/en/function.array-first.php) | [Документація PHP: array_last()](https://www.php.net/manual/en/function.array-last.php)

<a id="stable-sorting"></a>

## Стабільне сортування зберігає порядок рівних елементів

**PHP 8.0+ | Ядро**

З PHP 8.0 сортування зберігає початковий взаємний порядок елементів, що порівнюються як рівні. Уже встановлений додатковий порядок може зберегтися після сортування за іншим полем.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
echo implode( ', ', array_column($rows, 'name') ) . PHP_EOL; # Grace, Ada, Linus
```

**Важливо:** Повертайте з функції порівняння від’ємне ціле число, нуль або додатне ціле число. bool не виражає всі три випадки. `usort()` перенумеровує ключі, `uasort()` зберігає їхні зв’язки.

[Документація PHP: usort()](https://www.php.net/manual/en/function.usort.php)
