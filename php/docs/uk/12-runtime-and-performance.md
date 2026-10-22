# Виконання й продуктивність

[English](../en/12-runtime-and-performance.md) | [Українська](../uk/12-runtime-and-performance.md) | [Русский](../ru/12-runtime-and-performance.md)

[Зміст](../../README.uk.md)

Можливості середовища виконання впливають на час життя, розгортання й вимірювання. Вони корисні, коли їхня вартість відповідає застосунку: новий синтаксис чи ввімкнений параметр сам собою не гарантує швидшого запиту.

**У цьому розділі**

- [Спостереження за об’єктом без утримання в пам’яті](#weak-reference)
- [Кеш даних зі слабкими ключами-об’єктами](#weak-map)
- [Призупинення й відновлення стека викликів](#fibers)
- [Попереднє завантаження сталого коду](#preloading)
- [JIT та OPcache є різними інструментами](#jit)
- [FFI для явної взаємодії з C](#ffi)
- [Розбір розмірів із php.ini](#ini-quantities)
- [Скидання піку пам’яті для окремого етапу](#measure-memory)
- [Типові значення середовища, права журналу й пакетний lint](#ini-and-cli)
- [Перевірка можливостей збірки: AVIF у GD](#extension-capabilities)
- [Вимірювання тривалості монотонним годинником](#monotonic-time)

<a id="weak-reference"></a>

## Спостереження за об’єктом без утримання в пам’яті

**PHP 7.4+ | Core**

WeakReference зберігає посилання, яке не утримує об’єкт. get() повертає об’єкт, поки інше сильне посилання підтримує його життя, і null після знищення.

```php
$object = new stdClass();
$reference = WeakReference::create( $object );
var_dump( $reference->get() === $object ); # bool(true)
unset( $object );
var_dump( $reference->get() ); # NULL
```

Це корисно для спостерігачів і кешів, які не повинні володіти цільовим об’єктом. Збереження get() у звичайну змінну знову створює сильне посилання. WeakReference керує часом життя, а не замінює звичайне проєктування володіння об’єктами. [Документація PHP](https://www.php.net/manual/en/class.weakreference.php).

<a id="weak-map"></a>

## Кеш даних зі слабкими ключами-об’єктами

**PHP 8.0+ | Core**

WeakMap пов’язує дані з ключами-об’єктами, не утримуючи самі ключі в пам’яті. Коли ключ знищується, відповідний запис зникає.

```php
$cache = new WeakMap();
$object = new stdClass();
$cache[$object] = ['rendered' => 'ready'];
var_dump( count($cache) ); # int(1)
unset( $object );
var_dump( count($cache) ); # int(0)
```

Значення утримуються звичайним способом. Якщо значення кешу сильно посилається на власний ключ, це може зберегти об’єкт. WeakMap допомагає кешам похідних даних об’єкта; він не обмежує сторонні дані кешу й не розв’язує цикли посилань решти застосунку. [Документація PHP](https://www.php.net/manual/en/class.weakmap.php).

<a id="fibers"></a>

## Призупинення й відновлення стека викликів

**PHP 8.1+ | Core**

Fiber має стек виконання, який можна відновити. Функція глибоко в ланцюжку може призупинити його, не змушуючи кожного викликувача ставати генератором.

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

Аргумент resume стає результатом suspend(). Остаточний результат функції читають через getReturn(), а не resume(). Fibers працюють кооперативно, це не паралельні CPU-потоки: блокувальне введення-виведення далі блокує без планувальника й відповідних неблокувальних операцій. [Документація PHP](https://www.php.net/manual/en/language.fibers.php).

<a id="preloading"></a>

## Попереднє завантаження сталого коду

**PHP 7.4+ | OPcache**

Попереднє завантаження читає вибрані функції й класи на старті PHP, щоб наступні запити постійного процесу користувалися ними без повторного завантаження. Це обмін базової пам’яті й гнучкості розгортання на меншу повторну роботу.

<!-- example: fragment -->
```php
# PHP code inside the configured preload script.
opcache_compile_file( __DIR__ . '/src/User.php' );
```

Задайте шлях до цього скрипту в opcache.preload; у Unix-розгортанні opcache.preload_user може вибрати користувача запуску. Windows не підтримує preloading. Зміна попередньо завантажених визначень потребує перезапуску процесу. Це насамперед для постійних процесів, а не разового CLI-виклику; вибирайте код із контрольованою поведінкою на старті. [Документація PHP](https://www.php.net/manual/en/opcache.preloading.php).

<a id="jit"></a>

## JIT та OPcache є різними інструментами

**PHP 8.0+ JIT | OPcache**

OPcache кешує скомпільований байткод PHP. JIT може компілювати придатні шляхи виконання в машинний код. Це різні оптимізації з різною вартістю.

```php
$status = opcache_get_status( false );
$jit = ($status === false) ? null : ($status['jit'] ?? null);
var_dump( $jit ); # Environment-dependent status, not a benchmark.
```

Вимкнений OPcache може повернути false. Наявність і користь JIT залежать від збірки, параметрів та навантаження. Вимірюйте справжні гарячі ділянки: очікування бази, диска й мережі не зникає від швидшої арифметики PHP. Для CLI-перевірок явно перевіряйте opcache.enable_cli та буфер і параметри JIT; не визначайте активність за номером PHP. [Документація PHP](https://www.php.net/manual/en/opcache.configuration.php).

<a id="ffi"></a>

## FFI для явної взаємодії з C

**PHP 7.4+ | FFI**

FFI дозволяє PHP описувати типи C й викликати функції нативних бібліотек без написання повного розширення PHP. Це корисно, коли потрібний C API не має відповідної обгортки.

<!-- example: fragment -->
```php
# Requires the FFI extension and a policy that permits this call.
$ffi = FFI::cdef();
$value = $ffi->new( 'int' );
$value->cdata = 42;
var_dump( $value->cdata ); # int(42)
```

Тут лише виділяється C-значення, бібліотека не завантажується. Справжнє використання потребує точних нативних сигнатур, знання платформи/ABI та контролю часу життя пам’яті. FFI може бути обмежений ffi.enable. Це не універсальний спосіб прискорити PHP; небезпечний нативний виклик здатний завершити процес аварійно. [Документація PHP](https://www.php.net/manual/en/book.ffi.php).

<a id="ini-quantities"></a>

## Розбір розмірів із php.ini

**PHP 8.2+ | Core**

ini_parse_quantity() розуміє скорочені множники PHP K, M і G. Це зручно для читання відомого параметра ini без власного парсера суфіксів.

```php
var_dump( ini_parse_quantity('128M') ); # int(134217728)
var_dump( ini_parse_quantity('512K') ); # int(524288)
var_dump( ini_parse_quantity('-1') ); # int(-1)
```

Множники використовують степені 1024. Зберігайте спеціальні значення параметра: -1 може означати відсутність ліміту memory_limit, а не кількість доступних байтів. Для некоректного тексту парсер може попередити й прочитати числовий початок; це не сувора перевірка довільного вводу. [Документація PHP](https://www.php.net/manual/en/function.ini-parse-quantity.php).

<a id="measure-memory"></a>

## Скидання піку пам’яті для окремого етапу

**PHP 8.2+ | Core**

memory_reset_peak_usage() скидає лічильник піку до поточного використання. Так наступний етап можна виміряти без максимуму, що лишився від попередньої операції.

```php
memory_reset_peak_usage();
$before = memory_get_usage();
$buffer = str_repeat( 'x', 1_000_000 );
$peak = memory_get_peak_usage();
var_dump( $peak >= $before + strlen($buffer) ); # bool(true)
unset( $buffer );
```

Пік залишається абсолютним значенням процесу, а не автоматично різницею етапу. Для порівняння використовуйте однаковий режим real_usage. Алокатор та інші збережені дані впливають на підсумок; скидання не звільняє пам’ять. [Документація PHP](https://www.php.net/manual/en/function.memory-reset-peak-usage.php).

<a id="ini-and-cli"></a>

## Типові значення середовища, права журналу й пакетний lint

**PHP 8.2+ log mode / PHP 8.3+ ini fallback and batch lint**

PHP 8.3 дозволяє в php.ini резервне значення для відсутньої змінної середовища та кілька файлів після php -l. PHP 8.2 додав error_log_mode для прав файлу журналу.

У php.ini використовуйте `max_execution_time = ${PHP_MAX_EXECUTION_TIME:-30}`. Для файлового журналу `error_log_mode = 0640` задає Unix-права створення з урахуванням правил ОС; Windows ACL налаштовуються окремо. `php -l first.php second.php` перевіряє лише синтаксис: не запускає тести, не перевіряє всі типи виконання й налаштування розширень. CLI та FPM можуть читати різні ini-файли; перевіряйте справжні параметри SAPI. [Документація PHP](https://www.php.net/manual/en/configuration.file.php).

<a id="extension-capabilities"></a>

## Перевірка можливостей збірки: AVIF у GD

**PHP 8.1+ AVIF API | gd**

Версія PHP може мати API, але розширення або нативний кодек відсутній. Підтримка AVIF у GD є прикладом, де важливо перевірити конкретну збірку.

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

Функції читання й запису AVIF з’явилися в PHP 8.1, але GD має бути зібраний із кодеком. Перед прийманням довільних зображень обмежуйте розміри та ресурси. Такий самий принцип діє для curl, intl, sodium, драйверів PDO та інших необов’язкових модулів: перевіряйте середовище виконання. [Документація PHP](https://www.php.net/manual/en/function.imageavif.php).

<a id="monotonic-time"></a>

## Вимірювання тривалості монотонним годинником

**PHP 7.3+ | standard**

hrtime() вимірює час високої роздільності від довільного початку. Годинник монотонний: зміни календарного часу не змінюють його шкалу. Для тривалості етапу відніміть два показники; для календарних міток використовуйте API дати й часу.

```php
$started_at = hrtime( true );
$digest = hash( 'sha256', str_repeat('x', 100000) );
$finished_at = hrtime( true );

if( $started_at === false || $finished_at === false )
	throw new RuntimeException( 'High-resolution clock is unavailable' );

$elapsed_ms = ($finished_at - $started_at) / 1000000;
var_dump( $elapsed_ms >= 0 ); # bool(true); the actual duration varies.
```

З true результатом є наносекунди як int на 64-бітних платформах або float на 32-бітних. Без true повертається [секунди, наносекунди]; при збої false. Одиниця наносекунд не гарантує наносекундної точності обладнання, а float на 32-бітній платформі не зберігає кожну цілу наносекунду. Порівнюйте однакове навантаження повторними вимірюваннями, а не робіть висновок з одного запуску. [Документація PHP](https://www.php.net/manual/en/function.hrtime.php).
