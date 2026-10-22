# Dates and numbers

[English](../en/07-dates-and-numbers.md) | [Українська](../uk/07-dates-and-numbers.md) | [Русский](../ru/07-dates-and-numbers.md)

[Contents](../../README.md)

For digit separators and explicit octal literals, see [numeric literals](01-syntax-and-functions.md#numeric-literals).

Date calculations need an explicit timezone and an input format. Numeric calculations need an explicit representation and rounding rule. These choices remain important even when a newer API makes the syntax shorter. Random generation is covered in [security and random](11-security-and-random.md).

- [Divide integers with `intdiv()`](#integer-division)
- [Use explicit IEEE floating-point operations](#ieee-math)
- [Copy dates across mutable and immutable APIs](#date-conversion)
- [Catch specific malformed-date exceptions](#date-exceptions)
- [Validate an exact calendar date](#strict-date-input)
- [Create a repeating period from ISO 8601](#iso-period)
- [Create dates from fractional timestamps](#timestamp-microseconds)
- [State the rounding rule with `RoundingMode`](#rounding-modes)
- [Use decimal arithmetic with `BcMath\Number`](#bcmath-number)
- [Round decimals and get quotient plus remainder](#bcmath-rounding)

<a id="integer-division"></a>

## Divide integers with `intdiv()`

**PHP 7.0+ | Core**

`intdiv()` returns an integer quotient without routing the calculation through a floating-point result. It is useful for grouping whole units, such as complete minutes or full pages.

```php
echo intdiv( 125, 60 ) . PHP_EOL; # 2
echo intdiv( -7, 3 ) . PHP_EOL; # -2
echo 125 % 60 . PHP_EOL; # 5
```

**Remember:** The quotient truncates towards zero, not towards negative infinity. Division by zero throws `DivisionByZeroError`. `PHP_INT_MIN` divided by -1 throws `ArithmeticError` because the result does not fit an integer.

[PHP manual: intdiv()](https://www.php.net/manual/en/function.intdiv.php)


<a id="ieee-math"></a>

## Use explicit IEEE floating-point operations

**PHP 8.0+ | Core**

`fdiv()` follows IEEE floating-point division, including infinity and NaN outcomes. `fpow()` brings the same explicit approach to exponentiation from PHP 8.4. They are useful when non-finite results are a deliberate part of a numerical model.

```php
var_dump( fdiv(1.0, 0.0) ); # float(INF)
var_dump( is_nan(fdiv(0.0, 0.0)) ); # bool(true)

# PHP 8.4+
var_dump( fpow(0.0, -1.0) ); # float(INF)
```

**Remember:** These functions do not make bad business inputs valid. Check `is_finite()` when a finite result is required. NaN is not equal to itself; use `is_nan()` instead of a comparison.

[PHP manual: fdiv()](https://www.php.net/manual/en/function.fdiv.php) | [PHP manual: fpow()](https://www.php.net/manual/en/function.fpow.php)

<a id="date-conversion"></a>

## Copy dates across mutable and immutable APIs

**PHP 8.0+ | date**

`DateTime::createFromInterface()` and `DateTimeImmutable::createFromInterface()` accept either standard date object through `DateTimeInterface`. The copied date keeps its time and timezone without modifying the original.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
echo $source->format( 'Y-m-d' ) . PHP_EOL; # 2026-01-01
echo $tomorrow->format( 'Y-m-d' ) . PHP_EOL; # 2026-01-02
```

**Remember:** `DateTime::createFromImmutable()` already became available in PHP 7.3. `DateTimeImmutable` itself predates PHP 7. The new interface factories remove the need to branch on which standard date implementation arrived.

[PHP manual: createFromInterface()](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [PHP manual: createFromImmutable()](https://www.php.net/manual/en/datetime.createfromimmutable.php)

<a id="date-exceptions"></a>

## Catch specific malformed-date exceptions

**PHP 8.3+ | date**

The date extension has a more specific exception and error hierarchy from PHP 8.3. `DateMalformedStringException` identifies malformed constructor or `modify()` input, instead of forcing every date failure into a broad Exception handler.

```php
try
{
	new DateTimeImmutable( 'not a date at all' );
}
catch( DateMalformedStringException $error )
{
	echo 'Invalid date' . PHP_EOL;
}
```

**Remember:** Some impossible calendar dates are normalized rather than rejected. Constructor success alone is not strict form validation. Related classes include `DateInvalidTimeZoneException`, `DateMalformedIntervalStringException` and `DateMalformedPeriodStringException`.

[PHP manual: DateMalformedStringException](https://www.php.net/manual/en/class.datemalformedstringexception.php)

<a id="strict-date-input"></a>

## Validate an exact calendar date

**PHP 7.0+ | date | Background**

For a form field with one exact format, use `createFromFormat()`, inspect parser errors and compare the formatted result with the input. The API predates PHP 7; the important migration detail is that `getLastErrors()` returns false for a clean parse from PHP 8.2.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input;
var_dump( $valid ); # bool(false)
```

**Remember:** The ! resets unspecified fields. Read `getLastErrors()` immediately after the parse you are checking. Validation of external date ranges and access rules remains an application decision.

[PHP manual: createFromFormat()](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [PHP manual: getLastErrors()](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

<a id="iso-period"></a>

## Create a repeating period from ISO 8601

**PHP 8.3+ | date**

`DatePeriod::createFromISO8601String()` is an explicit factory for a repeating interval description. Iterating the result produces `DateTimeImmutable` objects, which is convenient when reading a stored schedule.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );

foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

**Remember:** The start date is included by default, so two repetitions produce three dates here. PHP accepts only a subset of ISO 8601 repeating intervals; arbitrary non-UTC offsets and R0 are not supported. Invalid input throws `DateMalformedPeriodStringException`.

[PHP manual: DatePeriod factory](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

<a id="timestamp-microseconds"></a>

## Create dates from fractional timestamps

**PHP 8.4+ | date**

`createFromTimestamp()` accepts an integer or float Unix timestamp. `getMicrosecond()` and `setMicrosecond()` expose the fractional component directly, without formatting and reparsing a U.u string.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
echo $date->format( 'Y-m-d H:i:s.u' ) . PHP_EOL; # 2023-11-14 22:13:20.125000
echo $changed->getMicrosecond() . PHP_EOL; # 500000
echo $date->getMicrosecond() . PHP_EOL; # 125000
```

**Remember:** The immutable setter returns a new object; `DateTime` mutates itself. A float may already have lost precision before the call. A Unix timestamp represents an instant; choose the display timezone explicitly when formatting for a reader.

[PHP manual: createFromTimestamp()](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [PHP manual: setMicrosecond()](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

<a id="rounding-modes"></a>

## State the rounding rule with `RoundingMode`

**PHP 8.4+ | Core**

`RoundingMode` gives `round()` a named rounding policy. HalfEven resolves exact ties towards an even result; HalfAwayFromZero resolves ties away from zero. TowardsZero is truncation at the selected precision rather than nearest rounding.

```php
echo round( 2.5, 0, RoundingMode::HalfEven ) . PHP_EOL; # 2
echo round( 2.5, 0, RoundingMode::HalfAwayFromZero ) . PHP_EOL; # 3
echo round( -2.9, 0, RoundingMode::TowardsZero ) . PHP_EOL; # -2
```

**Remember:** The enum is shared with `bcround()` and `BcMath\Number::round()`. It does not make binary float arithmetic decimal-exact. Choose the numeric representation and the rounding point as well as the mode.

[PHP manual: RoundingMode](https://www.php.net/manual/en/enum.roundingmode.php)

<a id="bcmath-number"></a>

## Use decimal arithmetic with `BcMath\Number`

**PHP 8.4+ | bcmath**

`BcMath\Number` is an immutable arbitrary-precision decimal object with arithmetic operators and explicit methods. Supply decimal strings or integers so the value has not already passed through binary float rounding.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero );
echo $total . PHP_EOL; # 59.99

$expected = new BcMath\Number( '59.99' );
var_dump( $total->compare($expected) === 0 ); # bool(true)
```

**Remember:** === on two Number objects checks object identity, not decimal equality; `compare()` === 0 expresses numeric equality. The class is not governed by bcmath.scale. Explicitly select scale for operations such as division when the business rule requires it.

[PHP manual: BcMath\Number](https://www.php.net/manual/en/class.bcmath-number.php)

<a id="bcmath-rounding"></a>

## Round decimals and get quotient plus remainder

**PHP 8.4+ | bcmath**

`bcround()`, `bcfloor()` and `bcceil()` handle decimal strings without manually cutting their fractional part. `bcdivmod()` returns a quotient and remainder together and fits naturally with array destructuring.

```php
echo bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ) . PHP_EOL; # 20.00
echo bcfloor( '-1.2' ) . PHP_EOL; # -2
echo bcceil( '-1.2' ) . PHP_EOL; # -1

[$quotient, $remainder] = bcdivmod( '17', '5', 0 );
echo $quotient . ':' . $remainder . PHP_EOL; # 3:2
```

**Remember:** Results are numeric strings. floor and ceil follow negative and positive infinity respectively, which differs from simply removing a minus number’s fractional part. Set `bcdivmod()` scale explicitly to avoid a hidden dependency on global settings.

[PHP manual: bcround()](https://www.php.net/manual/en/function.bcround.php) | [PHP manual: bcdivmod()](https://www.php.net/manual/en/function.bcdivmod.php)
