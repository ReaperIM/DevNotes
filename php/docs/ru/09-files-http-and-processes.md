# Файлы, HTTP и процессы

[EN](../en/09-files-http-and-processes.md) | [UK](../uk/09-files-http-and-processes.md) | [RU](../ru/09-files-http-and-processes.md)

[Содержание](../../README.ru.md)

**В этой главе**

- [Аргументы процесса массивом](#process-arguments)
- [Запись данных на диск через `fsync()`](#flush-to-disk)
- [Несколько параметров потока одним вызовом](#stream-options)
- [Разбор тела формы для других HTTP-методов](#request-body)
- [Чтение и изменение URI-объектов](#uri-objects)
- [Дескрипторы cURL стали объектами](#curl-objects)
- [Отправка строки как файла](#curl-string-file)
- [Повторное использование данных cURL между запросами](#persistent-curl-share)
- [Список дескрипторов в multi handle](#multi-handles)
- [Чтение последних заголовков HTTP-потока](#http-response-headers)
- [Загрузка каталогов и `full_path`](#upload-paths)
- [Параметры cookie и `Partitioned`](#cookie-options)
- [Чтение сеанса с освобождением блокировки](#read-only-session)

<a id="process-arguments"></a>

## Аргументы процесса массивом

**PHP 7.4+ | standard** [Документация PHP](https://www.php.net/manual/en/function.proc-open.php)

Массив аргументов отделяет исполняемый файл от его аргументов и обходит разбор оболочкой. Это удобно для передачи значений известной программе.

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

В Windows экранирование аргументов массива рассчитано на правила VC runtime; параметры самой программы всё равно требуют проверки.

<a id="flush-to-disk"></a>

## Запись данных на диск через `fsync()`

**PHP 8.1+ | standard** [Документация PHP](https://www.php.net/manual/en/function.fsync.php)

`fflush()` сбрасывает буфер PHP. `fsync()` синхронизирует данные и метаданные файла; `fdatasync()` синхронизирует данные и только те метаданные, которые нужны для их чтения.

<!-- example: fragment -->
```php
# $stream is an open local file, for example fopen($log_path, 'ab').
$data = "checkpoint\n";

if( fwrite($stream, $data) !== strlen($data) || !fsync($stream) )
	throw new RuntimeException( 'Cannot persist checkpoint' );
```

Не каждый поток поддерживает синхронизацию.

<a id="stream-options"></a>

## Несколько параметров потока одним вызовом

**PHP 8.3+ | standard** [Документация PHP](https://www.php.net/manual/en/function.stream-context-set-options.php)

`stream_context_set_options()` применяет вложенный массив параметров к существующему контексту потока.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

Старая `stream_context_set_option()` в единственном числе уже поддерживала вариант с массивом.

<a id="request-body"></a>

## Разбор тела формы для других HTTP-методов

**PHP 8.4+ | standard** [Документация PHP](https://www.php.net/manual/en/function.request-parse-body.php)

`request_parse_body()` возвращает поля формы и загруженные файлы, в том числе для обработчиков `PUT` и `PATCH`, не заполняя суперглобальные переменные.

<!-- example: fragment -->
```php
# HTTP handler receiving a form-encoded PUT request.
[$fields, $files] = request_parse_body( [
	'post_max_size' => '2M',
	'upload_max_filesize' => '1M',
] );
$name = $fields['name'] ?? null;
```

Вызывайте до чтения `php://input`: тело потребляется один раз, там не буферизуется, JSON не поддерживается.

<a id="uri-objects"></a>

## Чтение и изменение URI-объектов

**PHP 8.5+ | uri** [Документация PHP](https://www.php.net/manual/en/class.uri-rfc3986-uri.php)

Расширение URI имеет отдельные модели RFC 3986 и WHATWG для правил URI или браузерных URL. В показанной RFC-модели `withPath()` возвращает изменённую копию.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog: the original is unchanged
```

Некорректные данные могут вызвать `Uri\InvalidUriException`.

<a id="curl-objects"></a>

## Дескрипторы cURL стали объектами

**PHP 8.0+ | curl** [Документация PHP](https://www.php.net/manual/en/function.curl-init.php)

Дескрипторы cURL easy, multi и share имеют классы вместо типа `resource`.

```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

`curl_init()` по-прежнему может вернуть `false`; `is_resource()` больше не проверяет успешность инициализации.

<a id="curl-string-file"></a>

## Отправка строки как файла

**PHP 8.1+ | curl** [Документация PHP](https://www.php.net/manual/en/class.curlstringfile.php)

`CURLStringFile` отправляет байты из памяти как multipart-файл без создания временного файла.

<!-- example: fragment -->
```php
$handle = curl_init( 'https://example.com/upload' );

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

$file = new CURLStringFile( "id,total\n7,42\n", 'report.csv', 'text/csv' );
curl_setopt( $handle, CURLOPT_POSTFIELDS, ['report' => $file] );
# curl_exec($handle) would send the multipart request.
```

Аргументы конструктора: содержимое, имя для отправки, MIME-тип; для большого готового файла используйте `CURLFile`.

<a id="persistent-curl-share"></a>

## Повторное использование данных cURL между запросами

**PHP 8.5+ | curl** [Документация PHP](https://www.php.net/manual/en/function.curl-share-init-persistent.php)

`curl_share_init_persistent()` повторно использует общие DNS-данные и соединения между запросами одного долгоживущего процесса.

<!-- example: fragment -->
```php
$share = curl_share_init_persistent( [CURL_LOCK_DATA_DNS, CURL_LOCK_DATA_CONNECT] );
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_setopt( $handle, CURLOPT_SHARE, $share );
```

Дескриптор действует в пределах процесса, а не как пул всех процессов; `CURL_LOCK_DATA_COOKIE` запрещён.

<a id="multi-handles"></a>

## Список дескрипторов в multi handle

**PHP 8.5+ | curl** [Документация PHP](https://www.php.net/manual/en/function.curl-multi-get-handles.php)

`curl_multi_get_handles()` возвращает easy-дескрипторы, добавленные в multi handle.

```php
$multi = curl_multi_init();
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_multi_add_handle( $multi, $handle );
var_dump( count(curl_multi_get_handles($multi)) ); # int(1)
curl_multi_remove_handle( $multi, $handle );
```

Список дескрипторов не выполняет запросы и не сообщает о завершении передачи.

<a id="http-response-headers"></a>

## Чтение последних заголовков HTTP-потока

**PHP 8.4+ | standard** [Документация PHP](https://www.php.net/manual/en/function.http-get-last-response-headers.php)

`http_get_last_response_headers()` читает сохранённые заголовки HTTP-обёртки потоков или возвращает `null`, если их нет.

<!-- example: fragment -->
```php
$context = stream_context_create( ['http' => ['timeout' => 5]] );
http_clear_last_response_headers();
$body = file_get_contents( 'https://example.com/', false, $context );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();
var_dump( $headers ); # Response-header lines, or null.
```

Перенаправления могут создать несколько блоков заголовков ответа.

<a id="upload-paths"></a>

## Загрузка каталогов и `full_path`

**PHP 8.1+ | standard** [Документация PHP](https://www.php.net/manual/en/features.file-upload.multiple.php)

Загрузка каталогов передаёт относительный путь клиента в `full_path`.

<!-- example: fragment -->
```php
# Directory upload field: files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;
var_dump( $relative_path );
```

Это недоверенные метаданные: отклоняйте абсолютные пути и переходы в родительский каталог перед сохранением.

<a id="cookie-options"></a>

## Параметры cookie и `Partitioned`

**PHP 7.3+ options / PHP 8.5+ `partitioned` | standard** [Документация PHP](https://www.php.net/manual/en/function.setcookie.php)

Параметры cookie задаются именованными элементами массива; PHP 8.5 добавляет `partitioned` для разделения хранилища по сайту верхнего уровня в совместимых клиентах.

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

`partitioned` требует `secure`, иначе PHP выбрасывает `ValueError`; клиенты также требуют `Secure` для `SameSite=None`.

<a id="read-only-session"></a>

## Чтение сеанса с освобождением блокировки

**PHP 7.0+ | session** [Документация PHP](https://www.php.net/manual/en/function.session-start.php) | [Реализация PHP: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c) | [Реализация PHP: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c)

`read_and_close` загружает `$_SESSION` и сразу закрывает сеанс, освобождая блокировку стандартного файлового обработчика.

<!-- example: fragment -->
```php
# HTTP handler before output; session settings are already configured.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# Later changes to $_SESSION are not saved automatically.
```

Файловый обработчик пропускает обновление времени файла, поэтому даже при активных запросах только для чтения сеанс может устареть для сборщика мусора.
