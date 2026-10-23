# Arrays and iteration

[EN](../en/04-arrays-and-iteration.md) | [UK](../uk/04-arrays-and-iteration.md) | [RU](../ru/04-arrays-and-iteration.md)

[Contents](../../README.md)

PHP arrays preserve insertion order. Take the fields you need, combine arrays and iterate without unnecessary copies. Keys matter along the way: they affect both merging and the shape of the JSON you end up with.

- [Unpack an array into variables](#destructuring)
- [Take selected fields by key](#keyed-destructuring)
- [Change the original element through a reference](#reference-destructuring)
- [Iterate over arrays and iterators the same way](#iterable)
- [Yield values from several sources with `yield from`](#generator-delegation)
- [Get a generator’s final result](#generator-return)
- [Build an array with `...`](#unpacking)
- [Merge batches even when there are none](#merge-empty)
- [Get the first and last keys](#boundary-keys)
- [Check whether `count()` can be used](#countable)
- [Check for a list before encoding JSON](#list-arrays)
- [Find the first value that matches a condition](#predicate-search)
- [Check whether any item or every item matches](#predicate-checks)
- [Get the first or last value](#boundary-values)
- [Keep the original order when values compare equal](#stable-sorting)

<a id="destructuring"></a>
## Unpack an array into variables

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Instead of reading each index separately, assign the array’s elements to several variables at once. This is called destructuring. An empty position between commas skips an element, and the same syntax in `foreach` unpacks each row as it is visited.

```php
[$id, , $name] = [7, true, 'Ada']; # $id: 7, $name: 'Ada'

foreach( [[7, 'Ada'], [9, 'Linus']] as [$id, $name] )
	echo $id . ': ' . $name . PHP_EOL;
# 7: Ada
# 9: Linus
```

PHP expects the requested positions to exist. A missing element produces a warning and `null`, so this syntax does not replace input validation.

<a id="keyed-destructuring"></a>
## Take selected fields by key

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/function.list.php)

For named fields, say which key should go into which variable. Their order in the original array no longer matters, and fields we do not ask for are left out.

```php
$row = ['name' => 'Ada', 'id' => 7, 'active' => true];
['id' => $user_id, 'name' => $user_name] = $row; # 7, 'Ada'
```

Keyed and positional entries cannot be mixed at the same level. Supply defaults for optional fields first; otherwise a missing key produces a warning.

<a id="reference-destructuring"></a>
## Change the original element through a reference

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/migration73.new-features.php)

The `&` keeps the variable linked to the array element. Assigning `$x = 15` therefore changes the first element of `$point` too, rather than only a separate copy of its value.

```php
$point = [10, 20];
[&$x, $y] = $point;
$x = 15; # $point is [15, 20]
unset( $x );
```

Use `unset($x)` when that link is no longer needed. The array element itself stays in place.

<a id="iterable"></a>
## Iterate over arrays and iterators the same way

**PHP 7.1+ | Core** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

If a function only goes through values with `foreach`, it does not need to require an array. `iterable` accepts arrays and `Traversable` objects, including generators. `sum_values()` can therefore sum values from an `ArrayIterator` too.

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

But `iterable` does not promise indexed access, support for `count()` or another pass over the data. It does not check element types either. From PHP 8.2 it is an alias for `array|Traversable`.

<a id="generator-delegation"></a>
## Yield values from several sources with `yield from`

**PHP 7.0+ | Core** | [PHP](https://www.php.net/manual/en/language.generators.syntax.php)

`yield from` lets a generator pass on values from another array or iterable object. Here `10` and `11` come first, followed by `12` and `13`. There is no need to build a combined array in between; values arrive as the generator is consumed.

```php
function page_ids(): Generator
{
	yield from [10, 11];
	yield from [12, 13];
}
# keep every value instead of overwriting repeated keys
$ids = iterator_to_array( page_ids(), false ); # [10, 11, 12, 13]
```

Keys are preserved. Both arrays here start their keys at zero, so `iterator_to_array()` gets `false`: we want every value rather than later values overwriting earlier ones with the same keys. Create a new generator instance for another pass after it has completed.

<a id="generator-return"></a>
## Get a generator’s final result

**PHP 7.0+ | Core** | [PHP](https://www.php.net/manual/en/generator.getreturn.php)

A generator has two kinds of results: values it produces through `yield` and the final value from `return`. Read that final value separately with `getReturn()` once the generator has finished.

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

Here `$ids` gets `[10]` and `$count` gets `1`. Calling `getReturn()` before normal completion throws an exception. A `break` out of `foreach` does not by itself run the generator through to its `return`.

<a id="unpacking"></a>
## Build an array with `...`

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/language.types.array.php#language.types.array.unpacking)

Use `...` to insert the elements of an array or `Traversable` into a new array. Integer keys are renumbered from zero, so the original `8` and `9` in the first example are not kept. String keys are supported from PHP 8.1.

```php
$items = [...[8 => 'a', 9 => 'b'], 'c']; # ['a', 'b', 'c']

# PHP 8.1+
$defaults = ['timeout' => 10, 'debug' => false];
$config = [...$defaults, ...['timeout' => 30]]; # timeout: 30
```

For a repeated string key, the later value wins, so `timeout` becomes `30`. Array union with `+` follows different rules: it preserves integer keys and keeps the left-hand value when keys collide.

<a id="merge-empty"></a>
## Merge batches even when there are none

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/function.array-merge.php)

A list of arrays may turn out to be empty. From PHP 7.4, that needs no special branch: `array_merge()` and `array_merge_recursive()` accept zero arguments. Unpacking `...$batches` therefore works in both cases.

```php
$batches = [];
$items = array_merge( ...$batches ); # []
$batches = [[10, 20], [30]];
$items = array_merge( ...$batches ); # [10, 20, 30]
```

Regular `array_merge()` renumbers integer keys. The recursive version handles repeated string keys differently: it collects their values into an array rather than simply replacing the earlier value.

<a id="boundary-keys"></a>
## Get the first and last keys

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/function.array-key-first.php) | [PHP](https://www.php.net/manual/en/function.array-key-last.php)

`array_key_first()` and `array_key_last()` follow element order, not numeric key size. The first key here is therefore `40` and the last is `10`. Neither function moves the array’s internal pointer.

```php
$queue = [40 => 'first', 10 => 'last'];
$first_key = array_key_first( $queue ); # 40
$last_key = array_key_last( $queue ); # 10
$missing = array_key_first( [] ); # null
```

An empty array gives `null`. Zero is a valid key, so check for a result with `!== null` rather than a truth check such as `if($key)`.

<a id="countable"></a>
## Check whether `count()` can be used

**PHP 7.3+ | Core** | [PHP](https://www.php.net/manual/en/function.is-countable.php)

Being iterable does not automatically make something countable. `is_countable()` checks for an array or a `Countable` object before we use `count()`.

```php
$values = new ArrayObject( [10, 20] );
if( is_countable($values) )
	$count = count( $values ); # 2
```

The `ArrayObject` here qualifies and gives `2`. In PHP 8, passing an unsuitable value to `count()` throws `TypeError` instead of giving a count.

<a id="list-arrays"></a>
## Check for a list before encoding JSON

**PHP 8.1+ | Core** | [PHP](https://www.php.net/manual/en/function.array-is-list.php)

A list needs consecutive integer keys starting at zero: `0, 1, 2`. `array_is_list()` checks that, and an empty array counts as a list too. Removing the middle element here leaves a gap, so JSON encoding produces an object rather than an array.

```php
$ids = [10, 20, 30];
unset( $ids[1] );
$is_list = array_is_list( $ids ); # false
$object_json = json_encode( $ids ); # {"0":10,"2":30}
$list_json = json_encode( array_values($ids) ); # [10,30]
```

`array_values()` rebuilds the keys, giving us the JSON array `[10,30]`. The same issue can appear after `array_filter()`, which preserves the original keys.

<a id="predicate-search"></a>
## Find the first value that matches a condition

**PHP 8.4+ | Core** | [PHP](https://www.php.net/manual/en/function.array-find.php) | [PHP](https://www.php.net/manual/en/function.array-find-key.php)

`array_find()` returns the first value that passes the check, while `array_find_key()` returns its key. Here the first match is `12` for `Linus`; the search never needs to reach `Grace`.

```php
$scores = ['Ada' => 7, 'Linus' => 12, 'Grace' => 15];
$score = array_find( $scores, fn( int $value ): bool => $value >= 10 ); # 12
$name = array_find_key( $scores, fn( int $value ): bool => $value >= 10 ); # Linus
```

The checking function receives the value first, then its key. Both searches return `null` when nothing matches. If a matching value can itself be `null`, search for the key instead and check it with `!== null`.

<a id="predicate-checks"></a>
## Check whether any item or every item matches

**PHP 8.4+ | Core** | [PHP](https://www.php.net/manual/en/function.array-any.php) | [PHP](https://www.php.net/manual/en/function.array-all.php)

`array_any()` asks whether at least one item matches. `array_all()` asks whether every item does. There is a minor here, so the first check is `true` and the claim that everyone is an adult is `false`.

```php
$ages = [18, 22, 16];
$has_minor = array_any( $ages, fn( int $age ): bool => $age < 18 ); # true
$all_adults = array_all( $ages, fn( int $age ): bool => $age >= 18 ); # false
$any_empty = array_any( [], fn( $value ): bool => true ); # false
$all_empty = array_all( [], fn( $value ): bool => false ); # true
```

Both stop as soon as the answer is known, so do not rely on the checking function running for every element. On an empty array, `array_any()` returns `false` and `array_all()` returns `true`. Check non-emptiness separately if the array also needs to contain something.

<a id="boundary-values"></a>
## Get the first or last value

**PHP 8.5+ | Core** | [PHP](https://www.php.net/manual/en/function.array-first.php) | [PHP](https://www.php.net/manual/en/function.array-last.php)

Use `array_first()` and `array_last()` when you need the element itself rather than its key. They follow array order without moving the internal pointer.

```php
$stages = ['queued' => 'Waiting', 'done' => 'Finished'];
$first = array_first( $stages ); # Waiting
$last = array_last( $stages ); # Finished
$missing = array_last( [] ); # null
```

Here they give `Waiting` and `Finished`. An empty array returns `null`, but so does a boundary element that contains `null`. The return value alone cannot tell those cases apart.

<a id="stable-sorting"></a>
## Keep the original order when values compare equal

**PHP 8.0+ | Core** | [PHP](https://www.php.net/manual/en/function.usort.php)

Sorting is stable from PHP 8.0: when two elements compare equal, their order relative to each other is preserved. That is why `Ada` still comes before `Linus` after sorting by `score`.

```php
$rows = [
	['name' => 'Ada', 'score' => 10],
	['name' => 'Linus', 'score' => 10],
	['name' => 'Grace', 'score' => 5],
];
usort( $rows, fn( array $a, array $b ): int => $a['score'] <=> $b['score'] );
$names = array_column( $rows, 'name' ); # ['Grace', 'Ada', 'Linus']
```

The comparison function must return an integer below zero, zero or an integer above zero. `usort()` also renumbers the keys. Use `uasort()` when the original key associations need to stay intact.
