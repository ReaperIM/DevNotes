# Runtime and performance

[English](../en/12-runtime-and-performance.md) | [Українська](../uk/12-runtime-and-performance.md) | [Русский](../ru/12-runtime-and-performance.md)

[Contents](../../README.md)

Runtime features affect lifetime, deployment and measurement. They are useful when their cost matches the application: none is a blanket promise that a newer syntax or enabled option makes a request faster.

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

**PHP 7.4+ | Core**

WeakReference stores a non-owning reference. Its get() method returns the object while another strong reference keeps it alive, then null after destruction.

```php
$object = new stdClass();
$reference = WeakReference::create( $object );
var_dump( $reference->get() === $object ); # bool(true)
unset( $object );
var_dump( $reference->get() ); # NULL
```

This is useful for observers and caches that should not own their targets. Saving get() into a normal variable creates a strong reference again. WeakReference is a lifetime tool, not a substitute for ordinary object ownership design. [PHP manual](https://www.php.net/manual/en/class.weakreference.php).

<a id="weak-map"></a>

## Cache data with weak object keys

**PHP 8.0+ | Core**

WeakMap attaches data to object keys without keeping the keys alive. When a key is destroyed, the corresponding map entry disappears.

```php
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = ['rendered' => 'ready'];
var_dump( count($cache) ); # int(1)
unset( $object );
var_dump( count($cache) ); # int(0)
```

Values are held normally. If a cached value strongly references its own key, that reference can keep the object alive. WeakMap helps derived per-object caches; it does not automatically bound unrelated cache data or solve reference cycles in the rest of the application. [PHP manual](https://www.php.net/manual/en/class.weakmap.php).

<a id="fibers"></a>

## Suspend and resume a call stack

**PHP 8.1+ | Core**

A Fiber has a resumable execution stack. A deeply called function can suspend it without forcing every caller to become a generator.

```php
$fiber = new Fiber( function(): int
{
	$value = Fiber::suspend( 'waiting' );
	return $value * 2;
} );

echo $fiber->start(), "\n"; # waiting
var_dump( $fiber->resume(21) ); # NULL: the fiber completed
var_dump( $fiber->getReturn() ); # int(42)
```

The resume argument becomes the result of suspend(). The final function return is read with getReturn(), not resume(). Fibers are cooperative, not CPU-parallel threads: blocking I/O still blocks unless paired with a scheduler and appropriate non-blocking operations. [PHP manual](https://www.php.net/manual/en/language.fibers.php).

<a id="preloading"></a>

## Preload stable application code

**PHP 7.4+ | OPcache**

Preloading loads selected functions and classes at PHP startup so later requests in persistent processes can use them without loading them again. It trades baseline memory and deployment flexibility for less repeated setup.

<!-- example: fragment -->
```php
# PHP code inside the configured preload script.
opcache_compile_file( __DIR__ . '/src/User.php' );
```

Configure opcache.preload with that script path; on Unix deployments opcache.preload_user can select the executing user. Preloading is not supported on Windows. Changed preloaded definitions require restarting the process. It is mainly useful for persistent workers, not a short one-off CLI invocation; preload only selected code with controlled startup behavior. [PHP manual](https://www.php.net/manual/en/opcache.preloading.php).

<a id="jit"></a>

## JIT and OPcache are different tools

**PHP 8.0+ JIT | OPcache**

OPcache caches compiled PHP bytecode. JIT can compile eligible execution paths into machine code. These are different optimizations with different costs.

```php
$status = opcache_get_status( false );
$jit = ($status === false) ? null : ($status['jit'] ?? null);
var_dump( $jit ); # Environment-dependent status, not a benchmark.
```

A disabled OPcache can return false. JIT availability and benefit depend on build, configuration and workload. Measure real hot paths: database, disk and network waiting do not disappear when PHP arithmetic becomes faster. For CLI experiments check opcache.enable_cli and the chosen JIT buffer/settings explicitly; do not infer activation from the PHP version. [PHP manual](https://www.php.net/manual/en/opcache.configuration.php).

<a id="ffi"></a>

## FFI for explicit C interoperability

**PHP 7.4+ | FFI**

FFI lets PHP describe C types and call functions from native libraries without writing a full PHP extension. It is useful when a needed C API has no appropriate wrapper.

<!-- example: fragment -->
```php
# Requires the FFI extension and a policy that permits this call.
$ffi = FFI::cdef();
$value = $ffi->new( 'int' );
$value->cdata = 42;
var_dump( $value->cdata ); # int(42)
```

This only allocates a C value; it does not load a library. Real use requires exact native signatures, platform/ABI knowledge and memory lifetime control. FFI can be restricted by ffi.enable. It is not a general shortcut to faster PHP, and unsafe native calls can crash the process. [PHP manual](https://www.php.net/manual/en/book.ffi.php).

<a id="ini-quantities"></a>

## Parse PHP ini quantities

**PHP 8.2+ | Core**

ini_parse_quantity() understands PHP shorthand multipliers K, M and G. It is useful when interpreting a known ini setting without keeping a custom suffix parser.

```php
var_dump( ini_parse_quantity('128M') ); # int(134217728)
var_dump( ini_parse_quantity('512K') ); # int(524288)
var_dump( ini_parse_quantity('-1') ); # int(-1)
```

Multipliers use powers of 1024. Preserve setting-specific sentinel values: -1 can mean unlimited for memory_limit, not a literal number of usable bytes. This parser can warn and interpret a numeric prefix for malformed text; it is not strict validation of arbitrary user input. [PHP manual](https://www.php.net/manual/en/function.ini-parse-quantity.php).

<a id="measure-memory"></a>

## Reset memory peaks for a measured stage

**PHP 8.2+ | Core**

memory_reset_peak_usage() resets the process peak counter to current usage. It makes a later stage measurable without retaining a peak from an earlier operation.

```php
memory_reset_peak_usage();
$before = memory_get_usage();
$buffer = str_repeat( 'x', 1_000_000 );
$peak = memory_get_peak_usage();
var_dump( $peak >= $before + strlen($buffer) ); # bool(true)
unset( $buffer );
```

The reported peak remains an absolute process value, not automatically the stage delta. Keep the same real_usage mode when comparing measurements. Allocator behavior and other retained data affect totals; resetting does not free memory. [PHP manual](https://www.php.net/manual/en/function.memory-reset-peak-usage.php).

<a id="ini-and-cli"></a>

## Environment defaults, log mode and batch lint

**PHP 8.2+ log mode / PHP 8.3+ ini fallback and batch lint**

PHP 8.3 accepts a fallback for an absent environment variable in php.ini. It also allows multiple filenames after php -l. PHP 8.2 added error_log_mode for file log permissions.

In php.ini use `max_execution_time = ${PHP_MAX_EXECUTION_TIME:-30}`. For a file log, `error_log_mode = 0640` selects Unix-style creation permissions subject to operating-system rules; Windows ACLs are separate. Run `php -l first.php second.php` for syntax only. It does not execute tests, resolve every runtime type or prove an extension is configured. CLI and FPM can load different ini files; inspect the actual SAPI configuration. [PHP manual](https://www.php.net/manual/en/configuration.file.php).

<a id="extension-capabilities"></a>

## Check build capabilities: AVIF in GD

**PHP 8.1+ AVIF API | gd**

A PHP version can expose an API while the extension or native codec is absent. GD AVIF support is an example where checking the actual build matters.

<!-- example: fragment -->
```php
# Requires GD; $source_path and $target_path are trusted local paths.
if( !(imagetypes() & IMG_AVIF) )
	throw new RuntimeException( 'AVIF support is unavailable' );

$image = imagecreatefromavif( $source_path );

if( $image === false )
	throw new RuntimeException( 'Cannot decode AVIF' );

imageavif( $image, $target_path, 80 );
```

AVIF read/write functions arrived in PHP 8.1, but GD must be built with the codec. Apply input dimension and resource limits before accepting arbitrary images. The same principle applies to curl, intl, sodium, PDO drivers and other bundled-but-optional modules: verify the running environment. [PHP manual](https://www.php.net/manual/en/function.imageavif.php).

<a id="monotonic-time"></a>

## Measure elapsed time with a monotonic clock

**PHP 7.3+ | standard**

hrtime() measures high-resolution time from an arbitrary origin. Its clock is monotonic: wall-clock adjustments do not change the measured timeline. Subtract two readings to measure a stage; use date/time APIs for calendar timestamps.

```php
$started_at = hrtime( true );
$digest = hash( 'sha256', str_repeat('x', 100000) );
$finished_at = hrtime( true );

if( $started_at === false || $finished_at === false )
	throw new RuntimeException( 'High-resolution clock is unavailable' );

$elapsed_ms = ($finished_at - $started_at) / 1000000;
var_dump( $elapsed_ms >= 0 ); # bool(true); the actual duration varies.
```

With true, the result is nanoseconds as an int on 64-bit platforms or a float on 32-bit platforms. Without true, the result is [seconds, nanoseconds]; failure returns false. A nanosecond unit does not promise nanosecond hardware precision, and the floating-point result on a 32-bit platform can lose integer-nanosecond precision for large readings. Compare the same workload over repeated measurements rather than treating one run as a benchmark. [PHP manual](https://www.php.net/manual/en/function.hrtime.php).
