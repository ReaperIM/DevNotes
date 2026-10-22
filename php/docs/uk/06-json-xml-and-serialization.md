# JSON, XML, HTML та серіалізація

[English](../en/06-json-xml-and-serialization.md) | [Українська](../uk/06-json-xml-and-serialization.md) | [Русский](../ru/06-json-xml-and-serialization.md)

[Зміст](../../README.uk.md)

Розрізняйте три питання: чи правильний синтаксис документа, чи містить він очікувані дані та чи безпечний він у місці використання? Парсер відповідає на перше. Приклади показують, як залишити інші перевірки явними.

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

**PHP 7.2+ | json**

З PHP 7.2 прапорці `JSON_INVALID_UTF8_SUBSTITUTE` та `JSON_INVALID_UTF8_IGNORE` дають змогу явно обрати поведінку `json_encode()` і `json_decode()` для некоректного UTF-8. `SUBSTITUTE` замінює неправильні послідовності видимим символом заміни U+FFFD. `IGNORE` видаляє їх, через що текст по обидва боки пошкоджених байтів може непомітно з’єднатися.

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

**Важливо:** Залишайте відхилення типовою поведінкою для даних, які мають зберегтися точно, наприклад ідентифікатора або підписаного повідомлення. Якщо частково пошкоджений людський текст допустимий, вибирайте `SUBSTITUTE`, щоб втрату було видно; `IGNORE` підходить лише для явного правила видалення під час імпорту. Приклад зберігає `json_last_error()` до того, як наступні виклики змінять його. `JSON_THROW_ON_ERROR` є окремим доповненням PHP 7.3.

Ці прапорці впливають на значення, оброблені викликом JSON. Вони не змінюють початковий `$text`, не перетворюють довільне старе кодування на UTF-8 і не змінюють кодування файлу PHP або Markdown.

