# Рядки та текст

[EN](../en/02-strings-and-text.md) | [UK](../uk/02-strings-and-text.md) | [RU](../ru/02-strings-and-text.md)

[Зміст](../../README.uk.md)

- [Скільки тексту поміщається в буфер](#buffers-and-charsmax)
- [Форматування значень у буфер](#format-values)
- [Коли потрібен `format`, а не `formatex`](#overlapping-buffers)
- [Дописуємо текст частинами](#append-with-offset)
- [Лапки та перенесення рядків через `^`](#caret-escaping)
- [Порівняння рядків і позиція підрядка](#comparison-and-search)
- [`parse` для відомої кількості полів](#parse-fields)
- [Перший аргумент і решта тексту](#argbreak-and-argparse)
- [Прибираємо зовнішні пробіли та лапки](#trim-and-remove-quotes)
- [Перевіряємо весь рядок із числом](#number-parsing)
- [Байти UTF-8 та зміна регістру](#unicode-bytes)
- [Повідомлення зі словника через `%L`](#multilingual-messages)

<a id="buffers-and-charsmax"></a>
## Скільки тексту поміщається в буфер

У рядковому буфері одна комірка потрібна для завершального нуля. Тому в `name[16]` поміститься до 15 байт тексту. `charsmax(name)` якраз дає ці `15`, а `strlen(name)` показує, скільки байт уже зайнято. У прикладі ім’я `Ada` займає лише три.

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

Передаємо довжину в тому вигляді, якого чекає конкретна функція. Для рядків часто потрібна межа без завершального нуля, а для масивів може знадобитися кількість усіх комірок. Тому замінювати `charsmax` на `sizeof` усюди поспіль не можна.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strlen)

<a id="format-values"></a>
## Форматування значень у буфер

Коли треба зібрати текст з імені, чисел та інших значень, використовуємо `formatex()`. Передаємо буфер і доступну довжину, а потім рядок формату: `%d` для цілого числа, `%f` для `Float`, `%s` для тексту. У прикладі `%.2f` залишає два знаки після крапки, а `%%` виводить звичайний знак відсотка.

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

Чужий текст передаємо через `%s`: `server_print( "%s", text )`. Тоді `%` в імені чи повідомленні залишиться текстом і не сприйматиметься як інструкція форматування.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="overlapping-buffers"></a>
## Коли потрібен `format`, а не `formatex`

Треба взяти ім’я в дужки та записати результат у той самий рядок? Використовуємо `format()`, як нижче. Він враховує, що початковий текст і результат розташовані в одному буфері. `formatex()` для цього не підходить: він може затерти текст, який ще читає.

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

Це важливо і для частини того самого буфера, наприклад `text[5]`. Якщо початкові рядки лежать окремо від результату, підходить звичайний `formatex()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/format)

<a id="append-with-offset"></a>
## Дописуємо текст частинами

Повідомлення можна збирати частинами, запам’ятовуючи довжину вже записаного тексту. Наступну частину починаємо з `text[length]` і передаємо лише вільне місце. У прикладі до імені дописується кількість очок, а перша частина зберігається.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[64], length;
	length += formatex( text[length], charsmax( text ) - length, "Player: %s", "Ada" );
	// Передаємо лише місце, яке залишилося після першої частини.
	length += formatex( text[length], charsmax( text ) - length, "; score: %d", 7 );

	server_print( "%s", text );
	// Player: Ada; score: 7
}
```

Якщо дописуємо в циклі, зупиняємося, коли місце закінчилося. Саме зміщення також має бути перевірене. Читати попередню частину цього ж буфера через `%s` у такому виклику `formatex()` не можна: вхід і результат перетинатимуться.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="caret-escaping"></a>
## Лапки та перенесення рядків через `^`

У звичайному початковому коді AMXX Pawn для спеціальних символів використовується `^`. Запис `^"` додасть лапку, `^n` перенесе рядок, а `^^` дасть один символ `^`. У першому виводі нижче отримаємо лапки навколо імені та звичайний знак `^` після нього. Це обробка початкового коду, до кольорів чату вона не належить.

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

Якщо прочитати буквальні `^n` із файлу, перенесення рядка саме не з’явиться. Компілятор обробляє такі записи у вихідному коді; текст, отриманий під час роботи, за потреби розбираємо окремо.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Strings)

<a id="comparison-and-search"></a>
## Порівняння рядків і позиція підрядка

Для порівняння рядків є `equal()`, а якщо регістр не важливий, `equali()`. `containi()` шукає частину рядка без урахування регістру й повертає її позицію. У слова `admin` у прикладі позиція `0`: збіг знайшовся на самому початку.

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

Перевіряємо результат пошуку через `!= -1`. Умова `if( containi(...) )` сплутає успішний пошук у позиції `0` з невдачею, а `-1` пропустить як істину. Для пошуку без урахування регістру в Unicode окремо перевіряємо підтримку потрібних символів вибраним API.

[AMXX / Pawn](https://www.amxmodx.org/api/string/containi)

<a id="parse-fields"></a>
## `parse` для відомої кількості полів

Якщо в рядку очікується кілька полів, `parse()` розкладе їх по буферах. Пробіли розділяють поля, а текст у лапках залишається одним полем. Тому `Ada Lovelace` нижче повністю потрапляє в `name`, а `42` окремо у `score_text`. Для кожного буфера передаємо допустиму довжину.

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

Перед використанням перевіряємо кількість знайдених полів. Тут очікується `2`; за неповного рядка використовувати решту даних зарано. Для CSV потрібен відповідний розбір: у `parse()` правила звичайних аргументів команд, без CSV-екранування.

[AMXX / Pawn](https://www.amxmodx.org/api/string/parse)

<a id="argbreak-and-argparse"></a>
## Перший аргумент і решта тексту

Іноді треба відділити лише перший аргумент, а решту тексту зберегти цілком. Для цього є `argbreak()`. У прикладі ім’я в лапках іде в `target`, а фраза після нього в `message`. Якщо треба розбирати аргументи по одному далі, `argparse()` дозволяє продовжувати зі збереженої позиції.

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

Результат `-1` означає, що аргумент не знайдено. Порожній рядок у лапках при цьому вважається аргументом. Для нового коду вибираємо ці функції замість застарілої `strbreak()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/argparse)

<a id="trim-and-remove-quotes"></a>
## Прибираємо зовнішні пробіли та лапки

Для одного значення на кшталт імені спочатку прибираємо пробіли зовні через `trim()`, потім зовнішні лапки через `remove_quotes()`. Нижче з рядка з пробілами й лапками виходить `Ada Lovelace`. Якщо в рядку кілька аргументів, спочатку розбираємо їх, інакше видалені лапки змінять значення пробілів.

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

Порядок важливий: після `trim()` лапки опиняються по краях, і `remove_quotes()` може їх прибрати. Якщо пробіли всередині лапок мають значення, другий `trim()` після цього не потрібен.

[AMXX / Pawn](https://www.amxmodx.org/api/string/remove_quotes)

<a id="number-parsing"></a>
## Перевіряємо весь рядок із числом

Із `12x` можна прочитати числовий початок `12`, але коректним числом увесь рядок від цього не стане. `strtol()` дає не лише значення, а й позицію, де розбір зупинився. Нижче перевіряємо, що число взагалі знайдене, після нього нічого не залишилося, а значення потрапило в діапазон `1..32`.

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

Така перевірка приймає пробіли та знак за правилами самої функції. Якщо формат дозволяє лише цифри, перевіряємо це окремо. Самого `str_to_num()` недостатньо, щоб підтвердити правильність усього введеного рядка.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strtol)

<a id="unicode-bytes"></a>
## Байти UTF-8 та зміна регістру

Літери UTF-8 можуть займати кілька байт. Тому `strlen()` для слова `Кіт` повертає `6`, хоча видимих літер три. Місце в буфері теж рахуємо в байтах. Для зміни регістру такого тексту використовуємо функції `mb_*`: у прикладі виходить `КІТ`.

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

Після зміни регістру довжина в байтах може змінитися, тому передаємо справжню місткість буфера. Обрізати UTF-8 за випадковим байтом небезпечно: можна розірвати одну літеру. Кількість байт також не показує кількість видимих складених символів.

[AMXX / Pawn](https://www.amxmodx.org/api/string/mb_strtoupper)

<a id="multilingual-messages"></a>
## Повідомлення зі словника через `%L`

Якщо текст залежить від мови гравця, виносимо його у словник і використовуємо `%L`. Словник реєструємо один раз, потім передаємо, для кого потрібен переклад, і ключ повідомлення. Нижче беремо готовий `YES` зі стандартного `common.txt`; за англійської мови сервера отримаємо `Yes`.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_dictionary( "common.txt" );

	new text[64];
	formatex( text, charsmax( text ), "%L", LANG_SERVER, "YES" );
	server_print( "%s", text );
	// Yes, якщо мова сервера англійська.
}
```

Для одного гравця замість `LANG_SERVER` передаємо його індекс. `LANG_PLAYER` підходить для функцій розсилки, які його підтримують: заздалегідь зібрати один спільний буфер із різними мовами так не вийде. Сам файл словника має бути на сервері.

[AMXX / Pawn](https://www.amxmodx.org/api/lang/register_dictionary)
