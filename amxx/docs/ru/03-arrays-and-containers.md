# Массивы и контейнеры

[EN](../en/03-arrays-and-containers.md) | [UK](../uk/03-arrays-and-containers.md) | [RU](../ru/03-arrays-and-containers.md)

[Содержание](../../README.ru.md)

- [Фиксированные массивы и допустимые индексы](#fixed-array-bounds)
- [Строки, столбцы и `sizeof`](#multidimensional-size)
- [Собираем поля одной записи через `enum`](#enum-records)
- [`Array`: вместимость не равна размеру](#dynamic-cell-array)
- [Динамические массивы строк](#dynamic-strings)
- [Прочитать, изменить и записать динамическую запись](#dynamic-records)
- [Удаление без пропуска следующего элемента](#delete-while-iterating)
- [Отдельная копия `Array` и её освобождение](#clone-and-ownership)
- [Поиск в `Trie`: отсутствие не равно нулю](#trie-presence-and-zero)
- [Снимок ключей `Trie`](#trie-snapshot)
- [Готовая сортировка целых чисел и `Array`](#built-in-sorting)
- [Что получает обработчик `ArraySort` и `ArraySortEx`](#custom-sorting)

<a id="fixed-array-bounds"></a>
## Фиксированные массивы и допустимые индексы

У обычного массива размер известен уже при компиляции. В `scores` ниже три элемента с индексами `0`, `1` и `2`. Перед обращением по индексу, полученному снаружи, проверяем обе границы: индекс не должен быть отрицательным или выходить за `sizeof scores - 1`.

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

Если массив заполнен частично, отдельно храним число записей. Вместимость отвечает на вопрос, сколько всего поместится, а счётчик показывает, сколько уже записано. Для чтения нужен именно счётчик.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="multidimensional-size"></a>
## Строки, столбцы и `sizeof`

В `names[3][16]` есть три строки, и в каждой по 16 ячеек. `sizeof names` даёт количество строк, а `sizeof names[]` размер одной строки. Так цикл и буферы можно привязать к объявлению массива и не повторять размеры вручную.

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

`sizeof` показывает выделенное место, а не длину записанного текста. В каждой строке одна ячейка всё ещё нужна для завершающего нуля.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Two_Dimensional_Arrays)

<a id="enum-records"></a>
## Собираем поля одной записи через `enum`

Очки, скорость и имя игрока удобно держать вместе. `enum` ниже задаёт, где каждое поле находится в массиве и сколько места занимает. Вместо числовых индексов используем имена вроде `player_score`; не приходится заводить отдельный массив для каждого поля.

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

Это по-прежнему массив со схемой полей. Размер буфера для native проверяем сами: `enum` не добавит такую проверку во время работы и не превратит запись в класс или структуру C.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="dynamic-cell-array"></a>
## `Array`: вместимость не равна размеру

Если число записей заранее неизвестно, подойдёт динамический `Array`. Вызов `ArrayCreate(1, 8)` резервирует место для восьми одноячеечных записей, но сам массив пока пустой. Поэтому сначала размер `0`, а после двух добавлений `2`. Обращаться можно к элементам, добавленным через `ArrayPushCell()`, другие `ArrayPush*()` или `ArrayResize()`.

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

Результат `ArrayCreate()` называют handle: это идентификатор массива, который передаём его функциям и затем освобождаем. Для небольшого известного размера достаточно обычного массива. Динамический добавляет вызовы native и заботу об освобождении, а прямой доступ быстрее от этого не становится.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayCreate)

<a id="dynamic-strings"></a>
## Динамические массивы строк

Для массива строк указываем, сколько ячеек может занимать каждая строка вместе с завершающим нулём. Это параметр `cellsize`. Ниже берём `sizeof name`, поэтому размер записи совпадает с нашим буфером для чтения. По индексу `1` получим второе имя, `Grace`.

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

В массив с `cellsize = 1` произвольная строка не поместится. Большой буфер при чтении уже не восстановит текст, который обрезался во время записи в слишком маленький элемент.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayPushString)

<a id="dynamic-records"></a>
## Прочитать, изменить и записать динамическую запись

`ArrayGetArray()` читает запись в отдельный локальный буфер. Меняем цену в `row`, затем возвращаем запись в массив через `ArraySetArray()`. Без `ArraySetArray()` цена внутри `items` останется прежней. В примере после сохранения она равна `125`.

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
	// Сохраняем изменённую копию обратно в массив.
	ArraySetArray( items, 0, row );

	ArrayGetArray( items, 0, row );
	server_print( "id=%d price=%d", row[item_id], row[item_price] );
	// id=7 price=125

	ArrayDestroy( items );
}
```

При обычном копировании буфер `row` должен вмещать весь `cellsize` элемента. Совпадение имени поля `enum` ничего не гарантирует: фактический размер всё равно должен подходить.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayGetArray)

<a id="delete-while-iterating"></a>
## Удаление без пропуска следующего элемента

После удаления из `Array` следующие элементы сдвигаются на одну позицию влево. Если в этот момент увеличить индекс, один из них будет пропущен. Поэтому при удалении по условию идём с конца: ниже так удаляются оба отрицательных числа, а `10` и `20` остаются.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Array:values = ArrayCreate();
	ArrayPushCell( values, 10 );
	ArrayPushCell( values, -1 );
	ArrayPushCell( values, -2 );
	ArrayPushCell( values, 20 );

	// Идём с конца, чтобы не пропустить сдвинутый элемент.
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

Сохранённые раньше индексы после удаления тоже могут измениться. Если запись должна находиться позже из другого места кода, храним внутри неё свой постоянный ID.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayDeleteItem)

<a id="clone-and-ownership"></a>
## Отдельная копия `Array` и её освобождение

Если присвоить handle `Array` другой переменной, обе будут обращаться к одному массиву. Для отдельной копии нужен `ArrayClone()`. Ниже меняем копию на `99`, а в исходном массиве остаётся `10`. В конце освобождаем оба массива.

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

`ArrayDestroy()` обнуляет переданную переменную, но её другие копии останутся со старым номером. Если в ячейках лежат идентификаторы других объектов, клонируются только эти номера. Вложенные объекты отдельно не копируются и не удаляются.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayClone)

<a id="trie-presence-and-zero"></a>
## Поиск в `Trie`: отсутствие не равно нулю

В `Trie` удобно хранить значения по строковому ключу. `TrieGetCell()` отдельно сообщает, нашлась ли запись, и отдельно заполняет `score`. Поэтому сохранённый `0` остаётся нормальным результатом. Ниже запись найдена, хотя у игрока пока нет очков.

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

Для чисел, строк и массивов выбираем соответствующие функции Get/Set. Если ключ связан с аккаунтом, учитываем его смысл: одинаковые имена и переименования могут смешать записи разных игроков.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieGetCell)

<a id="trie-snapshot"></a>
## Снимок ключей `Trie`

Снимок `Trie` сохраняет список ключей на момент создания. Значения в него не копируются. В примере ключ `Ada` остался в снимке, но из самого `Trie` уже удалён. Поэтому при обходе снова проверяем `TrieGetCell()` и выводим только `Grace=9`.

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
		// Ключ из снимка уже могли удалить из самого Trie.
		if( TrieGetCell( scores, key, score ) )
			server_print( "%s=%d", key, score );
	}
	// Grace=9

	TrieSnapshotDestroy( keys );
	TrieDestroy( scores );
}
```

Порядок ключей не гарантирует сортировку. Здесь имена короткие и помещаются в `key`; для произвольных ключей смотрим `TrieSnapshotKeyBufferSize()`, чтобы незаметно их не обрезать. У снимка собственный handle, его тоже освобождаем.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieSnapshotCreate)

<a id="built-in-sorting"></a>
## Готовая сортировка целых чисел и `Array`

Для обычной сортировки чисел или строк уже есть готовые функции. Ниже `SortIntegers()` расставляет числа по возрастанию, а `SortADTArray()` ставит самое большое значение первым. Собственный обработчик сравнения для таких случаев не нужен.

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

Выбираем тип сортировки под хранимые данные. После сортировки элементы оказываются на других индексах. Если значения равны, дополнительный порядок для них этими настройками не задаётся.

[AMXX / Pawn](https://www.amxmodx.org/api/sorting/SortADTArray)

<a id="custom-sorting"></a>
## Что получает обработчик `ArraySort` и `ArraySortEx`

Когда нужен свой порядок, передаём функцию сравнения. У `ArraySort()` она получает индексы записей, у `ArraySortEx()` сами записи: отдельные значения при `cellsize = 1` или массивы при большем размере. Ниже сравниваются сразу числа, поэтому выбран `ArraySortEx()`.

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

Возвращаем `-1`, `0` или `1`: первое значение идёт раньше, равно второму или идёт позже. Такое сравнение избегает переполнения, которое возможно у `first - second`. Сам массив из обработчика не меняем; тяжёлая работа тоже будет повторяться на каждом сравнении.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArraySortEx)
