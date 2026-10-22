# Виконання й продуктивність

[EN](../en/12-runtime-and-performance.md) | [UK](../uk/12-runtime-and-performance.md) | [RU](../ru/12-runtime-and-performance.md)

[Зміст](../../README.uk.md)

**У цьому розділі**

- [Спостереження за об’єктом без утримання в пам’яті](#weak-reference)
- [Кеш даних зі слабкими ключами-об’єктами](#weak-map)
- [Призупинення й відновлення стека викликів](#fibers)
- [Попереднє завантаження сталого коду](#preloading)
- [JIT та OPcache є різними інструментами](#jit)
- [`FFI` для явної взаємодії з C](#ffi)
- [Розбір розмірів із `php.ini`](#ini-quantities)
- [Скидання піку пам’яті для окремого етапу](#measure-memory)
- [Типові значення середовища, права журналу й пакетний lint](#ini-and-cli)
- [Перевірка можливостей збірки: AVIF у GD](#extension-capabilities)
- [Вимірювання тривалості монотонним годинником](#monotonic-time)

<a id="weak-reference"></a>

## Спостереження за об’єктом без утримання в пам’яті

**PHP 7.4+ | Core** [Документація PHP](https://www.php.net/manual/en/class.weakreference.php)

`WeakReference` зберігає посилання, яке не утримує об’єкт у пам’яті. `get()` повертає об’єкт, поки інше сильне посилання підтримує його життя, і `null` після знищення.

```php
$object = new stdClass();
$reference = WeakReference::create( $object );
var_dump( $reference->get() === $object ); # bool(true)
unset( $object );
var_dump( $reference->get() ); # NULL
```

Збереження `get()` у звичайну змінну знову створює сильне посилання.

<a id="weak-map"></a>

## Кеш даних зі слабкими ключами-об’єктами

**PHP 8.0+ | Core** [Документація PHP](https://www.php.net/manual/en/class.weakmap.php)

`WeakMap` пов’язує дані з ключами-об’єктами, не утримуючи ці ключі в пам’яті. Коли ключ знищується, його запис у мапі зникає.

```php
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = ['rendered' => 'ready'];
var_dump( count($cache) ); # int(1)
unset( $object );
var_dump( count($cache) ); # int(0)
```

Значення утримуються сильними посиланнями, тому посилання значення на власний ключ може зберегти ключ у пам'яті.

<a id="fibers"></a>

## Призупинення й відновлення стека викликів

**PHP 8.1+ | Core** [Документація PHP](https://www.php.net/manual/en/language.fibers.php)

`Fiber` зберігає стек викликів, який можна відновити, тож функція глибоко в ланцюжку може його призупинити. `resume()` задає результат `suspend()`, а `getReturn()` читає остаточний результат функції.

```php
$fiber = new Fiber( function(): int
{
	$value = Fiber::suspend( 'waiting' );
	return $value * 2;
} );

echo $fiber->start(), "\n"; # waiting
var_dump( $fiber->resume(21) ); # NULL: completed
var_dump( $fiber->getReturn() ); # int(42)
```

Fibers працюють кооперативно: блокувальне введення-виведення досі блокує без планувальника та неблокувальних операцій.

<a id="preloading"></a>

## Попереднє завантаження сталого коду

**PHP 7.4+ | OPcache** [Документація PHP](https://www.php.net/manual/en/opcache.preloading.php)

У `php.ini` задайте в `opcache.preload` шлях до стартового скрипта, який один раз завантажить вибрані функції та класи для наступних запитів постійних процесів.

<!-- example: fragment -->
```php
# preload.php, selected by opcache.preload in php.ini.
opcache_compile_file( __DIR__ . '/src/User.php' );
```

Windows не підтримує preloading; зміна попередньо завантажених визначень потребує перезапуску процесу.

<a id="jit"></a>

## JIT та OPcache є різними інструментами

**PHP 8.0+ JIT | OPcache** [Документація PHP](https://www.php.net/manual/en/opcache.configuration.php)

OPcache кешує скомпільований байткод; JIT може компілювати відповідні шляхи виконання в машинний код.

```php
$status = opcache_get_status( false );
$jit = ($status === false) ? null : ($status['jit'] ?? null);
var_dump( $jit ); # Environment-dependent.
```

Наявність і користь залежать від збірки, параметрів і навантаження; для CLI-вимірювань перевіряйте `opcache.enable_cli` та параметри JIT.

<a id="ffi"></a>

## `FFI` для явної взаємодії з C

**PHP 7.4+ | FFI** [Документація PHP](https://www.php.net/manual/en/book.ffi.php)

`FFI` описує нативні типи та функції C; цей приклад виділяє ціле C без завантаження бібліотеки.

<!-- example: fragment -->
```php
$ffi = FFI::cdef();
$value = $ffi->new( 'int' );
$value->cdata = 42;
var_dump( $value->cdata ); # int(42)
```

`ffi.enable` може обмежувати виклики, а неправильні нативні сигнатури чи робота з пам'яттю можуть аварійно завершити процес.

<a id="ini-quantities"></a>

## Розбір розмірів із `php.ini`

**PHP 8.2+ | Core** [Документація PHP](https://www.php.net/manual/en/function.ini-parse-quantity.php)

`ini_parse_quantity()` розбирає розміри `php.ini` з множниками `K`, `M` і `G` на основі 1024.

```php
var_dump( ini_parse_quantity('128M') ); # int(134217728)
var_dump( ini_parse_quantity('512K') ); # int(524288)
var_dump( ini_parse_quantity('-1') ); # int(-1)
```

Зберігайте спеціальні значення параметрів на зразок `memory_limit=-1`; некоректний текст може викликати попередження та дати числовий початок.

<a id="measure-memory"></a>

## Скидання піку пам’яті для окремого етапу

**PHP 8.2+ | Core** [Документація PHP](https://www.php.net/manual/en/function.memory-reset-peak-usage.php)

Скиньте пік пам'яті перед вимірюванням етапу, щоб пік попередньої операції не приховував його витрати пам'яті.

```php
memory_reset_peak_usage();
$before = memory_get_usage();
$buffer = str_repeat( 'x', 1_000_000 );
$peak_growth = memory_get_peak_usage() - $before;
var_dump( $peak_growth ); # Peak growth in bytes.
unset( $buffer );
```

Пік залишається абсолютним значенням процесу, а скидання не звільняє пам'ять.

<a id="ini-and-cli"></a>

## Типові значення середовища, права журналу й пакетний lint

**PHP 8.2+ log mode / PHP 8.3+ ini fallback and batch lint** [Документація PHP](https://www.php.net/manual/en/configuration.file.php)

PHP 8.3 додає запасні значення змінних середовища в `php.ini` та `php -l` для кількох файлів; PHP 8.2 додає `error_log_mode`.

```ini
max_execution_time = ${PHP_MAX_EXECUTION_TIME:-30}
error_log_mode = 0640
```

```sh
php -l first.php second.php
```

`error_log_mode` задає Unix-права створення; ACL Windows налаштовуються окремо.

<a id="extension-capabilities"></a>

## Перевірка можливостей збірки: AVIF у GD

**PHP 8.1+ AVIF API | gd** [Документація PHP](https://www.php.net/manual/en/function.imageavif.php)

GD має функції читання та запису AVIF від PHP 8.1; `imagetypes()` перевіряє підтримку кодека в поточній збірці.

<!-- example: fragment -->
```php
# $source_path and $target_path are trusted local paths.
if( !(imagetypes() & IMG_AVIF) )
	throw new RuntimeException( 'AVIF support is unavailable' );

$image = imagecreatefromavif( $source_path );

if( $image === false )
	throw new RuntimeException( 'Cannot decode AVIF' );

imageavif( $image, $target_path, 80 );
```

Наявність PHP API не гарантує, що GD зібрано з кодеком.

<a id="monotonic-time"></a>

## Вимірювання тривалості монотонним годинником

**PHP 7.3+ | standard** [Документація PHP](https://www.php.net/manual/en/function.hrtime.php)

`hrtime()` вимірює тривалість монотонними годинниками, незалежними від змін календарного часу.

```php
$started_at = hrtime( true );
$digest = hash( 'sha256', str_repeat('x', 100000) );
$finished_at = hrtime( true );

if( $started_at === false || $finished_at === false )
	throw new RuntimeException( 'Clock unavailable' );

$elapsed_ms = ($finished_at - $started_at) / 1000000;
var_dump( $elapsed_ms ); # Elapsed milliseconds; varies each run.
```

З `true` наносекунди мають тип `int` на 64-бітних і `float` на 32-бітних платформах; одиниця не гарантує наносекундної точності обладнання.
