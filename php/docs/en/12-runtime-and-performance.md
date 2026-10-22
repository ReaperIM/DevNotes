# Runtime and performance

[English](../en/12-runtime-and-performance.md) | [Українська](../uk/12-runtime-and-performance.md) | [Русский](../ru/12-runtime-and-performance.md)

[Contents](../../README.md)

**In this chapter**

- [Observe an object without keeping it alive](#weak-reference)
- [Cache data with weak object keys](#weak-map)
- [Suspend and resume a call stack](#fibers)
- [Preload stable application code](#preloading)
- [JIT and OPcache are different tools](#jit)
- [FFI for explicit C interoperability](#ffi)
- [Parse PHP ini quantities](#ini-quantities)
- [Reset memory peaks for a measured stage](#measure-memory)
- [Environment defaults, log mode and batch lint](#ini-and-cli)
- [Check build capabilities: AVIF in GD](#extension-capabilities)
- [Measure elapsed time with a monotonic clock](#monotonic-time)

<a id="weak-reference"></a>

## Observe an object without keeping it alive

**PHP 7.4+ | Core** [PHP manual](https://www.php.net/manual/en/class.weakreference.php)

`WeakReference` stores a reference without keeping its object alive. `get()` returns the object while another strong reference keeps it alive, then `null` after destruction.

```php
$object = new stdClass();
$reference = WeakReference::create( $object );
var_dump( $reference->get() === $object ); # bool(true)
unset( $object );
var_dump( $reference->get() ); # NULL
```

Saving `get()` into a normal variable creates a strong reference again.

<a id="weak-map"></a>

## Cache data with weak object keys

**PHP 8.0+ | Core** [PHP manual](https://www.php.net/manual/en/class.weakmap.php)

`WeakMap` attaches data to object keys without keeping those keys alive. When a key is destroyed, its map entry disappears.

```php
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = ['rendered' => 'ready'];
var_dump( count($cache) ); # int(1)
unset( $object );
var_dump( count($cache) ); # int(0)
```

Values are held strongly, so a value referencing its own key can keep the key alive.

<a id="fibers"></a>

## Suspend and resume a call stack

**PHP 8.1+ | Core** [PHP manual](https://www.php.net/manual/en/language.fibers.php)

A Fiber preserves a resumable call stack, allowing a deeply called function to suspend it. `resume()` supplies the value returned by `suspend()`, while `getReturn()` reads the final function return.

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

Fibers are cooperative: blocking I/O still blocks without a scheduler and non-blocking operations.

<a id="preloading"></a>

## Preload stable application code

**PHP 7.4+ | OPcache** [PHP manual](https://www.php.net/manual/en/opcache.preloading.php)

Point `opcache.preload` in php.ini to a startup script that loads selected functions and classes once for later requests in persistent processes.

<!-- example: fragment -->
```php
# preload.php, selected by opcache.preload in php.ini.
opcache_compile_file( __DIR__ . '/src/User.php' );
```

Preloading is unsupported on Windows; changing preloaded definitions requires restarting the process.

<a id="jit"></a>

## JIT and OPcache are different tools

**PHP 8.0+ JIT | OPcache** [PHP manual](https://www.php.net/manual/en/opcache.configuration.php)

OPcache caches compiled bytecode; JIT can compile eligible execution paths into machine code.

```php
$status = opcache_get_status( false );
$jit = ($status === false) ? null : ($status['jit'] ?? null);
var_dump( $jit ); # Environment-dependent.
```

Availability and benefit depend on build, settings and workload; inspect `opcache.enable_cli` and JIT settings for CLI measurements.

<a id="ffi"></a>

## FFI for explicit C interoperability

**PHP 7.4+ | FFI** [PHP manual](https://www.php.net/manual/en/book.ffi.php)

FFI describes native C types and functions; this example allocates a C integer without loading a library.

<!-- example: fragment -->
```php
$ffi = FFI::cdef();
$value = $ffi->new( 'int' );
$value->cdata = 42;
var_dump( $value->cdata ); # int(42)
```

`ffi.enable` can restrict calls, and incorrect native signatures or memory handling can crash the process.

<a id="ini-quantities"></a>

## Parse PHP ini quantities

**PHP 8.2+ | Core** [PHP manual](https://www.php.net/manual/en/function.ini-parse-quantity.php)

`ini_parse_quantity()` parses PHP ini quantities with K, M and G multipliers based on 1024.

```php
var_dump( ini_parse_quantity('128M') ); # int(134217728)
var_dump( ini_parse_quantity('512K') ); # int(524288)
var_dump( ini_parse_quantity('-1') ); # int(-1)
```

Preserve setting-specific sentinels such as `memory_limit=-1`; malformed text can warn and yield a numeric prefix.

<a id="measure-memory"></a>

## Reset memory peaks for a measured stage

**PHP 8.2+ | Core** [PHP manual](https://www.php.net/manual/en/function.memory-reset-peak-usage.php)

Reset the memory peak before measuring a stage so an earlier operation's peak does not hide its allocation.

```php
memory_reset_peak_usage();
$before = memory_get_usage();
$buffer = str_repeat( 'x', 1_000_000 );
$peak_growth = memory_get_peak_usage() - $before;
var_dump( $peak_growth ); # Peak growth in bytes.
unset( $buffer );
```

The peak is still an absolute process value, and resetting does not free memory.

<a id="ini-and-cli"></a>

## Environment defaults, log mode and batch lint

**PHP 8.2+ log mode / PHP 8.3+ ini fallback and batch lint** [PHP manual](https://www.php.net/manual/en/configuration.file.php)

PHP 8.3 adds php.ini environment fallbacks and multi-file `php -l`; PHP 8.2 adds `error_log_mode`.

```ini
max_execution_time = ${PHP_MAX_EXECUTION_TIME:-30}
error_log_mode = 0640
```

```sh
php -l first.php second.php
```

`error_log_mode` uses Unix-style creation permissions; Windows ACLs are separate.

<a id="extension-capabilities"></a>

## Check build capabilities: AVIF in GD

**PHP 8.1+ AVIF API | gd** [PHP manual](https://www.php.net/manual/en/function.imageavif.php)

GD exposes AVIF read/write functions from PHP 8.1; `imagetypes()` checks codec support in the running build.

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

An available PHP API does not guarantee that GD was built with the codec.

<a id="monotonic-time"></a>

## Measure elapsed time with a monotonic clock

**PHP 7.3+ | standard** [PHP manual](https://www.php.net/manual/en/function.hrtime.php)

`hrtime()` measures elapsed time on a monotonic clock unaffected by wall-clock adjustments.

```php
$started_at = hrtime( true );
$digest = hash( 'sha256', str_repeat('x', 100000) );
$finished_at = hrtime( true );

if( $started_at === false || $finished_at === false )
	throw new RuntimeException( 'Clock unavailable' );

$elapsed_ms = ($finished_at - $started_at) / 1000000;
var_dump( $elapsed_ms ); # Elapsed milliseconds; varies each run.
```

With `true`, nanoseconds use int on 64-bit and float on 32-bit platforms; the unit does not guarantee nanosecond hardware precision.
