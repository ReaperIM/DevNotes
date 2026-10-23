# Files, HTTP and processes

[EN](../en/09-files-http-and-processes.md) | [UK](../uk/09-files-http-and-processes.md) | [RU](../ru/09-files-http-and-processes.md)

[Contents](../../README.md)

Start processes, read and send HTTP data, and work with uploads, cookies and sessions. The examples point out what must already be prepared and which results still need checking.

**In this chapter**

- [Process arguments as an array](#process-arguments)
- [Flush file data with `fsync()`](#flush-to-disk)
- [Several stream options in one call](#stream-options)
- [Parse form bodies outside `POST`](#request-body)
- [Change address parts through a URI object](#uri-objects)
- [cURL handles are objects](#curl-objects)
- [Upload a string as a file](#curl-string-file)
- [Reuse cURL data between requests](#persistent-curl-share)
- [Which handles have been added to cURL multi](#multi-handles)
- [Read the last stream response headers](#http-response-headers)
- [Directory uploads and `full_path`](#upload-paths)
- [Cookie options and `Partitioned`](#cookie-options)
- [Read a session and release the lock immediately](#read-only-session)

<a id="process-arguments"></a>

## Process arguments as an array

**PHP 7.4+ | standard** [PHP manual](https://www.php.net/manual/en/function.proc-open.php)

`proc_open()` can take the program and its arguments as an array. We do not have to build a command string for a shell to parse: the first element selects the program and the rest are its arguments. Here we start another PHP process and read its output from a pipe.

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

On Windows, array arguments are escaped according to VC runtime rules. Still check the arguments the program accepts: bypassing the shell does not stop someone from passing an unwanted option to the program itself.

<a id="flush-to-disk"></a>

## Flush file data with `fsync()`

**PHP 8.1+ | standard** [PHP manual](https://www.php.net/manual/en/function.fsync.php)

A successful buffered write does not mean the file has been synchronized to disk yet. `fflush()` flushes PHP's buffer, while `fsync()` synchronizes file data and metadata. `fdatasync()` synchronizes the data and only the metadata needed to read it.

<!-- example: fragment -->
```php
# $stream is already an open local file, for example fopen($log_path, 'ab').
$data = "checkpoint\n";

if( fwrite($stream, $data) !== strlen($data) || !fsync($stream) )
	throw new RuntimeException( 'Cannot persist checkpoint' );
```

Here we check both that the whole string was written and that synchronization succeeded: a partial checkpoint is not useful. Support depends on the stream, so this check is not suitable for every arbitrary stream.

<a id="stream-options"></a>

## Several stream options in one call

**PHP 8.3+ | standard** [PHP manual](https://www.php.net/manual/en/function.stream-context-set-options.php)

Use `stream_context_set_options()` to add several settings to an existing stream context in one call. The outer array keys select a wrapper, such as `http` or `ssl`, with its settings nested inside. Here we set a timeout, disable following redirects and keep TLS certificate checks enabled.

```php
$context = stream_context_create();
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'follow_location' => 0],
	'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
] );
var_dump( stream_context_get_options($context)['http']['timeout'] ); # int(5)
```

The `int(5)` output shows that the existing context now contains the timeout. The older `stream_context_set_option()` also accepted an array; the plural name does not mean this capability is entirely new.

<a id="request-body"></a>

## Parse form bodies outside `POST`

**PHP 8.4+ | standard** [PHP manual](https://www.php.net/manual/en/function.request-parse-body.php)

Use `request_parse_body()` for a form submitted with a method such as `PUT` or `PATCH`. It returns two arrays: form fields and uploaded files. It does not fill `$_POST` or `$_FILES`, so the example works with `$fields` and `$files` directly.

<!-- example: fragment -->
```php
# This handler receives a form through PUT, not JSON.
[$fields, $files] = request_parse_body( [
	'post_max_size' => '2M',
	'upload_max_filesize' => '1M',
] );
$name = $fields['name'] ?? null;
```

Call it before reading `php://input`. The request body is consumed once and is not buffered there after this call. The function parses form bodies, not JSON.

<a id="uri-objects"></a>

## Change address parts through a URI object

**PHP 8.5+ | uri** [PHP manual](https://www.php.net/manual/en/class.uri-rfc3986-uri.php)

Instead of assembling an address by hand, we can work with a URI object. The extension offers two models: RFC 3986 for URIs and WHATWG for browser-style URLs. This example uses the RFC model: `withPath()` returns a copy with a different path and keeps the rest of the address.

```php
$uri = new Uri\Rfc3986\Uri( 'https://example.com/catalog?page=2' );
$next = $uri->withPath( '/archive' );
echo $uri->getHost(), "\n"; # example.com
echo $next->toString(), "\n"; # https://example.com/archive?page=2
echo $uri->getPath(), "\n"; # /catalog: the original stays unchanged
```

`$next` now points to `/archive`, while the original `$uri` still has `/catalog`. An invalid address can throw `Uri\InvalidUriException`, so creating the object can also need error handling.

<a id="curl-objects"></a>

## cURL handles are objects

**PHP 8.0+ | curl** [PHP manual](https://www.php.net/manual/en/function.curl-init.php)

Since PHP 8.0, cURL returns objects instead of resources for easy, multi and share handles. An old `is_resource()` check therefore no longer tells us whether initialization succeeded. Here a valid `CurlHandle` passes `instanceof`, but `is_resource()` returns `false`.

```php
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

var_dump( $handle instanceof CurlHandle ); # bool(true)
var_dump( is_resource($handle) ); # bool(false)
```

`curl_init()` can still return `false` on failure. Check that result before using the handle.

<a id="curl-string-file"></a>

## Upload a string as a file

**PHP 8.1+ | curl** [PHP manual](https://www.php.net/manual/en/class.curlstringfile.php)

If the file contents are already in a string, there is no need to write a temporary file just to upload it. `CURLStringFile` presents those bytes as a file in a multipart request. Here the CSV lives in memory, but the receiver gets a file named `report.csv` with type `text/csv`.

<!-- example: fragment -->
```php
$handle = curl_init( 'https://example.com/upload' );

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

$file = new CURLStringFile( "id,total\n7,42\n", 'report.csv', 'text/csv' );
curl_setopt( $handle, CURLOPT_POSTFIELDS, ['report' => $file] );
# curl_exec($handle) sends the actual multipart request.
```

The constructor takes contents, upload filename and MIME type, in that order. For a large file already on disk, use `CURLFile` rather than loading it all into a string first.

<a id="persistent-curl-share"></a>

## Reuse cURL data between requests

**PHP 8.5+ | curl** [PHP manual](https://www.php.net/manual/en/function.curl-share-init-persistent.php)

In a long-running PHP process, DNS data and connections do not always need to be rebuilt for every request. `curl_share_init_persistent()` lets requests in that process reuse selected cURL data. Here an easy handle shares DNS data and connections.

<!-- example: fragment -->
```php
$share = curl_share_init_persistent( [CURL_LOCK_DATA_DNS, CURL_LOCK_DATA_CONNECT] );
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_setopt( $handle, CURLOPT_SHARE, $share );
```

The shared data belongs to one process; it is not a pool shared by every PHP process. Cookies cannot be shared this way: `CURL_LOCK_DATA_COOKIE` is not allowed for this function.

<a id="multi-handles"></a>

## Which handles have been added to cURL multi

**PHP 8.5+ | curl** [PHP manual](https://www.php.net/manual/en/function.curl-multi-get-handles.php)

Use `curl_multi_get_handles()` to get the easy handles added to a multi handle. Here we add one handle, see one item in the list, then remove it again.

```php
$multi = curl_multi_init();
$handle = curl_init();

if( $handle === false )
	throw new RuntimeException( 'Cannot initialize cURL' );

curl_multi_add_handle( $multi, $handle );
var_dump( count(curl_multi_get_handles($multi)) ); # int(1)
curl_multi_remove_handle( $multi, $handle );
```

Reading the list does not start any requests. It also does not tell us which transfers have finished; it only lists the handles currently added.

<a id="http-response-headers"></a>

## Read the last stream response headers

**PHP 8.4+ | standard** [PHP manual](https://www.php.net/manual/en/function.http-get-last-response-headers.php)

After an HTTP stream request, such as `file_get_contents()`, use `http_get_last_response_headers()` to read the response headers. It returns `null` if none are stored. Here we clear old headers before the request and clear them again after collecting the result.

<!-- example: fragment -->
```php
$context = stream_context_create( ['http' => ['timeout' => 5]] );
http_clear_last_response_headers();
$body = file_get_contents( 'https://example.com/', false, $context );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();
var_dump( $headers ); # Response-header lines, or null.
```

Redirects can leave several response-header blocks in the list. Do not assume that the entire array describes just one response.

<a id="upload-paths"></a>

## Directory uploads and `full_path`

**PHP 8.1+ | standard** [PHP manual](https://www.php.net/manual/en/features.file-upload.multiple.php)

When uploading a directory, filenames alone do not preserve its nested folders. The client sends each file's relative path in `full_path`, which we can read from `$_FILES`. Here we take the first file's path if it was supplied.

<!-- example: fragment -->
```php
# The files come from a directory upload field named files[].
$relative_path = $_FILES['files']['full_path'][0] ?? null;
var_dump( $relative_path );
```

The client controls this path, so do not trust it. Reject absolute paths and parent-directory traversal before saving, or a file could end up outside the intended folder.

<a id="cookie-options"></a>

## Cookie options and `Partitioned`

**PHP 7.3+ options / PHP 8.5+ `partitioned` | standard** [PHP manual](https://www.php.net/manual/en/function.setcookie.php)

An options array makes `setcookie()` easier to read: the path, `secure`, `httponly` and `samesite` are named explicitly. PHP 8.5 adds `partitioned`. In supporting clients, this keeps a widget's cookie storage separate for each top-level site where the widget appears.

<!-- example: fragment -->
```php
# Send over HTTPS before output; $token has already been generated.
setcookie( 'widget_session', $token, [
	'path'        => '/',
	'secure'      => true,
	'httponly'    => true,
	'samesite'    => 'None',
	'partitioned' => true,
] );
```

`partitioned` requires `secure`, otherwise PHP throws `ValueError`. Clients also require `Secure` for `SameSite=None`. The example sends the cookie over HTTPS before writing the response body.

<a id="read-only-session"></a>

## Read a session and release the lock immediately

**PHP 7.0+ | session** [PHP manual](https://www.php.net/manual/en/function.session-start.php) | [PHP implementation: session.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/session.c) | [PHP implementation: mod_files.c](https://github.com/php/php-src/blob/PHP-8.5/ext/session/mod_files.c)

If a request only needs to read the session, pass `read_and_close`. PHP loads `$_SESSION` and closes the session immediately. With the standard file handler, this releases the lock so other requests for that session do not wait for our work to finish.

<!-- example: fragment -->
```php
# Session settings are already configured; response output has not started.
if( !session_start(['read_and_close' => true]) )
	throw new RuntimeException( 'Cannot start session' );

$user_id = $_SESSION['user_id'] ?? null;
# Further changes to $_SESSION will not save themselves.
```

Later changes to `$_SESSION` are not saved automatically. There is also a file-handler detail: this kind of read does not update the file time. Even actively reading the session may therefore not prevent garbage collection from expiring it.
