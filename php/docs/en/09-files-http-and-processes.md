# Files, HTTP and processes

[English](../en/09-files-http-and-processes.md) | [Українська](../uk/09-files-http-and-processes.md) | [Русский](../ru/09-files-http-and-processes.md)

[Contents](../../README.md)

Commands, uploads, request bodies and HTTP clients are boundaries where explicit data handling helps. Examples involving a server, files or extensions state their required context in the text.

**In this chapter**

- [Process arguments as an array](#process-arguments)
- [Flush file data with fsync()](#flush-to-disk)
- [Several stream options in one call](#stream-options)
- [Parse form bodies outside POST](#request-body)
- [Read and modify URI objects](#uri-objects)
- [cURL handles are objects](#curl-objects)
- [Upload a string as a file](#curl-string-file)
- [Reuse cURL data between requests](#persistent-curl-share)
- [List handles in a multi handle](#multi-handles)
- [Read the last stream response headers](#http-response-headers)
- [Directory uploads and full_path](#upload-paths)
- [Cookie options and Partitioned](#cookie-options)
- [Read a session and release its lock](#read-only-session)

<a id="process-arguments"></a>

## Process arguments as an array

**PHP 7.4+ | standard**

An array separates the executable from its arguments and avoids sending a combined command through a shell.

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

Choose a fixed executable and validate arguments according to that program: avoiding a shell does not prevent unwanted program options. Windows escaping assumes normal VC runtime argument rules. This small child only writes stdout; a general runner also needs time/output limits and concurrent draining of stdout and stderr. [PHP manual](https://www.php.net/manual/en/function.proc-open.php).

<a id="flush-to-disk"></a>

## Flush file data with fsync()

**PHP 8.1+ | standard**

`fflush()` flushes PHP buffering. `fsync()` requests synchronization of file data and metadata; `fdatasync()` focuses on data and metadata needed to retrieve it.

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

Use it for a concrete durability requirement: synchronizing every small write can be expensive. It does not replace locking or partial-write checks. Not every stream supports synchronization. [PHP manual](https://www.php.net/manual/en/function.fsync.php).

<a id="stream-options"></a>

## Several stream options in one call

**PHP 8.3+ | standard**

`stream_context_set_options()` applies a nested options array to an existing context. The plural name gives the operation a clear API.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

The older singular function also had an array overload; this is an explicit alternative. Creating a context does not send a request. [PHP manual](https://www.php.net/manual/en/function.stream-context-set-options.php).

<a id="request-body"></a>

## Parse form bodies outside POST

**PHP 8.4+ | standard**

`request_parse_body()` returns field and upload arrays for form-encoded or multipart input. It is useful in PUT and PATCH handlers.

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

Supported types are application/x-www-form-urlencoded and multipart/form-data, not JSON. Call before reading php://input: the body is consumed once and is not buffered there. The function does not update superglobals. Handle RequestParseBodyException at the HTTP boundary and validate the parsed values. [PHP manual](https://www.php.net/manual/en/function.request-parse-body.php).

<a id="uri-objects"></a>

## Read and modify URI objects

**PHP 8.5+ | uri**

The extension provides distinct RFC 3986 and WHATWG URL models. Select the protocol or browser rules you need. The RFC model below creates a modified copy.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog
```

Malformed input can throw Uri\InvalidUriException. A valid URI is not automatically a safe request destination: restrict schemes, hosts and addresses separately. Use documented raw or normalized serialization instead of assembling components manually. [PHP manual](https://www.php.net/manual/en/class.uri-rfc3986-uri.php).

<a id="curl-objects"></a>

## cURL handles are objects

**PHP 8.0+ | curl**

curl_init() returns CurlHandle instead of a resource. Multi and share handles also have classes. Existing curl functions still work with them.

<!-- example: fragment -->
```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

An old is_resource() test no longer proves initialization succeeded or failed. Dedicated classes also allow typed parameters. [PHP manual](https://www.php.net/manual/en/function.curl-init.php).

<a id="curl-string-file"></a>

## Upload a string as a file

**PHP 8.1+ | curl**

CURLStringFile turns bytes already in memory into a multipart upload. Generated reports do not need a temporary file just for cURL.

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

Arguments are contents, upload filename, then MIME type. Memory is still required; use CURLFile for an existing large file. Let cURL generate the multipart boundary. [PHP manual](https://www.php.net/manual/en/class.curlstringfile.php).

<a id="persistent-curl-share"></a>

## Reuse cURL data between requests

**PHP 8.5+ | curl**

curl_share_init_persistent() can retain shared DNS and connection data beyond one request in a long-lived worker. The same options reuse a matching share handle.

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

This is worker-local reuse, not a pool shared by all workers. CURL_LOCK_DATA_COOKIE is forbidden: different requests may belong to different users. Persistence does not cancel server timeouts. [PHP manual](https://www.php.net/manual/en/function.curl-share-init-persistent.php).

<a id="multi-handles"></a>

## List handles in a multi handle

**PHP 8.5+ | curl**

curl_multi_get_handles() lists the easy handles currently attached to a multi handle, avoiding a second array maintained only for that purpose.

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

Listing membership does not execute requests or report completion. Progress and transfer results still come from the execution and information APIs. [PHP manual](https://www.php.net/manual/en/function.curl-multi-get-handles.php).

<a id="http-response-headers"></a>

## Read the last stream response headers

**PHP 8.4+ | standard**

http_get_last_response_headers() reads headers stored by the HTTP stream wrapper. The paired clear function makes their lifetime explicit.

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

The result is an array, or null when nothing is stored. This is not a cURL header API. Redirects can produce multiple response blocks; this example disables them and uses a fixed URL. [PHP manual](https://www.php.net/manual/en/function.http-get-last-response-headers.php).

<a id="upload-paths"></a>

## Directory uploads and full_path

**PHP 8.1+ | standard**

PHP exposes a client-supplied relative path for directory uploads. After validation, it can help reconstruct the folder tree.

<!-- example: fragment -->
```php
# Expected field name: files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;

if( !is_string($relative_path) )
	throw new InvalidArgumentException( 'Missing upload path' );

# Metadata only: do not use directly as a storage path.
var_dump( $relative_path );
```

The browser supplies the path; PHP does not prove it is genuine. Reject absolute paths, traversal components and unexpected separators. Storage also needs upload-error and content checks, and move_uploaded_file(). [PHP manual](https://www.php.net/manual/en/features.file-upload.multiple.php).

<a id="cookie-options"></a>

## Cookie options and Partitioned

**PHP 7.3+ options / PHP 8.5+ partitioned | standard**

An options array names each setting. PHP 8.5 adds partitioned, requesting cookie storage partitioned by the top-level site in supporting clients.

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

Partitioned requires Secure or PHP throws ValueError. SameSite=None also requires Secure for client acceptance. This combination serves intentional cross-site embedding, not every session policy. A true result only means PHP could send the header. [PHP manual](https://www.php.net/manual/en/function.setcookie.php).

<a id="read-only-session"></a>

## Read a session and release its lock

**PHP 7.0+ | session**

Pass read_and_close to session_start() when the request only needs existing session data. PHP reads the data into $_SESSION and immediately closes the session. With the default file handler, this releases the lock so other requests for the same session do not wait for the rest of this request.

<!-- example: fragment -->
```php
# HTTP handler, before output; application session settings are already configured.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# The session is already closed; the rest of this request does not hold its lock.
```

Use this for reading, not for a request that must save session changes: later assignments to $_SESSION are not persisted automatically after this close. It can still create a new session if necessary; the option does not mean only accept an existing session. The startup failure return became consistently false from PHP 7.1. For an updating request, use ordinary session_start(), make the changes and call session_write_close() as soon as the session updates are complete. [PHP manual](https://www.php.net/manual/en/function.session-start.php).

For the files handler, read-and-close skips the normal timestamp refresh. Requests that only read this way may therefore leave the session file old enough for garbage collection, even while the user is active. Account for the retention policy of the actual save handler. This follows the read-and-close path and file-handler timestamp/GC logic in the PHP implementation. [PHP implementation: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c), [PHP implementation: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c).
