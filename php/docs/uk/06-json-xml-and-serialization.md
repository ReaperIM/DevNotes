# JSON, XML, HTML та серіалізація

[EN](../en/06-json-xml-and-serialization.md) | [UK](../uk/06-json-xml-and-serialization.md) | [RU](../ru/06-json-xml-and-serialization.md)

[Зміст](../../README.uk.md)

Синтаксис JSON і структура даних перевіряються окремо; приклади DOM та XML охоплюють розбір, вставлення тексту й контрольовані зміни документа.

- [Політика обробки некоректного UTF-8 у JSON](#invalid-utf8-json)
- [Обробка помилок JSON через винятки](#json-exceptions)
- [Перевірка JSON, коли його вміст не потрібен](#json-validation)
- [Збереження числових ID та структури JSON](#json-shape)
- [Кодування backed enum як його значення](#json-enums)
- [Керування станом через `__serialize()` та `__unserialize()`](#object-serialization)
- [Операції DOM з атрибутами та текстом](#dom-convenience)
- [Розбір сучасного HTML через `Dom\HTMLDocument`](#html5-dom)
- [Правильне екранування рядкових значень XPath](#xpath-quote)
- [Вставка та читання фрагментів HTML](#html-fragments)
- [Створення `XMLReader` та `XMLWriter` іменованими фабриками](#xml-factories)

<a id="invalid-utf8-json"></a>

## Політика обробки некоректного UTF-8 у JSON

**PHP 7.2+ | json** | [PHP](https://www.php.net/manual/en/json.constants.php)

`JSON_INVALID_UTF8_SUBSTITUTE` замінює некоректний UTF-8 на U+FFFD, `JSON_INVALID_UTF8_IGNORE` видаляє некоректні байти під час кодування чи декодування. Це правила обробки пошкодженого тексту, а не перетворення кодування.

```php
$text = "A\xFFB";
$rejected = json_encode( $text ); # false
$error = json_last_error(); # JSON_ERROR_UTF8
$substituted = json_encode( $text, JSON_INVALID_UTF8_SUBSTITUTE ); # "A\ufffdB"
$ignored = json_encode( $text, JSON_INVALID_UTF8_IGNORE ); # "AB"
```

Обидва варіанти втрачають дані, тому для точних значень, наприклад ідентифікаторів, лишайте відмову. Початковий рядок не змінюється; збережіть `json_last_error()` до наступного виклику JSON.

<a id="json-exceptions"></a>

## Обробка помилок JSON через винятки

**PHP 7.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-decode.php) | [PHP](https://www.php.net/manual/en/class.jsonexception.php)

`JSON_THROW_ON_ERROR` перетворює помилки JSON на `JsonException`, усуваючи залежність від глобального стану помилок. Прапорець працює для кодування й декодування.

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

Коректний JSON `null` і далі декодується як `null`; з увімкненими винятками його не можна сплутати з помилкою розбору.

<a id="json-validation"></a>

## Перевірка JSON, коли його вміст не потрібен

**PHP 8.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-validate.php)

`json_validate()` перевіряє синтаксис без побудови декодованого значення. Вона доречна для документів, які потрібно лише зберегти чи передати після перевірки синтаксису.

```php
$json = '{"active":true}';
$valid = json_validate( $json ); # true
$invalid = json_validate( '{"active":}' ); # false
```

Якщо потрібні дані, декодуйте один раз із `JSON_THROW_ON_ERROR`; попередня перевірка розбирає документ двічі й усе одно не перевіряє схему даних.

<a id="json-shape"></a>

## Збереження числових ID та структури JSON

**PHP 7.3+ | json | Контекст** | [PHP](https://www.php.net/manual/en/json.constants.php) | [PHP](https://www.php.net/manual/en/function.json-decode.php)

Декодування може повернути скаляр, список, об’єкт або `null`, тому перевіряйте структуру, яку приймає API. `JSON_BIGINT_AS_STRING` зберігає завеликі для платформи цілі числа, не перетворюючи ідентифікатори на `float`.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

$id = $data['id']; # '123456789012345678901234567890'
```

`JSON_BIGINT_AS_STRING` існує до PHP 7; версія 7.3 тут потрібна для прапорця винятків. Асоціативне декодування може перетворити порожній об’єкт на порожній список під час повторного кодування.

<a id="json-enums"></a>

## Кодування backed enum як його значення

**PHP 8.1+ | json / Ядро** | [PHP](https://www.php.net/manual/en/language.enumerations.serialization.php)

Переліки зі скалярною основою кодуються безпосередньо своїми значеннями, задаючи явне подання внутрішніх станів у JSON.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}
$json = json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR );
# {"status":"ready"}
```

Переліки без основи спричиняють помилку кодування JSON. Декодування повертає скаляр; відновлюйте перелік через `from()` або `tryFrom()`.

<a id="object-serialization"></a>

## Керування станом через `__serialize()` та `__unserialize()`

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [PHP](https://www.php.net/manual/en/function.unserialize.php)

`__serialize()` визначає збережений стан, а `__unserialize()` відновлює його без виклику конструктора. Перевіряйте відновлені значення в цьому методі, навіть якщо їх уже перевіряє конструктор.

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

Відновлюйте лише довірені дані: `allowed_classes` не робить `unserialize()` безпечним для недовірених даних. Реалізація лише старого інтерфейсу `Serializable` застаріла з 8.1.

<a id="dom-convenience"></a>

## Операції DOM з атрибутами та текстом

**PHP 8.3+ | dom** | [PHP](https://www.php.net/manual/en/migration83.new-functions.php)

`DOMElement` отримує `toggleAttribute()`, `insertAdjacentText()` та `insertAdjacentElement()`, а `DOMNode::contains()` перевіряє належність до піддерева. Текстові операції вставляють екранований текст, а не розбирають розмітку.

```php
$document = new DOMDocument();
$document->loadXML( '<item>PHP</item>' );
$item = $document->documentElement;
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
$xml = $document->saveXML( $item ); # <item hidden="">PHP &amp; XML</item>
```

Логічні атрибути HTML залежать від наявності, тому рядкове значення `"false"` їх не вимикає.

<a id="html5-dom"></a>

## Розбір сучасного HTML через `Dom\HTMLDocument`

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/class.dom-htmldocument.php)

`Dom\HTMLDocument` розбирає документ за сучасним стандартом HTML і підтримує селектори через новий API `Dom`. Він доречний для витягування чи зміни HTML без спроб трактувати його як XML.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><main id="content"><p>PHP</p></main>'
);
$element = $document->querySelector( '#content p' );
$text = $element?->textContent; # PHP
```

`querySelector()` може повернути `null`; нові об’єкти `Dom` і старі об’єкти DOM є різними API. Розбір не очищує HTML для публікації.

<a id="xpath-quote"></a>

## Правильне екранування рядкових значень XPath

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/domxpath.quote.php)

`DOMXPath::quote()` будує рядковий літерал XPath, зокрема для значень з обома видами лапок. Це замінює вставлення сирих значень у вираз.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
$id = $xpath->evaluate( 'string(' . $query . ')' ); # '7'
```

Вона екранує значення, а не надані користувачем шляхи, оператори чи назви функцій; структуру виразу контролює застосунок.

<a id="html-fragments"></a>

## Вставка та читання фрагментів HTML

**PHP 8.5+ | dom** | [PHP](https://www.php.net/manual/en/migration85.new-functions.php) | [PHP](https://www.php.net/manual/en/class.dom-element.php)

`Dom\Element::insertAdjacentHTML()` розбирає фрагмент у контексті елемента. `outerHTML` читає і сам елемент, і його вміст.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><div id="box"></div>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
$html = $box->outerHTML; # <div id="box"><strong>PHP</strong></div>
```

Новий API приймає варіанти `Dom\AdjacentPosition` замість старих рядкових позицій. Для звичайного недовіреного тексту використовуйте `textContent` чи `insertAdjacentText()`; сирий HTML потребує очищення перед показом.

<a id="xml-factories"></a>

## Створення `XMLReader` та `XMLWriter` іменованими фабриками

**PHP 8.4+ | xmlreader / xmlwriter** | [PHP](https://www.php.net/manual/en/xmlreader.fromstring.php) | [PHP](https://www.php.net/manual/en/xmlwriter.tomemory.php)

Іменовані фабрики поєднують створення об’єкта з вибором джерела чи призначення: `fromString()` / `fromStream()` / `fromUri()` та `toMemory()` / `toStream()` / `toUri()`.

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

`fromString()` тримає джерело в пам’яті; для великих даних використовуйте потік. Для недовіреного XML не вмикайте зовнішні сутності чи завантаження DTD без контрольованої потреби.
