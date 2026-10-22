# JSON, XML, HTML and serialization

[English](../en/06-json-xml-and-serialization.md) | [Українська](../uk/06-json-xml-and-serialization.md) | [Русский](../ru/06-json-xml-and-serialization.md)

[Contents](../../README.md)

Separate three questions: is the document syntactically valid, does it contain the expected data, and is it safe in its destination context? A parser answers the first question. These examples show how to keep the other checks explicit.

- [Choose a policy for invalid UTF-8 in JSON](#invalid-utf8-json)
- [Handle JSON failures with exceptions](#json-exceptions)
- [Validate JSON only when you do not need its contents](#json-validation)
- [Keep numeric IDs and JSON shapes intact](#json-shape)
- [Encode backed enums as their values](#json-enums)
- [Control object state with `__serialize()` and `__unserialize()`](#object-serialization)
- [Use DOM operations for attributes and text](#dom-convenience)
- [Parse modern HTML with `Dom\HTMLDocument`](#html5-dom)
- [Quote XPath string values correctly](#xpath-quote)
- [Insert and read HTML fragments](#html-fragments)
- [Create XML readers and writers with named factories](#xml-factories)

<a id="invalid-utf8-json"></a>

## Choose a policy for invalid UTF-8 in JSON

**PHP 7.2+ | json**

From PHP 7.2, `JSON_INVALID_UTF8_SUBSTITUTE` and `JSON_INVALID_UTF8_IGNORE` let `json_encode()` and `json_decode()` handle malformed UTF-8 deliberately. `SUBSTITUTE` replaces invalid sequences with the visible replacement character U+FFFD. `IGNORE` discards them, which can silently join text that was separated by bad bytes.

```php
$text = "A\xFFB";
$rejected = json_encode( $text );
$error = json_last_error();
$substituted = json_encode( $text, JSON_INVALID_UTF8_SUBSTITUTE );
$ignored = json_encode( $text, JSON_INVALID_UTF8_IGNORE );

var_dump( $rejected === false && $error === JSON_ERROR_UTF8 ); # bool(true)
echo $substituted . PHP_EOL; # "A\ufffdB"
echo $ignored . PHP_EOL; # "AB"
echo bin2hex( $text ) . PHP_EOL; # 41ff42
```

**Remember:** Keep rejection as the default when data must remain exact, such as an identifier or a signed payload. If degraded human text is acceptable, prefer `SUBSTITUTE` so the loss remains visible; use `IGNORE` only when deletion is an explicit import rule. The example captures `json_last_error()` before later calls replace it. `JSON_THROW_ON_ERROR` is a separate PHP 7.3 addition.

These flags affect the values processed by the JSON call. They do not change the original `$text`, convert an arbitrary legacy encoding to UTF-8, or change the encoding of a PHP or Markdown file.

[PHP manual: JSON flags](https://www.php.net/manual/en/json.constants.php)


<a id="json-exceptions"></a>

## Handle JSON failures with exceptions

**PHP 7.3+ | json**

`JSON_THROW_ON_ERROR` makes encoding and decoding failures throw `JsonException`. Valid JSON null is then clearly different from a parsing failure, without relying on global `json_last_error()` state.

```php
try
{
	$value = json_decode( 'null', true, 512, JSON_THROW_ON_ERROR );
	var_dump( $value ); # NULL
	json_decode( '{"broken":}', true, 512, JSON_THROW_ON_ERROR );
}
catch( JsonException $error )
{
	echo 'Invalid JSON' . PHP_EOL;
}
```

**Remember:** The flag is available to `json_encode()` too. Do not expose raw payloads or internal exception details in a public response. Use the same flag consistently rather than suppressing errors with @.

[PHP manual: json_decode()](https://www.php.net/manual/en/function.json-decode.php) | [PHP manual: JsonException](https://www.php.net/manual/en/class.jsonexception.php)

<a id="json-validation"></a>

## Validate JSON only when you do not need its contents

**PHP 8.3+ | json**

`json_validate()` checks JSON syntax without constructing the decoded array or object. Use it when storing or forwarding a document that only needs a syntax check.

```php
$json = '{"active":true}';
var_dump( json_validate($json) ); # bool(true)
var_dump( json_validate('{"active":}') ); # bool(false)
```

**Remember:** If you need the decoded data immediately, call `json_decode()` once with `JSON_THROW_ON_ERROR`. Validating first parses the document twice. Syntax validity does not establish your required fields, types or permissions.

[PHP manual: json_validate()](https://www.php.net/manual/en/function.json-validate.php)

<a id="json-shape"></a>

## Keep numeric IDs and JSON shapes intact

**PHP 7.3+ | json | Background**

Successful decoding can return a scalar, list, object or null. Check the shape your API actually accepts. Combine `JSON_BIGINT_AS_STRING` with exception handling when an external integer can exceed the platform integer range.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );

if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

var_dump( $data['id'] ); # string(30) "123456789012345678901234567890"
```

**Remember:** `JSON_BIGINT_AS_STRING` predates PHP 7; the 7.3 minimum here is for `JSON_THROW_ON_ERROR`. Do not turn identifiers into floats. Converting JSON objects to associative arrays can also change empty-object/list round trips.

[PHP manual: JSON flags](https://www.php.net/manual/en/json.constants.php) | [PHP manual: json_decode()](https://www.php.net/manual/en/function.json-decode.php)

<a id="json-enums"></a>

## Encode backed enums as their values

**PHP 8.1+ | json / Core**

A backed enum has a stable scalar value that JSON can encode directly. This makes an enum useful for an internal state with an explicit representation in an API.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}

echo json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR ) . PHP_EOL;
# {"status":"ready"}
```

**Remember:** A pure enum without a backing value cannot be encoded this way. On input, decode the scalar first and use `tryFrom()` or `from()`; `json_decode()` does not recreate an enum object.

[PHP manual: enum serialization](https://www.php.net/manual/en/language.enumerations.serialization.php)

<a id="object-serialization"></a>

## Control object state with `__serialize()` and `__unserialize()`

**PHP 7.4+ | Core**

These magic methods explicitly define the state used by PHP serialization. Keep the payload small and validate its shape when restoring it. `__unserialize()` runs without calling the constructor, so constructor validation alone is insufficient.

```php
class SavedCounter
{
	private int $value;

	public function __construct( int $value )
	{
		$this->value = $value;
	}

	public function __serialize(): array
	{
		return ['value' => $this->value];
	}

	public function __unserialize( array $data ): void
	{
		if( !isset($data['value']) || !is_int($data['value']) )
			throw new UnexpectedValueException( 'Invalid counter state' );

		$this->value = $data['value'];
	}

	public function value(): int
	{
		return $this->value;
	}
}

$payload = serialize( new SavedCounter(7) );
$counter = unserialize( $payload, ['allowed_classes' => [SavedCounter::class]] );
echo $counter->value() . PHP_EOL; # 7
```

**Remember:** The example only restores its own trusted payload. Never feed untrusted data to `unserialize()`; allowed_classes is not a general safety guarantee. Prefer JSON for external data. The old `Serializable` interface without these methods is deprecated from PHP 8.1.

[PHP manual: serialization methods](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [PHP manual: unserialize()](https://www.php.net/manual/en/function.unserialize.php)

<a id="dom-convenience"></a>

## Use DOM operations for attributes and text

**PHP 8.3+ | dom**

`DOMElement` gained convenience methods such as `toggleAttribute()`, `insertAdjacentText()` and `insertAdjacentElement()`. `DOMNode::contains()` checks ancestry without a manual parent loop. Text insertion creates text, not parsed markup.

```php
$document = new DOMDocument();
$document->loadXML( '<root><item>PHP</item></root>' );
$item = $document->getElementsByTagName( 'item' )->item( 0 );
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
echo $document->saveXML( $item ) . PHP_EOL;
# <item hidden="">PHP &amp; XML</item>
```

**Remember:** HTML boolean attributes are meaningful by presence, not by a string such as "false". For untrusted text use text operations; inserting raw HTML is a different operation.

[PHP manual: DOM additions](https://www.php.net/manual/en/migration83.new-functions.php)

<a id="html5-dom"></a>

## Parse modern HTML with `Dom\HTMLDocument`

**PHP 8.4+ | dom**

`Dom\HTMLDocument` parses HTML using the modern HTML standard. Its new Dom namespace also offers familiar selectors. This is useful for extracting or transforming an HTML document without pretending it is XML.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><html><head><title>Notes</title></head>'
	. '<body><main id="content"><p>PHP</p></main></body></html>'
);
$element = $document->querySelector( '#content p' );
echo $element->textContent . PHP_EOL; # PHP
```

**Remember:** New Dom objects and legacy `DOMDocument`/`DOMElement` objects are different APIs. `querySelector()` can return null when no element matches. Parsing HTML does not sanitize it for publication.

[PHP manual: Dom\HTMLDocument](https://www.php.net/manual/en/class.dom-htmldocument.php)

<a id="xpath-quote"></a>

## Quote XPath string values correctly

**PHP 8.4+ | dom**

`DOMXPath::quote()` creates an XPath string literal, including the case where a value contains both quote styles. Use it for values instead of interpolating raw text into an XPath expression.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
echo $xpath->evaluate( 'string(' . $query . ')' ) . PHP_EOL; # 7
```

**Remember:** Quoting protects a literal value, not an arbitrary user-supplied XPath path or function name. Keep expression structure under application control.

[PHP manual: DOMXPath::quote()](https://www.php.net/manual/en/domxpath.quote.php)

<a id="html-fragments"></a>

## Insert and read HTML fragments

**PHP 8.5+ | dom**

`Dom\Element::insertAdjacentHTML()` parses an HTML fragment relative to an element. outerHTML includes the element itself as well as its contents. This avoids constructing an extra document solely to insert a fragment.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><html><head><title>Notes</title></head>'
	. '<body><div id="box"></div></body></html>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
echo $box->outerHTML . PHP_EOL; # <div id="box"><strong>PHP</strong></div>
```

**Remember:** The modern Dom API takes a `Dom\AdjacentPosition` case: BeforeBegin, AfterBegin, BeforeEnd or AfterEnd. Legacy `DOMElement` text insertion uses string positions instead. Raw untrusted HTML remains dangerous when rendered; use `insertAdjacentText()` or textContent for plain user text.

[PHP manual: DOM additions in 8.5](https://www.php.net/manual/en/migration85.new-functions.php) | [PHP manual: Dom\Element](https://www.php.net/manual/en/class.dom-element.php)

<a id="xml-factories"></a>

## Create XML readers and writers with named factories

**PHP 8.4+ | xmlreader / xmlwriter**

`XMLReader::fromString()` and `XMLWriter::toMemory()` describe both the object and its source or destination in one call. Related `fromStream()`/`fromUri()` and `toStream()`/`toUri()` factories cover streams and files.

```php
$reader = XMLReader::fromString( '<items><item id="7"/></items>' );

while( $reader->read() )
{
	if( $reader->nodeType === XMLReader::ELEMENT && $reader->name === 'item' )
		echo $reader->getAttribute( 'id' ) . PHP_EOL; # 7
}

$reader->close();
$writer = XMLWriter::toMemory();
$writer->writeElement( 'name', 'PHP & XML' );
echo $writer->outputMemory() . PHP_EOL; # <name>PHP &amp; XML</name>
```

**Remember:** `fromString()` keeps the source string in memory; use a stream reader for a genuinely large source. For untrusted XML do not enable external entity expansion or DTD loading without a justified, controlled requirement.

[PHP manual: XMLReader::fromString()](https://www.php.net/manual/en/xmlreader.fromstring.php) | [PHP manual: XMLWriter::toMemory()](https://www.php.net/manual/en/xmlwriter.tomemory.php)
