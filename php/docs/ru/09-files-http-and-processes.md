# Файлы, HTTP и процессы

[EN](../en/09-files-http-and-processes.md) | [UK](../uk/09-files-http-and-processes.md) | [RU](../ru/09-files-http-and-processes.md)

[Содержание](../../README.ru.md)

Запускаем процессы, читаем и отправляем HTTP-данные, работаем с загрузками, cookie и сессиями. В примерах рядом с вызовом отмечено, что должно быть подготовлено заранее и где результат ещё нужно проверить.

**В этой главе**

- [Аргументы процесса массивом](#process-arguments)
- [Запись данных на диск через `fsync()`](#flush-to-disk)
- [Несколько параметров потока одним вызовом](#stream-options)
- [Разбор тела формы для других HTTP-методов](#request-body)
- [Меняем части адреса через объект URI](#uri-objects)
- [Дескрипторы cURL стали объектами](#curl-objects)
- [Отправка строки как файла](#curl-string-file)
- [Повторное использование данных cURL между запросами](#persistent-curl-share)
- [Какие дескрипторы добавлены в cURL multi](#multi-handles)
- [Чтение последних заголовков HTTP-потока](#http-response-headers)
- [Загрузка каталогов и `full_path`](#upload-paths)
- [Параметры cookie и `Partitioned`](#cookie-options)
- [Читаем сессию и сразу отпускаем блокировку](#read-only-session)

<a id="process-arguments"></a>

## Аргументы процесса массивом

**PHP 7.4+ | standard** [Документация PHP](https://www.php.net/manual/en/function.proc-open.php)

В `proc_open()` можно передать программу и аргументы массивом. Тогда не нужно собирать командную строку, которую потом разбирает оболочка: первый элемент задаёт программу, остальные идут её аргументами. В примере запускаем ещё один PHP-процесс и читаем его вывод из канала.

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

В Windows экранирование такого массива рассчитано на правила VC runtime. И сами аргументы программы всё равно проверяем: отказ от оболочки не мешает передать программе нежелательный для нашей задачи параметр.

<a id="flush-to-disk"></a>

## Запись данных на диск через `fsync()`

**PHP 8.1+ | standard** [Документация PHP](https://www.php.net/manual/en/function.fsync.php)

Успешная запись в буфер ещё не означает, что данные уже синхронизированы с диском. `fflush()` сбрасывает буфер PHP, а `fsync()` синхронизирует данные файла вместе с метаданными. Если достаточно данных и метаданных, необходимых для их чтения, есть `fdatasync()`.

<!-- example: fragment -->
```php
# $stream уже открыт для локального файла, например через fopen($log_path, 'ab').
$data = "checkpoint\n";

if( fwrite($stream, $data) !== strlen($data) || !fsync($stream) )
	throw new RuntimeException( 'Cannot persist checkpoint' );
```

В примере проверяем и полную запись строки, и успешную синхронизацию: недописанная контрольная отметка нам не подходит. Поддержка зависит от потока, поэтому применять такую проверку к любому произвольному потоку нельзя.

<a id="stream-options"></a>

## Несколько параметров потока одним вызовом

**PHP 8.3+ | standard** [Документация PHP](https://www.php.net/manual/en/function.stream-context-set-options.php)

Если контекст потока уже создан, несколько его настроек можно добавить одним вызовом `stream_context_set_options()`. Внешние ключи массива выбирают обёртку, например `http` или `ssl`, а внутри идут её параметры. Здесь задаём таймаут, отключаем переходы по перенаправлениям и оставляем проверку TLS-сертификата включённой.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

Вывод `int(5)` показывает, что таймаут появился в существующем контексте. Старая функция `stream_context_set_option()` тоже умела принимать массив: название во множественном числе не означает, что эта возможность появилась впервые.

<a id="request-body"></a>

## Разбор тела формы для других HTTP-методов

**PHP 8.4+ | standard** [Документация PHP](https://www.php.net/manual/en/function.request-parse-body.php)

Для формы, отправленной через `PUT` или `PATCH`, можно вызвать `request_parse_body()`. Получим два массива: поля формы и загруженные файлы. В `$_POST` и `$_FILES` функция их не записывает, поэтому в примере работаем с `$fields` и `$files` напрямую.

<!-- example: fragment -->
```php
# Обработчик получает форму через PUT, не JSON.
[$fields, $files] = request_parse_body( [
	'post_max_size' => '2M',
	'upload_max_filesize' => '1M',
] );
$name = $fields['name'] ?? null;
```

Вызвать её нужно до чтения `php://input`. Тело запроса читается один раз и после этой функции там не буферизуется. JSON функция не разбирает: она предназначена для тела формы.

<a id="uri-objects"></a>

## Меняем части адреса через объект URI

**PHP 8.5+ | uri** [Документация PHP](https://www.php.net/manual/en/class.uri-rfc3986-uri.php)

Вместо ручной сборки адреса можно работать с объектом URI. Расширение предлагает две модели: RFC 3986 для URI и WHATWG с правилами браузерных URL. Здесь используем RFC-модель: `withPath()` меняет путь в новой копии, сохраняя остальную часть адреса.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog: исходный объект не меняли
```

В `$next` путь уже `/archive`, а у исходного `$uri` по-прежнему `/catalog`. Неверный адрес может вызвать `Uri\InvalidUriException`, поэтому создание объекта тоже может потребовать обработки ошибки.

<a id="curl-objects"></a>

## Дескрипторы cURL стали объектами

**PHP 8.0+ | curl** [Документация PHP](https://www.php.net/manual/en/function.curl-init.php)

С PHP 8.0 cURL возвращает объекты вместо ресурсов: это касается обычных запросов, multi и share. Поэтому старый `is_resource()` больше не подходит для проверки успешной инициализации. В примере корректный `CurlHandle` проходит `instanceof`, но для `is_resource()` даёт `false`.

```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

При ошибке `curl_init()` по-прежнему может вернуть `false`. Сначала проверяем этот результат и уже потом работаем с дескриптором.

<a id="curl-string-file"></a>

## Отправка строки как файла

**PHP 8.1+ | curl** [Документация PHP](https://www.php.net/manual/en/class.curlstringfile.php)

Если содержимое файла уже находится в строке, не нужно сначала сохранять его во временный файл ради отправки. `CURLStringFile` оформляет эти байты как файл в multipart-запросе. Здесь CSV хранится в памяти, но получатель увидит файл с именем `report.csv` и типом `text/csv`.

<!-- example: fragment -->
```php
$handle = curl_init( 'https://example.com/upload' );

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

$file = new CURLStringFile( "id,total\n7,42\n", 'report.csv', 'text/csv' );
curl_setopt( $handle, CURLOPT_POSTFIELDS, ['report' => $file] );
# Сам запрос отправится при curl_exec($handle).
```

Порядок аргументов конструктора: содержимое, имя для отправки, MIME-тип. Если большой файл уже лежит на диске, удобнее использовать `CURLFile`, не загружая его заранее целиком в строку.

<a id="persistent-curl-share"></a>

## Повторное использование данных cURL между запросами

**PHP 8.5+ | curl** [Документация PHP](https://www.php.net/manual/en/function.curl-share-init-persistent.php)

В долгоживущем PHP-процессе не всегда нужно заново собирать DNS-данные и соединения для каждого запроса. `curl_share_init_persistent()` позволяет повторно использовать выбранные данные между запросами этого процесса. В примере подключаем к обычному cURL-дескриптору общие DNS-данные и соединения.

<!-- example: fragment -->
```php
$share = curl_share_init_persistent( [CURL_LOCK_DATA_DNS, CURL_LOCK_DATA_CONNECT] );
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_setopt( $handle, CURLOPT_SHARE, $share );
```

Это общее хранилище одного процесса, а не единый пул для всех PHP-процессов. Cookies так разделять нельзя: `CURL_LOCK_DATA_COOKIE` для этой функции запрещён.

<a id="multi-handles"></a>

## Какие дескрипторы добавлены в cURL multi

**PHP 8.5+ | curl** [Документация PHP](https://www.php.net/manual/en/function.curl-multi-get-handles.php)

Если запросы добавлены в multi handle, их обычные cURL-дескрипторы можно получить через `curl_multi_get_handles()`. В примере добавляем один дескриптор и видим в списке ровно один элемент, после чего убираем его обратно.

```php
$multi = curl_multi_init();
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_multi_add_handle( $multi, $handle );
var_dump( count(curl_multi_get_handles($multi)) ); # int(1)
curl_multi_remove_handle( $multi, $handle );
```

Само чтение списка запросы не запускает. По нему также нельзя определить, какая передача уже завершилась: функция показывает, какие дескрипторы сейчас добавлены.

<a id="http-response-headers"></a>

## Чтение последних заголовков HTTP-потока

**PHP 8.4+ | standard** [Документация PHP](https://www.php.net/manual/en/function.http-get-last-response-headers.php)

После HTTP-запроса через поток, например `file_get_contents()`, заголовки ответа можно взять через `http_get_last_response_headers()`. Если сохранённых заголовков нет, получим `null`. В примере очищаем старые данные перед запросом и ещё раз после того, как забрали результат.

<!-- example: fragment -->
```php
$context = stream_context_create( ['http' => ['timeout' => 5]] );
http_clear_last_response_headers();
$body = file_get_contents( 'https://example.com/', false, $context );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();
var_dump( $headers ); # Строки заголовков ответа или null.
```

Если были перенаправления, в списке могут оказаться несколько блоков заголовков от разных ответов. Поэтому не стоит заранее считать, что весь массив относится к одному ответу.

<a id="upload-paths"></a>

## Загрузка каталогов и `full_path`

**PHP 8.1+ | standard** [Документация PHP](https://www.php.net/manual/en/features.file-upload.multiple.php)

При загрузке каталога одного имени файла недостаточно, чтобы восстановить вложенные папки. В `full_path` клиент передаёт относительный путь файла, а из `$_FILES` можно его прочитать. В примере берём путь первого файла, если он вообще пришёл.

<!-- example: fragment -->
```php
# Файлы пришли из поля загрузки каталога files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;
var_dump( $relative_path );
```

Этот путь задаёт клиент, доверять ему нельзя. Перед сохранением проверяем, что он не абсолютный и не содержит переходов в родительские каталоги, иначе файл может попасть за пределы нужной папки.

<a id="cookie-options"></a>

## Параметры cookie и `Partitioned`

**PHP 7.3+ параметры / PHP 8.5+ `partitioned` | standard** [Документация PHP](https://www.php.net/manual/en/function.setcookie.php)

Массив параметров `setcookie()` удобнее читать по именам: сразу видно путь, `secure`, `httponly` и `samesite`. С PHP 8.5 можно добавить `partitioned`. В клиентах с поддержкой этой возможности cookie хранится отдельно для каждого сайта верхнего уровня, внутри которого открыт виджет.

<!-- example: fragment -->
```php
# Отправляем по HTTPS до вывода; $token уже сгенерирован.
setcookie( 'widget_session', $token, [
	'path'        => '/',
	'secure'      => true,
	'httponly'    => true,
	'samesite'    => 'None',
	'partitioned' => true,
] );
```

Для `partitioned` нужен `secure`, иначе PHP выбросит `ValueError`. Для `SameSite=None` клиентам тоже требуется `Secure`. В примере отправляем cookie по HTTPS и делаем это до вывода тела ответа.

<a id="read-only-session"></a>

## Читаем сессию и сразу отпускаем блокировку

**PHP 7.0+ | session** [Документация PHP](https://www.php.net/manual/en/function.session-start.php) | [Реализация PHP: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c) | [Реализация PHP: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c)

Если запросу нужно только прочитать данные сессии, можно передать `read_and_close`. PHP загрузит `$_SESSION` и сразу закроет сессию. Стандартный файловый обработчик при этом отпустит блокировку, чтобы другие запросы этой сессии не ждали окончания нашей работы.

<!-- example: fragment -->
```php
# Настройки сессии уже заданы, вывод ответа ещё не начался.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# Если дальше менять $_SESSION, эти изменения сами не сохранятся.
```

Последующие изменения `$_SESSION` автоматически не сохранятся. Есть и нюанс файлового обработчика: время файла при таком чтении не обновляется. Поэтому одни только активные запросы на чтение могут не спасти сессию от удаления сборщиком мусора.
