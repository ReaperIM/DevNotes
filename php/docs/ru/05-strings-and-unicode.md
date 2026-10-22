# Строки и Unicode

[English](../en/05-strings-and-unicode.md) | [Українська](../uk/05-strings-and-unicode.md) | [Русский](../ru/05-strings-and-unicode.md)

[Содержание](../../README.ru.md)

Для двоичных или ASCII-данных выбирайте байтовые операции, для человеческого текста учитывайте Unicode. Эта разница важнее длины имени функции. Примеры с mb_ требуют mbstring, а grapheme_ и Locale требуют intl.

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

**PHP 7.0+ | Ядро / mbstring / intl | Контекст**

Строка PHP хранит байты. Разные операции считают байты, кодовые точки Unicode или графемные кластеры, воспринимаемые пользователем как символы. Unicode-последовательности в двойных кавычках добавлены в PHP 7.0; они делают невидимые комбинируемые знаки явными.

```php
$text = "e\u{0301}"; # e + combining acute accent
echo strlen( $text ) . PHP_EOL; # 3 bytes
echo mb_strlen( $text, 'UTF-8' ) . PHP_EOL; # 2 code points
echo grapheme_strlen( $text ) . PHP_EOL; # 1 grapheme cluster
```

**Важно:** Выбирайте единицу до обрезания, дополнения или проверки длины. Графемные кластеры также не равны ширине столбцов терминала. Для соответствующих вызовов нужны включённые mbstring и intl.

