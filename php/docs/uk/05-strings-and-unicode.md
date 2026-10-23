# Рядки та Unicode

[EN](../en/05-strings-and-unicode.md) | [UK](../uk/05-strings-and-unicode.md) | [RU](../ru/05-strings-and-unicode.md)

[Зміст](../../README.uk.md)

З рядками головне питання: рахуємо байти чи видимі символи? Від цього залежить, яку функцію взяти для довжини, пошуку чи поділу тексту.

- [Чому один символ може мати три довжини](#byte-codepoint-grapheme)
- [Читаємо рядок із кінця](#negative-offsets)
- [Перевіряємо вміст, початок або кінець рядка](#string-search)
- [Ділимо текст без розрізання байтів однієї літери](#mb-split)
- [Доповнюємо рядок до потрібної довжини](#mb-pad)
- [Змінюємо регістр лише першої літери](#mb-case)
- [Прибираємо пробіли Unicode з країв](#unicode-trim)
- [Не відриваємо знак наголосу від літери](#grapheme-split)
- [Отримуємо наступну буквено-числову мітку](#string-increment)
- [Залишаємо нормальні відступи в багаторядковому тексті](#heredoc)
- [Передаємо дозволені теги масивом](#allowed-tags)
- [Складаємо список за правилами мови](#intl-lists)
- [Дізнаємося напрямок письма для мови](#locale-direction)
- [Порівнюємо текст за цілими видимими символами](#grapheme-distance)

<a id="byte-codepoint-grapheme"></a>
## Чому один символ може мати три довжини

**PHP 7.0+ | Ядро / mbstring / intl | Контекст** | [PHP](https://www.php.net/manual/en/migration70.new-features.php) | [PHP](https://www.php.net/manual/en/function.grapheme-strlen.php)

У PHP рядок зберігається як набір байтів, але один видимий символ не завжди дорівнює одному байту чи одній частині Unicode. У прикладі літера `e` й окремий знак наголосу виглядають разом як один символ. Тому `strlen()` нарахує `3` байти, `mb_strlen()` дві кодові точки, а `grapheme_strlen()` одну графему, тобто видимий символ цілком.

```php
$text = "e\u{0301}";
$bytes = strlen( $text ); # 3
$code_points = mb_strlen( $text, 'UTF-8' ); # 2
$graphemes = grapheme_strlen( $text ); # 1
```

Запис Unicode-символу через його код, як у прикладі, доступний із PHP 7.0. Для пошуку й розрізання тексту важливо вибрати потрібну одиницю. Навіть кількість графем не каже, скільки колонок текст займе в терміналі.

<a id="negative-offsets"></a>
## Читаємо рядок із кінця

**PHP 7.1+ | Ядро** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Від’ємний індекс рахує від кінця рядка: `-1` бере останній байт, `-2` передостанній. Для коротких ASCII-кодів на кшталт `INV-42` це зручно й передбачувано.

```php
$code = 'INV-42';
$last = $code[-1]; # '2'
$previous = $code[-2]; # '4'
```

У UTF-8 одна літера може складатися з кількох байтів, тому так легко взяти лише її частину. Для кодових точок є `mb_substr()`, а для цілих видимих символів `grapheme_substr()`.

<a id="string-search"></a>
## Перевіряємо вміст, початок або кінець рядка

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-contains.php) | [PHP](https://www.php.net/manual/en/function.str-starts-with.php) | [PHP](https://www.php.net/manual/en/function.str-ends-with.php)

Коли потрібна лише перевірка наявності, позицію можна взагалі не шукати. `str_contains()` перевіряє підрядок, `str_starts_with()` початок, а `str_ends_with()` кінець рядка. Усі три одразу дають `true` чи `false`.

```php
$path = '/api/users.php';
$contains = str_contains( $path, 'users' ); # true
$starts = str_starts_with( $path, '/api/' ); # true
$ends = str_ends_with( $path, '.php' ); # true
```

Регістр має значення, пошук іде за байтами. Порожній шуканий рядок вважається знайденим в усіх трьох функціях, тому за потреби порожній ввід перевіряємо окремо.

<a id="mb-split"></a>
## Ділимо текст без розрізання байтів однієї літери

**PHP 7.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-split.php)

`mb_str_split()` ділить рядок за символами вибраного кодування. Тут просимо частини завдовжки по два символи UTF-8 й отримуємо `Ки` та `їв`, а не шматки окремих байтів.

```php
$parts = mb_str_split( 'Київ', 2, 'UTF-8' ); # ['Ки', 'їв']
```

Для Unicode це рахунок за кодовими точками. Окремий знак наголосу все ще може відірватися від літери. Якщо треба зберігати такі поєднання цілком, із PHP 8.4 є `grapheme_str_split()`.

<a id="mb-pad"></a>
## Доповнюємо рядок до потрібної довжини

**PHP 8.3+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-str-pad.php)

`mb_str_pad()` додає символи ліворуч або праворуч до вказаної довжини. У UTF-8 він рахує кодові точки, тому `Київ` рахується як чотири, і до шести бракує двох крапок.

```php
$right = mb_str_pad( 'Київ', 6, '.', STR_PAD_RIGHT, 'UTF-8' ); # Київ..
$left = mb_str_pad( 'PHP', 6, '.', STR_PAD_LEFT, 'UTF-8' ); # ...PHP
```

Довший рядок функція не обріже. Для вирівнювання на екрані це теж не точна лінійка: видима ширина складних символів та іншого тексту може відрізнятися від кількості кодових точок.

<a id="mb-case"></a>
## Змінюємо регістр лише першої літери

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-ucfirst.php) | [PHP](https://www.php.net/manual/en/function.mb-lcfirst.php)

`mb_ucfirst()` змінює перший символ за правилами титульного регістру Unicode, а `mb_lcfirst()` робить його малим. Решта рядка залишається як була. Для `київ` і `Київ` результат видно просто у прикладі.

```php
$upper = mb_ucfirst( 'київ', 'UTF-8' ); # Київ
$lower = mb_lcfirst( 'Київ', 'UTF-8' ); # київ
```

Це робота з першим символом, а не оформлення всього заголовка за правилами конкретної мови. У Unicode зміна регістру іноді змінює й кількість кодових точок.

<a id="unicode-trim"></a>
## Прибираємо пробіли Unicode з країв

**PHP 8.4+ | mbstring** | [PHP](https://www.php.net/manual/en/function.mb-trim.php)

Звичайний `trim()` знає не всі пробільні символи Unicode. `mb_trim()`, `mb_ltrim()` та `mb_rtrim()` за замовчуванням беруть ширший набір. У прикладі прибираються нерозривний пробіл ліворуч і широкий пробіл праворуч.

```php
$text = "\u{00A0}PHP\u{3000}";
$trimmed = mb_trim( $text, null, 'UTF-8' ); # PHP
```

Якщо передати власний список символів, він замінить стандартний набір. Позначати діапазони так само, як у `trim()`, тут не можна: це саме список символів.

<a id="grapheme-split"></a>
## Не відриваємо знак наголосу від літери

**PHP 8.4+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-str-split.php)

Якщо один видимий символ складається з літери й окремого знака, під час поділу хочеться зберегти їх разом. `grapheme_str_split()` ділить UTF-8 за графемами, тому `e` зі знаком наголосу залишається однією частиною, а `x` іде наступною.

```php
$text = "e\u{0301}x";
$parts = grapheme_str_split( $text ); # ["e\u{0301}", 'x']
```

Розмір частини має бути не меншим за `1`. За пошкодженого UTF-8 чи іншої помилки результат може бути `false`, тому його не варто одразу вважати готовим масивом.

<a id="string-increment"></a>
## Отримуємо наступну буквено-числову мітку

**PHP 8.3+ | Ядро** | [PHP](https://www.php.net/manual/en/function.str-increment.php) | [PHP](https://www.php.net/manual/en/function.str-decrement.php)

Для ASCII-міток на кшталт номера документа є явні `str_increment()` і `str_decrement()`. Вони збільшують або зменшують такий рядок за його буквено-числовими правилами: після `INV0099` іде `INV0100`, а після `ZZ` уже `AAA`.

```php
$next = str_increment( 'INV0099' ); # INV0100
$previous = str_decrement( 'INV0100' ); # INV0099
$carry = str_increment( 'ZZ' ); # AAA
```

Підходять лише непорожні рядки з літер і цифр ASCII. Пунктуація, літери Unicode й порожній рядок спричинять `ValueError`.

<a id="heredoc"></a>
## Залишаємо нормальні відступи в багаторядковому тексті

**PHP 7.3+ | Ядро** | [PHP](https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc)

Завершальний маркер heredoc чи nowdoc більше не обов’язково притискати до лівого краю. Його відступ віднімається від рядків усередині, тому текст можна охайно вписати в навколишній код без зайвої табуляції в результаті.

```php
$name = 'Ada';
$message = <<<TEXT
	Hello, $name.
	PHP notes
	TEXT;
```

Heredoc підставляє змінні, тому `$name` потрапить у привітання. Nowdoc з лапками біля початкового маркера залишає текст буквально. В одному відступі не змішуємо табуляцію й пробіли.

<a id="allowed-tags"></a>
## Передаємо дозволені теги масивом

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/function.strip-tags.php)

У `strip_tags()` можна передати масив імен тегів, які треба залишити. Тут зберігаємо `p`, а сам тег `script` видаляємо.

```php
$html = '<p class="note">Hello</p><script>bad()</script>';
$text = strip_tags( $html, ['p'] ); # <p class="note">Hello</p>bad()
```

За результатом видно важливий нюанс: атрибут `class` залишився, а `bad()` перетворився на звичайний текст. Функція прибирає теги, але не робить довільний HTML безпечним. Атрибути дозволених тегів теж залишаються, тому це не готовий захист від небезпечного HTML.

<a id="intl-lists"></a>
## Складаємо список за правилами мови

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/class.intllistformatter.php)

Для списку у звичайному тексті одного `implode()` іноді замало: останній елемент треба з’єднати сполучником потрібної мови. `IntlListFormatter` бере це на себе. У прикладі англійська локаль і `TYPE_AND` дають `PHP and HTML`.

```php
$formatter = new IntlListFormatter( 'en', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML'] ); # PHP and HTML
```

Для інших способів поєднання є `TYPE_OR` і `TYPE_UNITS`. Потрібна ICU 67 або новіша, а конкретний текст залежить від її даних локалі. Якщо форматування не вдалося, можна отримати `false`.

<a id="locale-direction"></a>
## Дізнаємося напрямок письма для мови

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/locale.isrighttoleft.php)

`Locale::isRightToLeft()` підказує, чи прийнято в основній писемності локалі писати справа наліво. Для англійської `en` отримуємо `false`, для арабської `ar` отримуємо `true`.

```php
$english_rtl = Locale::isRightToLeft( 'en' ); # false
$arabic_rtl = Locale::isRightToLeft( 'ar' ); # true
```

Це властивість локалі, а не аналіз переданого тексту. За нею не можна визначити напрямок довільного рядка чи окремих частин змішаного тексту.

<a id="grapheme-distance"></a>
## Порівнюємо текст за цілими видимими символами

**PHP 8.5+ | intl** | [PHP](https://www.php.net/manual/en/function.grapheme-levenshtein.php)

`grapheme_levenshtein()` рахує відстань редагування за графемами, а не за окремими байтами. Вона також розуміє рівнозначні записи Unicode: готова літера з наголосом і літера з окремим знаком у прикладі вважаються однаковими, тому відстань дорівнює `0`.

```php
$composed = "\u{00E9}";
$decomposed = "e\u{0301}";
$distance = grapheme_levenshtein( $composed, $decomposed ); # 0
```

Пошкоджений UTF-8 або помилка ICU можуть дати `false`. Перевіряємо саме `=== false`: нуль тут правильний результат для однакових рядків.
