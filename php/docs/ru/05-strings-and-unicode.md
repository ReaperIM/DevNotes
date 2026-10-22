# Строки и Unicode

[EN](../en/05-strings-and-unicode.md) | [UK](../uk/05-strings-and-unicode.md) | [RU](../ru/05-strings-and-unicode.md)

[Содержание](../../README.ru.md)

Строковые API работают с разными единицами: байтами, кодовыми точками или графемными кластерами. Сначала определите единицу, затем операцию поиска, разделения или измерения длины.

- [UTF-8: байты, кодовые точки и графемные кластеры](#byte-codepoint-grapheme)
- [Чтение с конца через отрицательные индексы](#negative-offsets)
- [Поиск подстроки, префикса или суффикса](#string-search)
- [Разбиение многобайтовой строки по кодовым точкам](#mb-split)
- [Дополнение текста по количеству кодовых точек](#mb-pad)
- [Изменение регистра первого символа Unicode](#mb-case)
- [Удаление пробелов Unicode по краям](#unicode-trim)
- [Разбиение по границам видимых символов](#grapheme-split)
- [Явное увеличение буквенно-числовой ASCII-метки](#string-increment)
- [Естественные отступы в heredoc и nowdoc](#heredoc)
- [Разрешённые теги массивом для `strip_tags()`](#allowed-tags)
- [Оформление списка по правилам языка читателя](#intl-lists)
- [Стандартное направление письма локали](#locale-direction)
- [Сравнение Unicode-текста по графемному расстоянию](#grapheme-distance)

<a id="byte-codepoint-grapheme"></a>

## UTF-8: байты, кодовые точки и графемные кластеры

**PHP 7.0+ | Ядро / mbstring / intl | Контекст** | [PHP](https://www.php.net/manual/en/migration70.new-features.php) | [PHP](https://www.php.net/manual/en/function.grapheme-strlen.php)

Строка PHP содержит байты; в этом примере UTF-8 `strlen()`, `mb_strlen()` и `grapheme_strlen()` считают соответственно байты, кодовые точки и графемные кластеры. Unicode-последовательности появились в 7.0.

```php
$text = "e\u{0301}";
$bytes = strlen( $text ); # 3
$code_points = mb_strlen( $text, 'UTF-8' ); # 2
$graphemes = grapheme_strlen( $text ); # 1
```

Кодовые точки и графемные кластеры являются разными единицами, и ни одна не гарантирует ширину в колонках терминала.

<a id="negative-offsets"></a>

## Чтение с конца через отрицательные индексы

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Отрицательные индексы строки адресуют байты с конца.

```php
$code = 'INV-42';
$last = $code[-1]; # '2'
$previous = $code[-2]; # '4'
```

Для UTF-8 используйте `mb_substr()` для кодовых точек или `grapheme_substr()` для графемных кластеров.

<a id="string-search"></a>

## Поиск подстроки, префикса или суффикса

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-contains.php) | [PHP](https://www.php.net/manual/en/function.str-starts-with.php) | [PHP](https://www.php.net/manual/en/function.str-ends-with.php)

`str_contains()`, `str_starts_with()` и `str_ends_with()` возвращают логические значения вместо позиций.

```php
$path = '/api/users.php';
$contains = str_contains( $path, 'users' ); # true
$starts = str_starts_with( $path, '/api/' ); # true
$ends = str_ends_with( $path, '.php' ); # true
```

Поиск чувствителен к регистру и работает с байтами; пустой образец совпадает во всех трёх функциях.

<a id="mb-split"></a>

## Разбиение многобайтовой строки по кодовым точкам

**PHP 7.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-split.php)

`mb_str_split()` разбивает строку по символам выбранной кодировки, а не по байтам.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' ); # ['Ки', 'їв']
```

Комбинируемый знак может отделиться от основы; с 8.4 `grapheme_str_split()` сохраняет графемные кластеры.

<a id="mb-pad"></a>

## Дополнение текста по количеству кодовых точек

**PHP 8.3+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-pad.php)

`mb_str_pad()` дополняет строку по числу кодовых точек.

```php
$right = mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ); # Київ..
$left = mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ); # ...PHP
```

Она не обрезает более длинную строку и не измеряет видимую ширину эмодзи и другого текста.

<a id="mb-case"></a>

## Изменение регистра первого символа Unicode

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-ucfirst.php) | [PHP](https://www.php.net/manual/en/function.mb-lcfirst.php)

`mb_ucfirst()` переводит первый символ в титульный регистр, `mb_lcfirst()` в нижний; остальное не меняется.

```php
$upper = mb_ucfirst( 'київ', 'UTF-8' ); # Київ
$lower = mb_lcfirst( 'Київ', 'UTF-8' ); # київ
```

Регистровое преобразование Unicode может изменить число кодовых точек; это не локализованное оформление заголовков.

<a id="unicode-trim"></a>

## Удаление пробелов Unicode по краям

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-trim.php)

`mb_trim()`, `mb_ltrim()` и `mb_rtrim()` удаляют крайние многобайтовые символы с более широким стандартным набором пробелов, чем `trim()`.

```php
$text = "\u{00A0}PHP\u{3000}";
$trimmed = mb_trim( $text, null, 'UTF-8' ); # PHP
```

Свой список символов заменяет стандартный набор и не поддерживает обозначение диапазонов из `trim()`.

<a id="grapheme-split"></a>

## Разбиение по границам видимых символов

**PHP 8.4+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-str-split.php)

`grapheme_str_split()` разбивает UTF-8 на границах графем, сохраняя комбинируемые знаки вместе с основой.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text ); # ["e\u{0301}", 'x']
```

Некорректный UTF-8 или другая ошибка может дать `false`; размер части должен быть не меньше 1.

<a id="string-increment"></a>

## Явное увеличение буквенно-числовой ASCII-метки

**PHP 8.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-increment.php) | [PHP](https://www.php.net/manual/en/function.str-decrement.php)

`str_increment()` и `str_decrement()` явно изменяют буквенно-цифровые метки ASCII.

```php
$next = str_increment( 'INV0099' ); # INV0100
$previous = str_decrement( 'INV0100' ); # INV0099
$carry = str_increment( 'ZZ' ); # AAA
```

Пустые строки, пунктуация и буквы Unicode вызывают `ValueError`.

<a id="heredoc"></a>

## Естественные отступы в heredoc и nowdoc

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

Отступ завершающего маркера heredoc или nowdoc удаляется из тела строки.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;
```

Heredoc подставляет переменные, nowdoc с кавычками в начальном маркере сохраняет текст буквально; в отступе нельзя смешивать табуляции и пробелы.

<a id="allowed-tags"></a>

## Разрешённые теги массивом для `strip_tags()`

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.strip-tags.php)

С 7.4 `strip_tags()` принимает массив имён разрешённых тегов.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
$text = strip_tags( $html, ['p'] ); # <p class="note">Hello</p>bad()
```

Это не средство очистки HTML: атрибуты разрешённых тегов сохраняются, а содержимое скриптов может остаться текстом.

<a id="intl-lists"></a>

## Оформление списка по правилам языка читателя

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/class.intllistformatter.php)

`IntlListFormatter` соединяет списки локализованными союзами; `TYPE_OR` и `TYPE_UNITS` задают другие стили.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] ); # PHP and HTML
```

Требуется ICU 67+; результат зависит от данных локали, а ошибка форматирования может дать `false`.

<a id="locale-direction"></a>

## Стандартное направление письма локали

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/locale.isrighttoleft.php)

`Locale::isRightToLeft()` возвращает стандартное направление письма основной письменности локали.

```php
$english_rtl = Locale::isRightToLeft( 'en' ); # false
$arabic_rtl = Locale::isRightToLeft( 'ar' ); # true
```

Она описывает локаль, а не направление произвольного текста или фрагментов со смешанным направлением.

<a id="grapheme-distance"></a>

## Сравнение Unicode-текста по графемному расстоянию

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-levenshtein.php)

`grapheme_levenshtein()` измеряет расстояние редактирования в графемных кластерах и учитывает каноническую эквивалентность.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
$distance = grapheme_levenshtein( $composed, $decomposed ); # 0
```

Некорректный UTF-8 или ошибки ICU могут дать `false`; перед использованием расстояния проверяйте `=== false`.
