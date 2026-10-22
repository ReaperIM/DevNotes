# Arrays and iteration

[English](../en/04-arrays-and-iteration.md) | [Українська](../uk/04-arrays-and-iteration.md) | [Русский](../ru/04-arrays-and-iteration.md)

[Contents](../../README.md)

PHP arrays are ordered maps. Lists, records and ID-indexed lookups share a type but have different contracts. These examples make key behavior, streaming and missing values explicit. Each example runs independently.

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

**PHP 7.1+ | Core**

Short destructuring names the fields you need without repeatedly indexing the same row. It also works directly in foreach. Use it for fixed tuples such as an ID and a display name.

```php
$rows = [[7, 'Ada'], [9, 'Linus']];

foreach( $rows as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

**Remember:** This is assignment, not schema validation. A missing index produces a warning in PHP 8 and yields null. Validate external data first; skip a known position with [$first, , $third].

[PHP manual: destructuring](https://www.php.net/manual/en/migration71.new-features.php)

<a id="keyed-destructuring"></a>

## Read selected named fields

**PHP 7.1+ | Core**

Associative destructuring states which keys matter. Key order is irrelevant because PHP looks them up by name. This reads like selecting columns from a record.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row;
echo $user_id . ': ' . $user_name . PHP_EOL; # 7: Ada
```

**Remember:** Do not mix keyed and unkeyed entries at the same destructuring level. Establish defaults separately for optional fields.

