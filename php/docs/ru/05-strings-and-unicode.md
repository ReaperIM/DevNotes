# Строки и Unicode

[EN](../en/05-strings-and-unicode.md) | [UK](../uk/05-strings-and-unicode.md) | [RU](../ru/05-strings-and-unicode.md)

[Содержание](../../README.ru.md)

Со строками главный вопрос: считаем байты или видимые символы? От этого зависит, какую функцию взять для длины, поиска или разбиения текста.

- [Почему у одного символа бывает три длины](#byte-codepoint-grapheme)
- [Читаем строку с конца](#negative-offsets)
- [Проверяем подстроку, начало и конец строки](#string-search)
- [Делим текст без разрезания байтов одной буквы](#mb-split)
- [Дополняем строку до нужной длины](#mb-pad)
- [Меняем регистр только первой буквы](#mb-case)
- [Убираем пробелы Unicode по краям](#unicode-trim)
- [Не отрываем знак ударения от буквы](#grapheme-split)
- [Получаем следующую буквенно-числовую метку](#string-increment)
- [Оставляем нормальные отступы у многострочного текста](#heredoc)
- [Передаём разрешённые теги массивом](#allowed-tags)
- [Собираем список по правилам языка](#intl-lists)
- [Узнаём направление письма для языка](#locale-direction)
- [Сравниваем текст по целым видимым символам](#grapheme-distance)

<a id="byte-codepoint-grapheme"></a>
## Почему у одного символа бывает три длины

**PHP 7.0+ | Ядро / mbstring / intl | Контекст** | [PHP](https://www.php.net/manual/en/migration70.new-features.php) | [PHP](https://www.php.net/manual/en/function.grapheme-strlen.php)

В PHP строка хранится как набор байтов, но один видимый символ не всегда равен одному байту или одной части Unicode. В примере буква `e` и отдельный знак ударения выглядят вместе как один символ. Поэтому `strlen()` насчитает `3` байта, `mb_strlen()` две кодовые точки, а `grapheme_strlen()` одну графему, то есть видимый символ целиком.

```php
$text = "e\u{0301}";
$bytes = strlen( $text ); # 3
$code_points = mb_strlen( $text, 'UTF-8' ); # 2
$graphemes = grapheme_strlen( $text ); # 1
```

Запись Unicode-символа через его код, как в примере, доступна с PHP 7.0. Для поиска и разрезания текста важно выбрать нужную единицу. Даже количество графем не говорит, сколько колонок текст займёт в терминале.

<a id="negative-offsets"></a>
## Читаем строку с конца

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Отрицательный индекс считает от конца строки: `-1` берёт последний байт, `-2` предпоследний. Для коротких ASCII-кодов вроде `INV-42` это удобно и предсказуемо.

```php
$code = 'INV-42';
$last = $code[-1]; # '2'
$previous = $code[-2]; # '4'
```

У UTF-8 одна буква может состоять из нескольких байтов, поэтому так легко взять только её часть. Для кодовых точек есть `mb_substr()`, а для целых видимых символов `grapheme_substr()`.

<a id="string-search"></a>
## Проверяем подстроку, начало и конец строки

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-contains.php) | [PHP](https://www.php.net/manual/en/function.str-starts-with.php) | [PHP](https://www.php.net/manual/en/function.str-ends-with.php)

Когда нужна только проверка наличия, позицию можно вообще не искать. `str_contains()` проверяет подстроку, `str_starts_with()` начало, а `str_ends_with()` конец строки. Все три сразу дают `true` или `false`.

```php
$path = '/api/users.php';
$contains = str_contains( $path, 'users' ); # true
$starts = str_starts_with( $path, '/api/' ); # true
$ends = str_ends_with( $path, '.php' ); # true
```

Регистр имеет значение, поиск идёт по байтам. Пустая искомая строка считается найденной во всех трёх функциях, поэтому при необходимости пустой ввод проверяем отдельно.

<a id="mb-split"></a>
## Делим текст без разрезания байтов одной буквы

**PHP 7.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-split.php)

`mb_str_split()` делит строку по символам выбранной кодировки. Здесь просим части длиной по два символа UTF-8 и получаем `Ки` и `їв`, а не куски отдельных байтов.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' ); # ['Ки', 'їв']
```

Для Unicode это счёт по кодовым точкам. Отдельный знак ударения всё ещё может оторваться от буквы. Если нужно сохранять такие сочетания целиком, с PHP 8.4 есть `grapheme_str_split()`.

<a id="mb-pad"></a>
## Дополняем строку до нужной длины

**PHP 8.3+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-pad.php)

`mb_str_pad()` добавляет символы слева или справа до указанной длины. В UTF-8 он считает кодовые точки, поэтому `Київ` считается четырьмя, и до шести не хватает двух точек.

```php
$right = mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ); # Київ..
$left = mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ); # ...PHP
```

Более длинную строку функция не обрежет. Для выравнивания на экране это тоже не точная линейка: видимая ширина сложных символов и прочего текста может отличаться от числа кодовых точек.

<a id="mb-case"></a>
## Меняем регистр только первой буквы

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-ucfirst.php) | [PHP](https://www.php.net/manual/en/function.mb-lcfirst.php)

`mb_ucfirst()` меняет первый символ по правилам титульного регистра Unicode, а `mb_lcfirst()` делает его строчным. Остальная строка остаётся как была. Для `київ` и `Київ` результат виден прямо в примере.

```php
$upper = mb_ucfirst( 'київ', 'UTF-8' ); # Київ
$lower = mb_lcfirst( 'Київ', 'UTF-8' ); # київ
```

Это работа с первым символом, а не оформление всего заголовка по правилам конкретного языка. У Unicode изменение регистра иногда меняет и количество кодовых точек.

<a id="unicode-trim"></a>
## Убираем пробелы Unicode по краям

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-trim.php)

Обычный `trim()` знает не все пробельные символы Unicode. `mb_trim()`, `mb_ltrim()` и `mb_rtrim()` по умолчанию берут более широкий набор. В примере убираются неразрывный пробел слева и широкий пробел справа.

```php
$text = "\u{00A0}PHP\u{3000}";
$trimmed = mb_trim( $text, null, 'UTF-8' ); # PHP
```

Если передать свой список символов, он заменит стандартный набор. Обозначать диапазоны так же, как в `trim()`, здесь нельзя: это именно список символов.

<a id="grapheme-split"></a>
## Не отрываем знак ударения от буквы

**PHP 8.4+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-str-split.php)

Если один видимый символ состоит из буквы и отдельного знака, при разбиении хочется сохранить их вместе. `grapheme_str_split()` делит UTF-8 по графемам, поэтому `e` со знаком ударения остаётся одной частью, а `x` идёт следующей.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text ); # ["e\u{0301}", 'x']
```

Размер части должен быть не меньше `1`. При повреждённом UTF-8 или другой ошибке результат может быть `false`, поэтому его не стоит сразу считать готовым массивом.

<a id="string-increment"></a>
## Получаем следующую буквенно-числовую метку

**PHP 8.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-increment.php) | [PHP](https://www.php.net/manual/en/function.str-decrement.php)

Для ASCII-меток вроде номера документа есть явные `str_increment()` и `str_decrement()`. Они увеличивают или уменьшают такую строку по её буквенно-числовым правилам: после `INV0099` идёт `INV0100`, а после `ZZ` уже `AAA`.

```php
$next = str_increment( 'INV0099' ); # INV0100
$previous = str_decrement( 'INV0100' ); # INV0099
$carry = str_increment( 'ZZ' ); # AAA
```

Подходят только непустые строки из букв и цифр ASCII. Пунктуация, буквы Unicode и пустая строка вызовут `ValueError`.

<a id="heredoc"></a>
## Оставляем нормальные отступы у многострочного текста

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

Закрывающий маркер heredoc или nowdoc больше не обязательно прижимать к левому краю. Его отступ вычитается из строк внутри, поэтому текст можно аккуратно вписать в окружающий код без лишней табуляции в результате.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;
```

Heredoc подставляет переменные, поэтому `$name` попадёт в приветствие. Nowdoc с кавычками у начального маркера оставляет текст буквально. В одном отступе не смешиваем табуляцию и пробелы.

<a id="allowed-tags"></a>
## Передаём разрешённые теги массивом

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.strip-tags.php)

В `strip_tags()` можно передать массив имён тегов, которые нужно оставить. Здесь сохраняем `p`, а сам тег `script` удаляем.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
$text = strip_tags( $html, ['p'] ); # <p class="note">Hello</p>bad()
```

По результату видно важный нюанс: атрибут `class` остался, а `bad()` превратился в обычный текст. Функция убирает теги, но не делает произвольный HTML безопасным. Атрибуты разрешённых тегов тоже остаются, поэтому это не готовая защита от опасного HTML.

<a id="intl-lists"></a>
## Собираем список по правилам языка

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/class.intllistformatter.php)

Для списка в обычном тексте одного `implode()` иногда мало: последний элемент нужно соединить союзом нужного языка. `IntlListFormatter` берёт это на себя. В примере английская локаль и `TYPE_AND` дают `PHP and HTML`.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] ); # PHP and HTML
```

Для других способов соединения есть `TYPE_OR` и `TYPE_UNITS`. Нужна ICU 67 или новее, а конкретный текст зависит от её данных локали. Если форматирование не удалось, можно получить `false`.

<a id="locale-direction"></a>
## Узнаём направление письма для языка

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/locale.isrighttoleft.php)

`Locale::isRightToLeft()` подсказывает, принято ли в основной письменности локали писать справа налево. Для английской `en` получаем `false`, для арабской `ar` получаем `true`.

```php
$english_rtl = Locale::isRightToLeft( 'en' ); # false
$arabic_rtl = Locale::isRightToLeft( 'ar' ); # true
```

Это свойство локали, а не анализ переданного текста. По нему нельзя определить направление произвольной строки или отдельных частей смешанного текста.

<a id="grapheme-distance"></a>
## Сравниваем текст по целым видимым символам

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-levenshtein.php)

`grapheme_levenshtein()` считает расстояние редактирования по графемам, а не по отдельным байтам. Она также понимает равнозначные записи Unicode: готовая буква с ударением и буква с отдельным знаком в примере считаются одинаковыми, поэтому расстояние равно `0`.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
$distance = grapheme_levenshtein( $composed, $decomposed ); # 0
```

Повреждённый UTF-8 или ошибка ICU могут дать `false`. Проверяем именно `=== false`: ноль здесь правильный результат для совпавших строк.
