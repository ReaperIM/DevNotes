# Строки и текст

[EN](../en/02-strings-and-text.md) | [UK](../uk/02-strings-and-text.md) | [RU](../ru/02-strings-and-text.md)

[Содержание](../../README.ru.md)

- [Сколько текста помещается в буфер](#buffers-and-charsmax)
- [Форматирование значений в буфер](#format-values)
- [Когда нужен `format`, а не `formatex`](#overlapping-buffers)
- [Дописываем текст по частям](#append-with-offset)
- [Кавычки и переносы строк через `^`](#caret-escaping)
- [Сравнение строк и позиция подстроки](#comparison-and-search)
- [`parse` для известного количества полей](#parse-fields)
- [Первый аргумент и остальной текст](#argbreak-and-argparse)
- [Убираем внешние пробелы и кавычки](#trim-and-remove-quotes)
- [Проверяем всю строку с числом](#number-parsing)
- [Байты UTF-8 и смена регистра](#unicode-bytes)
- [Сообщения из словаря через `%L`](#multilingual-messages)

<a id="buffers-and-charsmax"></a>
## Сколько текста помещается в буфер

В строковом буфере одна ячейка нужна для завершающего нуля. Поэтому в `name[16]` поместится до 15 байт текста. `charsmax(name)` как раз даёт эти `15`, а `strlen(name)` показывает, сколько байт уже занято. В примере имя `Ada` занимает только три.

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

Передаём длину в том виде, который ждёт конкретная функция. Для строк часто нужен предел без завершающего нуля, а для массивов может требоваться число всех ячеек. Поэтому заменять `charsmax` на `sizeof` везде подряд нельзя.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strlen)

<a id="format-values"></a>
## Форматирование значений в буфер

Когда нужно собрать текст из имени, чисел и других значений, используем `formatex()`. Передаём буфер и доступную длину, затем строку формата: `%d` для целого числа, `%f` для `Float`, `%s` для текста. В примере `%.2f` оставляет два знака после точки, а `%%` выводит обычный знак процента.

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

Чужой текст передаём через `%s`: `server_print( "%s", text )`. Тогда встретившийся в имени или сообщении `%` останется текстом и не будет воспринят как инструкция форматирования.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="overlapping-buffers"></a>
## Когда нужен `format`, а не `formatex`

Нужно обернуть имя в скобки и записать результат в ту же строку? Используем `format()`, как ниже. Он учитывает, что входной текст и результат находятся в одном буфере. `formatex()` для этого не подходит: он может затереть текст, который ещё читает.

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

Это важно и для части того же буфера, например `text[5]`. Если исходные строки лежат отдельно от результата, подходит обычный `formatex()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/format)

<a id="append-with-offset"></a>
## Дописываем текст по частям

Сообщение можно собирать по частям, запоминая длину уже записанного текста. Следующую часть начинаем с `text[length]` и передаём только оставшееся место. В примере к имени дописывается количество очков, первая часть при этом сохраняется.

```pawn
#include <amxmodx>

public plugin_init()
{
	new text[64], length;
	length += formatex( text[length], charsmax( text ) - length, "Player: %s", "Ada" );
	// Передаём только место, оставшееся после первой части.
	length += formatex( text[length], charsmax( text ) - length, "; score: %d", 7 );

	server_print( "%s", text );
	// Player: Ada; score: 7
}
```

Если дописываем в цикле, останавливаемся, когда место закончилось. Само смещение тоже должно быть проверено. Читать прежнюю часть этого же буфера через `%s` в таком вызове `formatex()` нельзя: вход и результат будут пересекаться.

[AMXX / Pawn](https://www.amxmodx.org/api/string/formatex)

<a id="caret-escaping"></a>
## Кавычки и переносы строк через `^`

В обычном исходнике AMXX Pawn для специальных символов используется `^`. Запись `^"` добавит кавычку, `^n` перенесёт строку, а `^^` даст один символ `^`. В первом выводе ниже получатся кавычки вокруг имени и обычный знак `^` после него. Это обработка исходного текста, к цветам чата она не относится.

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

Если прочитать буквальные `^n` из файла, перенос строки сам не появится. Компилятор обрабатывает такие записи в исходнике; текст, полученный во время работы, при необходимости разбираем отдельно.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Strings)

<a id="comparison-and-search"></a>
## Сравнение строк и позиция подстроки

Для сравнения строк есть `equal()`, а если регистр не важен, `equali()`. `containi()` ищет часть строки без учёта регистра и возвращает её позицию. У слова `admin` в примере позиция `0`: совпадение нашлось в самом начале.

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

Проверяем результат поиска через `!= -1`. Условие `if( containi(...) )` перепутает успешный поиск в позиции `0` с неудачей, а `-1` пропустит как истину. Для поиска без учёта регистра в Unicode отдельно проверяем поддержку нужных символов выбранным API.

[AMXX / Pawn](https://www.amxmodx.org/api/string/containi)

<a id="parse-fields"></a>
## `parse` для известного количества полей

Если в строке ожидается несколько полей, `parse()` разложит их по буферам. Пробелы разделяют поля, а текст в кавычках остаётся одним полем. Поэтому `Ada Lovelace` ниже целиком попадает в `name`, а `42` отдельно в `score_text`. Для каждого буфера передаём допустимую длину.

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

Перед использованием проверяем число найденных полей. Здесь ожидается `2`; при неполной строке остальные данные использовать рано. Для CSV нужен подходящий разбор: у `parse()` правила обычных аргументов команд, без CSV-экранирования.

[AMXX / Pawn](https://www.amxmodx.org/api/string/parse)

<a id="argbreak-and-argparse"></a>
## Первый аргумент и остальной текст

Иногда нужно отделить только первый аргумент, а оставшийся текст сохранить целиком. Для этого есть `argbreak()`. В примере имя в кавычках уходит в `target`, а фраза после него в `message`. Если нужно разбирать аргументы по одному дальше, `argparse()` позволяет продолжать с сохранённой позиции.

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

Результат `-1` означает, что аргумент не найден. Пустая строка в кавычках при этом считается аргументом. Для нового кода выбираем эти функции вместо устаревшей `strbreak()`.

[AMXX / Pawn](https://www.amxmodx.org/api/string/argparse)

<a id="trim-and-remove-quotes"></a>
## Убираем внешние пробелы и кавычки

Для одного значения вроде имени сначала убираем пробелы снаружи через `trim()`, затем внешние кавычки через `remove_quotes()`. Ниже из строки с пробелами и кавычками получается `Ada Lovelace`. Если в строке несколько аргументов, сначала разбираем их, иначе удалённые кавычки изменят смысл пробелов.

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

Порядок важен: после `trim()` кавычки оказываются по краям, и `remove_quotes()` может их снять. Если пробелы внутри кавычек значимы, второй `trim()` после этого не нужен.

[AMXX / Pawn](https://www.amxmodx.org/api/string/remove_quotes)

<a id="number-parsing"></a>
## Проверяем всю строку с числом

Из `12x` можно прочитать числовое начало `12`, но корректным числом вся строка от этого не станет. `strtol()` даёт не только значение, но и позицию, где разбор остановился. Ниже проверяем, что число вообще найдено, после него ничего не осталось, а значение попало в диапазон `1..32`.

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

Такая проверка принимает пробелы и знак по правилам самой функции. Если формат разрешает только цифры, проверяем это отдельно. Одного `str_to_num()` недостаточно, чтобы подтвердить правильность всей введённой строки.

[AMXX / Pawn](https://www.amxmodx.org/api/string/strtol)

<a id="unicode-bytes"></a>
## Байты UTF-8 и смена регистра

У букв UTF-8 может быть несколько байт. Поэтому `strlen()` для слова `Кіт` возвращает `6`, хотя видимых букв три. Место в буфере тоже считаем в байтах. Для изменения регистра такого текста используем функции `mb_*`: в примере получается `КІТ`.

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

После смены регистра длина в байтах может измениться, поэтому передаём настоящую вместимость буфера. Обрезать UTF-8 по случайному байту опасно: можно разорвать одну букву. Число байт также не показывает число видимых составных символов.

[AMXX / Pawn](https://www.amxmodx.org/api/string/mb_strtoupper)

<a id="multilingual-messages"></a>
## Сообщения из словаря через `%L`

Если текст зависит от языка игрока, выносим его в словарь и используем `%L`. Словарь регистрируем один раз, затем передаём, для кого нужен перевод, и ключ сообщения. Ниже берём готовый `YES` из стандартного `common.txt`; при английском языке сервера получим `Yes`.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_dictionary( "common.txt" );

	new text[64];
	formatex( text, charsmax( text ), "%L", LANG_SERVER, "YES" );
	server_print( "%s", text );
	// Yes, если язык сервера английский.
}
```

Для одного игрока вместо `LANG_SERVER` передаём его индекс. `LANG_PLAYER` подходит для поддерживающих его функций рассылки: заранее собрать один общий буфер с разными языками так не получится. Сам файл словаря должен быть на сервере.

[AMXX / Pawn](https://www.amxmodx.org/api/lang/register_dictionary)
