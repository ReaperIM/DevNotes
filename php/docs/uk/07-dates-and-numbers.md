# Дати та числа

[English](../en/07-dates-and-numbers.md) | [Українська](../uk/07-dates-and-numbers.md) | [Русский](../ru/07-dates-and-numbers.md)

[Зміст](../../README.uk.md)

Обчислення дат залежать від правил розбору й часових поясів, а числові обчислення від подання та округлення. Див. також [числові літерали](01-syntax-and-functions.md#numeric-literals) і [випадкові значення](11-security-and-random.md).

- [Цілочислове ділення через `intdiv()`](#integer-division)
- [Явні операції IEEE з дробовими числами](#ieee-math)
- [Копіювання дат між змінним і незмінним API](#date-conversion)
- [Точне перехоплення помилок розбору дат](#date-exceptions)
- [Перевірка точної календарної дати](#strict-date-input)
- [Повторюваний період із рядка ISO 8601](#iso-period)
- [Створення дат із дробових часових міток](#timestamp-microseconds)
- [Явне правило округлення через `RoundingMode`](#rounding-modes)
- [Десяткова арифметика через `BcMath\Number`](#bcmath-number)
- [Округлення десяткових чисел і частка з остачею](#bcmath-rounding)

<a id="integer-division"></a>

## Цілочислове ділення через `intdiv()`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.intdiv.php)

`intdiv()` повертає цілу частку без проміжного float, що зручно для повних одиниць, наприклад хвилин чи сторінок.

```php
$minutes = intdiv( 125, 60 ); # 2
$remainder = 125 % 60; # 5
$negative = intdiv( -7, 3 ); # -2
```

Частка усікається до нуля. Нульовий дільник спричиняє `DivisionByZeroError`; `intdiv(PHP_INT_MIN, -1)` спричиняє `ArithmeticError` через переповнення результату.

<a id="ieee-math"></a>

## Явні операції IEEE з дробовими числами

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.fdiv.php) | [PHP](https://www.php.net/manual/en/function.fpow.php)

`fdiv()` виконує ділення IEEE з нескінченністю та NaN; з 8.4 `fpow()` явно виконує піднесення до степеня з рухомою комою. Ці функції доречні там, де нескінченні результати є свідомою частиною обчислень.

```php
$infinity = fdiv( 1.0, 0.0 ); # INF
$undefined = is_nan( fdiv(0.0, 0.0) ); # true

# PHP 8.4+
$power = fpow( 0.0, -1.0 ); # INF
```

Використовуйте `is_finite()`, коли потрібен скінченний результат, та `is_nan()` для NaN, який не дорівнює навіть собі.

<a id="date-conversion"></a>

## Копіювання дат між змінним і незмінним API

**PHP 8.0+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [PHP](https://www.php.net/manual/en/datetime.createfromimmutable.php)

`DateTime::createFromInterface()` та `DateTimeImmutable::createFromInterface()` приймають обидві стандартні реалізації, зберігаючи час і часовий пояс. Це прибирає розгалуження, коли API приймає `DateTimeInterface`.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
$original_day = $source->format( 'Y-m-d' ); # 2026-01-01
$next_day = $tomorrow->format( 'Y-m-d' ); # 2026-01-02
```

Незмінна копія не змінює змінний оригінал. `DateTime::createFromImmutable()` уже існує з 7.3.

<a id="date-exceptions"></a>

## Точне перехоплення помилок розбору дат

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/class.datemalformedstringexception.php)

З 8.3 розширення date має окремі винятки для некоректних рядків, часових поясів, інтервалів і періодів. `DateMalformedStringException` визначає некоректний ввід конструктора чи `modify()` без перехоплення всіх `Exception`.

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

Деякі неможливі календарні дати нормалізуються замість відмови; успішне створення об’єкта не забезпечує суворої перевірки дати.

<a id="strict-date-input"></a>

## Перевірка точної календарної дати

**PHP 7.0+ | date | Контекст** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

Сувора перевірка формату потребує діагностики розбору й зворотного форматування: 31 лютого може розібратися, але нормалізуватися до березня. Ці API існують до PHP 7.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input; # false
```

`!` скидає незадані поля; читайте `getLastErrors()` відразу після розбору. З 8.2 успішний розбір дає `false`, у попередніх версіях повертався масив.

<a id="iso-period"></a>

## Повторюваний період із рядка ISO 8601

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

`DatePeriod::createFromISO8601String()` перетворює повторюваний інтервал на ітеровані значення `DateTimeImmutable`, що зручно для збережених розкладів.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );
foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

Початок включено, тому `R2` дає три дати. Підтримується лише частина ISO 8601 без `R0` і зсувів поза UTC; некоректний ввід спричиняє `DateMalformedPeriodStringException`.

<a id="timestamp-microseconds"></a>

## Створення дат із дробових часових міток

**PHP 8.4+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

`createFromTimestamp()` приймає цілі чи дробові Unix-мітки; методи мікросекунд дають доступ до дробової частини без форматування й повторного розбору.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
$timestamp = $date->format( 'Y-m-d H:i:s.u' ); # 2023-11-14 22:13:20.125000
$original = $date->getMicrosecond(); # 125000
$updated = $changed->getMicrosecond(); # 500000
```

Незмінний сеттер повертає копію, а `DateTime` змінюється. Float уже міг втратити точність; часовий пояс для показу задається окремо від моменту часу.

<a id="rounding-modes"></a>

## Явне правило округлення через `RoundingMode`

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/enum.roundingmode.php)

`RoundingMode` задає спільне правило округлення для `round()`, `bcround()` і `BcMath\Number::round()`. HalfEven спрямовує точну половину до парного результату, HalfAwayFromZero від нуля.

```php
$even = round( 2.5, 0, RoundingMode::HalfEven ); # 2
$away = round( 2.5, 0, RoundingMode::HalfAwayFromZero ); # 3
$truncated = round( -2.9, 0, RoundingMode::TowardsZero ); # -2
```

Режим округлення не робить двійкові float десятково точними. Окремо обирайте подання числа й момент округлення; TowardsZero усікає до заданої точності.

<a id="bcmath-number"></a>

## Десяткова арифметика через `BcMath\Number`

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/class.bcmath-number.php)

`BcMath\Number` надає незмінні десяткові числа довільної точності з арифметичними операторами й явними методами. Створюйте значення з рядків або цілих чисел, щоб уникнути попереднього округлення float.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero ); # 59.99
$expected = new BcMath\Number( '59.99' );
$equal = $total->compare( $expected ) === 0; # true
```

`===` перевіряє тотожність об’єктів, а не рівність чисел; використовуйте `compare() === 0`. Клас ігнорує `bcmath.scale`, тому за потреби явно задавайте масштаб ділення.

<a id="bcmath-rounding"></a>

## Округлення десяткових чисел і частка з остачею

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/function.bcround.php) | [PHP](https://www.php.net/manual/en/function.bcdivmod.php)

`bcround()`, `bcfloor()` та `bcceil()` округлюють десяткові рядки без перетворення на float. `bcdivmod()` повертає частку й остачу разом, у формі для деструктуризації.

```php
$rounded = bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ); # '20.00'
$floor = bcfloor( '-1.2' ); # '-2'
$ceiling = bcceil( '-1.2' ); # '-1'
[$quotient, $remainder] = bcdivmod( '17', '5', 0 ); # ['3', '2']
```

Результати є числовими рядками. Floor і ceil прямують до мінус і плюс нескінченності, а не просто усікають число; задавайте масштаб `bcdivmod()` явно, щоб не залежати від глобальних налаштувань.
