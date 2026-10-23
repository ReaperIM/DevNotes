# Дати та числа

[EN](../en/07-dates-and-numbers.md) | [UK](../uk/07-dates-and-numbers.md) | [RU](../ru/07-dates-and-numbers.md)

[Зміст](../../README.uk.md)

Перевіряємо дати так, щоб 31 лютого непомітно не перетворилося на березень, рахуємо інтервали й обираємо округлення під задачу. Для точних десяткових розрахунків окремо розбираємо BCMath. Поряд стануть у пригоді [числові літерали](01-syntax-and-functions.md#numeric-literals) і [випадкові значення](11-security-and-random.md).

- [Цілочислове ділення через `intdiv()`](#integer-division)
- [Ділення й степінь з `INF` і `NaN`](#ieee-math)
- [Копіюємо дату в `DateTime` або `DateTimeImmutable`](#date-conversion)
- [Ловимо помилку в рядку дати](#date-exceptions)
- [Перевірка точної календарної дати](#strict-date-input)
- [Повторюваний період із рядка ISO 8601](#iso-period)
- [Дата з Unix-мітки з мікросекундами](#timestamp-microseconds)
- [Явне правило округлення через `RoundingMode`](#rounding-modes)
- [Десяткова арифметика через `BcMath\Number`](#bcmath-number)
- [Округлення десяткових чисел і частка з остачею](#bcmath-rounding)

<a id="integer-division"></a>

## Цілочислове ділення через `intdiv()`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.intdiv.php)

Якщо потрібні повні хвилини, сторінки чи порції елементів, беремо `intdiv()`. У прикладі зі 125 секунд отримуємо дві повні хвилини, а решту п'ять секунд рахуємо через `%`. Проміжного `float` тут немає.

```php
$minutes = intdiv( 125, 60 ); # 2
$remainder = 125 % 60; # 5
$negative = intdiv( -7, 3 ); # -2
```

Дробова частина відкидається в бік нуля: тому `intdiv(-7, 3)` дає `-2`, а не `-3`. Ділити на нуль не можна, отримаємо `DivisionByZeroError`. Результат ділення `PHP_INT_MIN` на `-1` теж не вміщується в ціле число й викликає `ArithmeticError`.

<a id="ieee-math"></a>

## Ділення й степінь з `INF` і `NaN`

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.fdiv.php) | [PHP](https://www.php.net/manual/en/function.fpow.php)

Іноді нескінченність у результаті обчислення допустима, а виняток лише заважає продовжити розрахунок. `fdiv()` ділить за правилами IEEE: `fdiv(1.0, 0.0)` дає `INF`, а `fdiv(0.0, 0.0)` дає `NaN`, тобто невизначений результат. З PHP 8.4 для піднесення до степеня за цими правилами є `fpow()`.

```php
$infinity = fdiv( 1.0, 0.0 ); # INF
$undefined = is_nan( fdiv(0.0, 0.0) ); # true

# PHP 8.4+
$power = fpow( 0.0, -1.0 ); # INF
```

Якщо за умовами задачі результат має бути скінченним числом, перевіряємо його через `is_finite()`. `NaN` не дорівнює навіть самому собі; для його перевірки беремо `is_nan()`.

<a id="date-conversion"></a>

## Копіюємо дату в `DateTime` або `DateTimeImmutable`

**PHP 8.0+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [PHP](https://www.php.net/manual/en/datetime.createfromimmutable.php)

Коли функція приймає `DateTimeInterface`, їй можуть передати і `DateTime`, і `DateTimeImmutable`. Щоб отримати потрібний варіант без окремих перевірок класів, використовуємо `createFromInterface()`. Час і часовий пояс збережуться. Тут робимо незмінну копію й уже від неї рахуємо завтрашній день.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
$original_day = $source->format( 'Y-m-d' ); # 2026-01-01
$next_day = $tomorrow->format( 'Y-m-d' ); # 2026-01-02
```

У вихідному `$source` залишається 1 січня, а в `$tomorrow` буде 2 січня. Методи `DateTimeImmutable` повертають новий об'єкт, тому початкову дату не зсуваємо. Для перетворення саме з `DateTimeImmutable` на `DateTime` ще з PHP 7.3 є `DateTime::createFromImmutable()`.

<a id="date-exceptions"></a>

## Ловимо помилку в рядку дати

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/class.datemalformedstringexception.php)

З PHP 8.3 помилки в рядку дати можна ловити окремо через `DateMalformedStringException`. Це зручно, коли треба обробити неправильне введення, не перехоплюючи заодно всі інші `Exception`. У прикладі рядок не схожий на дату, тому потрапляємо в `catch`.

```php
try
{
	$date = new DateTimeImmutable( 'not a date' );
}
catch( DateMalformedStringException $error )
{
	echo 'Invalid date';
}
```

Розширення date має окремі винятки й для часових поясів, інтервалів і періодів. Але навіть без винятку дата може виявитися не тією, яку ввели: деякі неможливі дати PHP виправляє сам. Для суворої перевірки календарної дати потрібен наступний приклад.

<a id="strict-date-input"></a>

## Перевірка точної календарної дати

**PHP 7.0+ | date | Для розуміння** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

31 лютого не існує, але одного `createFromFormat()` для перевірки замало: PHP може перенести зайві дні в березень і повернути об'єкт. Тому перевіряємо помилки розбору, а потім форматуємо дату назад і порівнюємо з введенням. Ці методи з'явилися ще до PHP 7.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
# Забираємо помилки відразу, наступний розбір їх перезапише.
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input; # false
```

`!` обнуляє поля, яких немає у форматі, щоб вони не підтягнулися з поточного часу. `getLastErrors()` викликаємо відразу після розбору: наступний розбір перезапише діагностику. З PHP 8.2 за відсутності помилок і попереджень отримуємо `false`, а раніше завжди повертався масив. Перевірка враховує обидва варіанти.

<a id="iso-period"></a>

## Повторюваний період із рядка ISO 8601

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

Якщо розклад зберігається рядком ISO 8601, `DatePeriod::createFromISO8601String()` дозволяє відразу перебрати його дати через `foreach`. У прикладі починаємо 1 січня й двічі додаємо по одному дню. На кожній ітерації отримуємо `DateTimeImmutable`.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );
foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

Початкова дата теж входить у результат, тому з `R2` дат буде три. Метод підтримує лише частину формату ISO 8601: `R0` і зміщення часового поясу поза UTC не підходять. Неправильний рядок викликає `DateMalformedPeriodStringException`.

<a id="timestamp-microseconds"></a>

## Дата з Unix-мітки з мікросекундами

**PHP 8.4+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

`createFromTimestamp()` створює дату відразу з Unix-мітки, зокрема з дробовою частиною. Для мікросекунд є `getMicrosecond()` і `setMicrosecond()`, тому не потрібно діставати їх через `format()` і збирати дату назад. У прикладі змінюємо `.125000` на `.500000`.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
$timestamp = $date->format( 'Y-m-d H:i:s.u' ); # 2023-11-14 22:13:20.125000
$original = $date->getMicrosecond(); # 125000
$updated = $changed->getMicrosecond(); # 500000
```

У `DateTimeImmutable` метод `setMicrosecond()` повертає новий об'єкт, а в `DateTime` змінює поточний. Дробовий `float` міг втратити точність ще до передачі в метод. Сам момент часу й часовий пояс для його відображення теж варто розрізняти: пояс для виведення задаємо окремо.

<a id="rounding-modes"></a>

## Явне правило округлення через `RoundingMode`

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/enum.roundingmode.php)

Коли число рівно посередині, результат залежить від обраного правила округлення. `HalfEven` округлює `2.5` до парного `2`, а `HalfAwayFromZero` відсуває половину від нуля, отримуємо `3`. Перелічення `RoundingMode` можна передавати в `round()`, `bcround()` і `BcMath\Number::round()`.

```php
$even = round( 2.5, 0, RoundingMode::HalfEven ); # 2
$away = round( 2.5, 0, RoundingMode::HalfAwayFromZero ); # 3
$truncated = round( -2.9, 0, RoundingMode::TowardsZero ); # -2
```

`TowardsZero` просто відкидає зайву дробову частину до потрібної точності, тому з `-2.9` виходить `-2`. Сам режим округлення не виправляє похибку двійкового `float`: спосіб зберігання числа й правило округлення обираємо окремо.

<a id="bcmath-number"></a>

## Десяткова арифметика через `BcMath\Number`

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/class.bcmath-number.php)

Якщо потрібні точні десяткові обчислення, можна взяти `BcMath\Number`. Він підтримує арифметичні оператори й методи, а результат операції повертає новим об'єктом. Значення передаємо рядком або цілим числом: якщо спочатку створити `float`, його похибка вже потрапить у розрахунок.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero ); # 59.99
$expected = new BcMath\Number( '59.99' );
# Порівнюємо числа всередині об’єктів, а не самі об’єкти.
$equal = $total->compare( $expected ) === 0; # true
```

У прикладі множимо ціну на три й округлюємо загальний результат до двох знаків. Самі об'єкти через `===` за числовим значенням не порівняти: це перевірка, чи той самий це об'єкт. Для чисел використовуємо `compare() === 0`. Налаштування `bcmath.scale` клас не враховує, тому потрібну кількість знаків під час ділення задаємо явно.

<a id="bcmath-rounding"></a>

## Округлення десяткових чисел і частка з остачею

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/function.bcround.php) | [PHP](https://www.php.net/manual/en/function.bcdivmod.php)

Для десяткових чисел у рядках є `bcround()`, `bcfloor()` і `bcceil()`. Вони не переводять число у `float`, тому не додають його похибку. А якщо потрібні й частка, і остача, `bcdivmod()` повертає їх разом: відразу розкладаємо результат у дві змінні.

```php
$rounded = bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ); # '20.00'
$floor = bcfloor( '-1.2' ); # '-2'
$ceiling = bcceil( '-1.2' ); # '-1'
[$quotient, $remainder] = bcdivmod( '17', '5', 0 ); # ['3', '2']
```

Результати теж приходять рядками. Зверніть увагу на від'ємні числа: `bcfloor('-1.2')` іде вниз до `-2`, а `bcceil('-1.2')` вгору до `-1`. Це не звичайне відкидання дробової частини. Кількість знаків після коми в `bcdivmod()` задаємо явно, щоб результат не залежав від загального налаштування.
