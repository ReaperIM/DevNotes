# Рядки та Unicode

[English](../en/05-strings-and-unicode.md) | [Українська](../uk/05-strings-and-unicode.md) | [Русский](../ru/05-strings-and-unicode.md)

[Зміст](../../README.uk.md)

Для двійкових або ASCII-даних вибирайте байтові операції, для людського тексту враховуйте Unicode. Ця різниця важливіша за довжину імені функції. Приклади з mb_ потребують mbstring, а grapheme_ та Locale потребують intl.

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

**PHP 7.0+ | Ядро / mbstring / intl | Контекст**

Рядок PHP зберігає байти. Різні операції рахують байти, кодові точки Unicode або графемні кластери, які користувач сприймає як символи. Unicode-послідовності в подвійних лапках додано у PHP 7.0; вони роблять невидимі комбіновані знаки явними.

```php
$text = "e\u{0301}"; # e + combining acute accent
echo strlen( $text ) . PHP_EOL; # 3 bytes
echo mb_strlen( $text, 'UTF-8' ) . PHP_EOL; # 2 code points
echo grapheme_strlen( $text ) . PHP_EOL; # 1 grapheme cluster
```

**Важливо:** Вибирайте одиницю до обрізання, доповнення чи перевірки довжини. Графемні кластери теж не дорівнюють ширині стовпців термінала. Для відповідних викликів потрібні ввімкнені mbstring та intl.

