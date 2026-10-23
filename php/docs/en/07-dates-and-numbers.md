# Dates and numbers

[EN](../en/07-dates-and-numbers.md) | [UK](../uk/07-dates-and-numbers.md) | [RU](../ru/07-dates-and-numbers.md)

[Contents](../../README.md)

Check dates without silently turning February 31 into March, calculate periods and choose the rounding rule the task needs. BCMath gets its own examples for exact decimal calculations. See also [numeric literals](01-syntax-and-functions.md#numeric-literals) and [random values](11-security-and-random.md).

- [Divide integers with `intdiv()`](#integer-division)
- [Division and powers with `INF` and `NaN`](#ieee-math)
- [Copy a date into `DateTime` or `DateTimeImmutable`](#date-conversion)
- [Catch an invalid date string](#date-exceptions)
- [Validate an exact calendar date](#strict-date-input)
- [Create a repeating period from ISO 8601](#iso-period)
- [A date from a Unix timestamp with microseconds](#timestamp-microseconds)
- [State the rounding rule with `RoundingMode`](#rounding-modes)
- [Use decimal arithmetic with `BcMath\Number`](#bcmath-number)
- [Round decimals and get quotient plus remainder](#bcmath-rounding)

<a id="integer-division"></a>

## Divide integers with `intdiv()`

**PHP 7.0+ | Core** | [PHP](https://www.php.net/manual/en/function.intdiv.php)

Use `intdiv()` when you need whole minutes, pages or batches of items. Here 125 seconds gives us two full minutes, and `%` gives us the remaining five seconds. There is no intermediate `float`.

```php
$minutes = intdiv( 125, 60 ); # 2
$remainder = 125 % 60; # 5
$negative = intdiv( -7, 3 ); # -2
```

The fractional part is dropped towards zero, so `intdiv(-7, 3)` gives `-2`, not `-3`. A zero divisor throws `DivisionByZeroError`. Dividing `PHP_INT_MIN` by `-1` does not fit in an integer and throws `ArithmeticError`.

<a id="ieee-math"></a>

## Division and powers with `INF` and `NaN`

**PHP 8.0+ | Core** | [PHP](https://www.php.net/manual/en/function.fdiv.php) | [PHP](https://www.php.net/manual/en/function.fpow.php)

Sometimes infinity is an acceptable calculation result, and an exception would only interrupt the calculation. `fdiv()` follows IEEE rules: `fdiv(1.0, 0.0)` gives `INF`, while `fdiv(0.0, 0.0)` gives `NaN`, an undefined result. PHP 8.4 adds `fpow()` for floating-point exponentiation under these rules.

```php
$infinity = fdiv( 1.0, 0.0 ); # INF
$undefined = is_nan( fdiv(0.0, 0.0) ); # true

# PHP 8.4+
$power = fpow( 0.0, -1.0 ); # INF
```

If the result needs to be finite, check it with `is_finite()`. `NaN` is not even equal to itself; use `is_nan()` to check for it.

<a id="date-conversion"></a>

## Copy a date into `DateTime` or `DateTimeImmutable`

**PHP 8.0+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [PHP](https://www.php.net/manual/en/datetime.createfromimmutable.php)

A function accepting `DateTimeInterface` may receive either `DateTime` or `DateTimeImmutable`. Use `createFromInterface()` to get the version you need without checking each class separately. It keeps the time and timezone. Here we make an immutable copy, then calculate tomorrow from that copy.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
$original_day = $source->format( 'Y-m-d' ); # 2026-01-01
$next_day = $tomorrow->format( 'Y-m-d' ); # 2026-01-02
```

The original `$source` stays on January 1, and `$tomorrow` holds January 2. `DateTimeImmutable` methods return a new object, so the starting date does not move. To convert specifically from `DateTimeImmutable` to `DateTime`, `DateTime::createFromImmutable()` has been available since PHP 7.3.

<a id="date-exceptions"></a>

## Catch an invalid date string

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/class.datemalformedstringexception.php)

Since PHP 8.3, `DateMalformedStringException` lets us catch an invalid date string separately. This is useful when we want to handle bad input without catching every other `Exception` as well. The example string cannot be parsed as a date, so execution goes into `catch`.

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

The date extension also has separate exceptions for timezones, intervals and periods. Still, no exception does not always mean the input date was valid: PHP adjusts some impossible dates automatically. The next example checks the actual calendar date.

<a id="strict-date-input"></a>

## Validate an exact calendar date

**PHP 7.0+ | date | Background** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

February 31 does not exist, but `createFromFormat()` alone does not reject it: PHP may carry the extra days into March and return an object. We check the parse errors, then format the date back and compare it with the input. These methods predate PHP 7.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
# Read the errors now; another date parse will overwrite them.
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input; # false
```

`!` resets fields missing from the format so they do not come from the current time. Call `getLastErrors()` right after parsing; another parse overwrites the diagnostics. Since PHP 8.2, a parse without errors or warnings returns `false` here; older versions always returned an array. The check handles both forms.

<a id="iso-period"></a>

## Create a repeating period from ISO 8601

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

If a schedule is stored as an ISO 8601 string, `DatePeriod::createFromISO8601String()` lets us iterate over its dates directly with `foreach`. Here we start on January 1 and add one day twice. Each iteration gives us a `DateTimeImmutable`.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );
foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

The starting date is included, so `R2` produces three dates. The method supports only part of ISO 8601: `R0` and timezone offsets outside UTC are not supported. Invalid input throws `DateMalformedPeriodStringException`.

<a id="timestamp-microseconds"></a>

## A date from a Unix timestamp with microseconds

**PHP 8.4+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

`createFromTimestamp()` creates a date directly from a Unix timestamp, including its fractional part. `getMicrosecond()` and `setMicrosecond()` let us work with microseconds without pulling them out through `format()` and parsing a date again. Here we change `.125000` to `.500000`.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
$timestamp = $date->format( 'Y-m-d H:i:s.u' ); # 2023-11-14 22:13:20.125000
$original = $date->getMicrosecond(); # 125000
$updated = $changed->getMicrosecond(); # 500000
```

For `DateTimeImmutable`, `setMicrosecond()` returns a new object; for `DateTime`, it changes the current one. A fractional `float` may already have lost precision before reaching the method. The timestamp identifies a moment in time; the timezone used to display it is set separately.

<a id="rounding-modes"></a>

## State the rounding rule with `RoundingMode`

**PHP 8.4+ | Core** | [PHP](https://www.php.net/manual/en/enum.roundingmode.php)

When a number is exactly halfway between two results, the rounding rule decides which one we get. `HalfEven` rounds `2.5` to the even value `2`; `HalfAwayFromZero` moves the halfway value away from zero and gives `3`. The `RoundingMode` enum works with `round()`, `bcround()` and `BcMath\Number::round()`.

```php
$even = round( 2.5, 0, RoundingMode::HalfEven ); # 2
$away = round( 2.5, 0, RoundingMode::HalfAwayFromZero ); # 3
$truncated = round( -2.9, 0, RoundingMode::TowardsZero ); # -2
```

`TowardsZero` drops the extra fractional part at the requested precision, so `-2.9` becomes `-2`. A rounding mode does not fix binary `float` precision. How we store the number and how we round it are separate choices.

<a id="bcmath-number"></a>

## Use decimal arithmetic with `BcMath\Number`

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/class.bcmath-number.php)

Use `BcMath\Number` when you need exact decimal calculations. It supports arithmetic operators and methods, and operations return new objects. Pass the starting value as a string or integer: if we create a `float` first, its rounding error is already part of the calculation.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero ); # 59.99
$expected = new BcMath\Number( '59.99' );
# Compare the numbers inside the objects, not object identity.
$equal = $total->compare( $expected ) === 0; # true
```

Here we multiply the price by three, then round the total to two decimal places. `===` checks whether two variables hold the same object, not whether their numbers are equal. Use `compare() === 0` for that. The class ignores `bcmath.scale`, so set the required decimal scale explicitly when dividing.

<a id="bcmath-rounding"></a>

## Round decimals and get quotient plus remainder

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/function.bcround.php) | [PHP](https://www.php.net/manual/en/function.bcdivmod.php)

For decimal numbers stored as strings, use `bcround()`, `bcfloor()` and `bcceil()`. They do not convert the number to `float`, so they do not introduce its precision loss. If we need both quotient and remainder, `bcdivmod()` returns them together, ready to unpack into two variables.

```php
$rounded = bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ); # '20.00'
$floor = bcfloor( '-1.2' ); # '-2'
$ceiling = bcceil( '-1.2' ); # '-1'
[$quotient, $remainder] = bcdivmod( '17', '5', 0 ); # ['3', '2']
```

The results are strings too. Watch negative numbers: `bcfloor('-1.2')` moves down to `-2`, while `bcceil('-1.2')` moves up to `-1`. Neither means simply dropping the fractional part. Set the decimal scale for `bcdivmod()` explicitly so the result does not depend on a global setting.
