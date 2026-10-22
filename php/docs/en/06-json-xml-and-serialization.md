# JSON, XML, HTML and serialization

[EN](../en/06-json-xml-and-serialization.md) | [UK](../uk/06-json-xml-and-serialization.md) | [RU](../ru/06-json-xml-and-serialization.md)

[Contents](../../README.md)

JSON syntax and data shape are separate checks; the DOM and XML examples cover parsing, text insertion and controlled document changes.

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

**PHP 7.2+ | json** | [PHP](https://www.php.net/manual/en/json.constants.php)

`JSON_INVALID_UTF8_SUBSTITUTE` replaces malformed UTF-8 with U+FFFD; `JSON_INVALID_UTF8_IGNORE` drops invalid bytes during encoding or decoding. These are policies for damaged text, not encoding conversion.

```php
$text = "A\xFFB";
$rejected = json_encode( $text ); # false
$error = json_last_error(); # JSON_ERROR_UTF8
$substituted = json_encode( $text, JSON_INVALID_UTF8_SUBSTITUTE ); # "A\ufffdB"
$ignored = json_encode( $text, JSON_INVALID_UTF8_IGNORE ); # "AB"
```

Both lose data, so keep rejection for exact values such as identifiers. The source string stays unchanged; capture `json_last_error()` before another JSON call replaces it.

<a id="json-exceptions"></a>

## Handle JSON failures with exceptions

**PHP 7.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-decode.php) | [PHP](https://www.php.net/manual/en/class.jsonexception.php)

`JSON_THROW_ON_ERROR` makes JSON failures throw `JsonException`, avoiding reliance on global error state. It works for both encoding and decoding.

```php
try
{
	$value = json_decode( 'null', true, 512, JSON_THROW_ON_ERROR ); # null
	json_decode( '{"broken":}', true, 512, JSON_THROW_ON_ERROR );
}
catch( JsonException $error )
{
	echo 'Invalid JSON';
}
```

Valid JSON `null` still decodes to `null`; with exceptions enabled it cannot be confused with a parsing failure.

<a id="json-validation"></a>

## Validate JSON only when you do not need its contents

**PHP 8.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-validate.php)

`json_validate()` checks syntax without building a decoded value. It fits documents that only need to be stored or forwarded after a syntax check.

```php
$json = '{"active":true}';
$valid = json_validate( $json ); # true
$invalid = json_validate( '{"active":}' ); # false
```

If the data is needed, decode once with `JSON_THROW_ON_ERROR`; validating first parses twice and still does not verify the data schema.

<a id="json-shape"></a>

## Keep numeric IDs and JSON shapes intact

