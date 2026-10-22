# Dates and numbers

[English](../en/07-dates-and-numbers.md) | [Українська](../uk/07-dates-and-numbers.md) | [Русский](../ru/07-dates-and-numbers.md)

[Contents](../../README.md)

Date calculations depend on parsing rules and timezones; numeric calculations depend on representation and rounding. See also [numeric literals](01-syntax-and-functions.md#numeric-literals) and [random generation](11-security-and-random.md).

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

**PHP 7.0+ | Core** | [PHP](https://www.php.net/manual/en/function.intdiv.php)

`intdiv()` returns an integer quotient without an intermediate float, useful for complete units such as minutes or pages.

```php
$minutes = intdiv( 125, 60 ); # 2
$remainder = 125 % 60; # 5
$negative = intdiv( -7, 3 ); # -2
```

The quotient truncates towards zero. Zero divisors throw `DivisionByZeroError`; `intdiv(PHP_INT_MIN, -1)` throws `ArithmeticError` because the result overflows.

<a id="ieee-math"></a>

## Use explicit IEEE floating-point operations

**PHP 8.0+ | Core** | [PHP](https://www.php.net/manual/en/function.fdiv.php) | [PHP](https://www.php.net/manual/en/function.fpow.php)

`fdiv()` follows IEEE division, including infinity and NaN; `fpow()` adds explicit floating-point exponentiation from 8.4. These functions fit calculations where non-finite results are intentional.

```php
$infinity = fdiv( 1.0, 0.0 ); # INF
$undefined = is_nan( fdiv(0.0, 0.0) ); # true

# PHP 8.4+
$power = fpow( 0.0, -1.0 ); # INF
```

Use `is_finite()` when a finite result is required, and `is_nan()` to recognize NaN, which never equals itself.

<a id="date-conversion"></a>

## Copy dates across mutable and immutable APIs

**PHP 8.0+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [PHP](https://www.php.net/manual/en/datetime.createfromimmutable.php)

`DateTime::createFromInterface()` and `DateTimeImmutable::createFromInterface()` accept either standard implementation, preserving its time and timezone. This avoids branching when an API accepts `DateTimeInterface`.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
$original_day = $source->format( 'Y-m-d' ); # 2026-01-01
$next_day = $tomorrow->format( 'Y-m-d' ); # 2026-01-02
```

The immutable copy leaves the mutable source unchanged. `DateTime::createFromImmutable()` already exists from 7.3.

<a id="date-exceptions"></a>

## Catch specific malformed-date exceptions

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/class.datemalformedstringexception.php)

The date extension gains specific exceptions in 8.3 for malformed strings, timezones, intervals and periods. `DateMalformedStringException` identifies bad constructor or `modify()` input without catching every `Exception`.

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

Some impossible calendar dates are normalized instead of rejected; constructor success does not provide strict date validation.

<a id="strict-date-input"></a>

## Validate an exact calendar date

**PHP 7.0+ | date | Background** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

Strict formatted input needs parser diagnostics plus a format round trip: a value such as February 31 may parse but normalize to March. These APIs predate PHP 7.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input; # false
```

`!` resets unspecified fields; read `getLastErrors()` immediately after parsing. A clean parse returns `false` from 8.2 and an array in earlier versions.

<a id="iso-period"></a>

## Create a repeating period from ISO 8601

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

`DatePeriod::createFromISO8601String()` turns a repeating interval into iterable `DateTimeImmutable` values, useful for stored schedules.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );
foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

The start is included, so `R2` yields three dates. Only a subset of ISO 8601 is supported, excluding `R0` and non-UTC offsets; invalid input throws `DateMalformedPeriodStringException`.

<a id="timestamp-microseconds"></a>

## Create dates from fractional timestamps

**PHP 8.4+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

`createFromTimestamp()` accepts integer or float Unix timestamps; microsecond accessors expose the fractional component without formatting and reparsing it.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
$timestamp = $date->format( 'Y-m-d H:i:s.u' ); # 2023-11-14 22:13:20.125000
$original = $date->getMicrosecond(); # 125000
$updated = $changed->getMicrosecond(); # 500000
```

The immutable setter returns a copy, while `DateTime` mutates. Floats may already have lost precision; choose the intended display timezone separately from the instant.

<a id="rounding-modes"></a>

## State the rounding rule with `RoundingMode`

**PHP 8.4+ | Core** | [PHP](https://www.php.net/manual/en/enum.roundingmode.php)

`RoundingMode` names the rounding policy shared by `round()`, `bcround()` and `BcMath\Number::round()`. HalfEven resolves ties to an even result; HalfAwayFromZero resolves them away from zero.

```php
$even = round( 2.5, 0, RoundingMode::HalfEven ); # 2
$away = round( 2.5, 0, RoundingMode::HalfAwayFromZero ); # 3
$truncated = round( -2.9, 0, RoundingMode::TowardsZero ); # -2
```

A rounding mode does not make binary floats decimal-exact. Choose the numeric representation and the point of rounding as well as the mode; TowardsZero truncates at the selected precision.

<a id="bcmath-number"></a>

## Use decimal arithmetic with `BcMath\Number`

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/class.bcmath-number.php)

`BcMath\Number` provides immutable arbitrary-precision decimals with arithmetic operators and explicit methods. Construct values from strings or integers so they have not already passed through float rounding.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero ); # 59.99
$expected = new BcMath\Number( '59.99' );
$equal = $total->compare( $expected ) === 0; # true
```

`===` checks object identity, not numeric equality; use `compare() === 0`. The class ignores `bcmath.scale`, so select division scale explicitly when the rule requires it.

<a id="bcmath-rounding"></a>

## Round decimals and get quotient plus remainder

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/function.bcround.php) | [PHP](https://www.php.net/manual/en/function.bcdivmod.php)

`bcround()`, `bcfloor()` and `bcceil()` round decimal strings without float conversion. `bcdivmod()` returns the quotient and remainder together, ready for destructuring.

```php
$rounded = bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ); # '20.00'
$floor = bcfloor( '-1.2' ); # '-2'
$ceiling = bcceil( '-1.2' ); # '-1'
[$quotient, $remainder] = bcdivmod( '17', '5', 0 ); # ['3', '2']
```

Results are numeric strings. Floor and ceil follow negative and positive infinity rather than truncating; pass `bcdivmod()` scale explicitly to avoid depending on global settings.
