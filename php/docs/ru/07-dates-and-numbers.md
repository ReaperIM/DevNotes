# Даты и числа

[English](../en/07-dates-and-numbers.md) | [Українська](../uk/07-dates-and-numbers.md) | [Русский](../ru/07-dates-and-numbers.md)

[Содержание](../../README.ru.md)

Разделители цифр и явная запись восьмеричных чисел разобраны в [числовых литералах](01-syntax-and-functions.md#numeric-literals).

Вычисления дат требуют явного часового пояса и формата ввода. Числовые вычисления требуют выбора представления и правила округления. Это важно даже тогда, когда новый API сокращает запись. Генерация случайных значений описана в главе [о безопасности](11-security-and-random.md).

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

**PHP 7.0+ | Ядро**

`intdiv()` возвращает целое частное без промежуточного дробного результата. Это удобно для группировки целых единиц, например полных минут или страниц.

```php
echo intdiv( 125, 60 ) . PHP_EOL; # 2
echo intdiv( -7, 3 ) . PHP_EOL; # -2
echo 125 % 60 . PHP_EOL; # 5
```

**Важно:** Частное получается отбрасыванием дробной части в сторону нуля, а не минус бесконечности. Деление на ноль выбрасывает `DivisionByZeroError`. `PHP_INT_MIN`, делённое на -1, выбрасывает `ArithmeticError`, поскольку результат не помещается в int.

[Документация PHP: intdiv()](https://www.php.net/manual/en/function.intdiv.php)


<a id="ieee-math"></a>

## Явные операции IEEE с дробными числами

**PHP 8.0+ | Ядро**

`fdiv()` выполняет деление по правилам IEEE, включая результаты бесконечности и NaN. С PHP 8.4 `fpow()` явно применяет такой подход к степени. Это полезно, когда бесконечные результаты являются осознанной частью числовой модели.

```php
var_dump( fdiv(1.0, 0.0) ); # float(INF)
var_dump( is_nan(fdiv(0.0, 0.0)) ); # bool(true)

# PHP 8.4+
var_dump( fpow(0.0, -1.0) ); # float(INF)
```

**Важно:** Эти функции не делают неправильные прикладные данные корректными. Проверяйте `is_finite()`, если нужен конечный результат. NaN не равен самому себе; используйте `is_nan()`.

[Документация PHP: fdiv()](https://www.php.net/manual/en/function.fdiv.php) | [Документация PHP: fpow()](https://www.php.net/manual/en/function.fpow.php)

<a id="date-conversion"></a>

## Копирование дат между изменяемым и неизменяемым API

**PHP 8.0+ | date**

`DateTime::createFromInterface()` и `DateTimeImmutable::createFromInterface()` принимают оба стандартных объекта даты через `DateTimeInterface`. Копия сохраняет время и часовой пояс без изменения оригинала.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
echo $source->format( 'Y-m-d' ) . PHP_EOL; # 2026-01-01
echo $tomorrow->format( 'Y-m-d' ) . PHP_EOL; # 2026-01-02
```

**Важно:** `DateTime::createFromImmutable()` появилась ещё в PHP 7.3. Сама `DateTimeImmutable` существовала до PHP 7. Новые фабрики для интерфейса убирают необходимость различать две стандартные реализации даты.

[Документация PHP: createFromInterface()](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [Документация PHP: createFromImmutable()](https://www.php.net/manual/en/datetime.createfromimmutable.php)

<a id="date-exceptions"></a>

## Точный перехват ошибок разбора дат

**PHP 8.3+ | date**

С PHP 8.3 расширение date имеет более точную иерархию исключений и ошибок. `DateMalformedStringException` обозначает некорректную строку для конструктора или `modify()`, поэтому не нужно перехватывать все ошибки дат общей Exception.

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

**Важно:** Некоторые невозможные календарные даты нормализуются вместо отклонения. Успешный конструктор сам не является строгой проверкой формы. Среди связанных классов есть `DateInvalidTimeZoneException`, `DateMalformedIntervalStringException` и `DateMalformedPeriodStringException`.

[Документация PHP: DateMalformedStringException](https://www.php.net/manual/en/class.datemalformedstringexception.php)

<a id="strict-date-input"></a>

## Проверка точной календарной даты

**PHP 7.0+ | date | Контекст**

Для поля формы с одним точным форматом используйте `createFromFormat()`, проверяйте ошибки парсера и сравнивайте отформатированный результат с вводом. API существовала до PHP 7; важное изменение: с PHP 8.2 `getLastErrors()` возвращает false при отсутствии ошибок.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input;
var_dump( $valid ); # bool(false)
```

**Важно:** ! сбрасывает неуказанные поля. Читайте `getLastErrors()` сразу после нужного разбора. Допустимые диапазоны дат и правила доступа остаются решением программы.

[Документация PHP: createFromFormat()](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [Документация PHP: getLastErrors()](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

<a id="iso-period"></a>

## Повторяющийся период из строки ISO 8601

**PHP 8.3+ | date**

`DatePeriod::createFromISO8601String()` явно создаёт период из описания повторяющегося интервала. Обход результата даёт объекты `DateTimeImmutable`, что удобно для чтения сохранённого расписания.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );

foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

**Важно:** Начальная дата по умолчанию включена, поэтому два повторения здесь дают три даты. PHP поддерживает лишь подмножество повторяющихся интервалов ISO 8601; произвольные смещения от UTC и R0 не поддерживаются. Некорректный ввод выбрасывает `DateMalformedPeriodStringException`.

[Документация PHP: DatePeriod factory](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

<a id="timestamp-microseconds"></a>

## Создание дат из дробных временных меток

**PHP 8.4+ | date**

`createFromTimestamp()` принимает целую или дробную Unix-метку времени. `getMicrosecond()` и `setMicrosecond()` дают прямой доступ к дробной части без форматирования и повторного разбора строки U.u.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
echo $date->format( 'Y-m-d H:i:s.u' ) . PHP_EOL; # 2023-11-14 22:13:20.125000
echo $changed->getMicrosecond() . PHP_EOL; # 500000
echo $date->getMicrosecond() . PHP_EOL; # 125000
```

**Важно:** Неизменяемый setter возвращает новый объект; `DateTime` меняет себя. float может потерять точность ещё до вызова. Unix-метка обозначает момент времени; при показе читателю явно выбирайте часовой пояс.

[Документация PHP: createFromTimestamp()](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [Документация PHP: setMicrosecond()](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

<a id="rounding-modes"></a>

## Явное правило округления через `RoundingMode`

**PHP 8.4+ | Ядро**

`RoundingMode` задаёт для `round()` именованное правило. HalfEven направляет точную половину к чётному результату, HalfAwayFromZero направляет её от нуля. TowardsZero отбрасывает лишние цифры по выбранной точности вместо округления до ближайшего.

```php
echo round( 2.5, 0, RoundingMode::HalfEven ) . PHP_EOL; # 2
echo round( 2.5, 0, RoundingMode::HalfAwayFromZero ) . PHP_EOL; # 3
echo round( -2.9, 0, RoundingMode::TowardsZero ) . PHP_EOL; # -2
```

**Важно:** Этот enum также используют `bcround()` и `BcMath\Number::round()`. Он не делает двоичную арифметику float точной десятичной. Выбирайте и представление числа, и момент округления, и правило.

[Документация PHP: RoundingMode](https://www.php.net/manual/en/enum.roundingmode.php)

<a id="bcmath-number"></a>

## Десятичная арифметика через `BcMath\Number`

**PHP 8.4+ | bcmath**

`BcMath\Number` является неизменяемым объектом десятичного числа произвольной точности с арифметическими операторами и явными методами. Передавайте десятичные строки или целые числа, чтобы значение не проходило через округление двоичного float.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero );
echo $total . PHP_EOL; # 59.99

$expected = new BcMath\Number( '59.99' );
var_dump( $total->compare($expected) === 0 ); # bool(true)
```

**Важно:** === для двух объектов Number проверяет тождество объектов, а не числовое равенство; `compare()` === 0 выражает равенство чисел. Класс не управляется bcmath.scale. Явно задавайте scale, в частности для деления, если этого требует прикладное правило.

[Документация PHP: BcMath\Number](https://www.php.net/manual/en/class.bcmath-number.php)

<a id="bcmath-rounding"></a>

## Округление десятичных чисел и частное с остатком

**PHP 8.4+ | bcmath**

`bcround()`, `bcfloor()` и `bcceil()` обрабатывают десятичные строки без ручного обрезания дробной части. `bcdivmod()` возвращает частное и остаток вместе и естественно сочетается с разбором массива.

```php
echo bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ) . PHP_EOL; # 20.00
echo bcfloor( '-1.2' ) . PHP_EOL; # -2
echo bcceil( '-1.2' ) . PHP_EOL; # -1

[$quotient, $remainder] = bcdivmod( '17', '5', 0 );
echo $quotient . ':' . $remainder . PHP_EOL; # 3:2
```

**Важно:** Результаты являются числовыми строками. floor и ceil направлены соответственно к минус и плюс бесконечности, что отличается от простого удаления дробной части отрицательного числа. Явно задавайте scale в `bcdivmod()`, чтобы не зависеть от глобальных настроек.

[Документация PHP: bcround()](https://www.php.net/manual/en/function.bcround.php) | [Документация PHP: bcdivmod()](https://www.php.net/manual/en/function.bcdivmod.php)
