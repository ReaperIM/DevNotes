# Файли, HTTP і процеси

[EN](../en/09-files-http-and-processes.md) | [UK](../uk/09-files-http-and-processes.md) | [RU](../ru/09-files-http-and-processes.md)

[Зміст](../../README.uk.md)

Запускаємо процеси, читаємо й надсилаємо HTTP-дані, працюємо із завантаженнями, cookie та сесіями. У прикладах поряд із викликом зазначено, що має бути підготовлено заздалегідь і де результат ще потрібно перевірити.

**У цьому розділі**

- [Аргументи процесу масивом](#process-arguments)
- [Запис даних на диск через `fsync()`](#flush-to-disk)
- [Кілька параметрів потоку одним викликом](#stream-options)
- [Розбір тіла форми для інших HTTP-методів](#request-body)
- [Змінюємо частини адреси через об’єкт URI](#uri-objects)
- [Дескриптори cURL стали об’єктами](#curl-objects)
- [Завантаження рядка як файлу](#curl-string-file)
- [Повторне використання даних cURL між запитами](#persistent-curl-share)
- [Які дескриптори додані в cURL multi](#multi-handles)
- [Читання останніх заголовків HTTP-потоку](#http-response-headers)
- [Завантаження каталогів і `full_path`](#upload-paths)
- [Параметри cookie та `Partitioned`](#cookie-options)
- [Читаємо сесію й відразу відпускаємо блокування](#read-only-session)

<a id="process-arguments"></a>

## Аргументи процесу масивом

**PHP 7.4+ | standard** [Документація PHP](https://www.php.net/manual/en/function.proc-open.php)

У `proc_open()` можна передати програму й аргументи масивом. Тоді не потрібно збирати командний рядок, який потім розбирає оболонка: перший елемент задає програму, решта йде її аргументами. У прикладі запускаємо ще один PHP-процес і читаємо його виведення з каналу.

<!-- example: fragment -->
```php
$process = proc_open( [PHP_BINARY, '-r', 'echo "ready";'], [1 => ['pipe', 'w']], $pipes );

if( $process === false )
	throw new RuntimeException( 'Cannot start PHP' );

$output = stream_get_contents( $pipes[1] );
fclose( $pipes[1] );
$exit_code = proc_close( $process );
var_dump( $output, $exit_code ); # string(5) "ready", int(0)
```

У Windows екранування такого масиву розраховане на правила VC runtime. І самі аргументи програми все одно перевіряємо: відмова від оболонки не заважає передати програмі небажаний для нашої задачі параметр.

<a id="flush-to-disk"></a>

## Запис даних на диск через `fsync()`

**PHP 8.1+ | standard** [Документація PHP](https://www.php.net/manual/en/function.fsync.php)

Успішний запис у буфер ще не означає, що дані вже синхронізовано з диском. `fflush()` скидає буфер PHP, а `fsync()` синхронізує дані файлу разом із метаданими. Якщо достатньо даних і метаданих, потрібних для їх читання, є `fdatasync()`.

<!-- example: fragment -->
```php
# $stream уже відкритий для локального файлу, наприклад через fopen($log_path, 'ab').
$data = "checkpoint\n";

if( fwrite($stream, $data) !== strlen($data) || !fsync($stream) )
	throw new RuntimeException( 'Cannot persist checkpoint' );
```

У прикладі перевіряємо й повний запис рядка, й успішну синхронізацію: недописана контрольна позначка нам не підходить. Підтримка залежить від потоку, тому застосовувати таку перевірку до будь-якого довільного потоку не можна.

<a id="stream-options"></a>

## Кілька параметрів потоку одним викликом

**PHP 8.3+ | standard** [Документація PHP](https://www.php.net/manual/en/function.stream-context-set-options.php)

Якщо контекст потоку вже створений, кілька його налаштувань можна додати одним викликом `stream_context_set_options()`. Зовнішні ключі масиву обирають обгортку, наприклад `http` або `ssl`, а всередині йдуть її параметри. Тут задаємо таймаут, вимикаємо переходи за перенаправленнями й залишаємо перевірку TLS-сертифіката ввімкненою.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

Виведення `int(5)` показує, що таймаут з'явився в наявному контексті. Стара функція `stream_context_set_option()` теж уміла приймати масив: назва у множині не означає, що ця можливість з'явилася вперше.

<a id="request-body"></a>

## Розбір тіла форми для інших HTTP-методів

**PHP 8.4+ | standard** [Документація PHP](https://www.php.net/manual/en/function.request-parse-body.php)

Для форми, надісланої через `PUT` або `PATCH`, можна викликати `request_parse_body()`. Отримаємо два масиви: поля форми й завантажені файли. У `$_POST` і `$_FILES` функція їх не записує, тому в прикладі працюємо з `$fields` і `$files` напряму.

<!-- example: fragment -->
```php
# Обробник отримує форму через PUT, не JSON.
[$fields, $files] = request_parse_body( [
	'post_max_size' => '2M',
	'upload_max_filesize' => '1M',
] );
$name = $fields['name'] ?? null;
```

Викликати її потрібно до читання `php://input`. Тіло запиту читається один раз і після цієї функції там не буферизується. JSON функція не розбирає: вона призначена для тіла форми.

<a id="uri-objects"></a>

## Змінюємо частини адреси через об’єкт URI

**PHP 8.5+ | uri** [Документація PHP](https://www.php.net/manual/en/class.uri-rfc3986-uri.php)

Замість ручного складання адреси можна працювати з об'єктом URI. Розширення пропонує дві моделі: RFC 3986 для URI й WHATWG із правилами браузерних URL. Тут використовуємо RFC-модель: `withPath()` змінює шлях у новій копії, зберігаючи решту адреси.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog: вихідний об’єкт не змінювали
```

У `$next` шлях уже `/archive`, а у вихідного `$uri` досі `/catalog`. Неправильна адреса може викликати `Uri\InvalidUriException`, тому створення об'єкта теж може потребувати обробки помилки.

<a id="curl-objects"></a>

## Дескриптори cURL стали об’єктами

**PHP 8.0+ | curl** [Документація PHP](https://www.php.net/manual/en/function.curl-init.php)

З PHP 8.0 cURL повертає об'єкти замість ресурсів: це стосується звичайних запитів, multi й share. Тому старий `is_resource()` більше не підходить для перевірки успішної ініціалізації. У прикладі правильний `CurlHandle` проходить `instanceof`, але для `is_resource()` дає `false`.

```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

У разі помилки `curl_init()` усе ще може повернути `false`. Спочатку перевіряємо цей результат і вже потім працюємо з дескриптором.

<a id="curl-string-file"></a>

## Завантаження рядка як файлу

**PHP 8.1+ | curl** [Документація PHP](https://www.php.net/manual/en/class.curlstringfile.php)

Якщо вміст файлу вже лежить у рядку, не потрібно спочатку зберігати його в тимчасовий файл заради надсилання. `CURLStringFile` оформлює ці байти як файл у multipart-запиті. Тут CSV зберігається в пам'яті, але отримувач побачить файл з ім'ям `report.csv` і типом `text/csv`.

<!-- example: fragment -->
```php
$handle = curl_init( 'https://example.com/upload' );

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

$file = new CURLStringFile( "id,total\n7,42\n", 'report.csv', 'text/csv' );
curl_setopt( $handle, CURLOPT_POSTFIELDS, ['report' => $file] );
# Сам запит надішлеться під час curl_exec($handle).
```

Порядок аргументів конструктора: вміст, ім'я для надсилання, MIME-тип. Якщо великий файл уже лежить на диску, зручніше використати `CURLFile`, не завантажуючи його заздалегідь цілком у рядок.

<a id="persistent-curl-share"></a>

## Повторне використання даних cURL між запитами

**PHP 8.5+ | curl** [Документація PHP](https://www.php.net/manual/en/function.curl-share-init-persistent.php)

У довготривалому PHP-процесі не завжди потрібно заново збирати DNS-дані й з'єднання для кожного запиту. `curl_share_init_persistent()` дозволяє повторно використовувати вибрані дані між запитами цього процесу. У прикладі підключаємо до звичайного cURL-дескриптора спільні DNS-дані й з'єднання.

<!-- example: fragment -->
```php
$share = curl_share_init_persistent( [CURL_LOCK_DATA_DNS, CURL_LOCK_DATA_CONNECT] );
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_setopt( $handle, CURLOPT_SHARE, $share );
```

Це спільне сховище одного процесу, а не єдиний пул для всіх PHP-процесів. Cookies так ділити не можна: `CURL_LOCK_DATA_COOKIE` для цієї функції заборонений.

<a id="multi-handles"></a>

## Які дескриптори додані в cURL multi

**PHP 8.5+ | curl** [Документація PHP](https://www.php.net/manual/en/function.curl-multi-get-handles.php)

Якщо запити додані в multi handle, їхні звичайні cURL-дескриптори можна отримати через `curl_multi_get_handles()`. У прикладі додаємо один дескриптор і бачимо в списку рівно один елемент, після чого прибираємо його назад.

```php
$multi = curl_multi_init();
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_multi_add_handle( $multi, $handle );
var_dump( count(curl_multi_get_handles($multi)) ); # int(1)
curl_multi_remove_handle( $multi, $handle );
```

Саме читання списку запити не запускає. За ним також не можна визначити, яка передача вже завершилася: функція показує, які дескриптори зараз додані.

<a id="http-response-headers"></a>

## Читання останніх заголовків HTTP-потоку

**PHP 8.4+ | standard** [Документація PHP](https://www.php.net/manual/en/function.http-get-last-response-headers.php)

Після HTTP-запиту через потік, наприклад `file_get_contents()`, заголовки відповіді можна взяти через `http_get_last_response_headers()`. Якщо збережених заголовків немає, отримаємо `null`. У прикладі очищаємо старі дані перед запитом і ще раз після того, як забрали результат.

<!-- example: fragment -->
```php
$context = stream_context_create( ['http' => ['timeout' => 5]] );
http_clear_last_response_headers();
$body = file_get_contents( 'https://example.com/', false, $context );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();
var_dump( $headers ); # Рядки заголовків відповіді або null.
```

Якщо були перенаправлення, у списку можуть опинитися кілька блоків заголовків від різних відповідей. Тому не варто заздалегідь вважати, що весь масив належить до однієї відповіді.

<a id="upload-paths"></a>

## Завантаження каталогів і `full_path`

**PHP 8.1+ | standard** [Документація PHP](https://www.php.net/manual/en/features.file-upload.multiple.php)

Під час завантаження каталогу одного імені файлу недостатньо, щоб відновити вкладені папки. У `full_path` клієнт передає відносний шлях файлу, а з `$_FILES` можна його прочитати. У прикладі беремо шлях першого файлу, якщо він узагалі надійшов.

<!-- example: fragment -->
```php
# Файли надійшли з поля завантаження каталогу files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;
var_dump( $relative_path );
```

Цей шлях задає клієнт, довіряти йому не можна. Перед збереженням перевіряємо, що він не абсолютний і не містить переходів до батьківських каталогів, інакше файл може потрапити за межі потрібної папки.

<a id="cookie-options"></a>

## Параметри cookie та `Partitioned`

**PHP 7.3+ параметри / PHP 8.5+ `partitioned` | standard** [Документація PHP](https://www.php.net/manual/en/function.setcookie.php)

Масив параметрів `setcookie()` зручніше читати за іменами: відразу видно шлях, `secure`, `httponly` і `samesite`. З PHP 8.5 можна додати `partitioned`. У клієнтах із підтримкою цієї можливості cookie зберігається окремо для кожного сайту верхнього рівня, усередині якого відкрито віджет.

<!-- example: fragment -->
```php
# Надсилаємо через HTTPS до виведення; $token уже згенерований.
setcookie( 'widget_session', $token, [
	'path'        => '/',
	'secure'      => true,
	'httponly'    => true,
	'samesite'    => 'None',
	'partitioned' => true,
] );
```

Для `partitioned` потрібен `secure`, інакше PHP викине `ValueError`. Для `SameSite=None` клієнтам теж потрібен `Secure`. У прикладі надсилаємо cookie через HTTPS і робимо це до виведення тіла відповіді.

<a id="read-only-session"></a>

## Читаємо сесію й відразу відпускаємо блокування

**PHP 7.0+ | session** [Документація PHP](https://www.php.net/manual/en/function.session-start.php) | [Реалізація PHP: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c) | [Реалізація PHP: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c)

Якщо запиту потрібно лише прочитати дані сесії, можна передати `read_and_close`. PHP завантажить `$_SESSION` і відразу закриє сесію. Стандартний файловий обробник при цьому відпустить блокування, щоб інші запити цієї сесії не чекали завершення нашої роботи.

<!-- example: fragment -->
```php
# Налаштування сесії вже задані, виведення відповіді ще не почалося.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# Якщо далі змінювати $_SESSION, ці зміни самі не збережуться.
```

Подальші зміни `$_SESSION` автоматично не збережуться. Є й нюанс файлового обробника: час файлу під час такого читання не оновлюється. Тому самі лише активні запити на читання можуть не врятувати сесію від видалення збирачем сміття.
