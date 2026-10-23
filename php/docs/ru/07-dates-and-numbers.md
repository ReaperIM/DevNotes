# Даты и числа

[EN](../en/07-dates-and-numbers.md) | [UK](../uk/07-dates-and-numbers.md) | [RU](../ru/07-dates-and-numbers.md)

[Содержание](../../README.ru.md)

Проверяем даты так, чтобы 31 февраля не превратилось незаметно в март, считаем интервалы и выбираем округление под задачу. Для точных десятичных расчётов отдельно разбираем BCMath. Рядом пригодятся [числовые литералы](01-syntax-and-functions.md#numeric-literals) и [случайные значения](11-security-and-random.md).

- [Целочисленное деление через `intdiv()`](#integer-division)
- [Деление и степень с `INF` и `NaN`](#ieee-math)
- [Копируем дату в `DateTime` или `DateTimeImmutable`](#date-conversion)
- [Ловим ошибку в строке даты](#date-exceptions)
- [Проверка точной календарной даты](#strict-date-input)
- [Повторяющийся период из строки ISO 8601](#iso-period)
- [Дата из Unix-метки с микросекундами](#timestamp-microseconds)
- [Явное правило округления через `RoundingMode`](#rounding-modes)
- [Десятичная арифметика через `BcMath\Number`](#bcmath-number)
- [Округление десятичных чисел и частное с остатком](#bcmath-rounding)

<a id="integer-division"></a>

## Целочисленное деление через `intdiv()`

**PHP 7.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.intdiv.php)

Если нужны полные минуты, страницы или пачки элементов, берём `intdiv()`. В примере из 125 секунд получаем две полные минуты, а оставшиеся пять секунд считаем через `%`. Промежуточного `float` здесь нет.

```php
$minutes = intdiv( 125, 60 ); # 2
$remainder = 125 % 60; # 5
$negative = intdiv( -7, 3 ); # -2
```

Дробная часть просто отбрасывается в сторону нуля: поэтому `-7 / 3` через `intdiv()` даёт `-2`, а не `-3`. Делить на ноль нельзя, получим `DivisionByZeroError`. Сочетание `PHP_INT_MIN` и `-1` тоже не помещается в целое число и вызывает `ArithmeticError`.

<a id="ieee-math"></a>

## Деление и степень с `INF` и `NaN`

**PHP 8.0+ | Ядро** | [PHP](https://www.php.net/manual/en/function.fdiv.php) | [PHP](https://www.php.net/manual/en/function.fpow.php)

Иногда бесконечность в результате вычисления допустима, и исключение только мешает продолжить расчёт. `fdiv()` делит по правилам IEEE: `fdiv(1.0, 0.0)` даёт `INF`, а `fdiv(0.0, 0.0)` даёт `NaN`, то есть неопределённый результат. С PHP 8.4 для возведения в степень по тем же правилам есть `fpow()`.

```php
$infinity = fdiv( 1.0, 0.0 ); # INF
$undefined = is_nan( fdiv(0.0, 0.0) ); # true

# PHP 8.4+
$power = fpow( 0.0, -1.0 ); # INF
```

Если по задаче результат должен быть конечным числом, проверяем его через `is_finite()`. `NaN` не равен даже самому себе; для его проверки берём `is_nan()`.

<a id="date-conversion"></a>

## Копируем дату в `DateTime` или `DateTimeImmutable`

**PHP 8.0+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfrominterface.php) | [PHP](https://www.php.net/manual/en/datetime.createfromimmutable.php)

Когда функция принимает `DateTimeInterface`, ей могут передать и `DateTime`, и `DateTimeImmutable`. Чтобы получить нужный вариант без отдельных проверок, используем `createFromInterface()`. Время и часовой пояс сохранятся. Здесь создаём неизменяемую копию и уже от неё считаем завтрашний день.

```php
$source = new DateTime( '2026-01-01 12:00:00', new DateTimeZone('UTC') );
$copy = DateTimeImmutable::createFromInterface( $source );
$tomorrow = $copy->modify( '+1 day' );
$original_day = $source->format( 'Y-m-d' ); # 2026-01-01
$next_day = $tomorrow->format( 'Y-m-d' ); # 2026-01-02
```

У исходного `$source` остаётся 1 января, а в `$tomorrow` будет 2 января. Методы `DateTimeImmutable` возвращают новый объект, поэтому исходную дату мы не сдвигаем. Для преобразования именно из `DateTimeImmutable` в `DateTime` ещё с PHP 7.3 есть `DateTime::createFromImmutable()`.

<a id="date-exceptions"></a>

## Ловим ошибку в строке даты

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/class.datemalformedstringexception.php)

С PHP 8.3 ошибки в строке даты можно ловить отдельно через `DateMalformedStringException`. Это удобно, когда нужно обработать неверный ввод, не перехватывая заодно все остальные `Exception`. В примере строка не похожа на дату, поэтому попадаем в `catch`.

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

У расширения date есть отдельные исключения и для часовых поясов, интервалов и периодов. Но даже без исключения дата может оказаться не той, которую ввели: некоторые невозможные даты PHP исправляет сам. Для строгой проверки календарной даты нужен следующий пример.

<a id="strict-date-input"></a>

## Проверка точной календарной даты

**PHP 7.0+ | date | Для понимания** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php)

31 февраля не существует, но одного `createFromFormat()` для проверки недостаточно: PHP может перенести лишние дни в март и вернуть объект. Поэтому проверяем ошибки разбора, а потом форматируем дату обратно и сравниваем со вводом. Эти методы появились ещё до PHP 7.

```php
$input = '2026-02-31';
$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input, new DateTimeZone('UTC') );
# Забираем ошибки сразу, следующий разбор их перезапишет.
$errors = DateTimeImmutable::getLastErrors();
$valid = $date !== false
	&& ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
	&& $date->format('Y-m-d') === $input; # false
```

`!` обнуляет поля, которых нет в формате, чтобы они не подтянулись из текущего времени. `getLastErrors()` вызываем сразу после разбора: следующий разбор перезапишет диагностику. С PHP 8.2 при разборе без ошибок и предупреждений получаем `false`, а раньше всегда возвращался массив. Проверка учитывает оба варианта.

<a id="iso-period"></a>

## Повторяющийся период из строки ISO 8601

**PHP 8.3+ | date** | [PHP](https://www.php.net/manual/en/dateperiod.createfromiso8601string.php)

Если расписание хранится строкой ISO 8601, `DatePeriod::createFromISO8601String()` позволяет сразу перебрать его даты через `foreach`. В примере начинаем 1 января и два раза прибавляем по одному дню. На каждой итерации получаем `DateTimeImmutable`.

```php
$period = DatePeriod::createFromISO8601String( 'R2/2026-01-01T00:00:00Z/P1D' );
foreach( $period as $date )
	echo $date->format( 'Y-m-d' ) . PHP_EOL;
# 2026-01-01
# 2026-01-02
# 2026-01-03
```

Начальная дата тоже входит в результат, поэтому при `R2` дат будет три. Метод поддерживает только часть формата ISO 8601: `R0` и смещения часового пояса вне UTC не подходят. Неверная строка вызывает `DateMalformedPeriodStringException`.

<a id="timestamp-microseconds"></a>

## Дата из Unix-метки с микросекундами

**PHP 8.4+ | date** | [PHP](https://www.php.net/manual/en/datetimeimmutable.createfromtimestamp.php) | [PHP](https://www.php.net/manual/en/datetimeimmutable.setmicrosecond.php)

`createFromTimestamp()` создаёт дату сразу из Unix-метки, в том числе с дробной частью. Для микросекунд есть `getMicrosecond()` и `setMicrosecond()`, поэтому не нужно вытаскивать их через `format()` и собирать дату обратно. В примере меняем `.125000` на `.500000`.

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.125 );
$changed = $date->setMicrosecond( 500000 );
$timestamp = $date->format( 'Y-m-d H:i:s.u' ); # 2023-11-14 22:13:20.125000
$original = $date->getMicrosecond(); # 125000
$updated = $changed->getMicrosecond(); # 500000
```

У `DateTimeImmutable` метод `setMicrosecond()` возвращает новый объект, а у `DateTime` меняет текущий. Дробный `float` мог потерять точность ещё до передачи в метод. И сам момент времени, и часовой пояс его отображения тоже стоит различать: пояс для вывода задаём отдельно.

<a id="rounding-modes"></a>

## Явное правило округления через `RoundingMode`

**PHP 8.4+ | Ядро** | [PHP](https://www.php.net/manual/en/enum.roundingmode.php)

Когда число ровно посередине, результат зависит от выбранного правила округления. `HalfEven` округляет `2.5` до чётного `2`, а `HalfAwayFromZero` отводит половину от нуля, получаем `3`. Перечисление `RoundingMode` можно передавать в `round()`, `bcround()` и `BcMath\Number::round()`.

```php
$even = round( 2.5, 0, RoundingMode::HalfEven ); # 2
$away = round( 2.5, 0, RoundingMode::HalfAwayFromZero ); # 3
$truncated = round( -2.9, 0, RoundingMode::TowardsZero ); # -2
```

`TowardsZero` просто отбрасывает лишнюю дробную часть до нужной точности, поэтому из `-2.9` получается `-2`. Сам режим округления не исправляет погрешность двоичного `float`: способ хранения числа и правило округления выбираем отдельно.

<a id="bcmath-number"></a>

## Десятичная арифметика через `BcMath\Number`

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/class.bcmath-number.php)

Если нужны точные десятичные вычисления, можно взять `BcMath\Number`. Он поддерживает арифметические операторы и методы, а результат операции возвращает новым объектом. Значение передаём строкой или целым числом: если сначала создать `float`, его погрешность уже попадёт в расчёт.

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * 3)->round( 2, RoundingMode::HalfAwayFromZero ); # 59.99
$expected = new BcMath\Number( '59.99' );
# Сравниваем числа внутри объектов, а не сами объекты.
$equal = $total->compare( $expected ) === 0; # true
```

В примере умножаем цену на три и округляем общий результат до двух знаков. Сами объекты через `===` по числовому значению не сравнить: это проверка, один ли это объект. Для чисел используем `compare() === 0`. Настройку `bcmath.scale` класс не учитывает, поэтому нужное число знаков при делении задаём явно.

<a id="bcmath-rounding"></a>

## Округление десятичных чисел и частное с остатком

**PHP 8.4+ | bcmath** | [PHP](https://www.php.net/manual/en/function.bcround.php) | [PHP](https://www.php.net/manual/en/function.bcdivmod.php)

Для десятичных чисел в строках есть `bcround()`, `bcfloor()` и `bcceil()`. Они не переводят число во `float`, поэтому не вносят его погрешность. А если нужны и частное, и остаток, `bcdivmod()` возвращает их вместе: сразу раскладываем результат по двум переменным.

```php
$rounded = bcround( '19.995', 2, RoundingMode::HalfAwayFromZero ); # '20.00'
$floor = bcfloor( '-1.2' ); # '-2'
$ceiling = bcceil( '-1.2' ); # '-1'
[$quotient, $remainder] = bcdivmod( '17', '5', 0 ); # ['3', '2']
```

Результаты тоже приходят строками. Обратите внимание на отрицательные числа: `bcfloor('-1.2')` идёт вниз до `-2`, а `bcceil('-1.2')` вверх до `-1`. Это не обычное отбрасывание дробной части. Число знаков после запятой у `bcdivmod()` задаём явно, чтобы результат не зависел от общей настройки.
