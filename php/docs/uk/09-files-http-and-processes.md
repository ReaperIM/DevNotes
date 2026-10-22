# Файли, HTTP і процеси

[English](../en/09-files-http-and-processes.md) | [Українська](../uk/09-files-http-and-processes.md) | [Русский](../ru/09-files-http-and-processes.md)

[Зміст](../../README.uk.md)

Команди, завантаження, тіла запитів та HTTP-клієнти потребують явної роботи з даними. Для прикладів, що залежать від сервера, файлів чи розширень, потрібне оточення описано в тексті.

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

**PHP 7.4+ | standard**

Масив відокремлює виконуваний файл від аргументів і дозволяє не передавати зібрану команду оболонці.

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

Виконуваний файл задавайте явно, а аргументи перевіряйте за правилами програми: відсутність оболонки не забороняє небажані параметри самої програми. Екранування Windows передбачає звичайні правила VC runtime. Цей малий процес пише лише у stdout; універсальний запуск потребує обмежень часу й обсягу та паралельного читання stdout і stderr. [Документація PHP](https://www.php.net/manual/en/function.proc-open.php).

<a id="flush-to-disk"></a>

## Запис даних на диск через fsync()

**PHP 8.1+ | standard**

Функція `fflush()` скидає буфер PHP. `fsync()` просить синхронізувати дані й метадані файлу; `fdatasync()` зосереджується на даних і метаданих, потрібних для їх читання.

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

Застосовуйте для конкретної вимоги надійного запису: синхронізація кожного малого запису може бути дорогою. Вона не замінює блокування й перевірку неповного запису. Не кожен потік підтримує синхронізацію. [Документація PHP](https://www.php.net/manual/en/function.fsync.php).

<a id="stream-options"></a>

## Кілька параметрів потоку одним викликом

**PHP 8.3+ | standard**

Функція `stream_context_set_options()` застосовує вкладений масив параметрів до наявного контексту. Множина в назві надає операції зрозумілий API.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

Стара функція в однині також мала варіант з масивом; це явна альтернатива. Створення контексту не надсилає запиту. [Документація PHP](https://www.php.net/manual/en/function.stream-context-set-options.php).

<a id="request-body"></a>

## Розбір тіла форми для інших HTTP-методів

**PHP 8.4+ | standard**

Функція `request_parse_body()` повертає масиви полів і файлів для даних форми або multipart. Це зручно в обробниках PUT і PATCH.

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

Підтримуються application/x-www-form-urlencoded і multipart/form-data, а не JSON. Викликайте до читання php://input: тіло споживається один раз і не буферизується там. Функція не змінює суперглобальні змінні. Обробляйте RequestParseBodyException на HTTP-межі та перевіряйте розібрані значення. [Документація PHP](https://www.php.net/manual/en/function.request-parse-body.php).

<a id="uri-objects"></a>

## Читання та зміна URI-об’єктів

**PHP 8.5+ | uri**

Розширення має окремі моделі RFC 3986 і WHATWG URL. Вибирайте потрібні правила протоколу або браузера. RFC-модель нижче створює змінену копію.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog
```

Некоректні дані можуть викликати Uri\InvalidUriException. Правильний URI не є автоматично безпечною адресою запиту: окремо обмежуйте схеми, хости й адреси. Користуйтеся задокументованим початковим або нормалізованим рядком замість ручної збірки компонентів. [Документація PHP](https://www.php.net/manual/en/class.uri-rfc3986-uri.php).

<a id="curl-objects"></a>

## Дескриптори cURL стали об’єктами

**PHP 8.0+ | curl**

curl_init() повертає CurlHandle замість ресурсу. Дескриптори multi й share також мають класи. Наявні curl-функції і далі працюють із ними.

<!-- example: fragment -->
```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

Стара перевірка is_resource() більше не доводить успіх або невдачу ініціалізації. Окремі класи також дозволяють типізувати параметри. [Документація PHP](https://www.php.net/manual/en/function.curl-init.php).

<a id="curl-string-file"></a>

## Завантаження рядка як файлу

**PHP 8.1+ | curl**

CURLStringFile перетворює байти з пам’яті на файл для multipart-завантаження. Створеному звіту не потрібен тимчасовий файл лише заради cURL.

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

Аргументи: вміст, ім’я для завантаження, потім MIME-тип. Пам’ять усе одно потрібна; для великого готового файлу використовуйте CURLFile. Нехай cURL сам створює multipart boundary. [Документація PHP](https://www.php.net/manual/en/class.curlstringfile.php).

<a id="persistent-curl-share"></a>

## Повторне використання даних cURL між запитами

**PHP 8.5+ | curl**

curl_share_init_persistent() може зберігати спільні DNS-дані та з’єднання довше за один запит у довгоживучому процесі. Ті самі параметри повторно використовують відповідний дескриптор.

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

Це повторне використання в межах процесу, а не пул усіх процесів. CURL_LOCK_DATA_COOKIE заборонений: різні запити можуть належати різним користувачам. Постійність не скасовує тайм-аути сервера. [Документація PHP](https://www.php.net/manual/en/function.curl-share-init-persistent.php).

<a id="multi-handles"></a>

## Список дескрипторів у multi handle

**PHP 8.5+ | curl**

curl_multi_get_handles() показує звичайні дескриптори, додані до multi handle, і дозволяє не вести другий масив лише для їх обліку.

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

Перегляд складу набору не виконує запити й не повідомляє про завершення. Стан і результати далі отримують через API виконання та читання інформації. [Документація PHP](https://www.php.net/manual/en/function.curl-multi-get-handles.php).

<a id="http-response-headers"></a>

## Читання останніх заголовків HTTP-потоку

**PHP 8.4+ | standard**

http_get_last_response_headers() читає заголовки HTTP-обгортки потоків. Парна функція очищення дозволяє явно керувати часом їхнього життя.

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

Результат є масивом або null, якщо нічого не збережено. Це не API заголовків cURL. Перенаправлення можуть створити кілька блоків відповіді; приклад вимикає їх і має сталий URL. [Документація PHP](https://www.php.net/manual/en/function.http-get-last-response-headers.php).

<a id="upload-paths"></a>

## Завантаження каталогів і full_path

**PHP 8.1+ | standard**

PHP передає відносний шлях клієнта для завантаження каталогу. Після перевірки він допомагає відновити дерево папок.

<!-- example: fragment -->
```php
# Expected field name: files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;

if( !is_string($relative_path) )
	throw new InvalidArgumentException( 'Missing upload path' );

# Metadata only: do not use directly as a storage path.
var_dump( $relative_path );
```

Шлях надсилає браузер; PHP не підтверджує його справжність. Відхиляйте абсолютні шляхи, переходи до батьківського каталогу й неочікувані роздільники. Для збереження також потрібні перевірки помилок, вмісту та move_uploaded_file(). [Документація PHP](https://www.php.net/manual/en/features.file-upload.multiple.php).

<a id="cookie-options"></a>

## Параметри cookie та Partitioned

**PHP 7.3+ options / PHP 8.5+ partitioned | standard**

Масив параметрів називає кожне налаштування. PHP 8.5 додає partitioned, що просить сумісний клієнт розділяти cookie за сайтом верхнього рівня.

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

Partitioned потребує Secure, інакше PHP викидає ValueError. SameSite=None також потребує Secure для прийняття клієнтом. Це поєднання для свідомо вбудованого міжсайтового сценарію, а не кожного сеансу. true означає лише, що PHP зміг надіслати заголовок. [Документація PHP](https://www.php.net/manual/en/function.setcookie.php).

<a id="read-only-session"></a>

## Читання сеансу зі звільненням блокування

**PHP 7.0+ | session**

Передайте read_and_close у session_start(), якщо запиту потрібно лише прочитати дані сеансу. PHP завантажує їх у $_SESSION та одразу закриває сеанс. Для стандартного файлового обробника це звільняє блокування: інші запити цього сеансу не чекають завершення решти поточного запиту.

<!-- example: fragment -->
```php
# HTTP handler, before output; application session settings are already configured.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# The session is already closed; the rest of this request does not hold its lock.
```

Використовуйте цей режим для читання, а не для запиту, що має зберігати зміни: подальші присвоєння у $_SESSION після такого закриття автоматично не збережуться. За потреби може створитися новий сеанс; параметр не означає приймати лише наявний. Послідовний результат false при невдалому старті з’явився в PHP 7.1. Для зміни даних використовуйте звичайний session_start(), внесіть зміни й викличте session_write_close(), щойно зміни сеансу завершено. [Документація PHP](https://www.php.net/manual/en/function.session-start.php).

Для файлового обробника read-and-close пропускає звичайне оновлення часу файлу. Якщо запити лише читають у цьому режимі, файл може достатньо застаріти для збирання сміття навіть за активного користувача. Враховуйте політику зберігання свого обробника. Це випливає зі шляху read-and-close та логіки часу файлу й GC у реалізації PHP. [Реалізація PHP: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c), [Реалізація PHP: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c).