[Документація PHP: прапорці JSON](https://www.php.net/manual/en/json.constants.php)


<a id="json-exceptions"></a>

## Обробка помилок JSON через винятки

**PHP 7.3+ | json**

`JSON_THROW_ON_ERROR` змушує помилки кодування та декодування кидати `JsonException`. Коректний JSON null тоді легко відрізнити від помилки розбору без глобального стану `json_last_error()`.

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

**Важливо:** Прапорець доступний і для `json_encode()`. Не показуйте сирі дані чи внутрішні подробиці винятку у відкритій відповіді. Використовуйте прапорець послідовно замість приховування помилок через @.

[Документація PHP: json_decode()](https://www.php.net/manual/en/function.json-decode.php) | [Документація PHP: JsonException](https://www.php.net/manual/en/class.jsonexception.php)

<a id="json-validation"></a>

## Перевірка JSON, коли його вміст не потрібен

**PHP 8.3+ | json**

`json_validate()` перевіряє синтаксис JSON без створення декодованого масиву чи об’єкта. Вона корисна, коли документ потрібно зберегти або передати далі після перевірки синтаксису.

```php
$json = '{"active":true}';
var_dump( json_validate($json) ); # bool(true)
var_dump( json_validate('{"active":}') ); # bool(false)
```

**Важливо:** Якщо декодовані дані потрібні одразу, один раз викликайте `json_decode()` з `JSON_THROW_ON_ERROR`. Попередня перевірка розбирає документ двічі. Коректний синтаксис не перевіряє потрібні поля, типи або права.

[Документація PHP: json_validate()](https://www.php.net/manual/en/function.json-validate.php)

<a id="json-shape"></a>

## Збереження числових ID та структури JSON

**PHP 7.3+ | json | Контекст**

Успішне декодування може дати скаляр, список, об’єкт або null. Перевіряйте структуру, яку дійсно приймає API. Поєднуйте `JSON_BIGINT_AS_STRING` з обробкою винятків, якщо зовнішнє ціле число може перевищити діапазон платформи.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );

if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

var_dump( $data['id'] ); # string(30) "123456789012345678901234567890"
```

**Важливо:** `JSON_BIGINT_AS_STRING` існувала до PHP 7; мінімум 7.3 тут потрібен для `JSON_THROW_ON_ERROR`. Не перетворюйте ідентифікатори на float. Перетворення об’єктів JSON на асоціативні масиви також може змінити форму порожнього об’єкта чи списку.

[Документація PHP: JSON flags](https://www.php.net/manual/en/json.constants.php) | [Документація PHP: json_decode()](https://www.php.net/manual/en/function.json-decode.php)

<a id="json-enums"></a>

## Кодування backed enum як його значення

**PHP 8.1+ | json / Ядро**

Backed enum має стабільне скалярне значення, яке JSON може кодувати безпосередньо. Це зручно для внутрішнього стану з явним представленням в API.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}

echo json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR ) . PHP_EOL;
# {"status":"ready"}
```

**Важливо:** Чистий enum без скалярного значення не можна кодувати так само. На вході спочатку декодуйте скаляр і викликайте `tryFrom()` або `from()`; `json_decode()` не відновлює об’єкт enum.

[Документація PHP: enum serialization](https://www.php.net/manual/en/language.enumerations.serialization.php)

<a id="object-serialization"></a>

## Керування станом через `__serialize()` та `__unserialize()`

**PHP 7.4+ | Ядро**

Ці магічні методи явно задають стан для серіалізації PHP. Зберігайте невеликий набір даних і перевіряйте його структуру під час відновлення. `__unserialize()` працює без виклику конструктора, тому його перевірок недостатньо.

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

**Важливо:** Приклад відновлює лише власні довірені дані. Не передавайте чужі дані до `unserialize()`; allowed_classes не є загальною гарантією безпеки. Для зовнішніх даних вибирайте JSON. Старий інтерфейс `Serializable` без цих методів застарілий із PHP 8.1.

[Документація PHP: serialization methods](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [Документація PHP: unserialize()](https://www.php.net/manual/en/function.unserialize.php)

<a id="dom-convenience"></a>

## Операції DOM з атрибутами та текстом

**PHP 8.3+ | dom**

`DOMElement` отримав зручні методи `toggleAttribute()`, `insertAdjacentText()` та `insertAdjacentElement()`. `DOMNode::contains()` перевіряє вкладеність без ручного обходу батьків. Вставка тексту створює текст, а не розібрану розмітку.

```php
$document = new DOMDocument();
$document->loadXML( '<root><item>PHP</item></root>' );
$item = $document->getElementsByTagName( 'item' )->item( 0 );
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
echo $document->saveXML( $item ) . PHP_EOL;
# <item hidden="">PHP &amp; XML</item>
```

**Важливо:** Логічні HTML-атрибути визначаються наявністю, а не рядком на кшталт "false". Для чужого тексту використовуйте текстові операції; вставка сирого HTML має інший зміст.

[Документація PHP: DOM additions](https://www.php.net/manual/en/migration83.new-functions.php)

<a id="html5-dom"></a>

## Розбір сучасного HTML через `Dom\HTMLDocument`

**PHP 8.4+ | dom**

`Dom\HTMLDocument` розбирає HTML за сучасним стандартом HTML. Новий простір імен Dom також надає звичні селектори. Це зручно для читання або перетворення HTML-документа без спроб трактувати його як XML.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><html><head><title>Notes</title></head>'
	. '<body><main id="content"><p>PHP</p></main></body></html>'
);
$element = $document->querySelector( '#content p' );
echo $element->textContent . PHP_EOL; # PHP
```

**Важливо:** Нові об’єкти Dom та старі `DOMDocument`/`DOMElement` мають різні API. `querySelector()` може повернути null, якщо елемент не знайдений. Розбір HTML не очищує його для публікації.

[Документація PHP: Dom\HTMLDocument](https://www.php.net/manual/en/class.dom-htmldocument.php)

<a id="xpath-quote"></a>

## Правильне екранування рядкових значень XPath

**PHP 8.4+ | dom**

`DOMXPath::quote()` створює рядковий літерал XPath, зокрема коли значення містить обидва типи лапок. Застосовуйте її для значень замість підстановки сирого тексту у вираз XPath.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
echo $xpath->evaluate( 'string(' . $query . ')' ) . PHP_EOL; # 7
```

**Важливо:** Екранування захищає літеральне значення, а не довільний шлях XPath чи ім’я функції від користувача. Структуру виразу має контролювати програма.

[Документація PHP: DOMXPath::quote()](https://www.php.net/manual/en/domxpath.quote.php)

<a id="html-fragments"></a>

## Вставка та читання фрагментів HTML

**PHP 8.5+ | dom**

`Dom\Element::insertAdjacentHTML()` розбирає HTML-фрагмент відносно елемента. outerHTML містить сам елемент разом із його вмістом. Це прибирає потребу створювати додатковий документ лише заради вставки фрагмента.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><html><head><title>Notes</title></head>'
	. '<body><div id="box"></div></body></html>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
echo $box->outerHTML . PHP_EOL; # <div id="box"><strong>PHP</strong></div>
```

**Важливо:** Сучасний API Dom приймає варіант `Dom\AdjacentPosition`: BeforeBegin, AfterBegin, BeforeEnd або AfterEnd. Старий `DOMElement` для вставки тексту використовує рядкові позиції. Сирий чужий HTML залишається небезпечним під час показу; для звичайного тексту використовуйте `insertAdjacentText()` або textContent.

[Документація PHP: DOM additions in 8.5](https://www.php.net/manual/en/migration85.new-functions.php) | [Документація PHP: Dom\Element](https://www.php.net/manual/en/class.dom-element.php)

<a id="xml-factories"></a>

## Створення `XMLReader` та `XMLWriter` іменованими фабриками

**PHP 8.4+ | xmlreader / xmlwriter**

`XMLReader::fromString()` та `XMLWriter::toMemory()` в одному виклику описують об’єкт і його джерело чи призначення. Пов’язані фабрики `fromStream()`/`fromUri()` та `toStream()`/`toUri()` працюють із потоками й файлами.

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

**Важливо:** `fromString()` зберігає вхідний рядок у пам’яті; для справді великого джерела використовуйте читання з потоку. Для чужого XML не вмикайте розгортання зовнішніх сутностей або завантаження DTD без обґрунтованої контрольованої потреби.

[Документація PHP: XMLReader::fromString()](https://www.php.net/manual/en/xmlreader.fromstring.php) | [Документація PHP: XMLWriter::toMemory()](https://www.php.net/manual/en/xmlwriter.tomemory.php)
