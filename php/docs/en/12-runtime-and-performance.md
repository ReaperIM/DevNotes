# Runtime and performance

[EN](../en/12-runtime-and-performance.md) | [UK](../uk/12-runtime-and-performance.md) | [RU](../ru/12-runtime-and-performance.md)

[Contents](../../README.md)

Look at what keeps objects alive, how to pause a function and how to measure code costs. Features such as JIT and AVIF also need checks against the current environment; the PHP version alone may not be enough.

**In this chapter**

- [Observe an object without keeping it alive](#weak-reference)
- [A cache that does not keep its object keys alive](#weak-map)
- [Pause and resume a function with `Fiber`](#fibers)
- [Preload stable application code](#preloading)
- [What JIT and OPcache each do](#jit)
- [Work with C through `FFI`](#ffi)
- [Parse PHP ini quantities](#ini-quantities)
- [Measure the memory peak of one stage](#measure-memory)
- [Fallbacks in `php.ini`, log permissions and `php -l`](#ini-and-cli)
- [Check build capabilities: AVIF in GD](#extension-capabilities)
- [Measure elapsed time with `hrtime()`](#monotonic-time)

<a id="weak-reference"></a>

## Observe an object without keeping it alive

**PHP 7.4+ | Core** [PHP manual](https://www.php.net/manual/en/class.weakreference.php)

An ordinary reference keeps an object alive. Use `WeakReference` when you only need to observe whether the object still exists. `get()` returns it while another reference keeps it alive. After `unset()` removes that reference in the example, the next `get()` returns `null`.

```php
# The observer should not keep the object alive.
$object = new stdClass();
$reference = WeakReference::create( $object );
var_dump( $reference->get() === $object ); # bool(true)
unset( $object );
var_dump( $reference->get() ); # NULL
```

Saving the result of `get()` in an ordinary variable keeps the object alive again. The `WeakReference` is weak; the references retrieved from it are not.

<a id="weak-map"></a>

## A cache that does not keep its object keys alive

**PHP 8.0+ | Core** [PHP manual](https://www.php.net/manual/en/class.weakmap.php)

`WeakMap` lets us attach extra data, such as a computed result, to an object without keeping the object alive just for the cache. The object is the key. When nothing else needs it and it is destroyed, its entry disappears from the map automatically.

```php
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = ['rendered' => 'ready'];
var_dump( count($cache) ); # int(1)
unset( $object );
var_dump( count($cache) ); # int(0)
```

Here `unset()` takes the entry count from one to zero. Values in the map are still held by ordinary strong references. If a stored value refers back to its own key, it can keep that object alive and prevent the entry from disappearing.

<a id="fibers"></a>

## Pause and resume a function with `Fiber`

**PHP 8.1+ | Core** [PHP manual](https://www.php.net/manual/en/language.fibers.php)

`Fiber` lets us pause a function and later continue from the same point. Its nested call chain is preserved too, so suspension can happen deeper than the outer function. Here `start()` runs up to `suspend()` and passes `'waiting'` back to the caller.

```php
$fiber = new Fiber( function(): int
{
	$value = Fiber::suspend( 'waiting' );
	return $value * 2;
} );

echo $fiber->start(), "\n"; # waiting
var_dump( $fiber->resume(21) ); # NULL: the function has finished
var_dump( $fiber->getReturn() ); # int(42)
```

`resume(21)` then continues execution, making `21` the result of `suspend()` inside the function. The function returns `42`, but its final result is read with `getReturn()`, so `resume()` itself returns `null` here. Fibers do not switch work automatically: without a scheduler and non-blocking I/O, an ordinary blocking operation still blocks execution.

<a id="preloading"></a>

## Preload stable application code

**PHP 7.4+ | OPcache** [PHP manual](https://www.php.net/manual/en/opcache.preloading.php)

Functions and classes needed throughout an application can be loaded once when the process starts. Set `opcache.preload` to a PHP script that prepares the selected code. Here that script compiles a class file so its definitions are available to later requests.

<!-- example: fragment -->
```php
# This preload.php is selected by opcache.preload in php.ini.
opcache_compile_file( __DIR__ . '/src/User.php' );
```

Preloading is not supported on Windows. Replacing a preloaded file also does not update its definitions on its own: restart the process after changing that code.

<a id="jit"></a>

## What JIT and OPcache each do

**PHP 8.0+ JIT | OPcache** [PHP manual](https://www.php.net/manual/en/opcache.configuration.php)

OPcache stores compiled bytecode so PHP does not compile it again. JIT handles another step: it can turn suitable execution paths into machine code. The example reads the OPcache status and gets the JIT data if it is present.

```php
$status = opcache_get_status( false );
$jit = ($status === false) ? null : ($status['jit'] ?? null);
var_dump( $jit ); # The result depends on the current environment.
```

Whether JIT helps depends on the workload, and whether it is available depends on the build and settings. Its presence alone does not prove that particular code runs faster. For CLI measurements, check `opcache.enable_cli` and the JIT settings so the environment actually matches what you intend to measure.

<a id="ffi"></a>

## Work with C through `FFI`

**PHP 7.4+ | FFI** [PHP manual](https://www.php.net/manual/en/book.ffi.php)

`FFI` lets PHP describe and use C types and functions when direct access to native code is needed. This small example loads no library: it creates a C `int`, writes `42` into it and reads the value back.

<!-- example: fragment -->
```php
$ffi = FFI::cdef();
$value = $ffi->new( 'int' );
$value->cdata = 42;
var_dump( $value->cdata ); # int(42)
```

The `ffi.enable` setting may restrict access. With C, an incorrect function declaration or invalid memory access can crash the whole process; PHP's ordinary safety checks cannot protect that native operation.

<a id="ini-quantities"></a>

## Parse PHP ini quantities

**PHP 8.2+ | Core** [PHP manual](https://www.php.net/manual/en/function.ini-parse-quantity.php)

A plain cast of a `php.ini` value such as `128M` does not give its size in bytes. `ini_parse_quantity()` understands the `K`, `M` and `G` multipliers, with each step based on 1024. The example shows the byte counts for `128M` and `512K`.

```php
var_dump( ini_parse_quantity('128M') ); # int(134217728)
var_dump( ini_parse_quantity('512K') ); # int(524288)
var_dump( ini_parse_quantity('-1') ); # int(-1)
```

Keep special values meaningful for their setting: `-1` for `memory_limit`, for example, is not a byte count. Invalid text can emit a warning and still return a number from its leading part, so this is not strict validation of arbitrary input.

<a id="measure-memory"></a>

## Measure the memory peak of one stage

**PHP 8.2+ | Core** [PHP manual](https://www.php.net/manual/en/function.memory-reset-peak-usage.php)

An earlier expensive operation can leave a memory peak that hides the cost of the stage we want to measure. `memory_reset_peak_usage()` restarts peak tracking before that stage. Here we reset it, allocate a string and calculate the growth above the starting usage.

```php
# Earlier work should not set the peak for this stage.
memory_reset_peak_usage();
$before = memory_get_usage();
$buffer = str_repeat( 'x', 1_000_000 );
$peak_growth = memory_get_peak_usage() - $before;
var_dump( $peak_growth ); # Peak growth in bytes above the start of this stage.
unset( $buffer );
```

Resetting the peak does not free memory or reset the process's actual usage. `memory_get_peak_usage()` still returns an absolute byte count, so the example subtracts `$before` to get the growth.

<a id="ini-and-cli"></a>

## Fallbacks in `php.ini`, log permissions and `php -l`

**PHP 8.2+ log mode / PHP 8.3+ ini fallback and batch lint** [PHP manual](https://www.php.net/manual/en/configuration.file.php)

Since PHP 8.3, `php.ini` can specify a fallback for an environment variable: here a missing `PHP_MAX_EXECUTION_TIME` gives `30`. The same version lets `php -l` check several files in one call. `error_log_mode`, available since PHP 8.2, sets permissions when creating the log file.

```ini
max_execution_time = ${PHP_MAX_EXECUTION_TIME:-30}
error_log_mode = 0640
```

```sh
php -l first.php second.php
```

The example's `0640` is a Unix permission mode. Windows file access is configured through ACLs separately; this setting does not replace them.

<a id="extension-capabilities"></a>

## Check build capabilities: AVIF in GD

**PHP 8.1+ AVIF API | gd** [PHP manual](https://www.php.net/manual/en/function.imageavif.php)

GD has AVIF functions from PHP 8.1, but a particular GD build may lack the codec. Check `imagetypes() & IMG_AVIF` before reading the file. The example then decodes an image and saves it to another AVIF file.

<!-- example: fragment -->
```php
# Both paths are local and already checked, not taken straight from request input.
if( !(imagetypes() & IMG_AVIF) )
	throw new RuntimeException( 'AVIF support is unavailable' );

$image = imagecreatefromavif( $source_path );

if( $image === false )
	throw new RuntimeException( 'Cannot decode AVIF' );

imageavif( $image, $target_path, 80 );
```

The PHP version and the existence of a function do not guarantee support for the format itself. Checking the current build helps distinguish a missing codec from a problem reading one particular file.

<a id="monotonic-time"></a>

## Measure elapsed time with `hrtime()`

**PHP 7.3+ | standard** [PHP manual](https://www.php.net/manual/en/function.hrtime.php)

Use `hrtime()` for elapsed time: its clock moves monotonically and is not affected by changes to calendar time. Read it before and after the work, subtract the values and convert nanoseconds to milliseconds. This measures duration, not a timestamp for a log.

```php
$started_at = hrtime( true );
$digest = hash( 'sha256', str_repeat('x', 100000) );
$finished_at = hrtime( true );

if( $started_at === false || $finished_at === false )
	throw new RuntimeException( 'Clock unavailable' );

$elapsed_ms = ($finished_at - $started_at) / 1000000;
var_dump( $elapsed_ms ); # Elapsed milliseconds; the number varies on each run.
```

With `true`, the result is `int` on 64-bit platforms and `float` on 32-bit platforms. Nanosecond units do not mean the hardware can actually resolve every nanosecond. Check the readings for `false`, as the example does.
