# Arrays and iteration

[English](../en/04-arrays-and-iteration.md) | [Українська](../uk/04-arrays-and-iteration.md) | [Русский](../ru/04-arrays-and-iteration.md)

[Contents](../../README.md)

PHP arrays preserve insertion order. The notes below cover working with keys, unpacking arrays and iterating over data.

- [Unpack a row into variables](#destructuring)
- [Read selected named fields](#keyed-destructuring)
- [Destructure by reference to modify the original](#reference-destructuring)
- [Accept arrays and iterators with `iterable`](#iterable)
- [Join streams with yield from](#generator-delegation)
- [Get a generator’s final result](#generator-return)
- [Build arrays with ... and understand collisions](#unpacking)
- [Merge zero or more arrays](#merge-empty)
- [Read first and last keys without moving a pointer](#boundary-keys)
- [Check whether `count()` is valid](#countable)
- [Recognize lists and preserve JSON array shape](#list-arrays)
- [Find the first matching value or key](#predicate-search)
- [Ask whether any or all items match](#predicate-checks)
- [Read the first or last value directly](#boundary-values)
- [Stable sorting keeps equal items in order](#stable-sorting)

<a id="destructuring"></a>

## Unpack a row into variables

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Destructuring assigns array elements to named variables without separate index lookups. It also works in `foreach`; an empty position skips an element.

```php
[$id, , $name] = [7, true, 'Ada']; # $id: 7, $name: 'Ada'

foreach( [[7, 'Ada'], [9, 'Linus']] as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

Missing indexes warn and yield `null`; destructuring does not validate a record.

<a id="keyed-destructuring"></a>

## Read selected named fields

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/function.list.php)

Keyed destructuring selects fields independently of their order.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row; # 7, 'Ada'
```

Keyed and unkeyed entries cannot be mixed at the same level; optional keys need defaults.

<a id="reference-destructuring"></a>

## Destructure by reference to modify the original

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/migration73.new-features.php)

References in destructuring bind variables to the original array slots.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15; # $point is [15, 20]
unset( $x );
```

`unset($x)` breaks the binding without removing the array element.

<a id="iterable"></a>

## Accept arrays and iterators with `iterable`

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

`iterable` accepts arrays and `Traversable` objects, including generators. Use this contract when the function needs iteration rather than array-specific operations.

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

It guarantees neither element types, `count()`, indexed access nor repeated traversal; since 8.2 it aliases `array|Traversable`.

<a id="generator-delegation"></a>

## Join streams with yield from

**PHP 7.0+ | Core** | [PHP](https://www.php.net/manual/en/language.generators.syntax.php)

`yield from` delegates iteration to another iterable, composing sources without building an intermediate array. Values are produced as the generator is consumed.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}
$ids = iterator_to_array( page_ids(), false ); # [10, 11, 12, 13]
```

Keys are preserved: duplicates overwrite in `iterator_to_array()` unless its second argument is `false`; generators are not automatically reusable.

<a id="generator-return"></a>

## Get a generator’s final result

**PHP 7.0+ | Core** | [PHP](https://www.php.net/manual/en/generator.getreturn.php)

`Generator::getReturn()` retrieves the return value separately from yielded items.

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

It throws before normal completion; breaking out of `foreach` does not reach the generator’s `return`.

<a id="unpacking"></a>

## Build arrays with ... and understand collisions

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

Array unpacking accepts arrays and `Traversable`; integer keys are renumbered, and string keys are supported from 8.1.

```php
$items = [...[8 => 'a', 9 => 'b'], 'c']; # ['a', 'b', 'c']

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]]; # timeout: 30
```

Later string keys win; array union with `+` instead preserves integer keys and keeps values from the left.

<a id="merge-empty"></a>

## Merge zero or more arrays

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/function.array-merge.php)

`array_merge()` and `array_merge_recursive()` accept zero arguments from 7.4. A dynamic batch list can therefore be unpacked even when it is empty.

```php
$batches = [];
$items = array_merge( ...$batches ); # []
$batches = [[10, 20], [30]];
$items = array_merge( ...$batches ); # [10, 20, 30]
```

`array_merge()` renumbers integer keys; recursive merging turns colliding string-key values into arrays rather than overriding them.

<a id="boundary-keys"></a>

## Read first and last keys without moving a pointer

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/function.array-key-first.php) | [PHP](https://www.php.net/manual/en/function.array-key-last.php)

`array_key_first()` and `array_key_last()` follow iteration order without changing the internal pointer.

```php
$queue = [40 => 'first', 10 => 'last'];
$first_key = array_key_first( $queue ); # 40
$last_key = array_key_last( $queue ); # 10
$missing = array_key_first( [] ); # null
```

An empty array returns `null`; key `0` is valid and needs `!== null`, not a truth check.

<a id="countable"></a>

## Check whether `count()` is valid

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/function.is-countable.php)

`is_countable()` recognizes arrays and `Countable` objects before calling `count()`.

```php
$values = new ArrayObject( [10, 20] );
if( is_countable($values) )
	$count = count( $values ); # 2
```

An iterable need not be countable; `count()` on an unsuitable value throws `TypeError` in PHP 8.

<a id="list-arrays"></a>

## Recognize lists and preserve JSON array shape

**PHP 8.1+ | Core** | [PHP](https://www.php.net/manual/en/function.array-is-list.php)

`array_is_list()` checks for consecutive integer keys starting at zero; an empty array is a list.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
$is_list = array_is_list( $ids ); # false
$object_json = json_encode( $ids ); # {"0":10,"2":30}
$list_json = json_encode( array_values($ids) ); # [10,30]
```

`array_filter()` preserves keys; use `array_values()` when the result must encode as a JSON array.

<a id="predicate-search"></a>

## Find the first matching value or key

**PHP 8.4+ | Core** | [PHP](https://www.php.net/manual/en/function.array-find.php) | [PHP](https://www.php.net/manual/en/function.array-find-key.php)

`array_find()` returns the first matching value; `array_find_key()` returns its key. Both stop at that match; the predicate receives value, then key.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 ); # 12
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 ); # Linus
```

Both return `null` when absent; use the key variant and `!== null` if a matching value may itself be `null`.

<a id="predicate-checks"></a>

## Ask whether any or all items match

**PHP 8.4+ | Core** | [PHP](https://www.php.net/manual/en/function.array-any.php) | [PHP](https://www.php.net/manual/en/function.array-all.php)

`array_any()` asks whether at least one item matches; `array_all()` requires every item to match. They stop as soon as the result is known.

```php
$ages = [18, 22, 16];
$has_minor = array_any( $ages, fn( int $age ): bool => $age < 18 ); # true
$all_adults = array_all( $ages, fn( int $age ): bool => $age >= 18 ); # false
$any_empty = array_any( [], fn( $value ): bool => true ); # false
$all_empty = array_all( [], fn( $value ): bool => false ); # true
```

`array_all([])` is `true`; require non-emptiness separately, and do not rely on every callback running.

<a id="boundary-values"></a>

## Read the first or last value directly

**PHP 8.5+ | Core** | [PHP](https://www.php.net/manual/en/function.array-first.php) | [PHP](https://www.php.net/manual/en/function.array-last.php)

`array_first()` and `array_last()` read values in iteration order without moving the internal pointer.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
$first = array_first( $stages ); # Waiting
$last = array_last( $stages ); # Finished
$missing = array_last( [] ); # null
```

Both an empty array and a boundary element containing `null` return `null`.

<a id="stable-sorting"></a>

## Stable sorting keeps equal items in order

**PHP 8.0+ | Core** | [PHP](https://www.php.net/manual/en/function.usort.php)

Sorting is stable from 8.0: elements comparing equal keep their original relative order.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
$names = array_column( $rows, 'name' ); # ['Grace', 'Ada', 'Linus']
```

Comparators must return a negative integer, zero or a positive integer; `usort()` renumbers keys, while `uasort()` preserves associations.
