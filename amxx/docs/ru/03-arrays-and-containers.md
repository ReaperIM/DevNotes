# Массивы и контейнеры

[EN](../en/03-arrays-and-containers.md) | [UK](../uk/03-arrays-and-containers.md) | [RU](../ru/03-arrays-and-containers.md)

[Содержание](../../README.ru.md)

- [Фиксированные массивы и допустимые индексы](#fixed-array-bounds)
- [Строки, столбцы и `sizeof`](#multidimensional-size)
- [Массивы-записи с полями `enum`](#enum-records)
- [`Array`: вместимость не равна размеру](#dynamic-cell-array)
- [Динамические массивы строк](#dynamic-strings)
- [Прочитать, изменить и записать динамическую запись](#dynamic-records)
- [Удаление без пропуска следующего элемента](#delete-while-iterating)
- [Клонировать данные и освобождать каждый handle](#clone-and-ownership)
- [Поиск в `Trie`: отсутствие не равно нулю](#trie-presence-and-zero)
- [Снимок ключей `Trie`](#trie-snapshot)
- [Готовая сортировка целых чисел и `Array`](#built-in-sorting)
- [Callbacks `ArraySort` и `ArraySortEx`](#custom-sorting)

<a id="fixed-array-bounds"></a>
## Фиксированные массивы и допустимые индексы

Фиксированный массив имеет вместимость, известную при компиляции. Допустимые индексы от нуля до `sizeof array - 1`; внешний индекс требует проверки нижней и верхней границы.

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

Для частично заполненных буферов храните отдельный счётчик элементов и проверяйте его. Вместимость показывает, сколько может поместиться, а не сколько записей сейчас имеют значение.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="multidimensional-size"></a>
## Строки, столбцы и `sizeof`

Для многомерного массива `sizeof array` даёт первое измерение, а `sizeof array[]` второе. Размеры циклов и текстовых буферов можно привязать к объявлению.

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

`sizeof` измеряет объявленные ячейки, а не текущую длину строки. В текстовом буфере строки одна ячейка нужна для терминатора.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Two_Dimensional_Arrays)

<a id="enum-records"></a>
## Массивы-записи с полями `enum`

Enum может описывать смещения и размеры полей одной записи. Так связанные данные хранятся вместе без параллельных массивов, а доступ к полям имеет понятные имена.

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

Это всё ещё схема массива, а не класс или C struct. Буфер для native должен иметь ожидаемый размер; `enum` не добавляет проверку во время выполнения.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="dynamic-cell-array"></a>
## `Array`: вместимость не равна размеру

`ArrayCreate(1, 8)` создаёт пустой динамический массив с одноячеечными элементами и резервом на восемь записей. Доступны только элементы, добавленные через Push или Resize.

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

Для небольшой известной границы подойдёт фиксированный массив. Динамический `Array` нужен для переменного количества записей; он добавляет native-вызовы и владение handle, а не ускоряет прямой доступ.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayCreate)

<a id="dynamic-strings"></a>
## Динамические массивы строк

Для текстовых элементов `cellsize` является вместимостью каждой строки вместе с терминатором. Согласуйте её с буферами, используемыми для записи и чтения текста.

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

Одноячеечный `Array` не подходит для произвольных строк. Большой выходной буфер не вернёт текст, уже обрезанный из-за вместимости записи.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayPushString)

<a id="dynamic-records"></a>
## Прочитать, изменить и записать динамическую запись

`ArrayGetArray()` копирует запись в локальный буфер. Изменение буфера не меняет контейнер, пока не будет вызван `ArraySetArray()`.

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

С размером копирования по умолчанию локальная запись должна вмещать весь `cellsize` элемента. Меньший буфер не становится безопасным из-за совпадения названия поля `enum`.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayGetArray)

<a id="delete-while-iterating"></a>
## Удаление без пропуска следующего элемента

После удаления записи `Array` следующие элементы сдвигаются влево. При удалении по условию проходите с конца, чтобы сдвинутые значения уже были обработаны.

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

После удаления сохранённые индексы могут указывать уже на другой элемент. Если другой код должен позже найти именно эту запись, храните стабильный ID внутри неё.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayDeleteItem)

<a id="clone-and-ownership"></a>
## Клонировать данные и освобождать каждый handle

Присваивание handle `Array` копирует его идентификатор, а не данные. `ArrayClone()` создаёт отдельный контейнер; оба handle после этого требуют собственного освобождения.

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

Destroy обнуляет переданную по ссылке переменную, но не её копии. Клонируются сохранённые ячейки: если ячейка содержит другой handle, вложенный объект не клонируется и не удаляется рекурсивно.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayClone)

<a id="trie-presence-and-zero"></a>
## Поиск в `Trie`: отсутствие не равно нулю

`Trie` хранит значения по строковым ключам. `TrieGetCell()` отдельно сообщает об успехе чтения, поэтому ноль остаётся допустимым значением.

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

Для ячеек, строк и массивов используйте соответствующие Get/Set. Имя игрока не является надёжным постоянным ID, если переименования и совпадения имён не предусмотрены логикой.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieGetCell)

<a id="trie-snapshot"></a>
## Снимок ключей `Trie`

Снимок `Trie` фиксирует список ключей, а не значения. Последующие добавления и удаления список не меняют, поэтому чтение значения из текущей карты должно снова проверять ключ.

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

Ключи не отсортированы. Здесь длины ключей контролируются; для произвольных ключей проверяйте `TrieSnapshotKeyBufferSize()`, а не обрезайте их незаметно. Снимок имеет отдельный handle, который тоже нужно освободить.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieSnapshotCreate)

<a id="built-in-sorting"></a>
## Готовая сортировка целых чисел и `Array`

Для обычного числового или строкового порядка используйте готовую сортировку. Так стандартная операция не требует собственного callback сравнения.

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

Тип сортировки должен соответствовать представлению данных. Сортировка меняет массив и индексы элементов; для одинаковых значений она не задаёт дополнительный порядок вашей логики.

[AMXX / Pawn](https://www.amxmodx.org/api/sorting/SortADTArray)

<a id="custom-sorting"></a>
## Callbacks `ArraySort` и `ArraySortEx`

`ArraySort()` передаёт callback индексы записей. `ArraySortEx()` передаёт сами записи: скалярные значения при `cellsize` 1, массивы при большем `cellsize`. Эти контракты нельзя смешивать.

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

Возвращайте `-1`, `0` или `1` для порядка до, равно или после. Прямое сравнение избегает переполнения `first - second`. Не меняйте контейнер и не выполняйте дорогую повторную работу в callback.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArraySortEx)
