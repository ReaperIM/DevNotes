# Строки и текст

[EN](../en/02-strings-and-text.md) | [UK](../uk/02-strings-and-text.md) | [RU](../ru/02-strings-and-text.md)

[Содержание](../../README.ru.md)

- [Размер буфера и `charsmax`](#buffers-and-charsmax)
- [Форматирование значений в буфер](#format-values)
- [`format` при пересечении ввода и вывода](#overlapping-buffers)
- [Сборка текста со смещением записи](#append-with-offset)
- [Экранирование через ^ в Pawn](#caret-escaping)
- [Сравнение строк и позиция подстроки](#comparison-and-search)
- [`parse` для известного количества полей](#parse-fields)
- [Первый аргумент и остальной текст](#argbreak-and-argparse)
- [Убрать внешние пробелы и кавычки](#trim-and-remove-quotes)
- [Проверить целый числовой токен](#number-parsing)
- [Байты UTF-8 и смена регистра](#unicode-bytes)
- [Сообщения из словаря через `%L`](#multilingual-messages)

<a id="buffers-and-charsmax"></a>
## Размер буфера и `charsmax`

Обычная строка AMXX представляет собой массив с нулевой ячейкой в конце. В буфере из 16 ячеек поместятся до 15 байт текста и терминатор. `charsmax()` даёт доступную вместимость для текста.

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

Соблюдайте соглашение конкретного API: многие строковые natives принимают максимальную длину текста, а часть функций массивов количество ячеек. `sizeof` и `charsmax` не взаимозаменяемы.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strlen)

<a id="format-values"></a>
## Форматирование значений в буфер

`formatex()` записывает ограниченный результат прямо в буфер. Формат должен соответствовать значению: `%d` для целых чисел, `%f` для `Float`, `%s` для строк.

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

Внешний текст передавайте как значение: `server_print( "%s", text )`. Если он станет строкой формата, знаки процента в нём могут восприниматься как директивы форматирования.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="overlapping-buffers"></a>
## `format` при пересечении ввода и вывода

`formatex()` не может читать из буфера, который сейчас перезаписывает. Для изменения на месте с пересечением источника и результата `format()` обеспечивает необходимое промежуточное копирование.

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

Это относится и к части буфера вроде `text[5]`. Для обычного случая с отдельными источниками используйте `formatex()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/format)

<a id="append-with-offset"></a>
## Сборка текста со смещением записи

Когда сообщение собирается из частей, храните длину уже записанного текста. Каждый вызов получает только остаток вместимости, а не размер всего буфера.

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

В цикле прекращайте добавление, когда места больше нет. Не берите смещение из непроверенной внешней длины и не передавайте предыдущую часть этого же буфера через `%s`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="caret-escaping"></a>
## Экранирование через ^ в Pawn

В обычном исходном коде AMXX Pawn экранирование начинается с `^`: `^"` добавляет кавычку, `^n` новую строку, `^^` символ ^. Это экранирование исходного кода, а не коды цветов чата.

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

В строке из файла буквальные символы `^n` автоматически не станут новой строкой. Разбор исходного кода и обработка текста во время выполнения являются разными операциями.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Strings)

<a id="comparison-and-search"></a>
## Сравнение строк и позиция подстроки

`equal()` проверяет равенство, `equali()` не учитывает регистр, `containi()` возвращает позицию подстроки. Ноль означает совпадение в начале, а не неудачу.

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

Не пишите `if( containi(...) )`: это отвергнет позицию 0 и примет -1 за успех. Для сравнений Unicode без учёта регистра проверяйте поддержку нужных символов в выбранном API.

[AMXX / Pawn](https://www.amxmodx.org/api/string/containi)

<a id="parse-fields"></a>
## `parse` для известного количества полей

`parse()` разделяет поля по пробелам, сохраняя группы в кавычках. Для каждого результата передавайте отдельный буфер и максимальную длину текста.

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

Перед использованием проверяйте количество полученных полей. Это разбор аргументов в стиле команд, а не CSV-парсер с правилами экранирования CSV.

[AMXX / Pawn](https://www.amxmodx.org/api/string/parse)

<a id="argbreak-and-argparse"></a>
## Первый аргумент и остальной текст

`argbreak()` удобен, когда отдельно нужен только первый аргумент, а остальное должно остаться текстом. Он понимает кавычки вокруг первого аргумента; `argparse()` также позволяет последовательный разбор по смещению.

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

`-1` означает, что аргумент не найден; пустые кавычки всё равно задают аргумент. Эти API стоит использовать вместо устаревшего `strbreak()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/argparse)

<a id="trim-and-remove-quotes"></a>
## Убрать внешние пробелы и кавычки

Одно значение в кавычках можно нормализовать через `trim()` и `remove_quotes()`. Это обработка целого значения; не убирайте кавычки из строки аргументов до её разбора.

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

Порядок важен: сначала trim, чтобы кавычки стали крайними символами. Если пробелы внутри кавычек значимы, не выполняйте trim повторно после их удаления.

[AMXX / Pawn](https://www.amxmodx.org/api/string/remove_quotes)

<a id="number-parsing"></a>
## Проверить целый числовой токен

Одного `str_to_num()` недостаточно, чтобы доказать корректность всего ввода. Позиция завершения `strtol()` позволяет отвергнуть числовое начало с посторонним текстом в конце.

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

Пример принимает правила native относительно пробелов и знака, а затем проверяет диапазон. Если формат допускает только цифры, это нужно проверить отдельно: числовое преобразование не заменяет проверку формата.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strtol)

<a id="unicode-bytes"></a>
## Байты UTF-8 и смена регистра

Буквы UTF-8 могут занимать несколько байт. `strlen()` считает байты, а не видимые буквы; вместимость буфера тоже должна учитывать эти байты. Для регистра UTF-8 используйте функции `mb_*`.

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

Смена регистра может изменить число байт, поэтому передавайте реальную вместимость. Обрезка по произвольному байтовому смещению может разорвать UTF-8; байтовая длина также не равна числу графем.

[AMXX / Pawn](https://www.amxmodx.org/api/string/mb_strtoupper)

<a id="multilingual-messages"></a>
## Сообщения из словаря через `%L`

Словарь регистрируется один раз, после чего `%L` находит ключ для нужного языка. В примере используется стандартный `common.txt`, где уже есть ключ `YES`.

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

Для одного игрока передайте его индекс вместо `LANG_SERVER`. `LANG_PLAYER` предназначен для поддерживаемой печати нескольким адресатам, а не для однократного форматирования общего буфера. Файл словаря должен быть на сервере.

[AMXX / Pawn](https://www.amxmodx.org/api/lang/register_dictionary)
