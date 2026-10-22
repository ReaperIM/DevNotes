# Выполнение и производительность

[EN](../en/12-runtime-and-performance.md) | [UK](../uk/12-runtime-and-performance.md) | [RU](../ru/12-runtime-and-performance.md)

[Содержание](../../README.ru.md)

**В этой главе**

- [Наблюдение за объектом без удержания в памяти](#weak-reference)
- [Кеш данных со слабыми ключами-объектами](#weak-map)
- [Приостановка и возобновление стека вызовов](#fibers)
- [Предварительная загрузка постоянного кода](#preloading)
- [JIT и OPcache являются разными инструментами](#jit)
- [`FFI` для явного взаимодействия с C](#ffi)
- [Разбор размеров из `php.ini`](#ini-quantities)
- [Сброс пика памяти для отдельного этапа](#measure-memory)
- [Значения среды по умолчанию, права журнала и пакетный lint](#ini-and-cli)
- [Проверка возможностей сборки: AVIF в GD](#extension-capabilities)
- [Измерение длительности монотонными часами](#monotonic-time)

<a id="weak-reference"></a>

## Наблюдение за объектом без удержания в памяти

**PHP 7.4+ | Core** [Документация PHP](https://www.php.net/manual/en/class.weakreference.php)

`WeakReference` хранит ссылку, которая не удерживает объект в памяти. `get()` возвращает объект, пока другая сильная ссылка поддерживает его жизнь, и `null` после уничтожения.

```php
$object = new stdClass();
$reference = WeakReference::create( $object );
var_dump( $reference->get() === $object ); # bool(true)
unset( $object );
var_dump( $reference->get() ); # NULL
```

Сохранение `get()` в обычную переменную снова создаёт сильную ссылку.

<a id="weak-map"></a>

## Кеш данных со слабыми ключами-объектами

**PHP 8.0+ | Core** [Документация PHP](https://www.php.net/manual/en/class.weakmap.php)

`WeakMap` связывает данные с ключами-объектами, не удерживая эти ключи в памяти. Когда ключ уничтожается, его запись в карте исчезает.

```php
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = ['rendered' => 'ready'];
var_dump( count($cache) ); # int(1)
unset( $object );
var_dump( count($cache) ); # int(0)
```

Значения удерживаются сильными ссылками, поэтому ссылка значения на собственный ключ может сохранить ключ в памяти.

<a id="fibers"></a>

## Приостановка и возобновление стека вызовов

**PHP 8.1+ | Core** [Документация PHP](https://www.php.net/manual/en/language.fibers.php)

`Fiber` сохраняет стек вызовов, который можно возобновить, поэтому функция глубоко в цепочке может его приостановить. `resume()` задаёт результат `suspend()`, а `getReturn()` читает окончательный результат функции.

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

Fibers работают кооперативно: блокирующий ввод-вывод по-прежнему блокирует без планировщика и неблокирующих операций.

<a id="preloading"></a>

## Предварительная загрузка постоянного кода

**PHP 7.4+ | OPcache** [Документация PHP](https://www.php.net/manual/en/opcache.preloading.php)

В `php.ini` задайте в `opcache.preload` путь к стартовому скрипту, который один раз загрузит выбранные функции и классы для следующих запросов постоянных процессов.

<!-- example: fragment -->
```php
# preload.php, selected by opcache.preload in php.ini.
opcache_compile_file( __DIR__ . '/src/User.php' );
```

Windows не поддерживает preloading; изменение предварительно загруженных определений требует перезапуска процесса.

<a id="jit"></a>

## JIT и OPcache являются разными инструментами

**PHP 8.0+ JIT | OPcache** [Документация PHP](https://www.php.net/manual/en/opcache.configuration.php)

OPcache кеширует скомпилированный байткод; JIT может компилировать подходящие пути выполнения в машинный код.

```php
$status = opcache_get_status( false );
$jit = ($status === false) ? null : ($status['jit'] ?? null);
var_dump( $jit ); # Environment-dependent.
```

Наличие и польза зависят от сборки, настроек и нагрузки; для CLI-измерений проверяйте `opcache.enable_cli` и параметры JIT.

<a id="ffi"></a>

## `FFI` для явного взаимодействия с C

**PHP 7.4+ | FFI** [Документация PHP](https://www.php.net/manual/en/book.ffi.php)

`FFI` описывает нативные типы и функции C; этот пример выделяет целое C без загрузки библиотеки.

<!-- example: fragment -->
```php
$ffi = FFI::cdef();
$value = $ffi->new( 'int' );
$value->cdata = 42;
var_dump( $value->cdata ); # int(42)
```

`ffi.enable` может ограничивать вызовы, а неправильные нативные сигнатуры или работа с памятью могут аварийно завершить процесс.

<a id="ini-quantities"></a>

## Разбор размеров из `php.ini`

**PHP 8.2+ | Core** [Документация PHP](https://www.php.net/manual/en/function.ini-parse-quantity.php)

`ini_parse_quantity()` разбирает размеры `php.ini` с множителями `K`, `M` и `G` на основе 1024.

```php
var_dump( ini_parse_quantity('128M') ); # int(134217728)
var_dump( ini_parse_quantity('512K') ); # int(524288)
var_dump( ini_parse_quantity('-1') ); # int(-1)
```

Сохраняйте специальные значения параметров вроде `memory_limit=-1`; некорректный текст может вызвать предупреждение и дать числовое начало.

<a id="measure-memory"></a>

## Сброс пика памяти для отдельного этапа

**PHP 8.2+ | Core** [Документация PHP](https://www.php.net/manual/en/function.memory-reset-peak-usage.php)

Сбросьте пик памяти перед измерением этапа, чтобы пик предыдущей операции не скрывал его расход памяти.

```php
memory_reset_peak_usage();
$before = memory_get_usage();
$buffer = str_repeat( 'x', 1_000_000 );
$peak_growth = memory_get_peak_usage() - $before;
var_dump( $peak_growth ); # Peak growth in bytes.
unset( $buffer );
```

Пик остаётся абсолютным значением процесса, а сброс не освобождает память.

<a id="ini-and-cli"></a>

## Значения среды по умолчанию, права журнала и пакетный lint

**PHP 8.2+ log mode / PHP 8.3+ ini fallback and batch lint** [Документация PHP](https://www.php.net/manual/en/configuration.file.php)

PHP 8.3 добавляет запасные значения переменных среды в `php.ini` и `php -l` для нескольких файлов; PHP 8.2 добавляет `error_log_mode`.

```ini
max_execution_time = ${PHP_MAX_EXECUTION_TIME:-30}
error_log_mode = 0640
```

```sh
php -l first.php second.php
```

`error_log_mode` задаёт Unix-права создания; ACL Windows настраиваются отдельно.

<a id="extension-capabilities"></a>

## Проверка возможностей сборки: AVIF в GD

**PHP 8.1+ AVIF API | gd** [Документация PHP](https://www.php.net/manual/en/function.imageavif.php)

GD имеет функции чтения и записи AVIF с PHP 8.1; `imagetypes()` проверяет поддержку кодека в текущей сборке.

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

Наличие PHP API не гарантирует, что GD собран с кодеком.

<a id="monotonic-time"></a>

## Измерение длительности монотонными часами

**PHP 7.3+ | standard** [Документация PHP](https://www.php.net/manual/en/function.hrtime.php)

`hrtime()` измеряет длительность монотонными часами, независимыми от изменений календарного времени.

```php
$started_at = hrtime( true );
$digest = hash( 'sha256', str_repeat('x', 100000) );
$finished_at = hrtime( true );

if( $started_at === false || $finished_at === false )
	throw new RuntimeException( 'Clock unavailable' );

$elapsed_ms = ($finished_at - $started_at) / 1000000;
var_dump( $elapsed_ms ); # Elapsed milliseconds; varies each run.
```

С `true` наносекунды имеют тип `int` на 64-битных и `float` на 32-битных платформах; единица не гарантирует наносекундной точности оборудования.
