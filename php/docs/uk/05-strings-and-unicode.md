# Рядки та Unicode

[EN](../en/05-strings-and-unicode.md) | [UK](../uk/05-strings-and-unicode.md) | [RU](../ru/05-strings-and-unicode.md)

[Зміст](../../README.uk.md)

Рядкові API працюють із різними одиницями: байтами, кодовими точками чи графемними кластерами. Спочатку визначте одиницю, а потім операцію пошуку, поділу чи вимірювання довжини.

- [UTF-8: байти, кодові точки та графемні кластери](#byte-codepoint-grapheme)
- [Читання з кінця через від’ємні індекси](#negative-offsets)
- [Пошук підрядка, префікса чи суфікса](#string-search)
- [Поділ багатобайтового рядка за кодовими точками](#mb-split)
- [Доповнення тексту за кількістю кодових точок](#mb-pad)
- [Зміна регістру першого символу Unicode](#mb-case)
- [Видалення пробілів Unicode з країв](#unicode-trim)
- [Поділ за межами видимих символів](#grapheme-split)
- [Явне збільшення буквено-числової ASCII-мітки](#string-increment)
- [Природні відступи в heredoc та nowdoc](#heredoc)
- [Дозволені теги масивом для `strip_tags()`](#allowed-tags)
- [Оформлення списку за правилами мови читача](#intl-lists)
- [Типовий напрямок письма локалі](#locale-direction)
- [Порівняння Unicode-тексту за графемною відстанню](#grapheme-distance)

<a id="byte-codepoint-grapheme"></a>

## UTF-8: байти, кодові точки та графемні кластери

**PHP 7.0+ | Ядро / mbstring / intl | Контекст** | [PHP](https://www.php.net/manual/en/migration70.new-features.php) | [PHP](https://www.php.net/manual/en/function.grapheme-strlen.php)

Рядок PHP містить байти; у цьому прикладі UTF-8 `strlen()`, `mb_strlen()` та `grapheme_strlen()` рахують відповідно байти, кодові точки й графемні кластери. Unicode-послідовності з’явилися в 7.0.

```php
$text = "e\u{0301}";
$bytes = strlen( $text ); # 3
$code_points = mb_strlen( $text, 'UTF-8' ); # 2
$graphemes = grapheme_strlen( $text ); # 1
```

Кодові точки й графемні кластери є різними одиницями, і жодна не гарантує ширини в колонках термінала.

<a id="negative-offsets"></a>

## Читання з кінця через від’ємні індекси

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Від’ємні індекси рядка адресують байти з кінця.

```php
$code = 'INV-42';
$last = $code[-1]; # '2'
$previous = $code[-2]; # '4'
```

Для UTF-8 використовуйте `mb_substr()` для кодових точок або `grapheme_substr()` для графемних кластерів.

<a id="string-search"></a>

## Пошук підрядка, префікса чи суфікса

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-contains.php) | [PHP](https://www.php.net/manual/en/function.str-starts-with.php) | [PHP](https://www.php.net/manual/en/function.str-ends-with.php)

`str_contains()`, `str_starts_with()` та `str_ends_with()` повертають логічні значення замість позицій.

```php
$path = '/api/users.php';
$contains = str_contains( $path, 'users' ); # true
$starts = str_starts_with( $path, '/api/' ); # true
$ends = str_ends_with( $path, '.php' ); # true
```

Пошук чутливий до регістру й працює з байтами; порожній зразок збігається в усіх трьох функціях.

<a id="mb-split"></a>

## Поділ багатобайтового рядка за кодовими точками

**PHP 7.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-split.php)

`mb_str_split()` розбиває рядок за символами вибраного кодування, а не за байтами.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' ); # ['Ки', 'їв']
```

Комбінувальний знак може відокремитися від основи; з 8.4 `grapheme_str_split()` зберігає графемні кластери.

<a id="mb-pad"></a>

## Доповнення тексту за кількістю кодових точок

**PHP 8.3+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-pad.php)

`mb_str_pad()` доповнює рядок за кількістю кодових точок.

```php
$right = mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ); # Київ..
$left = mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ); # ...PHP
```

Вона не обрізає довший рядок і не вимірює видиму ширину емодзі та іншого тексту.

<a id="mb-case"></a>

## Зміна регістру першого символу Unicode

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-ucfirst.php) | [PHP](https://www.php.net/manual/en/function.mb-lcfirst.php)

`mb_ucfirst()` переводить перший символ у титульний регістр, `mb_lcfirst()` у нижній; решта не змінюється.

```php
$upper = mb_ucfirst( 'київ', 'UTF-8' ); # Київ
$lower = mb_lcfirst( 'Київ', 'UTF-8' ); # київ
```

Регістрове перетворення Unicode може змінити кількість кодових точок; це не локалізоване оформлення заголовків.

<a id="unicode-trim"></a>

## Видалення пробілів Unicode з країв

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-trim.php)

`mb_trim()`, `mb_ltrim()` та `mb_rtrim()` видаляють крайові багатобайтові символи з ширшим типовим набором пробілів, ніж `trim()`.

```php
$text = "\u{00A0}PHP\u{3000}";
$trimmed = mb_trim( $text, null, 'UTF-8' ); # PHP
```

Власний список символів замінює типовий набір і не підтримує позначення діапазонів із `trim()`.

<a id="grapheme-split"></a>

## Поділ за межами видимих символів

**PHP 8.4+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-str-split.php)

`grapheme_str_split()` розбиває UTF-8 на межах графем, зберігаючи комбінувальні знаки разом з основою.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text ); # ["e\u{0301}", 'x']
```

Некоректний UTF-8 або інша помилка може дати `false`; розмір частини має бути не меншим за 1.

<a id="string-increment"></a>

## Явне збільшення буквено-числової ASCII-мітки

**PHP 8.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-increment.php) | [PHP](https://www.php.net/manual/en/function.str-decrement.php)

`str_increment()` та `str_decrement()` явно змінюють буквено-цифрові мітки ASCII.

```php
$next = str_increment( 'INV0099' ); # INV0100
$previous = str_decrement( 'INV0100' ); # INV0099
$carry = str_increment( 'ZZ' ); # AAA
```

Порожні рядки, пунктуація та літери Unicode спричиняють `ValueError`.

<a id="heredoc"></a>

## Природні відступи в heredoc та nowdoc

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

Відступ завершального маркера heredoc або nowdoc видаляється з тіла рядка.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;
```

Heredoc підставляє змінні, nowdoc з лапками в початковому маркері зберігає текст буквально; у відступі не можна змішувати табуляції та пробіли.

<a id="allowed-tags"></a>

## Дозволені теги масивом для `strip_tags()`

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.strip-tags.php)

З 7.4 `strip_tags()` приймає масив назв дозволених тегів.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
$text = strip_tags( $html, ['p'] ); # <p class="note">Hello</p>bad()
```

Це не засіб очищення HTML: атрибути дозволених тегів зберігаються, а вміст скриптів може лишитися текстом.

<a id="intl-lists"></a>

## Оформлення списку за правилами мови читача

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/class.intllistformatter.php)

`IntlListFormatter` поєднує списки локалізованими сполучниками; `TYPE_OR` та `TYPE_UNITS` задають інші стилі.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] ); # PHP and HTML
```

Потрібна ICU 67+; результат залежить від даних локалі, а помилка форматування може дати `false`.

<a id="locale-direction"></a>

## Типовий напрямок письма локалі

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/locale.isrighttoleft.php)

`Locale::isRightToLeft()` повертає типовий напрям письма основної писемності локалі.

```php
$english_rtl = Locale::isRightToLeft( 'en' ); # false
$arabic_rtl = Locale::isRightToLeft( 'ar' ); # true
```

Вона описує локаль, а не напрям довільного тексту чи фрагментів зі змішаним напрямом.

<a id="grapheme-distance"></a>

## Порівняння Unicode-тексту за графемною відстанню

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-levenshtein.php)

`grapheme_levenshtein()` вимірює відстань редагування в графемних кластерах і враховує канонічну еквівалентність.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
$distance = grapheme_levenshtein( $composed, $decomposed ); # 0
```

Некоректний UTF-8 або помилки ICU можуть дати `false`; перед використанням відстані перевіряйте `=== false`.
