# Даты и числа

[English](../en/07-dates-and-numbers.md) | [Українська](../uk/07-dates-and-numbers.md) | [Русский](../ru/07-dates-and-numbers.md)

[Содержание](../../README.ru.md)

Вычисления дат зависят от правил разбора и часовых поясов, а числовые вычисления от представления и округления. См. также [числовые литералы](01-syntax-and-functions.md#numeric-literals) и [случайные значения](11-security-and-random.md).

- [Целочисленное деление через `intdiv()`](#integer-division)
- [Явные операции IEEE с дробными числами](#ieee-math)
- [Копирование дат между изменяемым и неизменяемым API](#date-conversion)
- [Точный перехват ошибок разбора дат](#date-exceptions)
- [Проверка точной календарной даты](#strict-date-input)
- [Повторяющийся период из строки ISO 8601](#iso-period)
- [Создание дат из дробных временных меток](#timestamp-microseconds)
- [Явное правило округления через `RoundingMode`](#rounding-modes)
- [Десятичная арифметика через `BcMath\Number`](#bcmath-number)
- [Округление десятичных чисел и частное с остатком](#bcmath-rounding)

<a id="integer-division"></a>

## Целочисленное деление через `intdiv()`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.intdiv.php)

`intdiv()` возвращает целое частное без промежуточного float, что удобно для полных единиц, например минут или страниц.

```php
$minutes = intdiv( 125, 60 ); # 2
$remainder = 125 % 60; # 5
$negative = intdiv( -7, 3 ); # -2
```

Частное усекается к нулю. Нулевой делитель вызывает `DivisionByZeroError`; `intdiv(PHP_INT_MIN, -1)` вызывает `ArithmeticError` из-за переполнения результата.

<a id="ieee-math"></a>

## Явные операции IEEE с дробными числами

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.fdiv.php) | [PHP](https://www.php.net/manual/en/function.fpow.php)

`fdiv()` выполняет деление IEEE с бесконечностью и NaN; с 8.4 `fpow()` явно выполняет возведение в степень с плавающей точкой. Эти функции подходят там, где бесконечные результаты являются осознанной частью вычислений.

```php
$infinity = fdiv( 1.0, 0.0 ); # INF
$undefined = is_nan( fdiv(0.0, 0.0) ); # true

# PHP 8.4+
$power = fpow( 0.0, -1.0 ); # INF
```

Используйте `is_finite()`, когда нужен конечный результат, и `is_nan()` для NaN, который не равен даже себе.

<a id="date-conversion"></a>

## Копирование дат между изменяемым и неизменяемым API

**PHP 8.0+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [PHP](https://www.php.net/manual/en/datetime.createfromimmutable.php)

`DateTime::createFromInterface()` и `DateTimeImmutable::createFromInterface()` принимают обе стандартные реализации, сохраняя время и часовой пояс. Это убирает ветвление, когда API принимает `DateTimeInterface`.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
$original_day = $source->format( 'Y-m-d' ); # 2026-01-01
$next_day = $tomorrow->format( 'Y-m-d' ); # 2026-01-02
```

Неизменяемая копия не изменяет изменяемый оригинал. `DateTime::createFromImmutable()` уже существует с 7.3.

<a id="date-exceptions"></a>

## Точный перехват ошибок разбора дат

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/class.datemalformedstringexception.php)

С 8.3 расширение date имеет отдельные исключения для некорректных строк, часовых поясов, интервалов и периодов. `DateMalformedStringException` определяет некорректный ввод конструктора или `modify()` без перехвата всех `Exception`.

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

Некоторые невозможные календарные даты нормализуются вместо отказа; успешное создание объекта не обеспечивает строгой проверки даты.

<a id="strict-date-input"></a>

## Проверка точной календарной даты

**PHP 7.0+ | date | Контекст** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

Строгая проверка формата требует диагностики разбора и обратного форматирования: 31 февраля может разобраться, но нормализоваться до марта. Эти API существуют до PHP 7.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input; # false
```

`!` сбрасывает незаданные поля; читайте `getLastErrors()` сразу после разбора. С 8.2 успешный разбор даёт `false`, в предыдущих версиях возвращался массив.

<a id="iso-period"></a>

## Повторяющийся период из строки ISO 8601

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

`DatePeriod::createFromISO8601String()` превращает повторяющийся интервал в итерируемые значения `DateTimeImmutable`, что удобно для сохранённых расписаний.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );
foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

Начало включено, поэтому `R2` даёт три даты. Поддерживается лишь часть ISO 8601 без `R0` и смещений вне UTC; некорректный ввод вызывает `DateMalformedPeriodStringException`.

<a id="timestamp-microseconds"></a>

## Создание дат из дробных временных меток

**PHP 8.4+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

`createFromTimestamp()` принимает целые или дробные Unix-метки; методы микросекунд дают доступ к дробной части без форматирования и повторного разбора.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
$timestamp = $date->format( 'Y-m-d H:i:s.u' ); # 2023-11-14 22:13:20.125000
$original = $date->getMicrosecond(); # 125000
$updated = $changed->getMicrosecond(); # 500000
```

Неизменяемый сеттер возвращает копию, а `DateTime` изменяется. Float уже мог потерять точность; часовой пояс для отображения задаётся отдельно от момента времени.

<a id="rounding-modes"></a>

## Явное правило округления через `RoundingMode`

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/enum.roundingmode.php)

`RoundingMode` задаёт общее правило округления для `round()`, `bcround()` и `BcMath\Number::round()`. HalfEven направляет точную половину к чётному результату, HalfAwayFromZero от нуля.

```php
$even = round( 2.5, 0, RoundingMode::HalfEven ); # 2
$away = round( 2.5, 0, RoundingMode::HalfAwayFromZero ); # 3
$truncated = round( -2.9, 0, RoundingMode::TowardsZero ); # -2
```

Режим округления не делает двоичные float десятично точными. Отдельно выбирайте представление числа и момент округления; TowardsZero усекает до заданной точности.

<a id="bcmath-number"></a>

## Десятичная арифметика через `BcMath\Number`

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/class.bcmath-number.php)

`BcMath\Number` предоставляет неизменяемые десятичные числа произвольной точности с арифметическими операторами и явными методами. Создавайте значения из строк или целых чисел, чтобы избежать предварительного округления float.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero ); # 59.99
$expected = new BcMath\Number( '59.99' );
$equal = $total->compare( $expected ) === 0; # true
```

`===` проверяет тождественность объектов, а не равенство чисел; используйте `compare() === 0`. Класс игнорирует `bcmath.scale`, поэтому при необходимости явно задавайте масштаб деления.

<a id="bcmath-rounding"></a>

## Округление десятичных чисел и частное с остатком

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/function.bcround.php) | [PHP](https://www.php.net/manual/en/function.bcdivmod.php)

`bcround()`, `bcfloor()` и `bcceil()` округляют десятичные строки без преобразования во float. `bcdivmod()` возвращает частное и остаток вместе, в форме для деструктуризации.

```php
$rounded = bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ); # '20.00'
$floor = bcfloor( '-1.2' ); # '-2'
$ceiling = bcceil( '-1.2' ); # '-1'
[$quotient, $remainder] = bcdivmod( '17', '5', 0 ); # ['3', '2']
```

Результаты являются числовыми строками. Floor и ceil направлены к минус и плюс бесконечности, а не просто усекают число; задавайте масштаб `bcdivmod()` явно, чтобы не зависеть от глобальных настроек.
