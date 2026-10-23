# Масиви та обхід даних

[EN](../en/04-arrays-and-iteration.md) | [UK](../uk/04-arrays-and-iteration.md) | [RU](../ru/04-arrays-and-iteration.md)

[Зміст](../../README.uk.md)

Масиви PHP зберігають порядок додавання елементів. Як узяти потрібні поля, зібрати кілька масивів і пройти дані без зайвих копій. Заодно розбираємося з ключами: вони впливають і на злиття, і на те, що зрештою потрапить у JSON.

- [Розкладаємо масив у змінні](#destructuring)
- [Беремо потрібні поля за ключами](#keyed-destructuring)
- [Змінюємо початковий елемент через посилання](#reference-destructuring)
- [Перебираємо масиви й ітератори однаково](#iterable)
- [Віддаємо значення з кількох джерел через `yield from`](#generator-delegation)
- [Отримуємо підсумок роботи генератора](#generator-return)
- [Збираємо масив через `...`](#unpacking)
- [З’єднуємо набори, навіть якщо наборів немає](#merge-empty)
- [Беремо перший і останній ключ](#boundary-keys)
- [Перевіряємо, чи можна викликати `count()`](#countable)
- [Перевіряємо список перед виведенням у JSON](#list-arrays)
- [Шукаємо перше значення за умовою](#predicate-search)
- [Перевіряємо хоча б один елемент або всі одразу](#predicate-checks)
- [Беремо перше або останнє значення](#boundary-values)
- [Зберігаємо порядок за рівних значень](#stable-sorting)

<a id="destructuring"></a>
## Розкладаємо масив у змінні

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Замість кількох звернень за індексом можна одразу розкласти масив у потрібні змінні. Це називається деструктуризацією. Порожнє місце між комами пропускає елемент, а у `foreach` такий запис розбирає кожен рядок під час обходу.

```php
[$id, , $name] = [7, true, 'Ada']; # $id: 7, $name: 'Ada'

foreach( [[7, 'Ada'], [9, 'Linus']] as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

PHP очікує, що вказані позиції існують. Якщо елемента немає, отримаємо попередження й `null`, тому сам цей запис не замінює перевірку вхідних даних.

<a id="keyed-destructuring"></a>
## Беремо потрібні поля за ключами

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.list.php)

Коли поля мають імена, можна одразу вказати, який ключ у яку змінну взяти. Порядок полів у початковому масиві вже не важливий, зайві поля просто не беремо.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row; # 7, 'Ada'
```

На одному рівні не можна змішати запис за ключами й запис лише за позиціями. Якщо поле необов’язкове, спочатку підставляємо для нього значення за замовчуванням, інакше отримаємо попередження про відсутній ключ.

<a id="reference-destructuring"></a>
## Змінюємо початковий елемент через посилання

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/migration73.new-features.php)

Знак `&` залишає змінну пов’язаною з елементом масиву. Тому присвоєння `$x = 15` змінює й перший елемент `$point`, а не лише окрему копію значення.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15; # у $point тепер [15, 20]
unset( $x );
```

Коли зв’язок більше не потрібен, прибираємо його через `unset($x)`. Сам елемент масиву при цьому залишається на місці.

<a id="iterable"></a>
## Перебираємо масиви й ітератори однаково

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

Якщо функція просто проходить значення через `foreach`, їй не обов’язково вимагати масив. `iterable` приймає масиви й об’єкти `Traversable`, зокрема генератори. Тому `sum_values()` може так само порахувати суму з `ArrayIterator`.

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

Але `iterable` не обіцяє доступу за індексом, можливості викликати `count()` чи пройти дані ще раз. Типи самих елементів він теж не перевіряє. Із PHP 8.2 це ім’я для типу `array|Traversable`.

<a id="generator-delegation"></a>
## Віддаємо значення з кількох джерел через `yield from`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/language.generators.syntax.php)

`yield from` дозволяє генератору віддавати значення іншого масиву чи об’єкта для перебору. Тут спочатку йдуть `10` та `11`, потім `12` та `13`. Проміжний спільний масив не потрібен, значення надходять у міру обходу.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}
# однакові ключі не мають затерти значення з першого масиву
$ids = iterator_to_array( page_ids(), false ); # [10, 11, 12, 13]
```

Ключі при цьому зберігаються. В обох масивів у прикладі вони починаються з нуля, тому в `iterator_to_array()` передаємо `false`: нам потрібні всі значення, а не перезапис за однаковими ключами. Для нового обходу вже завершеного генератора створюємо новий екземпляр.

<a id="generator-return"></a>
## Отримуємо підсумок роботи генератора

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/generator.getreturn.php)

Генератор має два різні результати: значення, які поступово віддає через `yield`, і підсумок із `return`. Підсумок читаємо окремо через `getReturn()`, коли генератор уже закінчив роботу.

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

Тут у `$ids` потрапить `[10]`, а в `$count` число `1`. Якщо викликати `getReturn()` до нормального завершення, отримаємо виняток. Звичайний `break` із `foreach` сам по собі не доводить генератор до його `return`.

<a id="unpacking"></a>
## Збираємо масив через `...`

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

Через `...` можна вставити в новий масив елементи іншого масиву чи `Traversable`. Числові ключі при цьому перераховуються з нуля: старі `8` та `9` у першому прикладі не збережуться. Рядкові ключі підтримуються з PHP 8.1.

```php
$items = [...[8 => 'a', 9 => 'b'], 'c']; # ['a', 'b', 'c']

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]]; # timeout: 30
```

Якщо рядковий ключ трапляється повторно, залишається пізніше значення, тому `timeout` стане `30`. Об’єднання через `+` має інші правила: числові ключі зберігаються, а за збігу ключа залишається значення ліворуч.

<a id="merge-empty"></a>
## З’єднуємо набори, навіть якщо наборів немає

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-merge.php)

Список масивів може виявитися порожнім. Із PHP 7.4 для цього не потрібна окрема гілка: `array_merge()` та `array_merge_recursive()` дозволено викликати без аргументів. Тому розпакування `...$batches` працює в обох випадках.

```php
$batches = [];
$items = array_merge( ...$batches ); # []
$batches = [[10, 20], [30]];
$items = array_merge( ...$batches ); # [10, 20, 30]
```

Звичайний `array_merge()` перераховує числові ключі. Рекурсивний варіант поводиться інакше за однакових рядкових ключів: збирає їхні значення в масив, а не просто замінює старе новим.

<a id="boundary-keys"></a>
## Беремо перший і останній ключ

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-key-first.php) | [PHP](https://www.php.net/manual/en/function.array-key-last.php)

`array_key_first()` та `array_key_last()` беруть ключі за порядком елементів, а не за величиною чисел. Тому перший ключ тут `40`, останній `10`. Внутрішній вказівник масиву ці функції не рухають.

```php
$queue = [40 => 'first', 10 => 'last'];
$first_key = array_key_first( $queue ); # 40
$last_key = array_key_last( $queue ); # 10
$missing = array_key_first( [] ); # null
```

Для порожнього масиву отримаємо `null`. Ключ `0` при цьому цілком допустимий, тому наявність результату перевіряємо через `!== null`, а не звичайним `if($key)`.

<a id="countable"></a>
## Перевіряємо, чи можна викликати `count()`

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.is-countable.php)

Не все, що можна перебрати, можна одразу порахувати. `is_countable()` перевіряє, що перед нами масив або об’єкт `Countable`, і після цього можна викликати `count()`.

```php
$values = new ArrayObject( [10, 20] );
if( is_countable($values) )
	$count = count( $values ); # 2
```

У прикладі `ArrayObject` підходить, отримуємо `2`. Якщо в PHP 8 передати `count()` непридатне значення, замість кількості буде `TypeError`.

<a id="list-arrays"></a>
## Перевіряємо список перед виведенням у JSON

**PHP 8.1+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-is-list.php)

Для списку потрібні цілі ключі поспіль від нуля: `0, 1, 2`. Це перевіряє `array_is_list()`; порожній масив теж вважається списком. Після видалення середнього елемента у прикладі залишається пропуск, і JSON уже буде об’єктом, а не масивом.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
$is_list = array_is_list( $ids ); # false
$object_json = json_encode( $ids ); # {"0":10,"2":30}
$list_json = json_encode( array_values($ids) ); # [10,30]
```

`array_values()` збирає ключі заново, після нього отримуємо JSON-масив `[10,30]`. Така сама ситуація буває після `array_filter()`: він зберігає початкові ключі.

<a id="predicate-search"></a>
## Шукаємо перше значення за умовою

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-find.php) | [PHP](https://www.php.net/manual/en/function.array-find-key.php)

`array_find()` віддає перше значення, яке пройшло перевірку, а `array_find_key()` віддає його ключ. У прикладі першим відповідним результатом буде `12` у `Linus`; до `Grace` пошук уже не дійде.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 ); # 12
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 ); # Linus
```

Функція перевірки отримує спочатку значення, потім ключ. Якщо нічого не знайдено, обидві функції повернуть `null`. Коли саме шукане значення теж може бути `null`, надійніше шукати ключ і перевіряти його через `!== null`.

<a id="predicate-checks"></a>
## Перевіряємо хоча б один елемент або всі одразу

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-any.php) | [PHP](https://www.php.net/manual/en/function.array-all.php)

`array_any()` відповідає, чи є хоча б один відповідний елемент. `array_all()` перевіряє, чи підходять усі. Тут неповнолітній є, тому перша перевірка дає `true`, а твердження «усі дорослі» вже `false`.

```php
$ages = [18, 22, 16];
$has_minor = array_any( $ages, fn( int $age ): bool => $age < 18 ); # true
$all_adults = array_all( $ages, fn( int $age ): bool => $age >= 18 ); # false
$any_empty = array_any( [], fn( $value ): bool => true ); # false
$all_empty = array_all( [], fn( $value ): bool => false ); # true
```

Обидві функції зупиняються, щойно відповідь відома, тому не розраховуємо на виклик перевірки для кожного елемента. Для порожнього масиву `array_any()` повертає `false`, а `array_all()` повертає `true`. Якщо за змістом масив іще й має бути непорожнім, це перевіряємо окремо.

<a id="boundary-values"></a>
## Беремо перше або останнє значення

**PHP 8.5+ | Ядро** | [PHP](https://www.php.net/manual/en/function.array-first.php) | [PHP](https://www.php.net/manual/en/function.array-last.php)

Якщо потрібен сам елемент, а не його ключ, є `array_first()` та `array_last()`. Вони йдуть за порядком масиву й не рухають його внутрішній вказівник.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
$first = array_first( $stages ); # Waiting
$last = array_last( $stages ); # Finished
$missing = array_last( [] ); # null
```

У прикладі отримуємо `Waiting` і `Finished`. Порожній масив поверне `null`, але такий самий результат буде, якщо крайній елемент сам містить `null`. За одним результатом ці випадки не розрізнити.

<a id="stable-sorting"></a>
## Зберігаємо порядок за рівних значень

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.usort.php)

Із PHP 8.0 сортування стабільне: якщо порівняння вважає два елементи рівними, вони залишаються в попередньому порядку один відносно одного. Тому після сортування за `score` `Ada` все ще йде перед `Linus`.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
$names = array_column( $rows, 'name' ); # ['Grace', 'Ada', 'Linus']
```

Функція порівняння має повертати ціле число: менше нуля, нуль або більше нуля. `usort()` при цьому перераховує ключі. Якщо треба зберегти зв’язок із початковими ключами, є `uasort()`.
