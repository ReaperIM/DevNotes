# Масиви та контейнери

[EN](../en/03-arrays-and-containers.md) | [UK](../uk/03-arrays-and-containers.md) | [RU](../ru/03-arrays-and-containers.md)

[Зміст](../../README.uk.md)

- [Фіксовані масиви та допустимі індекси](#fixed-array-bounds)
- [Рядки, стовпці та `sizeof`](#multidimensional-size)
- [Збираємо поля одного запису через `enum`](#enum-records)
- [`Array`: місткість не дорівнює розміру](#dynamic-cell-array)
- [Динамічні масиви рядків](#dynamic-strings)
- [Прочитати, змінити та записати динамічний запис](#dynamic-records)
- [Видалення без пропуску наступного елемента](#delete-while-iterating)
- [Окрема копія `Array` та її звільнення](#clone-and-ownership)
- [Пошук у `Trie`: відсутність не є нулем](#trie-presence-and-zero)
- [Знімок ключів `Trie`](#trie-snapshot)
- [Готове сортування цілих чисел та `Array`](#built-in-sorting)
- [Що отримує обробник `ArraySort` і `ArraySortEx`](#custom-sorting)

<a id="fixed-array-bounds"></a>
## Фіксовані масиви та допустимі індекси

У звичайного масиву розмір відомий уже під час компіляції. У `scores` нижче три елементи з індексами `0`, `1` і `2`. Перед зверненням за індексом, отриманим ззовні, перевіряємо обидві межі: індекс не має бути від’ємним або виходити за `sizeof scores - 1`.

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

Якщо масив заповнений частково, окремо зберігаємо кількість записів. Місткість відповідає на запитання, скільки всього поміститься, а лічильник показує, скільки вже записано. Для читання потрібен саме лічильник.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="multidimensional-size"></a>
## Рядки, стовпці та `sizeof`

У `names[3][16]` є три рядки, і в кожному по 16 комірок. `sizeof names` дає кількість рядків, а `sizeof names[]` розмір одного рядка. Так цикл і буфери можна прив’язати до оголошення масиву й не повторювати розміри вручну.

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

`sizeof` показує виділене місце, а не довжину записаного тексту. У кожному рядку одна комірка все ще потрібна для завершального нуля.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Two_Dimensional_Arrays)

<a id="enum-records"></a>
## Збираємо поля одного запису через `enum`

Очки, швидкість та ім’я гравця зручно тримати разом. `enum` нижче задає, де кожне поле розташоване в масиві й скільки місця займає. Замість числових індексів використовуємо імена на кшталт `player_score`; не доводиться створювати окремий масив для кожного поля.

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

Це й далі масив зі схемою полів. Розмір буфера для native перевіряємо самі: `enum` не додасть такої перевірки під час роботи й не перетворить запис на клас або структуру C.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Arrays)

<a id="dynamic-cell-array"></a>
## `Array`: місткість не дорівнює розміру

Якщо кількість записів заздалегідь невідома, підійде динамічний `Array`. Виклик `ArrayCreate(1, 8)` резервує місце для восьми однокоміркових записів, але сам масив поки порожній. Тому спочатку розмір `0`, а після двох додавань `2`. Звертатися можна до елементів, доданих через `ArrayPushCell()`, інші `ArrayPush*()` або `ArrayResize()`.

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

Результат `ArrayCreate()` називають handle: це ідентифікатор масиву, який передаємо його функціям і потім звільняємо. Для невеликого відомого розміру достатньо звичайного масиву. Динамічний додає виклики native і потребу звільнення, а прямий доступ швидшим від цього не стає.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayCreate)

<a id="dynamic-strings"></a>
## Динамічні масиви рядків

Для масиву рядків вказуємо, скільки комірок може займати кожен рядок разом із завершальним нулем. Це параметр `cellsize`. Нижче беремо `sizeof name`, тому розмір запису збігається з нашим буфером для читання. За індексом `1` отримаємо друге ім’я, `Grace`.

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

У масив із `cellsize = 1` довільний рядок не поміститься. Великий буфер під час читання вже не відновить текст, обрізаний під час запису в замалий елемент.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayPushString)

<a id="dynamic-records"></a>
## Прочитати, змінити та записати динамічний запис

`ArrayGetArray()` читає запис в окремий локальний буфер. Змінюємо ціну в `row`, потім повертаємо запис у масив через `ArraySetArray()`. Без `ArraySetArray()` ціна всередині `items` залишиться попередньою. У прикладі після збереження вона дорівнює `125`.

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
	// Зберігаємо змінену копію назад у масив.
	ArraySetArray( items, 0, row );

	ArrayGetArray( items, 0, row );
	server_print( "id=%d price=%d", row[item_id], row[item_price] );
	// id=7 price=125

	ArrayDestroy( items );
}
```

За звичайного копіювання буфер `row` має вміщати весь `cellsize` елемента. Збіг імені поля `enum` нічого не гарантує: фактичний розмір усе одно має підходити.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayGetArray)

<a id="delete-while-iterating"></a>
## Видалення без пропуску наступного елемента

Після видалення з `Array` наступні елементи зсуваються на одну позицію вліво. Якщо в цей момент збільшити індекс, один із них буде пропущено. Тому під час видалення за умовою йдемо з кінця: нижче так видаляються обидва від’ємні числа, а `10` і `20` залишаються.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Array:values = ArrayCreate();
	ArrayPushCell( values, 10 );
	ArrayPushCell( values, -1 );
	ArrayPushCell( values, -2 );
	ArrayPushCell( values, 20 );

	// Ідемо з кінця, щоб не пропустити зсунутий елемент.
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

Збережені раніше індекси після видалення теж можуть змінитися. Якщо запис треба буде знайти з іншого місця коду, зберігаємо всередині нього власний постійний ID.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayDeleteItem)

<a id="clone-and-ownership"></a>
## Окрема копія `Array` та її звільнення

Якщо присвоїти handle `Array` іншій змінній, обидві звертатимуться до одного масиву. Для окремої копії потрібен `ArrayClone()`. Нижче змінюємо копію на `99`, а в початковому масиві залишається `10`. Наприкінці звільняємо обидва масиви.

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

`ArrayDestroy()` обнуляє передану змінну, але її інші копії залишаться зі старим номером. Якщо в комірках лежать ідентифікатори інших об’єктів, клонуються лише ці номери. Вкладені об’єкти окремо не копіюються й не видаляються.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArrayClone)

<a id="trie-presence-and-zero"></a>
## Пошук у `Trie`: відсутність не є нулем

У `Trie` зручно зберігати значення за рядковим ключем. `TrieGetCell()` окремо повідомляє, чи знайшовся запис, і окремо заповнює `score`. Тому збережений `0` залишається нормальним результатом. Нижче запис знайдено, хоча в гравця поки немає очок.

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

Для чисел, рядків і масивів вибираємо відповідні функції Get/Set. Якщо ключ пов’язаний з акаунтом, враховуємо його зміст: однакові імена та перейменування можуть змішати записи різних гравців.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieGetCell)

<a id="trie-snapshot"></a>
## Знімок ключів `Trie`

Знімок `Trie` зберігає список ключів на момент створення. Значення в нього не копіюються. У прикладі ключ `Ada` залишився у знімку, але із самого `Trie` вже видалений. Тому під час обходу знову перевіряємо `TrieGetCell()` і виводимо лише `Grace=9`.

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
		// Ключ зі знімка вже могли видалити із самого Trie.
		if( TrieGetCell( scores, key, score ) )
			server_print( "%s=%d", key, score );
	}
	// Grace=9

	TrieSnapshotDestroy( keys );
	TrieDestroy( scores );
}
```

Порядок ключів не гарантує сортування. Тут імена короткі й поміщаються в `key`; для довільних ключів дивимося `TrieSnapshotKeyBufferSize()`, щоб непомітно їх не обрізати. Знімок має власний handle, його теж звільняємо.

[AMXX / Pawn](https://www.amxmodx.org/api/celltrie/TrieSnapshotCreate)

<a id="built-in-sorting"></a>
## Готове сортування цілих чисел та `Array`

Для звичайного сортування чисел або рядків уже є готові функції. Нижче `SortIntegers()` розставляє числа за зростанням, а `SortADTArray()` ставить найбільше значення першим. Власний обробник порівняння для таких випадків не потрібен.

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

Вибираємо тип сортування під збережені дані. Після сортування елементи опиняються на інших індексах. Якщо значення рівні, додатковий порядок для них цими налаштуваннями не задається.

[AMXX / Pawn](https://www.amxmodx.org/api/sorting/SortADTArray)

<a id="custom-sorting"></a>
## Що отримує обробник `ArraySort` і `ArraySortEx`

Коли потрібен власний порядок, передаємо функцію порівняння. У `ArraySort()` вона отримує індекси записів, у `ArraySortEx()` самі записи: окремі значення за `cellsize = 1` або масиви за більшого розміру. Нижче порівнюються одразу числа, тому вибрано `ArraySortEx()`.

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

Повертаємо `-1`, `0` або `1`: перше значення йде раніше, дорівнює другому або йде пізніше. Таке порівняння уникає переповнення, яке можливе у `first - second`. Сам масив з обробника не змінюємо; важка робота теж повторюватиметься на кожному порівнянні.

[AMXX / Pawn](https://www.amxmodx.org/api/cellarray/ArraySortEx)
