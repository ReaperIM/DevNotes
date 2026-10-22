# Выполнение и производительность

[English](../en/12-runtime-and-performance.md) | [Українська](../uk/12-runtime-and-performance.md) | [Русский](../ru/12-runtime-and-performance.md)

[Содержание](../../README.ru.md)

Возможности среды выполнения влияют на время жизни, развёртывание и измерения. Они полезны, когда их стоимость соответствует приложению: новый синтаксис или включённый параметр сам по себе не гарантирует более быстрый запрос.

**В этой главе**

- [Наблюдение за объектом без удержания в памяти](#weak-reference)
- [Кеш данных со слабыми ключами-объектами](#weak-map)
- [Приостановка и возобновление стека вызовов](#fibers)
- [Предварительная загрузка постоянного кода](#preloading)
- [JIT и OPcache являются разными инструментами](#jit)
- [FFI для явного взаимодействия с C](#ffi)
- [Разбор размеров из php.ini](#ini-quantities)
- [Сброс пика памяти для отдельного этапа](#measure-memory)
- [Значения среды по умолчанию, права журнала и пакетный lint](#ini-and-cli)
- [Проверка возможностей сборки: AVIF в GD](#extension-capabilities)
- [Измерение длительности монотонными часами](#monotonic-time)

<a id="weak-reference"></a>

## Наблюдение за объектом без удержания в памяти

**PHP 7.4+ | Core**

WeakReference хранит ссылку, которая не удерживает объект. get() возвращает объект, пока другая сильная ссылка поддерживает его жизнь, и null после уничтожения.

```php
$object = new stdClass();
$reference = WeakReference::create( $object );
var_dump( $reference->get() === $object ); # bool(true)
unset( $object );
var_dump( $reference->get() ); # NULL
```

Это полезно для наблюдателей и кешей, которые не должны владеть целевым объектом. Сохранение get() в обычную переменную снова создаёт сильную ссылку. WeakReference управляет временем жизни, а не заменяет обычное проектирование владения объектами. [Документация PHP](https://www.php.net/manual/en/class.weakreference.php).

<a id="weak-map"></a>

## Кеш данных со слабыми ключами-объектами

**PHP 8.0+ | Core**

WeakMap связывает данные с ключами-объектами, не удерживая сами ключи в памяти. Когда ключ уничтожается, соответствующая запись исчезает.

```php
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = ['rendered' => 'ready'];
var_dump( count($cache) ); # int(1)
unset( $object );
var_dump( count($cache) ); # int(0)
```

Значения удерживаются обычным способом. Если значение кеша сильно ссылается на собственный ключ, это может сохранить объект. WeakMap помогает кешам производных данных объекта; он не ограничивает посторонние данные кеша и не решает циклы ссылок остального приложения. [Документация PHP](https://www.php.net/manual/en/class.weakmap.php).

<a id="fibers"></a>

## Приостановка и возобновление стека вызовов

**PHP 8.1+ | Core**

Fiber имеет стек выполнения, который можно возобновить. Функция глубоко в цепочке может приостановить его, не заставляя каждого вызывающего становиться генератором.

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

Аргумент resume становится результатом suspend(). Окончательный результат функции читают через getReturn(), а не resume(). Fibers работают кооперативно, это не параллельные CPU-потоки: блокирующий ввод-вывод по-прежнему блокирует без планировщика и подходящих неблокирующих операций. [Документация PHP](https://www.php.net/manual/en/language.fibers.php).

<a id="preloading"></a>

## Предварительная загрузка постоянного кода

**PHP 7.4+ | OPcache**

Предварительная загрузка читает выбранные функции и классы при старте PHP, чтобы следующие запросы постоянного процесса пользовались ими без повторной загрузки. Это обмен базовой памяти и гибкости развёртывания на меньшую повторную работу.

<!-- example: fragment -->
```php
# PHP code inside the configured preload script.
opcache_compile_file( __DIR__ . '/src/User.php' );
```

Задайте путь к этому скрипту в opcache.preload; в Unix-развёртывании opcache.preload_user может выбрать пользователя запуска. Windows не поддерживает preloading. Изменение предварительно загруженных определений требует перезапуска процесса. Это прежде всего для постоянных процессов, а не разового CLI-вызова; выбирайте код с контролируемым поведением при старте. [Документация PHP](https://www.php.net/manual/en/opcache.preloading.php).

<a id="jit"></a>

## JIT и OPcache являются разными инструментами

**PHP 8.0+ JIT | OPcache**

OPcache кеширует скомпилированный байткод PHP. JIT может компилировать подходящие пути выполнения в машинный код. Это разные оптимизации с разной стоимостью.

```php
$status = opcache_get_status( false );
$jit = ($status === false) ? null : ($status['jit'] ?? null);
var_dump( $jit ); # Environment-dependent status, not a benchmark.
```

Отключённый OPcache может вернуть false. Наличие и польза JIT зависят от сборки, параметров и нагрузки. Измеряйте настоящие горячие участки: ожидание базы, диска и сети не исчезает от более быстрой арифметики PHP. Для CLI-проверок явно проверяйте opcache.enable_cli и буфер и параметры JIT; не определяйте активность по номеру PHP. [Документация PHP](https://www.php.net/manual/en/opcache.configuration.php).

<a id="ffi"></a>

## FFI для явного взаимодействия с C

**PHP 7.4+ | FFI**

FFI позволяет PHP описывать типы C и вызывать функции нативных библиотек без написания полного расширения PHP. Это полезно, когда нужный C API не имеет подходящей обёртки.

<!-- example: fragment -->
```php
# Requires the FFI extension and a policy that permits this call.
$ffi = FFI::cdef();
$value = $ffi->new( 'int' );
$value->cdata = 42;
var_dump( $value->cdata ); # int(42)
```

Здесь только выделяется C-значение, библиотека не загружается. Настоящее использование требует точных нативных сигнатур, знания платформы/ABI и контроля времени жизни памяти. FFI может быть ограничен ffi.enable. Это не универсальный способ ускорить PHP; опасный нативный вызов способен аварийно завершить процесс. [Документация PHP](https://www.php.net/manual/en/book.ffi.php).

<a id="ini-quantities"></a>

## Разбор размеров из php.ini

**PHP 8.2+ | Core**

ini_parse_quantity() понимает сокращённые множители PHP K, M и G. Это удобно для чтения известного параметра ini без собственного парсера суффиксов.

```php
var_dump( ini_parse_quantity('128M') ); # int(134217728)
var_dump( ini_parse_quantity('512K') ); # int(524288)
var_dump( ini_parse_quantity('-1') ); # int(-1)
```

Множители используют степени 1024. Сохраняйте специальные значения параметра: -1 может означать отсутствие лимита memory_limit, а не количество доступных байтов. Для некорректного текста парсер может предупредить и прочитать числовое начало; это не строгая проверка произвольного ввода. [Документация PHP](https://www.php.net/manual/en/function.ini-parse-quantity.php).

<a id="measure-memory"></a>

## Сброс пика памяти для отдельного этапа

**PHP 8.2+ | Core**

memory_reset_peak_usage() сбрасывает счётчик пика до текущего использования. Так следующий этап можно измерить без максимума, оставшегося от предыдущей операции.

```php
memory_reset_peak_usage();
$before = memory_get_usage();
$buffer = str_repeat( 'x', 1_000_000 );
$peak = memory_get_peak_usage();
var_dump( $peak >= $before + strlen($buffer) ); # bool(true)
unset( $buffer );
```

Пик остаётся абсолютным значением процесса, а не автоматически разницей этапа. Для сравнения используйте одинаковый режим real_usage. Аллокатор и другие сохранённые данные влияют на итог; сброс не освобождает память. [Документация PHP](https://www.php.net/manual/en/function.memory-reset-peak-usage.php).

<a id="ini-and-cli"></a>

## Значения среды по умолчанию, права журнала и пакетный lint

**PHP 8.2+ log mode / PHP 8.3+ ini fallback and batch lint**

PHP 8.3 позволяет в php.ini запасное значение для отсутствующей переменной среды и несколько файлов после php -l. PHP 8.2 добавил error_log_mode для прав файла журнала.

В php.ini используйте `max_execution_time = ${PHP_MAX_EXECUTION_TIME:-30}`. Для файлового журнала `error_log_mode = 0640` задаёт Unix-права создания с учётом правил ОС; Windows ACL настраиваются отдельно. `php -l first.php second.php` проверяет только синтаксис: не запускает тесты, не проверяет все типы выполнения и настройки расширений. CLI и FPM могут читать разные ini-файлы; проверяйте настоящие параметры SAPI. [Документация PHP](https://www.php.net/manual/en/configuration.file.php).

<a id="extension-capabilities"></a>

## Проверка возможностей сборки: AVIF в GD

**PHP 8.1+ AVIF API | gd**

Версия PHP может иметь API, но расширение или нативный кодек отсутствует. Поддержка AVIF в GD является примером, где важно проверить конкретную сборку.

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

Функции чтения и записи AVIF появились в PHP 8.1, но GD должен быть собран с кодеком. Перед приёмом произвольных изображений ограничивайте размеры и ресурсы. Такой же принцип действует для curl, intl, sodium, драйверов PDO и других необязательных модулей: проверяйте среду выполнения. [Документация PHP](https://www.php.net/manual/en/function.imageavif.php).

<a id="monotonic-time"></a>

## Измерение длительности монотонными часами

**PHP 7.3+ | standard**

hrtime() измеряет время высокого разрешения от произвольного начала. Часы монотонные: изменения календарного времени не меняют их шкалу. Для длительности этапа вычтите два показания; для календарных меток используйте API даты и времени.

```php
$started_at = hrtime( true );
$digest = hash( 'sha256', str_repeat('x', 100000) );
$finished_at = hrtime( true );

if( $started_at === false || $finished_at === false )
	throw new RuntimeException( 'High-resolution clock is unavailable' );

$elapsed_ms = ($finished_at - $started_at) / 1000000;
var_dump( $elapsed_ms >= 0 ); # bool(true); the actual duration varies.
```

С true результатом являются наносекунды как int на 64-битных платформах или float на 32-битных. Без true возвращается [секунды, наносекунды]; при сбое false. Единица наносекунд не гарантирует наносекундной точности оборудования, а float на 32-битной платформе не сохраняет каждую целую наносекунду. Сравнивайте одинаковую нагрузку повторными измерениями, а не делайте вывод из одного запуска. [Документация PHP](https://www.php.net/manual/en/function.hrtime.php).