[PHP manual: list()](https://www.php.net/manual/en/function.list.php)

<a id="reference-destructuring"></a>

## Destructure by reference to modify the original

**PHP 7.3+ | Core**

A reference inside destructuring connects a variable to the original array slot. Choose it when mutation is intentional; ordinary extraction does not need a reference.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15;
unset( $x );
echo json_encode( $point ) . PHP_EOL; # [15,20]
```

**Remember:** The binding survives assignment. unset($x) breaks that variable binding without deleting the array element.

[PHP manual: PHP 7.3](https://www.php.net/manual/en/migration73.new-features.php)

<a id="iterable"></a>

## Accept arrays and iterators with `iterable`

**PHP 7.1+ | Core**

Use `iterable` when a function needs foreach rather than array-specific operations. It accepts arrays and `Traversable` objects, including generators. Callers can supply values gradually instead of building an entire array.

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

**Remember:** `iterable` does not guarantee `count()`, indexed access, repeated traversal or element types. In PHP 8.2 it became an alias for array|`Traversable`.

[PHP manual: iterable](https://www.php.net/manual/en/language.types.iterable.php)

<a id="generator-delegation"></a>

## Join streams with yield from

**PHP 7.0+ | Core**

yield from forwards values from another `iterable`. A generator runs as values are requested, so a pipeline need not materialize all intermediate results. This is useful when composing several data sources.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}

$ids = iterator_to_array( page_ids(), false );
echo json_encode( $ids ) . PHP_EOL; # [10,11,12,13]
```

**Remember:** Delegation preserves keys. Duplicate keys can overwrite items in `iterator_to_array()`; pass false to collect a list. A generator is consumed as it runs and is not automatically reusable.

[PHP manual: generator syntax](https://www.php.net/manual/en/language.generators.syntax.php)

<a id="generator-return"></a>

## Get a generator’s final result

**PHP 7.0+ | Core**

A generator can yield intermediate values and return a separate summary. `getReturn()` reads that result after completion, keeping the summary distinct from the streamed items.

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

**Remember:** `getReturn()` throws before normal completion. Stopping foreach early does not make the generator reach its return statement.

[PHP manual: Generator::getReturn()](https://www.php.net/manual/en/generator.getreturn.php)

<a id="unpacking"></a>

## Build arrays with ... and understand collisions

**PHP 7.4+ | Core**

Unpacking inserts an array or `Traversable` into an array expression. Integer keys are renumbered. String keys are supported from PHP 8.1; a later value replaces an earlier value with the same string key.

```php
$first = [8 => 'a', 9 => 'b'];
echo json_encode( [...$first, 'c'] ) . PHP_EOL; # ["a","b","c"]

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]];
echo $config['timeout'] . PHP_EOL; # 30
```

**Remember:** The string-key example needs PHP 8.1. Array union with + follows different rules: existing keys on the left win and integer keys are preserved.

[PHP manual: array unpacking](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

<a id="merge-empty"></a>

## Merge zero or more arrays

**PHP 7.4+ | Core**

`array_merge()` and `array_merge_recursive()` can be called without arguments from PHP 7.4. A dynamic list of batches can therefore be unpacked even when there are no batches to merge.

```php
$batches = [];
echo json_encode( array_merge(...$batches) ) . PHP_EOL; # []

$batches = [[10, 20], [30]];
echo json_encode( array_merge(...$batches) ) . PHP_EOL; # [10,20,30]
```

**Remember:** `array_merge()` renumbers integer keys. `array_merge_recursive()` combines colliding string-key values into arrays, which often breaks configuration overrides; it is not simply a deeper merge.

[PHP manual: array_merge()](https://www.php.net/manual/en/function.array-merge.php)

<a id="boundary-keys"></a>

## Read first and last keys without moving a pointer

**PHP 7.3+ | Core**

`array_key_first()` and `array_key_last()` return keys in iteration order. They avoid the pointer side effects of `reset()` and `end()`. Use them when the key itself matters.

```php
$queue = [40 => 'first', 10 => 'last'];
var_dump( array_key_first($queue), array_key_last($queue) );
# int(40)
# int(10)
var_dump( array_key_first([]) ); # NULL
```

**Remember:** First is not the numerically smallest key. Empty arrays return null; key 0 is valid and must not be discarded by a truth check.

[PHP manual: array_key_first()](https://www.php.net/manual/en/function.array-key-first.php) | [PHP manual: array_key_last()](https://www.php.net/manual/en/function.array-key-last.php)

<a id="countable"></a>

## Check whether `count()` is valid

**PHP 7.3+ | Core**

`is_countable()` recognizes arrays and `Countable` objects. It asks the exact question before `count()`, which throws `TypeError` for unsuitable values in PHP 8.

```php
$values = new ArrayObject( [10, 20] );

if( is_countable($values) )
	echo count( $values ) . PHP_EOL; # 2

var_dump( is_countable(null) ); # bool(false)
```

**Remember:** An `iterable` is not necessarily countable. A generator can be traversed, but generally cannot be counted without consuming it.

[PHP manual: is_countable()](https://www.php.net/manual/en/function.is-countable.php)

<a id="list-arrays"></a>

## Recognize lists and preserve JSON array shape

**PHP 8.1+ | Core**

`array_is_list()` checks for consecutive integer keys starting at zero. It checks structure, not element types. This is useful when an API expects a JSON array rather than an object.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
var_dump( array_is_list($ids) ); # bool(false)
echo json_encode( $ids ) . PHP_EOL; # {"0":10,"2":30}
echo json_encode( array_values($ids) ) . PHP_EOL; # [10,30]
```

**Remember:** An empty array is a list. `array_filter()` preserves keys. Use `array_values()` deliberately for dense JSON arrays; preserve keys when they identify records.

[PHP manual: array_is_list()](https://www.php.net/manual/en/function.array-is-list.php)

<a id="predicate-search"></a>

## Find the first matching value or key

**PHP 8.4+ | Core**

`array_find()` returns the first value satisfying a predicate; `array_find_key()` returns its key. Both stop at the first match without building an intermediate filtered array.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 );
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 );
echo $name . ': ' . $score . PHP_EOL; # Linus: 12
```

**Remember:** The predicate receives value first, key second. Absence returns null. If a matching value can itself be null, use `array_find_key()` and test the result with !== null.

[PHP manual: array_find()](https://www.php.net/manual/en/function.array-find.php) | [PHP manual: array_find_key()](https://www.php.net/manual/en/function.array-find-key.php)

<a id="predicate-checks"></a>

## Ask whether any or all items match

**PHP 8.4+ | Core**

`array_any()` checks whether at least one item matches; `array_all()` requires every item to match. They stop once the answer is known, so later callbacks may not run.

```php
$ages = [18, 22, 16];
var_dump( array_any($ages, fn( int $age ): bool => $age < 18) ); # bool(true)
var_dump( array_all($ages, fn( int $age ): bool => $age >= 18) ); # bool(false)
var_dump( array_any([], fn( $value ): bool => true) ); # bool(false)
var_dump( array_all([], fn( $value ): bool => false) ); # bool(true)
```

**Remember:** all([]) is true because no item violates the condition. Check non-emptiness separately when required. Predicates should not contain side effects that must run for every element.

[PHP manual: array_any()](https://www.php.net/manual/en/function.array-any.php) | [PHP manual: array_all()](https://www.php.net/manual/en/function.array-all.php)

<a id="boundary-values"></a>

## Read the first or last value directly

**PHP 8.5+ | Core**

`array_first()` and `array_last()` read boundary values without moving the internal pointer. They follow iteration order, including for associative arrays. This removes the extra key lookup when only the value is needed.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
echo array_first( $stages ) . PHP_EOL; # Waiting
echo array_last( $stages ) . PHP_EOL; # Finished
var_dump( array_last([]) ); # NULL
```

**Remember:** An empty array and a boundary element containing null both return null. Check whether the array is empty if that distinction matters.

[PHP manual: array_first()](https://www.php.net/manual/en/function.array-first.php) | [PHP manual: array_last()](https://www.php.net/manual/en/function.array-last.php)

<a id="stable-sorting"></a>

## Stable sorting keeps equal items in order

**PHP 8.0+ | Core**

Sorting functions preserve the original relative order of items that compare equal from PHP 8.0. A previously established secondary order can therefore survive a later sort by another field.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
echo implode( ', ', array_column($rows, 'name') ) . PHP_EOL; # Grace, Ada, Linus
```

**Remember:** Return a negative integer, zero or a positive integer from a comparator. A boolean cannot correctly express all three cases. `usort()` renumbers keys; `uasort()` preserves key associations.

[PHP manual: usort()](https://www.php.net/manual/en/function.usort.php)
