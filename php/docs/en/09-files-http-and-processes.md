# Files, HTTP and processes

[EN](../en/09-files-http-and-processes.md) | [UK](../uk/09-files-http-and-processes.md) | [RU](../ru/09-files-http-and-processes.md)

[Contents](../../README.md)

**In this chapter**

- [Process arguments as an array](#process-arguments)
- [Flush file data with `fsync()`](#flush-to-disk)
- [Several stream options in one call](#stream-options)
- [Parse form bodies outside `POST`](#request-body)
- [Read and modify URI objects](#uri-objects)
- [cURL handles are objects](#curl-objects)
- [Upload a string as a file](#curl-string-file)
- [Reuse cURL data between requests](#persistent-curl-share)
- [List handles in a multi handle](#multi-handles)
- [Read the last stream response headers](#http-response-headers)
- [Directory uploads and `full_path`](#upload-paths)
- [Cookie options and `Partitioned`](#cookie-options)
- [Read a session and release its lock](#read-only-session)

<a id="process-arguments"></a>

## Process arguments as an array

**PHP 7.4+ | standard** [PHP manual](https://www.php.net/manual/en/function.proc-open.php)

An argument array keeps the executable and its arguments separate and bypasses shell parsing. This is useful when passing values to a known program.

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

On Windows, array argument escaping assumes VC runtime rules; program options still need their own validation.

<a id="flush-to-disk"></a>

## Flush file data with `fsync()`

**PHP 8.1+ | standard** [PHP manual](https://www.php.net/manual/en/function.fsync.php)

`fflush()` flushes PHP buffering. `fsync()` synchronizes file data and metadata; `fdatasync()` synchronizes data and only the metadata needed to retrieve it.

<!-- example: fragment -->
```php
# $stream is an open local file, for example fopen($log_path, 'ab').
$data = "checkpoint\n";

if( fwrite($stream, $data) !== strlen($data) || !fsync($stream) )
	throw new RuntimeException( 'Cannot persist checkpoint' );
```

Not every stream supports synchronization.

<a id="stream-options"></a>

## Several stream options in one call

**PHP 8.3+ | standard** [PHP manual](https://www.php.net/manual/en/function.stream-context-set-options.php)

`stream_context_set_options()` applies a nested options array to an existing stream context.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

The older singular `stream_context_set_option()` already supported an array overload.

<a id="request-body"></a>

## Parse form bodies outside `POST`

**PHP 8.4+ | standard** [PHP manual](https://www.php.net/manual/en/function.request-parse-body.php)

`request_parse_body()` returns form fields and uploads for handlers such as `PUT` and `PATCH`, without populating superglobals.

<!-- example: fragment -->
```php
# HTTP handler receiving a form-encoded PUT request.
[$fields, $files] = request_parse_body( [
	'post_max_size' => '2M',
	'upload_max_filesize' => '1M',
] );
$name = $fields['name'] ?? null;
```

Call before reading `php://input`: the body is consumed once, is not buffered there, and JSON is unsupported.

<a id="uri-objects"></a>

## Read and modify URI objects

**PHP 8.5+ | uri** [PHP manual](https://www.php.net/manual/en/class.uri-rfc3986-uri.php)

The URI extension provides separate RFC 3986 and WHATWG models for URI or browser URL rules. In the RFC model below, `withPath()` returns a modified copy.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog: the original is unchanged
```

Malformed input can throw `Uri\InvalidUriException`.

<a id="curl-objects"></a>

## cURL handles are objects

**PHP 8.0+ | curl** [PHP manual](https://www.php.net/manual/en/function.curl-init.php)

cURL easy, multi and share handles have classes instead of `resource` types.

```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

`curl_init()` can still return `false`; `is_resource()` is no longer an initialization check.

<a id="curl-string-file"></a>

## Upload a string as a file

**PHP 8.1+ | curl** [PHP manual](https://www.php.net/manual/en/class.curlstringfile.php)

`CURLStringFile` uploads in-memory bytes as a multipart file without creating a temporary file.

<!-- example: fragment -->
```php
$handle = curl_init( 'https://example.com/upload' );

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

$file = new CURLStringFile( "id,total\n7,42\n", 'report.csv', 'text/csv' );
curl_setopt( $handle, CURLOPT_POSTFIELDS, ['report' => $file] );
# curl_exec($handle) would send the multipart request.
```

Constructor arguments are contents, upload filename and MIME type; use `CURLFile` for an existing large file.

<a id="persistent-curl-share"></a>

## Reuse cURL data between requests

**PHP 8.5+ | curl** [PHP manual](https://www.php.net/manual/en/function.curl-share-init-persistent.php)

`curl_share_init_persistent()` reuses shared DNS and connection data across requests in the same long-lived process.

<!-- example: fragment -->
```php
$share = curl_share_init_persistent( [CURL_LOCK_DATA_DNS, CURL_LOCK_DATA_CONNECT] );
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_setopt( $handle, CURLOPT_SHARE, $share );
```

The share is process-local, not a pool across workers; `CURL_LOCK_DATA_COOKIE` is forbidden.

<a id="multi-handles"></a>

## List handles in a multi handle

**PHP 8.5+ | curl** [PHP manual](https://www.php.net/manual/en/function.curl-multi-get-handles.php)

`curl_multi_get_handles()` lists easy handles currently attached to a multi handle.

```php
$multi = curl_multi_init();
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_multi_add_handle( $multi, $handle );
var_dump( count(curl_multi_get_handles($multi)) ); # int(1)
curl_multi_remove_handle( $multi, $handle );
```

Listing handles neither executes requests nor reports transfer completion.

<a id="http-response-headers"></a>

## Read the last stream response headers

**PHP 8.4+ | standard** [PHP manual](https://www.php.net/manual/en/function.http-get-last-response-headers.php)

`http_get_last_response_headers()` reads the HTTP stream wrapper's stored headers, or `null` when none are stored.

<!-- example: fragment -->
```php
$context = stream_context_create( ['http' => ['timeout' => 5]] );
http_clear_last_response_headers();
$body = file_get_contents( 'https://example.com/', false, $context );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();
var_dump( $headers ); # Response-header lines, or null.
```

Redirects can produce multiple response-header blocks.

<a id="upload-paths"></a>

## Directory uploads and `full_path`

**PHP 8.1+ | standard** [PHP manual](https://www.php.net/manual/en/features.file-upload.multiple.php)

Directory uploads expose the client's relative path in `full_path`.

<!-- example: fragment -->
```php
# Directory upload field: files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;
var_dump( $relative_path );
```

This is untrusted metadata: reject absolute paths and traversal components before using it for storage.

<a id="cookie-options"></a>

## Cookie options and `Partitioned`

**PHP 7.3+ options / PHP 8.5+ `partitioned` | standard** [PHP manual](https://www.php.net/manual/en/function.setcookie.php)

Cookie options use named array entries; PHP 8.5 adds `partitioned` for storage partitioned by the top-level site in supporting clients.

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

`partitioned` requires `secure` or PHP throws `ValueError`; clients also require `Secure` for `SameSite=None`.

<a id="read-only-session"></a>

## Read a session and release its lock

**PHP 7.0+ | session** [PHP manual](https://www.php.net/manual/en/function.session-start.php) | [PHP implementation: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c) | [PHP implementation: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c)

`read_and_close` loads `$_SESSION` and immediately closes the session, releasing the default file handler's lock.

<!-- example: fragment -->
```php
# HTTP handler before output; session settings are already configured.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# Later changes to $_SESSION are not saved automatically.
```

The files handler skips the timestamp refresh, so read-only traffic can still leave an active session old enough for garbage collection.
