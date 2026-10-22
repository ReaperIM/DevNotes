# Масиви та контейнери

[English](../en/03-arrays-and-containers.md) | [Українська](../uk/03-arrays-and-containers.md) | [Русский](../ru/03-arrays-and-containers.md)

[Зміст](../../README.uk.md)

- [Фіксовані масиви та допустимі індекси](#fixed-array-bounds)
- [Рядки, стовпці та sizeof](#multidimensional-size)
- [Масиви-записи з полями enum](#enum-records)
- [Array: місткість не дорівнює розміру](#dynamic-cell-array)
- [Динамічні масиви рядків](#dynamic-strings)
- [Прочитати, змінити та записати динамічний запис](#dynamic-records)
- [Видалення без пропуску наступного елемента](#delete-while-iterating)
- [Клонувати дані та звільняти кожен handle](#clone-and-ownership)
- [Пошук у Trie: відсутність не є нулем](#trie-presence-and-zero)
- [Знімок ключів Trie](#trie-snapshot)
- [Готове сортування цілих чисел та Array](#built-in-sorting)
- [Callbacks ArraySort та ArraySortEx](#custom-sorting)

<a id="fixed-array-bounds"></a>
## Фіксовані масиви та допустимі індекси

Фіксований масив має місткість, відому під час компіляції. Допустимі індекси від нуля до `sizeof array - 1`; зовнішній індекс потребує перевірки нижньої й верхньої межі.

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

Для частково заповнених буферів зберігайте окремий лічильник елементів і перевіряйте його. Місткість показує, скільки може поміститися, а не скільки записів зараз мають значення.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="multidimensional-size"></a>
## Рядки, стовпці та sizeof

Для багатовимірного масиву `sizeof array` дає перший вимір, а `sizeof array[]` другий. Розміри циклів і текстових буферів можна прив’язати до оголошення.

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

`sizeof` вимірює оголошені комірки, а не поточну довжину рядка. У текстовому буфері рядка одна комірка потрібна для термінатора.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Two_Dimensional_Arrays)

<a id="enum-records"></a>
## Масиви-записи з полями enum

Enum може описувати зміщення та розміри полів одного запису. Так пов’язані дані зберігаються разом без паралельних масивів, а доступ до полів має зрозумілі імена.

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

Це все ще схема масиву, а не клас чи C struct. Буфер для native повинен мати очікуваний розмір; enum не додає перевірку під час виконання.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="dynamic-cell-array"></a>
## Array: місткість не дорівнює розміру

`ArrayCreate(1, 8)` створює порожній динамічний масив з однокомірковими елементами та резервом на вісім записів. Доступні лише елементи, додані через Push або Resize.

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

Для невеликої відомої межі підійде фіксований масив. Динамічний Array потрібен для змінної кількості записів; він додає native-виклики та володіння handle, а не прискорює прямий доступ.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayCreate)

<a id="dynamic-strings"></a>
## Динамічні масиви рядків

Для текстових елементів cellsize є місткістю кожного рядка разом із термінатором. Узгоджуйте її з буферами, які використовуються для запису й читання тексту.

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

Однокомірковий Array не підходить для довільних рядків. Великий вихідний буфер не поверне текст, який уже обрізано через місткість запису.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayPushString)

<a id="dynamic-records"></a>
## Прочитати, змінити та записати динамічний запис

`ArrayGetArray()` копіює запис до локального буфера. Зміна буфера не змінює контейнер, доки не буде викликано `ArraySetArray()`.

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

З типовим розміром копіювання локальний запис має вміщувати весь cellsize елемента. Менший буфер не стає безпечним через збіг назви поля enum.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayGetArray)

<a id="delete-while-iterating"></a>
## Видалення без пропуску наступного елемента

Після видалення запису Array наступні елементи зсуваються ліворуч. Під час видалення за умовою проходьте з кінця, щоб зсунуті значення вже були оброблені.

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

Після видалення збережені індекси можуть вказувати вже на інший елемент. Якщо інший код має пізніше знайти саме цей запис, зберігайте стабільний ID усередині нього.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayDeleteItem)

<a id="clone-and-ownership"></a>
## Клонувати дані та звільняти кожен handle

Присвоєння handle Array копіює його ідентифікатор, а не дані. `ArrayClone()` створює окремий контейнер; обидва handle після цього потребують власного звільнення.

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

Destroy скидає передану за посиланням змінну, але не її копії. Клонуються збережені комірки: якщо комірка містить інший handle, вкладений об’єкт не клонується і не видаляється рекурсивно.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayClone)

<a id="trie-presence-and-zero"></a>
## Пошук у Trie: відсутність не є нулем

Trie зберігає значення за рядковими ключами. `TrieGetCell()` окремо повідомляє про успіх читання, тому нуль залишається допустимим значенням.

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

Для комірок, рядків і масивів використовуйте відповідні Get/Set. Ім’я гравця не є надійним постійним ID, якщо перейменування та збіги імен не передбачені логікою.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieGetCell)

<a id="trie-snapshot"></a>
## Знімок ключів Trie

Знімок Trie фіксує список ключів, а не значення. Подальші додавання й видалення список не змінюють, тому читання значення з поточної мапи має знову перевіряти ключ.

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

Ключі не відсортовані. Тут довжини ключів контрольовані; для довільних ключів перевіряйте `TrieSnapshotKeyBufferSize()`, а не обрізайте їх непомітно. Знімок має окремий handle, який теж треба звільнити.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieSnapshotCreate)

<a id="built-in-sorting"></a>
## Готове сортування цілих чисел та Array

Для звичайного числового чи рядкового порядку використовуйте готове сортування. Так стандартна операція не потребує власного callback порівняння.

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

Тип сортування має відповідати поданню даних. Сортування змінює масив та індекси елементів; для однакових значень воно не задає додаткового порядку вашої логіки.

[AMXX / Pawn](https://www.amxmodx.org/api/sorting/SortADTArray)

<a id="custom-sorting"></a>
## Callbacks ArraySort та ArraySortEx

`ArraySort()` передає callback індекси записів. `ArraySortEx()` передає самі записи: скалярні значення для cellsize 1, масиви для більшого cellsize. Ці контракти не можна змішувати.

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

Повертайте `-1`, `0` або `1` для порядку до, рівно або після. Пряме порівняння уникає переповнення `first - second`. Не змінюйте контейнер і не виконуйте дорогу повторну роботу в callback.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArraySortEx)