**PHP 7.3+ | json | Background** | [PHP](https://www.php.net/manual/en/json.constants.php) | [PHP](https://www.php.net/manual/en/function.json-decode.php)

Decoding can return a scalar, list, object or `null`, so check the structure your API accepts. `JSON_BIGINT_AS_STRING` preserves integers beyond the platform range without converting identifiers to floats.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

$id = $data['id']; # '123456789012345678901234567890'
```

`JSON_BIGINT_AS_STRING` predates PHP 7; 7.3 is required by the exception flag here. Associative decoding can turn an empty object into an empty list when encoded again.

<a id="json-enums"></a>

## Encode backed enums as their values

**PHP 8.1+ | json / Core** | [PHP](https://www.php.net/manual/en/language.enumerations.serialization.php)

Backed enums encode directly as their scalar values, giving internal states an explicit JSON representation.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}
$json = json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR );
# {"status":"ready"}
```

Pure enums fail JSON encoding. Decoding returns the scalar; restore the enum with `from()` or `tryFrom()`.

<a id="object-serialization"></a>

## Control object state with `__serialize()` and `__unserialize()`

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [PHP](https://www.php.net/manual/en/function.unserialize.php)

`__serialize()` defines persisted state; `__unserialize()` restores it without invoking the constructor. Validate restored values in the hook, even if construction already validates them.

```php
class SavedCounter
{
	public int $value = 7;

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
}
$payload = serialize( new SavedCounter() );
$counter = unserialize( $payload, ['allowed_classes' => [SavedCounter::class]] );
$value = $counter->value; # 7
```

Only restore trusted payloads: `allowed_classes` does not make untrusted `unserialize()` safe. Implementing only the old `Serializable` interface is deprecated from 8.1.

<a id="dom-convenience"></a>

## Use DOM operations for attributes and text

**PHP 8.3+ | dom** | [PHP](https://www.php.net/manual/en/migration83.new-functions.php)

`DOMElement` gains `toggleAttribute()`, `insertAdjacentText()` and `insertAdjacentElement()`; `DOMNode::contains()` checks ancestry. Text operations insert escaped text rather than parsed markup.

```php
$document = new DOMDocument();
$document->loadXML( '<item>PHP</item>' );
$item = $document->documentElement;
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
$xml = $document->saveXML( $item ); # <item hidden="">PHP &amp; XML</item>
```

HTML boolean attributes depend on presence, so assigning the string `"false"` does not disable them.

<a id="html5-dom"></a>

## Parse modern HTML with `Dom\HTMLDocument`

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/class.dom-htmldocument.php)

`Dom\HTMLDocument` parses according to the modern HTML standard and supports selectors through the new `Dom` API. Use it when extracting or transforming HTML rather than treating it as XML.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><main id="content"><p>PHP</p></main>'
);
$element = $document->querySelector( '#content p' );
$text = $element?->textContent; # PHP
```

`querySelector()` can return `null`; new `Dom` objects and legacy DOM objects are separate APIs. Parsing does not sanitize HTML for publication.

<a id="xpath-quote"></a>

## Quote XPath string values correctly

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/domxpath.quote.php)

`DOMXPath::quote()` builds an XPath string literal, including values containing both quote styles. It avoids interpolating raw values into an expression.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
$id = $xpath->evaluate( 'string(' . $query . ')' ); # '7'
```

It quotes values, not user-supplied paths, operators or function names; the expression structure stays under application control.

<a id="html-fragments"></a>

## Insert and read HTML fragments

**PHP 8.5+ | dom** | [PHP](https://www.php.net/manual/en/migration85.new-functions.php) | [PHP](https://www.php.net/manual/en/class.dom-element.php)

`Dom\Element::insertAdjacentHTML()` parses a fragment in the element’s context. `outerHTML` reads both the element and its contents.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><div id="box"></div>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
$html = $box->outerHTML; # <div id="box"><strong>PHP</strong></div>
```

The new API takes `Dom\AdjacentPosition` cases rather than legacy string positions. Use `textContent` or `insertAdjacentText()` for plain untrusted text; raw HTML requires sanitization before rendering.

<a id="xml-factories"></a>

## Create XML readers and writers with named factories

**PHP 8.4+ | xmlreader / xmlwriter** | [PHP](https://www.php.net/manual/en/xmlreader.fromstring.php) | [PHP](https://www.php.net/manual/en/xmlwriter.tomemory.php)

Named factories combine construction with a source or destination: `fromString()` / `fromStream()` / `fromUri()` and `toMemory()` / `toStream()` / `toUri()`.

```php
$reader = XMLReader::fromString( '<items><item id="7"/></items>' );
while( $reader->read() )
{
	if( $reader->nodeType === XMLReader::ELEMENT && $reader->name === 'item' )
		echo $reader->getAttribute( 'id' ); # 7
}
$reader->close();

$writer = XMLWriter::toMemory();
$writer->writeElement( 'name', 'PHP & XML' );
$xml = $writer->outputMemory(); # <name>PHP &amp; XML</name>
```

`fromString()` retains the source in memory; use a stream for large inputs. For untrusted XML, do not enable external entity expansion or DTD loading without a controlled requirement.
