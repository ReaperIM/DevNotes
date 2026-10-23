# JSON, XML, HTML and serialization

[EN](../en/06-json-xml-and-serialization.md) | [UK](../uk/06-json-xml-and-serialization.md) | [RU](../ru/06-json-xml-and-serialization.md)

[Contents](../../README.md)

Decode data, save objects and edit documents. Keep a few distinctions in mind: valid JSON versus the fields it contains, restored objects versus newly constructed ones, and plain text versus markup.

- [Handle damaged UTF-8 in JSON](#invalid-utf8-json)
- [Catch a JSON error as an exception](#json-exceptions)
- [Validate JSON without building its data](#json-validation)
- [Keep a long ID intact and check the fields](#json-shape)
- [Encode an enum using its value](#json-enums)
- [Choose what to save and restore on an object](#object-serialization)
- [Change attributes and insert text with DOM](#dom-convenience)
- [Read HTML with `Dom\HTMLDocument`](#html5-dom)
- [Put a string into XPath without breaking its quotes](#xpath-quote)
- [Insert a ready-made HTML fragment](#html-fragments)
- [Choose where to read and write XML at creation](#xml-factories)

<a id="invalid-utf8-json"></a>
## Handle damaged UTF-8 in JSON

**PHP 7.2+ | json** | [PHP](https://www.php.net/manual/en/json.constants.php)

If a string contains bytes that are not valid UTF-8, plain `json_encode()` returns `false`. `JSON_INVALID_UTF8_SUBSTITUTE` replaces the damaged part with U+FFFD, while `JSON_INVALID_UTF8_IGNORE` drops it. These flags work when encoding and decoding.

```php
$text = "A\xFFB";
$rejected = json_encode( $text ); # false
$error = json_last_error(); # JSON_ERROR_UTF8
$substituted = json_encode( $text, JSON_INVALID_UTF8_SUBSTITUTE ); # "A\ufffdB"
$ignored = json_encode( $text, JSON_INVALID_UTF8_IGNORE ); # "AB"
```

One result keeps a replacement marker; the other becomes `AB`. The source string is unchanged, but the result has lost some data. For an identifier or another exact value, handle the error instead of silently changing the contents. These flags do not convert another encoding into UTF-8. Read `json_last_error()` before another JSON call replaces it.

<a id="json-exceptions"></a>
## Catch a JSON error as an exception

**PHP 7.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-decode.php) | [PHP](https://www.php.net/manual/en/class.jsonexception.php)

With `JSON_THROW_ON_ERROR`, there is no need to read the last JSON error after every call. A failed encode or decode throws `JsonException`, which we can handle in `catch`.

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

The JSON value `null` is valid and becomes an ordinary `null` here. The broken JSON instead reaches `catch`. The two cases no longer look like the same result.

<a id="json-validation"></a>
## Validate JSON without building its data

**PHP 8.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-validate.php)

If JSON only needs to be stored or forwarded, `json_validate()` checks its syntax without creating an array or object containing the data. The first document here is valid; the second is missing a value for `active`.

```php
$json = '{"active":true}';
$valid = json_validate( $json ); # true
$invalid = json_validate( '{"active":}' ); # false
```

If you need to read the contents anyway, decode once with `JSON_THROW_ON_ERROR`. Validating before decoding parses the same document twice. Valid JSON syntax also does not guarantee the fields and types your application expects.

<a id="json-shape"></a>
## Keep a long ID intact and check the fields

**PHP 7.3+ | json | Background** | [PHP](https://www.php.net/manual/en/json.constants.php) | [PHP](https://www.php.net/manual/en/function.json-decode.php)

JSON can contain a list, a single value or `null` as well as an object with fields. Check that decoding gives the structure you need. For an integer ID larger than the platform can hold, add `JSON_BIGINT_AS_STRING` so it stays a string instead of losing precision as a `float`.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

$id = $data['id']; # '123456789012345678901234567890'
```

`JSON_BIGINT_AS_STRING` predates PHP 7; PHP 7.3 is needed here for the exception flag. Also watch round trips through associative arrays: an empty object can become an empty list and be encoded as `[]` instead of `{}`.

<a id="json-enums"></a>
## Encode an enum using its value

**PHP 8.1+ | json / Core** | [PHP](https://www.php.net/manual/en/language.enumerations.serialization.php)

A backed enum has a string or integer value behind each case. JSON encoding uses that value, so `ExportStatus::Ready` becomes a regular `"ready"` in the result.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}
$json = json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR );
# {"status":"ready"}
```

A pure enum without a backing value causes a JSON encoding error. Decoding does not recreate the enum object either; it gives a string or integer. Use `from()` or `tryFrom()` to turn that value back into an enum case.

<a id="object-serialization"></a>
## Choose what to save and restore on an object

**PHP 7.4+ | Core** | [PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [PHP](https://www.php.net/manual/en/function.unserialize.php)

`__serialize()` returns the data to save. `__unserialize()` receives it again and fills the object without running its constructor. Restored values therefore need checks here too, even if the constructor already has similar checks.

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

The example saves only `value` and only accepts an integer back. Restore trusted data only: an `allowed_classes` list does not make `unserialize()` safe for untrusted contents. Implementing only the old `Serializable` interface is deprecated from PHP 8.1.

<a id="dom-convenience"></a>
## Change attributes and insert text with DOM

**PHP 8.3+ | dom** | [PHP](https://www.php.net/manual/en/migration83.new-functions.php)

`DOMElement` gains `toggleAttribute()` to add or remove an attribute, `insertAdjacentText()` for text and `insertAdjacentElement()` for an existing element. `DOMNode::contains()` checks whether a node belongs to a subtree. Here we enable `hidden` and append text inside `item`.

```php
$document = new DOMDocument();
$document->loadXML( '<item>PHP</item>' );
$item = $document->documentElement;
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
$xml = $document->saveXML( $item ); # <item hidden="">PHP &amp; XML</item>
```

The `&` is saved as `&amp;` because it was inserted as text, not parsed as markup. For HTML boolean attributes, presence is what matters. Setting the string `"false"` does not disable one; remove the attribute instead.

<a id="html5-dom"></a>
## Read HTML with `Dom\HTMLDocument`

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/class.dom-htmldocument.php)

`Dom\HTMLDocument` handles modern HTML according to HTML parsing rules instead of treating it as XML. Then a CSS selector can find the element we need: here it selects `p` inside `#content`.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><main id="content"><p>PHP</p></main>'
);
$element = $document->querySelector( '#content p' );
$text = $element?->textContent; # PHP
```

`querySelector()` returns `null` if nothing matches. The new `Dom` classes and the older DOM classes belong to separate APIs; they are not one interchangeable set. Parsing a document also does not make its HTML safe to publish.

<a id="xpath-quote"></a>
## Put a string into XPath without breaking its quotes

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/domxpath.quote.php)

`O'Brien` already contains an apostrophe. Pasting it between the same quotes in an XPath expression would break that expression. `DOMXPath::quote()` builds a proper XPath string value, even when it contains both quote styles.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
$id = $xpath->evaluate( 'string(' . $query . ')' ); # '7'
```

This handles the value only. It does not validate user-supplied paths, operators or function names; the application must still control the XPath structure.

<a id="html-fragments"></a>
## Insert a ready-made HTML fragment

**PHP 8.5+ | dom** | [PHP](https://www.php.net/manual/en/migration85.new-functions.php) | [PHP](https://www.php.net/manual/en/class.dom-element.php)

`Dom\Element::insertAdjacentHTML()` parses a fragment in the context of the receiving element. Here it adds `strong` inside `div`. `outerHTML` then returns the whole element, including its own tag and contents.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><div id="box"></div>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
$html = $box->outerHTML; # <div id="box"><strong>PHP</strong></div>
```

The new API uses a `Dom\AdjacentPosition` case for the position instead of an old-style string. Use `textContent` or `insertAdjacentText()` for plain text from a user. Actual HTML needs sanitizing before it is rendered.

<a id="xml-factories"></a>
## Choose where to read and write XML at creation

**PHP 8.4+ | xmlreader / xmlwriter** | [PHP](https://www.php.net/manual/en/xmlreader.fromstring.php) | [PHP](https://www.php.net/manual/en/xmlwriter.tomemory.php)

The new methods create the object with its source or destination already selected. Reading has `fromString()`, `fromStream()` and `fromUri()`; writing has `toMemory()`, `toStream()` and `toUri()`. Here we read an attribute from a string and build new XML in memory.

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

`fromString()` keeps the source text in memory; a stream is better for large inputs. For XML from outside the application, do not enable external entities or DTD loading without a specific controlled need: that lets the document refer to additional sources.
