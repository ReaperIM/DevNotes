# Файлы, HTTP и процессы

[English](../en/09-files-http-and-processes.md) | [Українська](../uk/09-files-http-and-processes.md) | [Русский](../ru/09-files-http-and-processes.md)

[Содержание](../../README.ru.md)

Команды, загрузки, тела запросов и HTTP-клиенты требуют явной работы с данными. Для примеров, зависящих от сервера, файлов или расширений, нужное окружение описано в тексте.

**В этой главе**

- [Аргументы процесса массивом](#process-arguments)
- [Запись данных на диск через fsync()](#flush-to-disk)
- [Несколько параметров потока одним вызовом](#stream-options)
- [Разбор тела формы для других HTTP-методов](#request-body)
- [Чтение и изменение URI-объектов](#uri-objects)
- [Дескрипторы cURL стали объектами](#curl-objects)
- [Отправка строки как файла](#curl-string-file)
- [Повторное использование данных cURL между запросами](#persistent-curl-share)
- [Список дескрипторов в multi handle](#multi-handles)
- [Чтение последних заголовков HTTP-потока](#http-response-headers)
- [Загрузка каталогов и full_path](#upload-paths)
- [Параметры cookie и Partitioned](#cookie-options)
- [Чтение сеанса с освобождением блокировки](#read-only-session)

<a id="process-arguments"></a>

## Аргументы процесса массивом

**PHP 7.4+ | standard**

Массив отделяет исполняемый файл от аргументов и позволяет не передавать собранную команду оболочке.

<!-- example: fragment -->
```php
$process = proc_open(
	[PHP_BINARY, '-r', 'fwrite(STDOUT, "ready");'],
	[1 => ['pipe', 'w']],
	$pipes,
);

if( $process === false )
	throw new RuntimeException( 'Cannot start PHP' );

$output = stream_get_contents( $pipes[1] );
fclose( $pipes[1] );
$exit_code = proc_close( $process );
var_dump( $output, $exit_code ); # string(5) "ready", int(0)
```

Исполняемый файл задавайте явно, а аргументы проверяйте по правилам программы: отсутствие оболочки не запрещает нежелательные параметры самой программы. Экранирование Windows предполагает обычные правила VC runtime. Этот маленький процесс пишет только в stdout; универсальный запуск требует ограничений времени и объёма и параллельного чтения stdout и stderr. [Документация PHP](https://www.php.net/manual/en/function.proc-open.php).

<a id="flush-to-disk"></a>

## Запись данных на диск через fsync()

**PHP 8.1+ | standard**

Функция `fflush()` сбрасывает буфер PHP. `fsync()` просит синхронизировать данные и метаданные файла; `fdatasync()` сосредоточена на данных и метаданных, нужных для их чтения.

<!-- example: fragment -->
```php
# $log_path is a trusted local file path.
$stream = fopen( $log_path, 'ab' );

if( $stream === false )
	throw new RuntimeException( 'Cannot open log' );

try
{
	$data = "checkpoint\n";

	if( fwrite($stream, $data) !== strlen($data) || !fsync($stream) )
		throw new RuntimeException( 'Cannot persist checkpoint' );
}
finally
{
	fclose( $stream );
}
```

Применяйте для конкретного требования надёжной записи: синхронизация каждой маленькой записи может быть дорогой. Она не заменяет блокировки и проверку неполной записи. Не каждый поток поддерживает синхронизацию. [Документация PHP](https://www.php.net/manual/en/function.fsync.php).

<a id="stream-options"></a>

## Несколько параметров потока одним вызовом

**PHP 8.3+ | standard**

Функция `stream_context_set_options()` применяет вложенный массив параметров к существующему контексту. Множественное число в имени даёт операции понятный API.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

Старая функция в единственном числе также имела вариант с массивом; это явная альтернатива. Создание контекста не отправляет запрос. [Документация PHP](https://www.php.net/manual/en/function.stream-context-set-options.php).

<a id="request-body"></a>

## Разбор тела формы для других HTTP-методов

**PHP 8.4+ | standard**

Функция `request_parse_body()` возвращает массивы полей и файлов для данных формы или multipart. Это удобно в обработчиках PUT и PATCH.

<!-- example: fragment -->
```php
# HTTP handler receiving a form-encoded PUT request.
[$fields, $files] = request_parse_body( [
	'post_max_size' => '2M',
	'upload_max_filesize' => '1M',
] );
$name = $fields['name'] ?? null;

if( !is_string($name) )
	throw new InvalidArgumentException( 'name must be a string' );
```

Поддерживаются application/x-www-form-urlencoded и multipart/form-data, а не JSON. Вызывайте до чтения php://input: тело потребляется один раз и не буферизуется там. Функция не меняет суперглобальные переменные. Обрабатывайте RequestParseBodyException на HTTP-границе и проверяйте разобранные значения. [Документация PHP](https://www.php.net/manual/en/function.request-parse-body.php).

<a id="uri-objects"></a>

## Чтение и изменение URI-объектов

**PHP 8.5+ | uri**

Расширение имеет отдельные модели RFC 3986 и WHATWG URL. Выбирайте нужные правила протокола или браузера. RFC-модель ниже создаёт изменённую копию.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog
```

Некорректные данные могут вызвать Uri\InvalidUriException. Правильный URI не становится автоматически безопасным адресом запроса: отдельно ограничивайте схемы, хосты и адреса. Пользуйтесь документированной исходной или нормализованной строкой вместо ручной сборки компонентов. [Документация PHP](https://www.php.net/manual/en/class.uri-rfc3986-uri.php).

<a id="curl-objects"></a>

## Дескрипторы cURL стали объектами

**PHP 8.0+ | curl**

curl_init() возвращает CurlHandle вместо ресурса. Дескрипторы multi и share также имеют классы. Существующие curl-функции по-прежнему работают с ними.

<!-- example: fragment -->
```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

Старая проверка is_resource() больше не доказывает успех или неудачу инициализации. Отдельные классы также позволяют типизировать параметры. [Документация PHP](https://www.php.net/manual/en/function.curl-init.php).

<a id="curl-string-file"></a>

## Отправка строки как файла

**PHP 8.1+ | curl**

CURLStringFile превращает байты из памяти в файл для multipart-отправки. Созданному отчёту не нужен временный файл только ради cURL.

<!-- example: fragment -->
```php
$handle = curl_init( 'https://example.com/upload' );

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

$file = new CURLStringFile( "id,total\n7,42\n", 'report.csv', 'text/csv' );
curl_setopt( $handle, CURLOPT_POSTFIELDS, ['report' => $file] );
curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $handle, CURLOPT_TIMEOUT, 5 );
# curl_exec( $handle ) would send the multipart request.
```

Аргументы: содержимое, имя для отправки, затем MIME-тип. Память всё равно нужна; для большого готового файла используйте CURLFile. Пусть cURL сам создаёт multipart boundary. [Документация PHP](https://www.php.net/manual/en/class.curlstringfile.php).

<a id="persistent-curl-share"></a>

## Повторное использование данных cURL между запросами

**PHP 8.5+ | curl**

curl_share_init_persistent() может сохранять общие DNS-данные и соединения дольше одного запроса в долгоживущем процессе. Те же параметры повторно используют подходящий дескриптор.

<!-- example: fragment -->
```php
$share = curl_share_init_persistent( [
	CURL_LOCK_DATA_DNS,
	CURL_LOCK_DATA_CONNECT,
] );
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_setopt( $handle, CURLOPT_SHARE, $share );
```

Это повторное использование в пределах процесса, а не пул всех процессов. CURL_LOCK_DATA_COOKIE запрещён: разные запросы могут принадлежать разным пользователям. Постоянство не отменяет тайм-ауты сервера. [Документация PHP](https://www.php.net/manual/en/function.curl-share-init-persistent.php).

<a id="multi-handles"></a>

## Список дескрипторов в multi handle

**PHP 8.5+ | curl**

curl_multi_get_handles() показывает обычные дескрипторы, добавленные в multi handle, и позволяет не вести второй массив только для их учёта.

<!-- example: fragment -->
```php
$multi = curl_multi_init();
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_multi_add_handle( $multi, $handle );
var_dump( count(curl_multi_get_handles($multi)) ); # int(1)
curl_multi_remove_handle( $multi, $handle );
```

Просмотр состава набора не выполняет запросы и не сообщает о завершении. Состояние и результаты по-прежнему получают через API выполнения и чтения информации. [Документация PHP](https://www.php.net/manual/en/function.curl-multi-get-handles.php).

<a id="http-response-headers"></a>

## Чтение последних заголовков HTTP-потока

**PHP 8.4+ | standard**

http_get_last_response_headers() читает заголовки HTTP-обёртки потоков. Парная функция очистки позволяет явно управлять временем их жизни.

<!-- example: fragment -->
```php
http_clear_last_response_headers();
$context = stream_context_create( ['http' => [
	'timeout' => 5,
	'follow_location' => 0,
]] );
$body = file_get_contents( 'https://example.com/', false, $context );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();

if( $body === false )
	throw new RuntimeException( 'HTTP request failed' );
```

Результат является массивом или null, если ничего не сохранено. Это не API заголовков cURL. Перенаправления могут создать несколько блоков ответа; пример отключает их и имеет постоянный URL. [Документация PHP](https://www.php.net/manual/en/function.http-get-last-response-headers.php).

<a id="upload-paths"></a>

## Загрузка каталогов и full_path

**PHP 8.1+ | standard**

PHP передаёт относительный путь клиента для загрузки каталога. После проверки он помогает восстановить дерево папок.

<!-- example: fragment -->
```php
# Expected field name: files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;

if( !is_string($relative_path) )
	throw new InvalidArgumentException( 'Missing upload path' );

# Metadata only: do not use directly as a storage path.
var_dump( $relative_path );
```

Путь присылает браузер; PHP не подтверждает его подлинность. Отклоняйте абсолютные пути, переходы в родительский каталог и неожиданные разделители. Для сохранения также нужны проверки ошибок, содержимого и move_uploaded_file(). [Документация PHP](https://www.php.net/manual/en/features.file-upload.multiple.php).

<a id="cookie-options"></a>

## Параметры cookie и Partitioned

**PHP 7.3+ options / PHP 8.5+ partitioned | standard**

Массив параметров называет каждую настройку. PHP 8.5 добавляет partitioned, который просит совместимый клиент разделять cookie по сайту верхнего уровня.

<!-- example: fragment -->
```php
# HTTPS response before output; $token has already been generated.
setcookie( 'widget_session', $token, [
	'path'        => '/',
	'secure'      => true,
	'httponly'    => true,
	'samesite'    => 'None',
	'partitioned' => true,
] );
```

Partitioned требует Secure, иначе PHP выбрасывает ValueError. SameSite=None также требует Secure для принятия клиентом. Это сочетание для намеренно встроенного межсайтового сценария, а не каждого сеанса. true означает только то, что PHP смог отправить заголовок. [Документация PHP](https://www.php.net/manual/en/function.setcookie.php).

<a id="read-only-session"></a>

## Чтение сеанса с освобождением блокировки

**PHP 7.0+ | session**

Передайте read_and_close в session_start(), если запросу нужно только прочитать данные сеанса. PHP загружает их в $_SESSION и сразу закрывает сеанс. Для стандартного файлового обработчика это освобождает блокировку: другие запросы этого сеанса не ждут завершения остальной части текущего запроса.

<!-- example: fragment -->
```php
# HTTP handler, before output; application session settings are already configured.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# The session is already closed; the rest of this request does not hold its lock.
```

Используйте этот режим для чтения, а не для запроса, который должен сохранять изменения: дальнейшие присваивания в $_SESSION после такого закрытия автоматически не сохранятся. При необходимости может создаться новый сеанс; параметр не означает принимать только существующий. Последовательный результат false при неудачном старте появился в PHP 7.1. Для изменения данных используйте обычный session_start(), внесите изменения и вызовите session_write_close(), как только изменения сеанса завершены. [Документация PHP](https://www.php.net/manual/en/function.session-start.php).

Для файлового обработчика read-and-close пропускает обычное обновление времени файла. Если запросы только читают в этом режиме, файл может достаточно состариться для сборки мусора даже при активном пользователе. Учитывайте политику хранения своего обработчика. Это следует из пути read-and-close и логики времени файла и GC в реализации PHP. [Реализация PHP: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c), [Реализация PHP: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c).
