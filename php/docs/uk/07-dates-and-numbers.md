# Дати та числа

[English](../en/07-dates-and-numbers.md) | [Українська](../uk/07-dates-and-numbers.md) | [Русский](../ru/07-dates-and-numbers.md)

[Зміст](../../README.uk.md)

Роздільники цифр і явний запис вісімкових чисел розглянуто в [числових літералах](01-syntax-and-functions.md#numeric-literals).

Обчислення дат потребують явного часового поясу та формату введення. Числові обчислення потребують вибору представлення й правила округлення. Це важливо навіть тоді, коли новий API скорочує запис. Генерацію випадкових значень описано у главі [про безпеку](11-security-and-random.md).

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

**PHP 7.0+ | Ядро**

`intdiv()` повертає цілу частку без проміжного дробового результату. Це зручно для групування цілих одиниць, наприклад повних хвилин або сторінок.

```php
echo intdiv( 125, 60 ) . PHP_EOL; # 2
echo intdiv( -7, 3 ) . PHP_EOL; # -2
echo 125 % 60 . PHP_EOL; # 5
```

**Важливо:** Частка відкидає дробову частину в напрямку нуля, а не мінус нескінченності. Ділення на нуль кидає `DivisionByZeroError`. `PHP_INT_MIN`, поділене на -1, кидає `ArithmeticError`, бо результат не вміщується в int.

[Документація PHP: intdiv()](https://www.php.net/manual/en/function.intdiv.php)


<a id="ieee-math"></a>

## Явні операції IEEE з дробовими числами

**PHP 8.0+ | Ядро**

`fdiv()` виконує ділення за правилами IEEE, зокрема з результатами нескінченності та NaN. З PHP 8.4 `fpow()` явно застосовує такий підхід до степеня. Це корисно, коли нескінченні результати є свідомою частиною числової моделі.

```php
var_dump( fdiv(1.0, 0.0) ); # float(INF)
var_dump( is_nan(fdiv(0.0, 0.0)) ); # bool(true)

# PHP 8.4+
var_dump( fpow(0.0, -1.0) ); # float(INF)
```

**Важливо:** Ці функції не роблять неправильні прикладні дані коректними. Перевіряйте `is_finite()`, якщо потрібен скінченний результат. NaN не дорівнює самому собі; використовуйте `is_nan()`.

[Документація PHP: fdiv()](https://www.php.net/manual/en/function.fdiv.php) | [Документація PHP: fpow()](https://www.php.net/manual/en/function.fpow.php)

<a id="date-conversion"></a>

## Копіювання дат між змінним і незмінним API

**PHP 8.0+ | date**

`DateTime::createFromInterface()` та `DateTimeImmutable::createFromInterface()` приймають обидва стандартні об’єкти дати через `DateTimeInterface`. Копія зберігає час і часовий пояс без зміни оригіналу.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
echo $source->format( 'Y-m-d' ) . PHP_EOL; # 2026-01-01
echo $tomorrow->format( 'Y-m-d' ) . PHP_EOL; # 2026-01-02
```

**Важливо:** `DateTime::createFromImmutable()` з’явилася ще у PHP 7.3. Сама `DateTimeImmutable` існувала до PHP 7. Нові фабрики для інтерфейсу прибирають необхідність розрізняти дві стандартні реалізації дати.

[Документація PHP: createFromInterface()](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [Документація PHP: createFromImmutable()](https://www.php.net/manual/en/datetime.createfromimmutable.php)

<a id="date-exceptions"></a>

## Точне перехоплення помилок розбору дат

**PHP 8.3+ | date**

З PHP 8.3 розширення date має точнішу ієрархію винятків і помилок. `DateMalformedStringException` позначає некоректний рядок для конструктора або `modify()`, тож не потрібно перехоплювати всі помилки дат загальною Exception.

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

**Важливо:** Деякі неможливі календарні дати нормалізуються замість відхилення. Успішний конструктор сам не є суворою перевіркою форми. Серед пов’язаних класів є `DateInvalidTimeZoneException`, `DateMalformedIntervalStringException` та `DateMalformedPeriodStringException`.

[Документація PHP: DateMalformedStringException](https://www.php.net/manual/en/class.datemalformedstringexception.php)

<a id="strict-date-input"></a>

## Перевірка точної календарної дати

**PHP 7.0+ | date | Контекст**

Для поля форми з одним точним форматом використовуйте `createFromFormat()`, перевіряйте помилки парсера та порівнюйте відформатований результат із введенням. API існувала до PHP 7; важлива зміна: з PHP 8.2 `getLastErrors()` повертає false за відсутності помилок.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input;
var_dump( $valid ); # bool(false)
```

**Важливо:** ! скидає невказані поля. Читайте `getLastErrors()` одразу після потрібного розбору. Допустимі діапазони дат і правила доступу залишаються рішенням програми.

[Документація PHP: createFromFormat()](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [Документація PHP: getLastErrors()](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

<a id="iso-period"></a>

## Повторюваний період із рядка ISO 8601

**PHP 8.3+ | date**

`DatePeriod::createFromISO8601String()` явно створює період з опису повторюваного інтервалу. Обхід результату дає об’єкти `DateTimeImmutable`, що зручно для читання збереженого розкладу.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );

foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

**Важливо:** Початкова дата типово включена, тому два повторення тут дають три дати. PHP підтримує лише підмножину повторюваних інтервалів ISO 8601; довільні зсуви від UTC та R0 не підтримуються. Некоректне введення кидає `DateMalformedPeriodStringException`.

[Документація PHP: DatePeriod factory](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

<a id="timestamp-microseconds"></a>

## Створення дат із дробових часових міток

**PHP 8.4+ | date**

`createFromTimestamp()` приймає цілу або дробову Unix-мітку часу. `getMicrosecond()` та `setMicrosecond()` дають прямий доступ до дробової частини без форматування й повторного розбору рядка U.u.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
echo $date->format( 'Y-m-d H:i:s.u' ) . PHP_EOL; # 2023-11-14 22:13:20.125000
echo $changed->getMicrosecond() . PHP_EOL; # 500000
echo $date->getMicrosecond() . PHP_EOL; # 125000
```

**Важливо:** Незмінний setter повертає новий об’єкт; `DateTime` змінює себе. float може втратити точність ще до виклику. Unix-мітка позначає момент часу; під час показу читачеві явно вибирайте часовий пояс.

[Документація PHP: createFromTimestamp()](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [Документація PHP: setMicrosecond()](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

<a id="rounding-modes"></a>

## Явне правило округлення через `RoundingMode`

**PHP 8.4+ | Ядро**

`RoundingMode` задає для `round()` іменоване правило. HalfEven спрямовує точну половину до парного результату, HalfAwayFromZero спрямовує її від нуля. TowardsZero відкидає зайві цифри за вибраною точністю замість округлення до найближчого.

```php
echo round( 2.5, 0, RoundingMode::HalfEven ) . PHP_EOL; # 2
echo round( 2.5, 0, RoundingMode::HalfAwayFromZero ) . PHP_EOL; # 3
echo round( -2.9, 0, RoundingMode::TowardsZero ) . PHP_EOL; # -2
```

**Важливо:** Цей enum також використовують `bcround()` та `BcMath\Number::round()`. Він не робить двійкову арифметику float точною десятковою. Вибирайте і представлення числа, і момент округлення, і правило.

[Документація PHP: RoundingMode](https://www.php.net/manual/en/enum.roundingmode.php)

<a id="bcmath-number"></a>

## Десяткова арифметика через `BcMath\Number`

**PHP 8.4+ | bcmath**

`BcMath\Number` є незмінним об’єктом десяткового числа довільної точності з арифметичними операторами та явними методами. Передавайте десяткові рядки або цілі числа, щоб значення не проходило через округлення двійкового float.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero );
echo $total . PHP_EOL; # 59.99

$expected = new BcMath\Number( '59.99' );
var_dump( $total->compare($expected) === 0 ); # bool(true)
```

**Важливо:** === для двох об’єктів Number перевіряє тотожність об’єктів, а не числову рівність; `compare()` === 0 виражає рівність чисел. Клас не керується bcmath.scale. Явно задавайте scale, зокрема для ділення, якщо цього потребує прикладне правило.

[Документація PHP: BcMath\Number](https://www.php.net/manual/en/class.bcmath-number.php)

<a id="bcmath-rounding"></a>

## Округлення десяткових чисел і частка з остачею

**PHP 8.4+ | bcmath**

`bcround()`, `bcfloor()` та `bcceil()` обробляють десяткові рядки без ручного обрізання дробової частини. `bcdivmod()` повертає частку й остачу разом і природно поєднується з розкладанням масиву.

```php
echo bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ) . PHP_EOL; # 20.00
echo bcfloor( '-1.2' ) . PHP_EOL; # -2
echo bcceil( '-1.2' ) . PHP_EOL; # -1

[$quotient, $remainder] = bcdivmod( '17', '5', 0 );
echo $quotient . ':' . $remainder . PHP_EOL; # 3:2
```

**Важливо:** Результати є числовими рядками. floor та ceil спрямовані відповідно до мінус і плюс нескінченності, що відрізняється від простого видалення дробової частини від’ємного числа. Явно задавайте scale у `bcdivmod()`, щоб не залежати від глобальних налаштувань.

[Документація PHP: bcround()](https://www.php.net/manual/en/function.bcround.php) | [Документація PHP: bcdivmod()](https://www.php.net/manual/en/function.bcdivmod.php)
