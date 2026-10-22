# Arrays and containers

[EN](../en/03-arrays-and-containers.md) | [UK](../uk/03-arrays-and-containers.md) | [RU](../ru/03-arrays-and-containers.md)

[Contents](../../README.md)

- [Fixed arrays and valid indexes](#fixed-array-bounds)
- [Rows, columns and `sizeof`](#multidimensional-size)
- [Record-shaped arrays with `enum` fields](#enum-records)
- [`Array` handles: capacity is not size](#dynamic-cell-array)
- [Dynamic arrays of strings](#dynamic-strings)
- [Read, change and write a dynamic record](#dynamic-records)
- [Delete entries without skipping the next one](#delete-while-iterating)
- [Clone data and own every handle](#clone-and-ownership)
- [`Trie` lookup: missing is not zero](#trie-presence-and-zero)
- [Snapshot of `Trie` keys](#trie-snapshot)
- [Built-in integer and `Array` sorting](#built-in-sorting)
- [`ArraySort` vs `ArraySortEx` callbacks](#custom-sorting)

<a id="fixed-array-bounds"></a>
## Fixed arrays and valid indexes

A fixed array has a compile-time capacity. Valid indexes run from zero to `sizeof array - 1`; an external index needs both a lower and an upper bound check.

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

For partially filled buffers, keep a separate item count and check against that count. Capacity answers how much can fit, not how many entries are currently meaningful.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="multidimensional-size"></a>
## Rows, columns and `sizeof`

For a multidimensional array, `sizeof array` gives the first dimension and `sizeof array[]` the second. This makes parallel loops and string capacities follow the declaration.

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

`sizeof` measures declared cells, not the runtime length of the string in a row. For a row’s text capacity, reserve one cell for its terminator.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Two_Dimensional_Arrays)

<a id="enum-records"></a>
## Record-shaped arrays with `enum` fields

An `enum` can describe offsets and sizes inside one record. This keeps related data together without parallel arrays and gives named access to its fields.

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

This is still an array layout, not a class or a C struct. A buffer passed to a native must have the size that native expects; the `enum` does not add runtime validation.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="dynamic-cell-array"></a>
## `Array` handles: capacity is not size

`ArrayCreate(1, 8)` creates an empty dynamic array of one-cell entries with space reserved for eight items. Only pushed or resized entries are valid to access.

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

Choose a fixed array for a small known limit. Dynamic `Array` is useful when item count really varies; it adds native calls and handle ownership, not a faster replacement for direct array access.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayCreate)

<a id="dynamic-strings"></a>
## Dynamic arrays of strings

For string entries, `cellsize` is the capacity of every stored string, including its terminator. Match it to the buffers used for pushing and reading text.

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

A one-cell `Array` is not a container for arbitrary strings. A larger output buffer cannot recover text already truncated by the entry’s storage capacity.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayPushString)

<a id="dynamic-records"></a>
## Read, change and write a dynamic record

`ArrayGetArray()` copies an entry into a local buffer. Editing that buffer does not update the container until you call `ArraySetArray()`.

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
	ArraySetArray( items, 0, row );

	ArrayGetArray( items, 0, row );
	server_print( "id=%d price=%d", row[item_id], row[item_price] );
	// id=7 price=125

	ArrayDestroy( items );
}
```

The local record must hold the entry’s complete `cellsize` when using the default copy size. A smaller destination is not made safe by a matching `enum` field name.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayGetArray)

<a id="delete-while-iterating"></a>
## Delete entries without skipping the next one

Deleting an `Array` entry shifts later entries left. Iterate backwards when removing matching entries so the shifted values have already been considered.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Array:values = ArrayCreate();
	ArrayPushCell( values, 10 );
	ArrayPushCell( values, -1 );
	ArrayPushCell( values, -2 );
	ArrayPushCell( values, 20 );

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

After deletion, stored numeric indexes can refer to a different item. Use a stable ID inside the record if another part of the plugin must identify the same item later.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayDeleteItem)

<a id="clone-and-ownership"></a>
## Clone data and own every handle

Assigning an `Array` handle copies its identifier, not its data. `ArrayClone()` creates a separate container; both handles then require their own cleanup.

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

Destroying resets the handle variable passed by reference, but not its aliases. Cloning copies stored cells: if a cell itself contains another handle, that nested object is not recursively cloned or destroyed.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayClone)

<a id="trie-presence-and-zero"></a>
## `Trie` lookup: missing is not zero

`Trie` stores values under string keys. `TrieGetCell()` reports whether retrieval succeeded separately from the stored value, so zero remains a valid value.

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

Use the matching Get/Set family for cells, strings or arrays. Do not use a player’s display name as a durable identity unless name changes and collisions are part of the intended behavior.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieGetCell)

<a id="trie-snapshot"></a>
## Snapshot of `Trie` keys

A `Trie` snapshot freezes its list of keys, not the values. Later additions and deletions do not change that list, so re-check a key when reading a value from the current map.

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
		if( TrieGetCell( scores, key, score ) )
			server_print( "%s=%d", key, score );
	}
	// Grace=9

	TrieSnapshotDestroy( keys );
	TrieDestroy( scores );
}
```

Keys are not sorted. The example controls key lengths; for arbitrary keys, inspect `TrieSnapshotKeyBufferSize()` instead of silently truncating them. A snapshot owns a separate handle that must also be destroyed.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieSnapshotCreate)

<a id="built-in-sorting"></a>
## Built-in integer and `Array` sorting

Use a built-in sorter when the ordering is ordinary numeric or string order. It avoids writing a comparison callback for a standard operation.

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

Choose the sort type to match the stored representation. Sorting mutates the array; it changes item indexes and does not promise an application-specific order for equal values.

[AMXX / Pawn](https://www.amxmodx.org/api/sorting/SortADTArray)

<a id="custom-sorting"></a>
## `ArraySort` vs `ArraySortEx` callbacks

`ArraySort()` passes entry indexes to its callback. `ArraySortEx()` passes the entries themselves: scalar values when `cellsize` is 1, arrays when `cellsize` is larger. Do not mix these callback contracts.

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

Return `-1`, `0` or `1` for before, equal or after. Comparing directly avoids overflow from `first - second`. Keep callbacks free of container mutations and expensive repeated work.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArraySortEx)
