# Рядки та текст

[EN](../en/02-strings-and-text.md) | [UK](../uk/02-strings-and-text.md) | [RU](../ru/02-strings-and-text.md)

[Зміст](../../README.uk.md)

- [Розмір буфера та `charsmax`](#buffers-and-charsmax)
- [Форматування значень у буфер](#format-values)
- [`format` для перекриття вводу та виводу](#overlapping-buffers)
- [Побудова тексту зі зміщенням запису](#append-with-offset)
- [Екранування через ^ у Pawn](#caret-escaping)
- [Порівняння рядків і позиція підрядка](#comparison-and-search)
- [`parse` для відомої кількості полів](#parse-fields)
- [Перший аргумент і решта тексту](#argbreak-and-argparse)
- [Прибрати зовнішні пробіли та лапки](#trim-and-remove-quotes)
- [Перевірити цілий числовий токен](#number-parsing)
- [Байти UTF-8 та зміна регістру](#unicode-bytes)
- [Повідомлення зі словника через `%L`](#multilingual-messages)

<a id="buffers-and-charsmax"></a>
## Розмір буфера та `charsmax`

Звичайний рядок AMXX є масивом із нульовою коміркою в кінці. У буфері з 16 комірок помістяться до 15 байтів тексту та термінатор. `charsmax()` дає доступну місткість для тексту.

```pawn
#include <amxmodx>

public plugin_init()
{
	new name[16];
	formatex( name, charsmax( name ), "%s", "Ada" );
	server_print( "capacity=%d length=%d text=%s", charsmax( name ), strlen( name ), name );
	// capacity=15 length=3 text=Ada
}
```

Дотримуйтеся угоди конкретного API: багато рядкових natives приймають максимальну довжину тексту, а частина функцій масивів кількість комірок. `sizeof` і `charsmax` не взаємозамінні.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strlen)

<a id="format-values"></a>
## Форматування значень у буфер

`formatex()` записує обмежений результат безпосередньо в буфер. Формат має відповідати значенню: `%d` для цілих чисел, `%f` для `Float`, `%s` для рядків.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[96];
	new Float:distance = 12.345;
	new written = formatex( text, charsmax( text ), "%s: %d points, %.2f units, 100%%", "Ada", 7, distance );

	server_print( "%s", text );
	server_print( "written=%d", written );
	// Ada: 7 points, 12.35 units, 100%
}
```

Зовнішній текст передавайте як значення: `server_print( "%s", text )`. Якщо він стане рядком формату, знаки відсотка в ньому можуть сприйматися як директиви форматування.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="overlapping-buffers"></a>
## `format` для перекриття вводу та виводу

`formatex()` не може читати з буфера, який зараз перезаписує. Для зміни на місці з перекриттям джерела й результату `format()` забезпечує потрібне проміжне копіювання.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[64] = "Ada";
	format( text, charsmax( text ), "[%s]", text );
	server_print( "%s", text );
	// [Ada]
}
```

Це стосується і частини буфера на кшталт `text[5]`. Для звичайного випадку з окремими джерелами використовуйте `formatex()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/format)

<a id="append-with-offset"></a>
## Побудова тексту зі зміщенням запису

Коли повідомлення складається з частин, зберігайте довжину вже записаного тексту. Кожен виклик отримує лише залишок місткості, а не розмір усього буфера.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[64], length;
	length += formatex( text[length], charsmax( text ) - length, "Player: %s", "Ada" );
	length += formatex( text[length], charsmax( text ) - length, "; score: %d", 7 );

	server_print( "%s", text );
	// Player: Ada; score: 7
}
```

У циклі припиняйте додавання, коли місця більше немає. Не беріть зміщення з неперевіреної зовнішньої довжини й не передавайте попередню частину цього ж буфера через `%s`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="caret-escaping"></a>
## Екранування через ^ у Pawn

У звичайному вихідному коді AMXX Pawn екранування починається з `^`: `^"` додає лапку, `^n` новий рядок, `^^` символ ^. Це екранування вихідного коду, а не коди кольорів чату.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[] = "^"Ada^"^^notes";
	server_print( "%s", text );
	// "Ada"^notes

	new lines[] = "first^nsecond";
	server_print( "%s", lines );
}
```

У рядку з файлу буквальні символи `^n` автоматично не стануть новим рядком. Розбір вихідного коду та обробка тексту під час виконання є різними операціями.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Strings)

<a id="comparison-and-search"></a>
## Порівняння рядків і позиція підрядка

`equal()` перевіряє рівність, `equali()` не враховує регістр, `containi()` повертає позицію підрядка. Нуль означає збіг на початку, а не невдачу.

```pawn
#include <amxmodx>

public plugin_init()
{
	new name[] = "Admin Ada";
	new position = containi( name, "admin" );

	if( position != -1 )
		server_print( "match at %d", position );

	server_print( "equal=%d", equali( "YES", "yes" ) );
	// match at 0
	// equal=1
}
```

Не пишіть `if( containi(...) )`: це відкине позицію 0 та прийме -1 як успіх. Для порівнянь Unicode без регістру перевіряйте підтримку потрібних символів у вибраному API.

[AMXX / Pawn](https://www.amxmodx.org/api/string/containi)

<a id="parse-fields"></a>
## `parse` для відомої кількості полів

`parse()` розділяє поля за пробілами, зберігаючи групи в лапках. Для кожного результату передавайте окремий буфер та максимальну довжину тексту.

```pawn
#include <amxmodx>

public plugin_init()
{
	new line[] = "^"Ada Lovelace^" 42";
	new name[32], score_text[12];
	new fields = parse( line, name, charsmax( name ), score_text, charsmax( score_text ) );

	if( fields == 2 )
		server_print( "name=%s score=%s", name, score_text );
	// name=Ada Lovelace score=42
}
```

Перед використанням перевіряйте кількість отриманих полів. Це розбір аргументів у стилі команд, а не CSV-парсер із правилами екранування CSV.

[AMXX / Pawn](https://www.amxmodx.org/api/string/parse)

<a id="argbreak-and-argparse"></a>
## Перший аргумент і решта тексту

`argbreak()` зручний, коли окремо потрібен лише перший аргумент, а решта має лишитися текстом. Він розуміє лапки навколо першого аргументу; `argparse()` також дозволяє послідовний розбір за зміщенням.

```pawn
#include <amxmodx>

public plugin_init()
{
	new line[] = "^"Ada Lovelace^" keep this text";
	new target[32], message[64];
	new next_position = argbreak( line, target, charsmax( target ), message, charsmax( message ) );

	if( next_position != -1 )
		server_print( "target=%s; message=%s", target, message );
	// target=Ada Lovelace; message=keep this text
}
```

`-1` означає, що аргумент не знайдено; порожні лапки все одно задають аргумент. Ці API варто використовувати замість застарілого `strbreak()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/argparse)

<a id="trim-and-remove-quotes"></a>
## Прибрати зовнішні пробіли та лапки

Одне значення в лапках можна нормалізувати через `trim()` та `remove_quotes()`. Це обробка цілого значення; не прибирайте лапки з рядка аргументів до його розбору.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[32] = "  ^"Ada Lovelace^"  ";
	trim( text );
	remove_quotes( text );
	server_print( "[%s]", text );
	// [Ada Lovelace]
}
```

Порядок важливий: спочатку trim, щоб лапки стали крайніми символами. Якщо пробіли всередині лапок важливі, не виконуйте trim повторно після їх видалення.

[AMXX / Pawn](https://www.amxmodx.org/api/string/remove_quotes)

<a id="number-parsing"></a>
## Перевірити цілий числовий токен

Самого `str_to_num()` недостатньо, щоб довести коректність усього вводу. Позиція завершення `strtol()` дозволяє відхилити числовий початок зі стороннім текстом наприкінці.

```pawn
#include <amxmodx>

public plugin_init()
{
	new token[] = "12x";
	new end_position;
	new value = strtol( token, end_position, 10 );

	if( end_position == 0 || token[end_position] != 0 || value < 1 || value > 32 )
		server_print( "invalid player count" );
	else
		server_print( "count=%d", value );
	// invalid player count
}
```

Приклад приймає правила native щодо пробілів і знаку, а потім перевіряє діапазон. Якщо формат дозволяє лише цифри, це слід перевірити окремо: числове перетворення не замінює перевірку формату.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strtol)

<a id="unicode-bytes"></a>
## Байти UTF-8 та зміна регістру

Літери UTF-8 можуть займати кілька байтів. `strlen()` рахує байти, а не видимі літери; місткість буфера теж має враховувати ці байти. Для регістру UTF-8 використовуйте функції `mb_*`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[32] = "Кіт";
	server_print( "bytes=%d", strlen( text ) ); // bytes=6

	mb_strtoupper( text, charsmax( text ) );
	server_print( "%s", text ); // КІТ
}
```

Зміна регістру може змінити кількість байтів, тому передавайте реальну місткість. Обрізання за довільним байтовим зміщенням може розірвати UTF-8; байтова довжина також не є кількістю графем.

[AMXX / Pawn](https://www.amxmodx.org/api/string/mb_strtoupper)

<a id="multilingual-messages"></a>
## Повідомлення зі словника через `%L`

Словник реєструється один раз, після чого `%L` знаходить ключ для потрібної мови. У прикладі використовується стандартний `common.txt`, де вже є ключ `YES`.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_dictionary( "common.txt" );

	new text[64];
	formatex( text, charsmax( text ), "%L", LANG_SERVER, "YES" );
	server_print( "%s", text );
	// Yes, when the server language is English.
}
```

Для одного гравця передайте його індекс замість `LANG_SERVER`. `LANG_PLAYER` призначений для підтримуваного друку кільком адресатам, а не для одноразового форматування спільного буфера. Файл словника має бути на сервері.

[AMXX / Pawn](https://www.amxmodx.org/api/lang/register_dictionary)