[Документация PHP: Unicode escapes](https://www.php.net/manual/en/migration70.new-features.php) | [Документация PHP: grapheme_strlen()](https://www.php.net/manual/en/function.grapheme-strlen.php)

<a id="negative-offsets"></a>

## Чтение с конца через отрицательные индексы

**PHP 7.1+ | Ядро**

Отрицательные индексы строки отсчитываются от конца. Для ASCII-токенов это простой способ прочитать последний символ без вычисления `strlen()` - 1.

```php
$code = 'INV-42';
echo $code[-1] . PHP_EOL; # 2
echo $code[-2] . PHP_EOL; # 4
```

**Важно:** Индексы всё равно адресуют байты. Для UTF-8 используйте `mb_substr()` или `grapheme_substr()`, в зависимости от нужной единицы. Индекс за пределами строки не является проверкой пустого поля.

[Документация PHP: negative offsets](https://www.php.net/manual/en/migration71.new-features.php)

<a id="string-search"></a>

## Поиск подстроки, префикса или суффикса

**PHP 8.0+ | Ядро**

`str_contains()`, `str_starts_with()` и `str_ends_with()` возвращают логический ответ на конкретный вопрос. Они не смешивают позицию 0 с маркером отсутствия false, возвращаемым `strpos()`.

```php
$path = '/api/users.php';
var_dump( str_contains($path, 'users') ); # bool(true)
var_dump( str_starts_with($path, '/api/') ); # bool(true)
var_dump( str_ends_with($path, '.php') ); # bool(true)
var_dump( str_contains($path, '') ); # bool(true)
```

**Важно:** Поиск чувствителен к регистру и работает с байтами. Пустая подстрока даёт совпадение. Отдельно отклоняйте пустой поисковый запрос, если он недопустим; поиск подстроки не проверяет корректность URL или пути.

[Документация PHP: str_contains()](https://www.php.net/manual/en/function.str-contains.php) | [Документация PHP: str_starts_with()](https://www.php.net/manual/en/function.str-starts-with.php) | [Документация PHP: str_ends_with()](https://www.php.net/manual/en/function.str-ends-with.php)

<a id="mb-split"></a>

## Разбиение многобайтовой строки по кодовым точкам

**PHP 7.4+ | mbstring**

`mb_str_split()` создаёт части, измеренные в символах выбранной кодировки. Она заменяет байтовую `str_split()` при обработке букв UTF-8.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' );
echo json_encode( $parts, JSON_UNESCAPED_UNICODE ) . PHP_EOL; # ["Ки","їв"]
```

**Важно:** Комбинируемый знак может отделиться от основной буквы. Для границ видимых символов используйте `grapheme_str_split()` с PHP 8.4. Явно задавайте кодировку для предсказуемой обработки.

[Документация PHP: mb_str_split()](https://www.php.net/manual/en/function.mb-str-split.php)

<a id="mb-pad"></a>

## Дополнение текста по количеству кодовых точек

**PHP 8.3+ | mbstring**

`mb_str_pad()` использует количество кодовых точек вместо количества байтов. Поэтому кириллическое и ASCII-слово можно дополнять по одинаковому правилу длины в символах.

```php
echo mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ) . PHP_EOL; # Київ..
echo mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ) . PHP_EOL; # ...PHP
```

**Важно:** Дополнение не обрезает более длинную входную строку. Кодовые точки не являются визуальными столбцами, поэтому функция сама не выровняет каждую таблицу терминала или надпись с эмодзи.

[Документация PHP: mb_str_pad()](https://www.php.net/manual/en/function.mb-str-pad.php)

<a id="mb-case"></a>

## Изменение регистра первого символа Unicode

**PHP 8.4+ | mbstring**

`mb_ucfirst()` переводит первый символ в титульный регистр, `mb_lcfirst()` делает его строчным. Остальной текст сохраняется. Это заменяет небольшую, но склонную к ошибкам комбинацию обрезания и изменения регистра.

```php
echo mb_ucfirst( 'київ', 'UTF-8' ) . PHP_EOL; # Київ
echo mb_lcfirst( 'Київ', 'UTF-8' ) . PHP_EOL; # київ
```

**Важно:** Это не оформление всего предложения как заголовка и не языковое правило для имён. Преобразование регистра Unicode может изменить количество кодовых точек.

[Документация PHP: mb_ucfirst()](https://www.php.net/manual/en/function.mb-ucfirst.php) | [Документация PHP: mb_lcfirst()](https://www.php.net/manual/en/function.mb-lcfirst.php)

<a id="unicode-trim"></a>

## Удаление пробелов Unicode по краям

**PHP 8.4+ | mbstring**

`mb_trim()`, `mb_ltrim()` и `mb_rtrim()` понимают многобайтовые символы и имеют более широкий стандартный набор пробелов, чем `trim()`. Это полезно для текста, вставленного из документов или веб-страниц.

```php
$text = "\u{00A0}PHP\u{3000}";
echo mb_trim( $text, null, 'UTF-8' ) . PHP_EOL; # PHP
```

**Важно:** Обрезание влияет только на выбранные края. Оно не удаляет внутренние пробелы и не проверяет текст. Переданный список символов заменяет стандартный набор; обозначение диапазонов из байтовой `trim()` не поддерживается.

[Документация PHP: mb_trim()](https://www.php.net/manual/en/function.mb-trim.php)

<a id="grapheme-split"></a>

## Разбиение по границам видимых символов

**PHP 8.4+ | intl**

`grapheme_str_split()` сохраняет основной символ вместе с комбинируемыми знаками. Используйте её для разбиения текста на единицы, воспринимаемые человеком как один символ.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text );
echo count( $parts ) . PHP_EOL; # 2
echo bin2hex( $parts[0] ) . PHP_EOL; # 65cc81
```

**Важно:** Вход должен быть корректным UTF-8. При ошибке возвращается false, поэтому проверяйте результат перед операциями массивов с внешними данными. Размер части должен быть не меньше 1.

[Документация PHP: grapheme_str_split()](https://www.php.net/manual/en/function.grapheme-str-split.php)

<a id="string-increment"></a>

## Явное увеличение буквенно-числовой ASCII-метки

**PHP 8.3+ | Ядро**

`str_increment()` и `str_decrement()` явно описывают переход между ASCII-метками. Они помогают избежать исторического поведения ++ для строки, зависящего от типа значения.

```php
echo str_increment( 'INV0099' ) . PHP_EOL; # INV0100
echo str_decrement( 'INV0100' ) . PHP_EOL; # INV0099
echo str_increment( 'ZZ' ) . PHP_EOL; # AAA
```

**Важно:** Принимаются только непустые буквенно-числовые строки ASCII. Знаки пунктуации и буквы Unicode вызывают `ValueError`. Это арифметика меток, а не безопасное распределение номеров счетов между параллельными запросами.

[Документация PHP: str_increment()](https://www.php.net/manual/en/function.str-increment.php) | [Документация PHP: str_decrement()](https://www.php.net/manual/en/function.str-decrement.php)

<a id="heredoc"></a>

## Естественные отступы в heredoc и nowdoc

**PHP 7.3+ | Ядро**

С PHP 7.3 завершающий маркер heredoc или nowdoc может иметь отступ. Такой отступ удаляется из тела строки. Многострочный текст можно оформлять в соответствии с окружающим кодом.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;

echo $message . PHP_EOL;
# Hello, Ada.
# PHP notes
```

**Важно:** Heredoc подставляет переменные; nowdoc использует начальный маркер в кавычках и оставляет текст буквальным. Не смешивайте табуляцию и пробелы в отступах. Подстановка не экранирует HTML, SQL или аргументы оболочки.

[Документация PHP: heredoc](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

<a id="allowed-tags"></a>

## Разрешённые теги массивом для `strip_tags()`

**PHP 7.4+ | Ядро**

`strip_tags()` принимает массив имён разрешённых тегов. Небольшой явный список легче читать, чем строку с угловыми скобками, собранную в другом месте.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
echo strip_tags( $html, ['p'] ) . PHP_EOL;
# <p class="note">Hello</p>bad()
```

**Важно:** Это не средство очистки HTML: атрибуты разрешённых тегов сохраняются, а содержимое script может остаться текстом. Для вывода обычного текста в HTML нужно соответствующее экранирование; для чужого форматированного HTML нужен настоящий санитайзер.

[Документация PHP: strip_tags()](https://www.php.net/manual/en/function.strip-tags.php)

<a id="intl-lists"></a>

## Оформление списка по правилам языка читателя

**PHP 8.5+ | intl**

`IntlListFormatter` добавляет знаки пунктуации и союзы по правилам локали. Это удобно для перечисления форматов, имён или возможностей в предложении; `implode()` сама не знает, как присоединить последний элемент.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] );
echo $text . PHP_EOL; # PHP and HTML
```

**Важно:** Нужна ICU 67 или новее. TYPE_OR и TYPE_UNITS задают другие стили списка. Детали результата зависят от данных локали; перед использованием проверяйте false при ошибке форматирования.

[Документация PHP: IntlListFormatter](https://www.php.net/manual/en/class.intllistformatter.php)

<a id="locale-direction"></a>

## Стандартное направление письма локали

**PHP 8.5+ | intl**

`Locale::isRightToLeft()` спрашивает ICU, направлено ли основное письмо локали справа налево. Она заменяет собственный список кодов языков для выбора направления страницы.

```php
var_dump( Locale::isRightToLeft('en') ); # bool(false)
var_dump( Locale::isRightToLeft('ar') ); # bool(true)
```

**Важно:** Это характеристика локали, а не произвольного введённого текста. Страница может содержать фрагменты с разными направлениями, которым нужна собственная разметка.

[Документация PHP: Locale::isRightToLeft()](https://www.php.net/manual/en/locale.isrighttoleft.php)

<a id="grapheme-distance"></a>

## Сравнение Unicode-текста по графемному расстоянию

**PHP 8.5+ | intl**

`grapheme_levenshtein()` измеряет расстояние вставок, удалений и замен в графемных кластерах. Канонически эквивалентные составные и разложенные символы могут сравниваться одинаково, а не как разные байты.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
var_dump( grapheme_levenshtein($composed, $decomposed) ); # int(0)
```

**Важно:** Расстояние является подсказкой сходства, а не проверкой тождества или прав доступа. Некорректный UTF-8 и ошибки ICU могут дать false; проверяйте === false до числового использования.

[Документация PHP: grapheme_levenshtein()](https://www.php.net/manual/en/function.grapheme-levenshtein.php)