[Документація PHP: Unicode escapes](https://www.php.net/manual/en/migration70.new-features.php) | [Документація PHP: grapheme_strlen()](https://www.php.net/manual/en/function.grapheme-strlen.php)

<a id="negative-offsets"></a>

## Читання з кінця через від’ємні індекси

**PHP 7.1+ | Ядро**

Від’ємні індекси рядка відраховуються від кінця. Для ASCII-токенів це простий спосіб прочитати останній символ без обчислення `strlen()` - 1.

```php
$code = 'INV-42';
echo $code[-1] . PHP_EOL; # 2
echo $code[-2] . PHP_EOL; # 4
```

**Важливо:** Індекси все одно адресують байти. Для UTF-8 використовуйте `mb_substr()` або `grapheme_substr()`, залежно від потрібної одиниці. Індекс поза межами не є перевіркою порожнього поля.

[Документація PHP: negative offsets](https://www.php.net/manual/en/migration71.new-features.php)

<a id="string-search"></a>

## Пошук підрядка, префікса чи суфікса

**PHP 8.0+ | Ядро**

`str_contains()`, `str_starts_with()` та `str_ends_with()` повертають логічну відповідь на конкретне питання. Вони не змішують позицію 0 з маркером відсутності false, який повертає `strpos()`.

```php
$path = '/api/users.php';
var_dump( str_contains($path, 'users') ); # bool(true)
var_dump( str_starts_with($path, '/api/') ); # bool(true)
var_dump( str_ends_with($path, '.php') ); # bool(true)
var_dump( str_contains($path, '') ); # bool(true)
```

**Важливо:** Пошук чутливий до регістру та працює з байтами. Порожній підрядок дає збіг. Окремо відхиляйте порожній пошуковий запит, якщо він недопустимий; пошук підрядка не перевіряє коректність URL чи шляху.

[Документація PHP: str_contains()](https://www.php.net/manual/en/function.str-contains.php) | [Документація PHP: str_starts_with()](https://www.php.net/manual/en/function.str-starts-with.php) | [Документація PHP: str_ends_with()](https://www.php.net/manual/en/function.str-ends-with.php)

<a id="mb-split"></a>

## Поділ багатобайтового рядка за кодовими точками

**PHP 7.4+ | mbstring**

`mb_str_split()` створює частини, виміряні в символах вибраного кодування. Вона замінює байтову `str_split()` під час обробки літер UTF-8.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' );
echo json_encode( $parts, JSON_UNESCAPED_UNICODE ) . PHP_EOL; # ["Ки","їв"]
```

**Важливо:** Комбінований наголос може відокремитися від основної літери. Для меж видимих символів використовуйте `grapheme_str_split()` з PHP 8.4. Явно задавайте кодування для передбачуваної обробки.

[Документація PHP: mb_str_split()](https://www.php.net/manual/en/function.mb-str-split.php)

<a id="mb-pad"></a>

## Доповнення тексту за кількістю кодових точок

**PHP 8.3+ | mbstring**

`mb_str_pad()` використовує кількість кодових точок замість кількості байтів. Тому кириличне й ASCII-слово можна доповнювати за однаковим правилом довжини в символах.

```php
echo mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ) . PHP_EOL; # Київ..
echo mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ) . PHP_EOL; # ...PHP
```

**Важливо:** Доповнення не обрізає довший вхідний рядок. Кодові точки не є візуальними стовпцями, тому функція сама не вирівняє кожну таблицю термінала або напис з емодзі.

[Документація PHP: mb_str_pad()](https://www.php.net/manual/en/function.mb-str-pad.php)

<a id="mb-case"></a>

## Зміна регістру першого символу Unicode

**PHP 8.4+ | mbstring**

`mb_ucfirst()` переводить перший символ у титульний регістр, `mb_lcfirst()` робить його малим. Решта тексту зберігається. Це замінює невелику, але схильну до помилок комбінацію обрізання та зміни регістру.

```php
echo mb_ucfirst( 'київ', 'UTF-8' ) . PHP_EOL; # Київ
echo mb_lcfirst( 'Київ', 'UTF-8' ) . PHP_EOL; # київ
```

**Важливо:** Це не оформлення всього речення як заголовка й не мовозалежне правило для імен. Перетворення регістру Unicode може змінити кількість кодових точок.

[Документація PHP: mb_ucfirst()](https://www.php.net/manual/en/function.mb-ucfirst.php) | [Документація PHP: mb_lcfirst()](https://www.php.net/manual/en/function.mb-lcfirst.php)

<a id="unicode-trim"></a>

## Видалення пробілів Unicode з країв

**PHP 8.4+ | mbstring**

`mb_trim()`, `mb_ltrim()` та `mb_rtrim()` розуміють багатобайтові символи й мають ширший типовий набір пробілів, ніж `trim()`. Це корисно для тексту, вставленого з документів або вебсторінок.

```php
$text = "\u{00A0}PHP\u{3000}";
echo mb_trim( $text, null, 'UTF-8' ) . PHP_EOL; # PHP
```

**Важливо:** Обрізання впливає лише на вибрані краї. Воно не видаляє внутрішні пробіли й не перевіряє текст. Переданий список символів замінює типовий набір; позначення діапазонів із байтової `trim()` не підтримується.

[Документація PHP: mb_trim()](https://www.php.net/manual/en/function.mb-trim.php)

<a id="grapheme-split"></a>

## Поділ за межами видимих символів

**PHP 8.4+ | intl**

`grapheme_str_split()` зберігає основний символ разом із комбінованими знаками. Використовуйте її для поділу тексту на одиниці, які людина зазвичай сприймає як один символ.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text );
echo count( $parts ) . PHP_EOL; # 2
echo bin2hex( $parts[0] ) . PHP_EOL; # 65cc81
```

**Важливо:** Вхід має бути коректним UTF-8. У разі помилки повертається false, тому перевіряйте результат перед операціями масивів із зовнішніми даними. Розмір частини має бути не менше 1.

[Документація PHP: grapheme_str_split()](https://www.php.net/manual/en/function.grapheme-str-split.php)

<a id="string-increment"></a>

## Явне збільшення буквено-числової ASCII-мітки

**PHP 8.3+ | Ядро**

`str_increment()` та `str_decrement()` явно описують перехід між ASCII-мітками. Вони допомагають уникнути історичної поведінки ++ для рядка, яка залежить від типу значення.

```php
echo str_increment( 'INV0099' ) . PHP_EOL; # INV0100
echo str_decrement( 'INV0100' ) . PHP_EOL; # INV0099
echo str_increment( 'ZZ' ) . PHP_EOL; # AAA
```

**Важливо:** Приймаються лише непорожні буквено-числові рядки ASCII. Розділові знаки та літери Unicode спричиняють `ValueError`. Це арифметика міток, а не безпечний розподіл номерів рахунків між паралельними запитами.

[Документація PHP: str_increment()](https://www.php.net/manual/en/function.str-increment.php) | [Документація PHP: str_decrement()](https://www.php.net/manual/en/function.str-decrement.php)

<a id="heredoc"></a>

## Природні відступи в heredoc та nowdoc

**PHP 7.3+ | Ядро**

З PHP 7.3 завершальний маркер heredoc або nowdoc може мати відступ. Такий відступ видаляється з тіла рядка. Багаторядковий текст можна оформлювати відповідно до навколишнього коду.

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

**Важливо:** Heredoc підставляє змінні; nowdoc використовує початковий маркер у лапках і залишає текст буквальним. Не змішуйте табуляцію та пробіли у відступах. Підстановка не екранує HTML, SQL чи аргументи оболонки.

[Документація PHP: heredoc](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

<a id="allowed-tags"></a>

## Дозволені теги масивом для `strip_tags()`

**PHP 7.4+ | Ядро**

`strip_tags()` приймає масив імен дозволених тегів. Невеликий явний список легше читати, ніж рядок із кутовими дужками, зібраний в іншому місці.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
echo strip_tags( $html, ['p'] ) . PHP_EOL;
# <p class="note">Hello</p>bad()
```

**Важливо:** Це не засіб очищення HTML: атрибути дозволених тегів зберігаються, а вміст script може лишитися текстом. Для виведення звичайного тексту в HTML потрібне відповідне екранування; для чужого форматованого HTML потрібен справжній санітайзер.

[Документація PHP: strip_tags()](https://www.php.net/manual/en/function.strip-tags.php)

<a id="intl-lists"></a>

## Оформлення списку за правилами мови читача

**PHP 8.5+ | intl**

`IntlListFormatter` додає розділові знаки та сполучники за правилами локалі. Це зручно для переліку форматів, імен чи можливостей у реченні; `implode()` сама не знає, як приєднати останній елемент.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] );
echo $text . PHP_EOL; # PHP and HTML
```

**Важливо:** Потрібна ICU 67 або новіша. TYPE_OR та TYPE_UNITS задають інші стилі списку. Деталі результату залежать від даних локалі; перед використанням перевіряйте false у разі помилки форматування.

[Документація PHP: IntlListFormatter](https://www.php.net/manual/en/class.intllistformatter.php)

<a id="locale-direction"></a>

## Типовий напрямок письма локалі

**PHP 8.5+ | intl**

`Locale::isRightToLeft()` запитує ICU, чи основне письмо локалі спрямоване справа наліво. Вона замінює власний список кодів мов для вибору напрямку сторінки.

```php
var_dump( Locale::isRightToLeft('en') ); # bool(false)
var_dump( Locale::isRightToLeft('ar') ); # bool(true)
```

**Важливо:** Це характеристика локалі, а не довільного введеного тексту. Сторінка може містити фрагменти з різними напрямками, для яких потрібна власна розмітка.

[Документація PHP: Locale::isRightToLeft()](https://www.php.net/manual/en/locale.isrighttoleft.php)

<a id="grapheme-distance"></a>

## Порівняння Unicode-тексту за графемною відстанню

**PHP 8.5+ | intl**

`grapheme_levenshtein()` вимірює відстань вставок, видалень і замін у графемних кластерах. Канонічно еквівалентні складені та розкладені символи можуть порівнюватися однаково, а не як різні байти.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
var_dump( grapheme_levenshtein($composed, $decomposed) ); # int(0)
```

**Важливо:** Відстань є підказкою схожості, а не перевіркою тотожності чи прав доступу. Некоректний UTF-8 та помилки ICU можуть дати false; перевіряйте === false до числового використання.

[Документація PHP: grapheme_levenshtein()](https://www.php.net/manual/en/function.grapheme-levenshtein.php)
