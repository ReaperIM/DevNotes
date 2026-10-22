# Файли, HTTP і процеси

[English](../en/09-files-http-and-processes.md) | [Українська](../uk/09-files-http-and-processes.md) | [Русский](../ru/09-files-http-and-processes.md)

[Зміст](../../README.uk.md)

**У цьому розділі**

- [Аргументи процесу масивом](#process-arguments)
- [Запис даних на диск через fsync()](#flush-to-disk)
- [Кілька параметрів потоку одним викликом](#stream-options)
- [Розбір тіла форми для інших HTTP-методів](#request-body)
- [Читання та зміна URI-об’єктів](#uri-objects)
- [Дескриптори cURL стали об’єктами](#curl-objects)
- [Завантаження рядка як файлу](#curl-string-file)
- [Повторне використання даних cURL між запитами](#persistent-curl-share)
- [Список дескрипторів у multi handle](#multi-handles)
- [Читання останніх заголовків HTTP-потоку](#http-response-headers)
- [Завантаження каталогів і full_path](#upload-paths)
- [Параметри cookie та Partitioned](#cookie-options)
- [Читання сеансу зі звільненням блокування](#read-only-session)

<a id="process-arguments"></a>

## Аргументи процесу масивом

**PHP 7.4+ | standard** [Документація PHP](https://www.php.net/manual/en/function.proc-open.php)

Масив аргументів відокремлює виконуваний файл від його аргументів і обходить розбір оболонкою. Це зручно для передавання значень відомій програмі.

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

У Windows екранування аргументів масиву розраховане на правила VC runtime; параметри самої програми все одно потребують перевірки.

<a id="flush-to-disk"></a>

## Запис даних на диск через fsync()

**PHP 8.1+ | standard** [Документація PHP](https://www.php.net/manual/en/function.fsync.php)

`fflush()` скидає буфер PHP. `fsync()` синхронізує дані та метадані файла; `fdatasync()` синхронізує дані й лише ті метадані, які потрібні для їх читання.

<!-- example: fragment -->
```php
# $stream is an open local file, for example fopen($log_path, 'ab').
$data = "checkpoint\n";

if( fwrite($stream, $data) !== strlen($data) || !fsync($stream) )
	throw new RuntimeException( 'Cannot persist checkpoint' );
```

Не кожен потік підтримує синхронізацію.

<a id="stream-options"></a>

## Кілька параметрів потоку одним викликом

**PHP 8.3+ | standard** [Документація PHP](https://www.php.net/manual/en/function.stream-context-set-options.php)

`stream_context_set_options()` застосовує вкладений масив параметрів до наявного контексту потоку.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

Стара `stream_context_set_option()` в однині вже підтримувала варіант із масивом.

<a id="request-body"></a>

## Розбір тіла форми для інших HTTP-методів

**PHP 8.4+ | standard** [Документація PHP](https://www.php.net/manual/en/function.request-parse-body.php)

`request_parse_body()` повертає поля форми та завантажені файли, зокрема для обробників PUT і PATCH, не заповнюючи суперглобальні змінні.

<!-- example: fragment -->
```php
# HTTP handler receiving a form-encoded PUT request.
[$fields, $files] = request_parse_body( [
	'post_max_size' => '2M',
	'upload_max_filesize' => '1M',
] );
$name = $fields['name'] ?? null;
```

Викликайте до читання `php://input`: тіло споживається один раз, там не буферизується, JSON не підтримується.

<a id="uri-objects"></a>

## Читання та зміна URI-об’єктів

**PHP 8.5+ | uri** [Документація PHP](https://www.php.net/manual/en/class.uri-rfc3986-uri.php)

Розширення URI має окремі моделі RFC 3986 і WHATWG для правил URI або браузерних URL. У наведеній RFC-моделі `withPath()` повертає змінену копію.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog: the original is unchanged
```

Некоректні дані можуть викликати `Uri\InvalidUriException`.

<a id="curl-objects"></a>

## Дескриптори cURL стали об’єктами

**PHP 8.0+ | curl** [Документація PHP](https://www.php.net/manual/en/function.curl-init.php)

Дескриптори cURL easy, multi і share мають класи замість типу resource.

```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

`curl_init()` досі може повернути `false`; `is_resource()` більше не перевіряє успішність ініціалізації.

<a id="curl-string-file"></a>

## Завантаження рядка як файлу

**PHP 8.1+ | curl** [Документація PHP](https://www.php.net/manual/en/class.curlstringfile.php)

`CURLStringFile` надсилає байти з пам'яті як multipart-файл без створення тимчасового файла.

<!-- example: fragment -->
```php
$handle = curl_init( 'https://example.com/upload' );

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

$file = new CURLStringFile( "id,total\n7,42\n", 'report.csv', 'text/csv' );
curl_setopt( $handle, CURLOPT_POSTFIELDS, ['report' => $file] );
# curl_exec($handle) would send the multipart request.
```

Аргументи конструктора: вміст, ім'я для надсилання, MIME-тип; для наявного великого файла використовуйте `CURLFile`.

<a id="persistent-curl-share"></a>

## Повторне використання даних cURL між запитами

**PHP 8.5+ | curl** [Документація PHP](https://www.php.net/manual/en/function.curl-share-init-persistent.php)

`curl_share_init_persistent()` повторно використовує спільні DNS-дані та з'єднання між запитами одного довгоживучого процесу.

<!-- example: fragment -->
```php
$share = curl_share_init_persistent( [CURL_LOCK_DATA_DNS, CURL_LOCK_DATA_CONNECT] );
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_setopt( $handle, CURLOPT_SHARE, $share );
```

Дескриптор діє в межах процесу, а не як пул усіх процесів; `CURL_LOCK_DATA_COOKIE` заборонено.

<a id="multi-handles"></a>

## Список дескрипторів у multi handle

**PHP 8.5+ | curl** [Документація PHP](https://www.php.net/manual/en/function.curl-multi-get-handles.php)

`curl_multi_get_handles()` повертає easy-дескриптори, додані до multi handle.

```php
$multi = curl_multi_init();
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_multi_add_handle( $multi, $handle );
var_dump( count(curl_multi_get_handles($multi)) ); # int(1)
curl_multi_remove_handle( $multi, $handle );
```

Перелік дескрипторів не виконує запити й не повідомляє про завершення передачі.

<a id="http-response-headers"></a>

## Читання останніх заголовків HTTP-потоку

**PHP 8.4+ | standard** [Документація PHP](https://www.php.net/manual/en/function.http-get-last-response-headers.php)

`http_get_last_response_headers()` читає збережені заголовки HTTP-обгортки потоків або повертає `null`, якщо їх немає.

<!-- example: fragment -->
```php
$context = stream_context_create( ['http' => ['timeout' => 5]] );
http_clear_last_response_headers();
$body = file_get_contents( 'https://example.com/', false, $context );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();
var_dump( $headers ); # Response-header lines, or null.
```

Перенаправлення можуть створити кілька блоків заголовків відповіді.

<a id="upload-paths"></a>

## Завантаження каталогів і full_path

**PHP 8.1+ | standard** [Документація PHP](https://www.php.net/manual/en/features.file-upload.multiple.php)

Завантаження каталогів передає відносний шлях клієнта в `full_path`.

<!-- example: fragment -->
```php
# Directory upload field: files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;
var_dump( $relative_path );
```

Це недовірені метадані: відхиляйте абсолютні шляхи та переходи до батьківського каталогу перед збереженням.

<a id="cookie-options"></a>

## Параметри cookie та Partitioned

**PHP 7.3+ options / PHP 8.5+ partitioned | standard** [Документація PHP](https://www.php.net/manual/en/function.setcookie.php)

Параметри cookie задаються іменованими елементами масиву; PHP 8.5 додає `partitioned` для поділу сховища за сайтом верхнього рівня в сумісних клієнтах.

<!-- example: fragment -->
```php
# HTTPS response before output; $token is already generated.
setcookie( 'widget_session', $token, [
	'path'        => '/',
	'secure'      => true,
	'httponly'    => true,
	'samesite'    => 'None',
	'partitioned' => true,
] );
```

`partitioned` потребує `secure`, інакше PHP викликає `ValueError`; клієнти також вимагають Secure для `SameSite=None`.

<a id="read-only-session"></a>

## Читання сеансу зі звільненням блокування

**PHP 7.0+ | session** [Документація PHP](https://www.php.net/manual/en/function.session-start.php) | [Реалізація PHP: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c) | [Реалізація PHP: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c)

`read_and_close` завантажує `$_SESSION` і відразу закриває сеанс, звільняючи блокування стандартного файлового обробника.

<!-- example: fragment -->
```php
# HTTP handler before output; session settings are already configured.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# Later changes to $_SESSION are not saved automatically.
```

Файловий обробник пропускає оновлення часу файла, тому навіть за активних запитів лише для читання сеанс може застаріти для збирача сміття.
