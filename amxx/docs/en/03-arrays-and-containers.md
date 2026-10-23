# Arrays and containers

[EN](../en/03-arrays-and-containers.md) | [UK](../uk/03-arrays-and-containers.md) | [RU](../ru/03-arrays-and-containers.md)

[Contents](../../README.md)

- [Fixed arrays and valid indexes](#fixed-array-bounds)
- [Rows, columns and `sizeof`](#multidimensional-size)
- [Keep record fields together with `enum`](#enum-records)
- [`Array` handles: capacity is not size](#dynamic-cell-array)
- [Dynamic arrays of strings](#dynamic-strings)
- [Read, change and write a dynamic record](#dynamic-records)
- [Delete entries without skipping the next one](#delete-while-iterating)
- [Make and release a separate `Array` copy](#clone-and-ownership)
- [`Trie` lookup: missing is not zero](#trie-presence-and-zero)
- [Snapshot of `Trie` keys](#trie-snapshot)
- [Built-in integer and `Array` sorting](#built-in-sorting)
- [What `ArraySort` and `ArraySortEx` pass to the handler](#custom-sorting)

<a id="fixed-array-bounds"></a>
## Fixed arrays and valid indexes

An ordinary array has a size known at compile time. The `scores` array below has three elements, at indexes `0`, `1` and `2`. Before reading an index received from elsewhere, check both ends: it must not be negative or greater than `sizeof scores - 1`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new scores[] = { 10, 20, 30 };
	new index = 2;

	if( index >= 0 && index < sizeof scores )
		server_print( "score=%d", scores[index] );
	// score=30
}
```

If an array is only partly filled, keep a separate count of its records. Capacity tells us how much could fit; the count tells us how much has actually been stored. Use that count when reading the filled part.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="multidimensional-size"></a>
## Rows, columns and `sizeof`

The declaration `names[3][16]` gives us three rows with 16 cells each. `sizeof names` returns the row count, and `sizeof names[]` returns the size of one row. This keeps loops and buffers tied to the array declaration without repeating the sizes by hand.

```pawn
#include <amxmodx>

public plugin_init()
{
	new names[3][16] = { "Ada", "Linus", "Grace" };

	server_print( "rows=%d row_cells=%d", sizeof names, sizeof names[] );

	for( new index; index < sizeof names; index++ )
		server_print( "%s", names[index] );
	// rows=3 row_cells=16
	// Ada
	// Linus
	// Grace
}
```

`sizeof` measures allocated space, not the length of the stored text. Each string still needs one cell for its terminating zero.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Two_Dimensional_Arrays)

<a id="enum-records"></a>
## Keep record fields together with `enum`

A player’s score, speed and name are useful together. The `enum` below defines where each field sits in the array and how much space it takes. Names such as `player_score` replace numeric offsets, without needing a separate array for every field.

```pawn
#include <amxmodx>

enum _:player_data
{
	player_score,
	Float:player_speed,
	player_name[32]
};

public plugin_init()
{
	new player[player_data];
	player[player_score] = 42;
	player[player_speed] = 250.0;
	formatex( player[player_name], charsmax( player[player_name] ), "%s", "Ada" );

	server_print( "%s score=%d speed=%.1f", player[player_name], player[player_score], player[player_speed] );
	// Ada score=42 speed=250.0
}
```

This is still an array with a field layout. Check the buffer size expected by a native yourself: `enum` does not add runtime bounds checks or turn the record into a class or C struct.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="dynamic-cell-array"></a>
## `Array` handles: capacity is not size

Use a dynamic `Array` when you do not know the record count in advance. `ArrayCreate(1, 8)` reserves room for eight single-cell records, but the array starts empty. Its size is `0` before the additions and `2` afterward. Only elements added through `ArrayPushCell()`, other `ArrayPush*()` functions or `ArrayResize()` can be accessed.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Array:scores = ArrayCreate( 1, 8 );
	server_print( "before=%d", ArraySize( scores ) ); // before=0

	ArrayPushCell( scores, 10 );
	ArrayPushCell( scores, 20 );
	server_print( "after=%d first=%d", ArraySize( scores ), ArrayGetCell( scores, 0 ) );
	// after=2 first=10

	ArrayDestroy( scores );
}
```

The result of `ArrayCreate()` is called a handle: an identifier we pass to the array functions and eventually release. For a small known size, an ordinary array is enough. A dynamic array adds native calls and cleanup to manage; it does not make direct access faster.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayCreate)

<a id="dynamic-strings"></a>
## Dynamic arrays of strings

For an array of strings, specify the number of cells available to each string, including its terminating zero. That is the `cellsize` parameter. Here, `sizeof name` makes each record the same size as our read buffer. Index `1` gives us the second name, `Grace`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new name[32];
	new Array:names = ArrayCreate( sizeof name );
	ArrayPushString( names, "Ada" );
	ArrayPushString( names, "Grace" );

	ArrayGetString( names, 1, name, charsmax( name ) );
	server_print( "%s", name ); // Grace

	ArrayDestroy( names );
}
```

An array with `cellsize = 1` cannot hold arbitrary strings. A larger read buffer will not recover text that was already cut off when written into a record that was too small.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayPushString)

<a id="dynamic-records"></a>
## Read, change and write a dynamic record

`ArrayGetArray()` reads a record into a separate local buffer. We change the price in `row`, then save the record back with `ArraySetArray()`. Without that write-back, the price inside `items` would stay unchanged. After saving, the example reads back `125`.

```pawn
#include <amxmodx>

enum _:item_data
{
	item_id,
	item_price
};

public plugin_init()
{
	new row[item_data];
	new Array:items = ArrayCreate( item_data );
	row[item_id] = 7;
	row[item_price] = 100;
	ArrayPushArray( items, row );

	ArrayGetArray( items, 0, row );
	row[item_price] += 25;
	// Save the changed copy back into the array.
	ArraySetArray( items, 0, row );

	ArrayGetArray( items, 0, row );
	server_print( "id=%d price=%d", row[item_id], row[item_price] );
	// id=7 price=125

	ArrayDestroy( items );
}
```

For the default copy operation, `row` must hold the element’s full `cellsize`. A matching `enum` field name is not enough; the actual buffer size must fit.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayGetArray)

<a id="delete-while-iterating"></a>
## Delete entries without skipping the next one

Deleting from an `Array` moves the following elements one position left. Incrementing the index at that point can skip an element. Work backward when deleting by a condition: this removes both negative numbers below and keeps `10` and `20`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Array:values = ArrayCreate();
	ArrayPushCell( values, 10 );
	ArrayPushCell( values, -1 );
	ArrayPushCell( values, -2 );
	ArrayPushCell( values, 20 );

	// Walk backward so a shifted element is not skipped.
	for( new index = ArraySize( values ) - 1; index >= 0; index-- )
	{
		if( ArrayGetCell( values, index ) < 0 )
			ArrayDeleteItem( values, index );
	}

	server_print( "size=%d second=%d", ArraySize( values ), ArrayGetCell( values, 1 ) );
	// size=2 second=20

	ArrayDestroy( values );
}
```

Indexes saved earlier can change meaning after deletion too. If another part of the plugin needs to find the same record later, store a stable ID inside that record.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayDeleteItem)

<a id="clone-and-ownership"></a>
## Make and release a separate `Array` copy

Assigning an `Array` handle to another variable makes both variables refer to the same array. Use `ArrayClone()` for a separate copy. Here, changing the copy to `99` leaves `10` in the original. Release both arrays when finished.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Array:original = ArrayCreate();
	ArrayPushCell( original, 10 );

	new Array:cloned = ArrayClone( original );
	ArraySetCell( cloned, 0, 99 );
	server_print( "%d / %d", ArrayGetCell( original, 0 ), ArrayGetCell( cloned, 0 ) );
	// 10 / 99

	ArrayDestroy( cloned );
	ArrayDestroy( original );
}
```

`ArrayDestroy()` clears the variable passed to it, but other copies of that handle keep the old identifier. If a cell contains another object’s handle, only that identifier is cloned. Nested objects are not copied or destroyed separately by this operation.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayClone)

<a id="trie-presence-and-zero"></a>
## `Trie` lookup: missing is not zero

A `Trie` is useful for storing values under string keys. `TrieGetCell()` reports whether a record was found separately from the value it writes into `score`. A stored `0` therefore remains a valid result. The record below is found even though the player has no points yet.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Trie:scores = TrieCreate();
	TrieSetCell( scores, "STEAM_0:1:42", 0 );

	new score;
	if( TrieGetCell( scores, "STEAM_0:1:42", score ) )
		server_print( "stored score=%d", score );
	else
		server_print( "not found" );
	// stored score=0

	TrieDestroy( scores );
}
```

Choose the matching Get/Set functions for cells, strings or arrays. If a key represents an account, consider what it identifies: duplicate names and renames can mix up different players’ records.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieGetCell)

<a id="trie-snapshot"></a>
## Snapshot of `Trie` keys

A `Trie` snapshot keeps the list of keys present when it was created. It does not copy their values. In this example, `Ada` remains in the snapshot after being deleted from the `Trie`. We check `TrieGetCell()` again during iteration and print only `Grace=9`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Trie:scores = TrieCreate();
	TrieSetCell( scores, "Ada", 7 );
	TrieSetCell( scores, "Grace", 9 );
	new Snapshot:keys = TrieSnapshotCreate( scores );

	TrieDeleteKey( scores, "Ada" );

	new key[32], score;
	for( new index; index < TrieSnapshotLength( keys ); index++ )
	{
		TrieSnapshotGetKey( keys, index, key, charsmax( key ) );
		// A key in the snapshot may already be gone from the Trie.
		if( TrieGetCell( scores, key, score ) )
			server_print( "%s=%d", key, score );
	}
	// Grace=9

	TrieSnapshotDestroy( keys );
	TrieDestroy( scores );
}
```

The keys are not guaranteed to be sorted. These names fit in `key`; for arbitrary keys, check `TrieSnapshotKeyBufferSize()` so they are not silently truncated. The snapshot has its own handle to release too.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieSnapshotCreate)

<a id="built-in-sorting"></a>
## Built-in integer and `Array` sorting

Ordinary numeric or string ordering already has built-in sorting functions. Below, `SortIntegers()` puts numbers in ascending order, while `SortADTArray()` puts the largest value first. These cases do not need a custom comparison handler.

```pawn
#include <amxmodx>

public plugin_init()
{
	new values[] = { 30, 10, 20 };
	SortIntegers( values, sizeof values, Sort_Ascending );
	server_print( "%d %d %d", values[0], values[1], values[2] );
	// 10 20 30

	new Array:scores = ArrayCreate();
	ArrayPushCell( scores, 3 );
	ArrayPushCell( scores, 7 );
	SortADTArray( scores, Sort_Descending, Sort_Integer );
	server_print( "highest=%d", ArrayGetCell( scores, 0 ) );
	// highest=7

	ArrayDestroy( scores );
}
```

Choose a sort type that matches the stored data. Sorting moves elements to different indexes. For equal values, these settings do not define an extra order for your plugin’s logic.

[AMXX / Pawn](https://www.amxmodx.org/api/sorting/SortADTArray)

<a id="custom-sorting"></a>
## What `ArraySort` and `ArraySortEx` pass to the handler

For your own ordering, pass a comparison function. `ArraySort()` gives it record indexes, while `ArraySortEx()` gives it the records themselves: scalar values for `cellsize = 1`, or arrays for larger records. We compare numbers directly below, so we use `ArraySortEx()`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Array:values = ArrayCreate();
	ArrayPushCell( values, 30 );
	ArrayPushCell( values, 10 );
	ArrayPushCell( values, 20 );
	ArraySortEx( values, "compare_cells" );

	server_print( "%d %d %d", ArrayGetCell( values, 0 ), ArrayGetCell( values, 1 ), ArrayGetCell( values, 2 ) );
	// 10 20 30

	ArrayDestroy( values );
}

public compare_cells( Array:array, first, second )
{
	#pragma unused array
	return (first > second) - (first < second);
}
```

Return `-1`, `0` or `1` to put the first value before, equal to or after the second. Comparing as shown avoids the overflow possible with `first - second`. Do not modify the array inside the handler, and remember that expensive work there repeats for every comparison.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArraySortEx)
