# JSON, XML, HTML и сериализация

[English](../en/06-json-xml-and-serialization.md) | [Українська](../uk/06-json-xml-and-serialization.md) | [Русский](../ru/06-json-xml-and-serialization.md)

[Содержание](../../README.ru.md)

Различайте три вопроса: правилен ли синтаксис документа, содержит ли он ожидаемые данные и безопасен ли он в месте использования? Парсер отвечает на первый. Примеры показывают, как оставить остальные проверки явными.

- [Политика обработки некорректного UTF-8 в JSON](#invalid-utf8-json)
- [Обработка ошибок JSON через исключения](#json-exceptions)
- [Проверка JSON, когда его содержимое не нужно](#json-validation)
- [Сохранение числовых ID и структуры JSON](#json-shape)
- [Кодирование backed enum как его значения](#json-enums)
- [Управление состоянием через `__serialize()` и `__unserialize()`](#object-serialization)
- [Операции DOM с атрибутами и текстом](#dom-convenience)
- [Разбор современного HTML через `Dom\HTMLDocument`](#html5-dom)
- [Правильное экранирование строковых значений XPath](#xpath-quote)
- [Вставка и чтение фрагментов HTML](#html-fragments)
- [Создание `XMLReader` и `XMLWriter` именованными фабриками](#xml-factories)

<a id="invalid-utf8-json"></a>

## Политика обработки некорректного UTF-8 в JSON

**PHP 7.2+ | json**

С PHP 7.2 флаги `JSON_INVALID_UTF8_SUBSTITUTE` и `JSON_INVALID_UTF8_IGNORE` позволяют явно выбрать поведение `json_encode()` и `json_decode()` для некорректного UTF-8. `SUBSTITUTE` заменяет неправильные последовательности видимым символом замены U+FFFD. `IGNORE` удаляет их, из-за чего текст по обе стороны повреждённых байтов может незаметно соединиться.

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

**Важно:** Оставляйте отклонение стандартным поведением для данных, которые должны сохраниться точно, например идентификатора или подписанного сообщения. Если частично повреждённый человеческий текст допустим, выбирайте `SUBSTITUTE`, чтобы потеря была видна; `IGNORE` подходит только для явного правила удаления при импорте. Пример сохраняет `json_last_error()` до того, как последующие вызовы изменят его. `JSON_THROW_ON_ERROR` является отдельным дополнением PHP 7.3.

Эти флаги влияют на значения, обработанные вызовом JSON. Они не меняют исходный `$text`, не преобразуют произвольную старую кодировку в UTF-8 и не меняют кодировку файла PHP или Markdown.

[Документация PHP: флаги JSON](https://www.php.net/manual/en/json.constants.php)


<a id="json-exceptions"></a>

## Обработка ошибок JSON через исключения

**PHP 7.3+ | json**

`JSON_THROW_ON_ERROR` заставляет ошибки кодирования и декодирования выбрасывать `JsonException`. Корректный JSON null тогда легко отличить от ошибки разбора без глобального состояния `json_last_error()`.

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

**Важно:** Флаг доступен и для `json_encode()`. Не показывайте сырые данные или внутренние подробности исключения в публичном ответе. Используйте флаг последовательно вместо подавления ошибок через @.

[Документация PHP: json_decode()](https://www.php.net/manual/en/function.json-decode.php) | [Документация PHP: JsonException](https://www.php.net/manual/en/class.jsonexception.php)

<a id="json-validation"></a>

## Проверка JSON, когда его содержимое не нужно

**PHP 8.3+ | json**

`json_validate()` проверяет синтаксис JSON без создания декодированного массива или объекта. Она полезна, когда документ нужно сохранить или передать дальше после проверки синтаксиса.

```php
$json = '{"active":true}';
var_dump( json_validate($json) ); # bool(true)
var_dump( json_validate('{"active":}') ); # bool(false)
```

**Важно:** Если декодированные данные нужны сразу, один раз вызывайте `json_decode()` с `JSON_THROW_ON_ERROR`. Предварительная проверка разбирает документ дважды. Корректный синтаксис не проверяет нужные поля, типы или права.

[Документация PHP: json_validate()](https://www.php.net/manual/en/function.json-validate.php)

<a id="json-shape"></a>

## Сохранение числовых ID и структуры JSON

**PHP 7.3+ | json | Контекст**

Успешное декодирование может дать скаляр, список, объект или null. Проверяйте структуру, которую действительно принимает API. Сочетайте `JSON_BIGINT_AS_STRING` с обработкой исключений, если внешнее целое число может превысить диапазон платформы.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );

if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

var_dump( $data['id'] ); # string(30) "123456789012345678901234567890"
```

**Важно:** `JSON_BIGINT_AS_STRING` существовала до PHP 7; минимум 7.3 здесь нужен для `JSON_THROW_ON_ERROR`. Не превращайте идентификаторы в float. Преобразование объектов JSON в ассоциативные массивы также может изменить форму пустого объекта или списка.

[Документация PHP: JSON flags](https://www.php.net/manual/en/json.constants.php) | [Документация PHP: json_decode()](https://www.php.net/manual/en/function.json-decode.php)

<a id="json-enums"></a>

## Кодирование backed enum как его значения

**PHP 8.1+ | json / Ядро**

Backed enum имеет стабильное скалярное значение, которое JSON может кодировать напрямую. Это удобно для внутреннего состояния с явным представлением в API.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}

echo json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR ) . PHP_EOL;
# {"status":"ready"}
```

**Важно:** Чистый enum без скалярного значения нельзя кодировать так же. На входе сначала декодируйте скаляр и вызывайте `tryFrom()` или `from()`; `json_decode()` не восстанавливает объект enum.

[Документация PHP: enum serialization](https://www.php.net/manual/en/language.enumerations.serialization.php)

<a id="object-serialization"></a>

## Управление состоянием через `__serialize()` и `__unserialize()`

**PHP 7.4+ | Ядро**

Эти магические методы явно задают состояние для сериализации PHP. Храните небольшой набор данных и проверяйте его структуру при восстановлении. `__unserialize()` работает без вызова конструктора, поэтому его проверок недостаточно.

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

**Важно:** Пример восстанавливает только собственные доверенные данные. Не передавайте чужие данные в `unserialize()`; allowed_classes не является общей гарантией безопасности. Для внешних данных выбирайте JSON. Старый интерфейс `Serializable` без этих методов устарел с PHP 8.1.

[Документация PHP: serialization methods](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [Документация PHP: unserialize()](https://www.php.net/manual/en/function.unserialize.php)

<a id="dom-convenience"></a>

## Операции DOM с атрибутами и текстом

**PHP 8.3+ | dom**

`DOMElement` получил удобные методы `toggleAttribute()`, `insertAdjacentText()` и `insertAdjacentElement()`. `DOMNode::contains()` проверяет вложенность без ручного обхода родителей. Вставка текста создаёт текст, а не разобранную разметку.

```php
$document = new DOMDocument();
$document->loadXML( '<root><item>PHP</item></root>' );
$item = $document->getElementsByTagName( 'item' )->item( 0 );
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
echo $document->saveXML( $item ) . PHP_EOL;
# <item hidden="">PHP &amp; XML</item>
```

**Важно:** Логические HTML-атрибуты определяются наличием, а не строкой вроде "false". Для чужого текста используйте текстовые операции; вставка сырого HTML имеет другой смысл.

[Документация PHP: DOM additions](https://www.php.net/manual/en/migration83.new-functions.php)

<a id="html5-dom"></a>

## Разбор современного HTML через `Dom\HTMLDocument`

**PHP 8.4+ | dom**

`Dom\HTMLDocument` разбирает HTML по современному стандарту HTML. Новое пространство имён Dom также предоставляет привычные селекторы. Это удобно для чтения или преобразования HTML-документа без попыток трактовать его как XML.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><html><head><title>Notes</title></head>'
	. '<body><main id="content"><p>PHP</p></main></body></html>'
);
$element = $document->querySelector( '#content p' );
echo $element->textContent . PHP_EOL; # PHP
```

**Важно:** Новые объекты Dom и старые `DOMDocument`/`DOMElement` имеют разные API. `querySelector()` может вернуть null, если элемент не найден. Разбор HTML не очищает его для публикации.

[Документация PHP: Dom\HTMLDocument](https://www.php.net/manual/en/class.dom-htmldocument.php)

<a id="xpath-quote"></a>

## Правильное экранирование строковых значений XPath

**PHP 8.4+ | dom**

`DOMXPath::quote()` создаёт строковый литерал XPath, в том числе когда значение содержит оба типа кавычек. Применяйте её для значений вместо подстановки сырого текста в выражение XPath.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
echo $xpath->evaluate( 'string(' . $query . ')' ) . PHP_EOL; # 7
```

**Важно:** Экранирование защищает литеральное значение, а не произвольный путь XPath или имя функции от пользователя. Структуру выражения должна контролировать программа.

[Документация PHP: DOMXPath::quote()](https://www.php.net/manual/en/domxpath.quote.php)

<a id="html-fragments"></a>

## Вставка и чтение фрагментов HTML

**PHP 8.5+ | dom**

`Dom\Element::insertAdjacentHTML()` разбирает HTML-фрагмент относительно элемента. outerHTML содержит сам элемент вместе с его содержимым. Это убирает необходимость создавать дополнительный документ только ради вставки фрагмента.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><html><head><title>Notes</title></head>'
	. '<body><div id="box"></div></body></html>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
echo $box->outerHTML . PHP_EOL; # <div id="box"><strong>PHP</strong></div>
```

**Важно:** Современный API Dom принимает вариант `Dom\AdjacentPosition`: BeforeBegin, AfterBegin, BeforeEnd или AfterEnd. Старый `DOMElement` для вставки текста использует строковые позиции. Сырой чужой HTML остаётся опасным при показе; для обычного текста используйте `insertAdjacentText()` или textContent.

[Документация PHP: DOM additions in 8.5](https://www.php.net/manual/en/migration85.new-functions.php) | [Документация PHP: Dom\Element](https://www.php.net/manual/en/class.dom-element.php)

<a id="xml-factories"></a>

## Создание `XMLReader` и `XMLWriter` именованными фабриками

**PHP 8.4+ | xmlreader / xmlwriter**

`XMLReader::fromString()` и `XMLWriter::toMemory()` в одном вызове описывают объект и его источник или назначение. Связанные фабрики `fromStream()`/`fromUri()` и `toStream()`/`toUri()` работают с потоками и файлами.

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

**Важно:** `fromString()` хранит входную строку в памяти; для действительно большого источника используйте чтение из потока. Для чужого XML не включайте развёртывание внешних сущностей или загрузку DTD без обоснованной контролируемой необходимости.

[Документация PHP: XMLReader::fromString()](https://www.php.net/manual/en/xmlreader.fromstring.php) | [Документация PHP: XMLWriter::toMemory()](https://www.php.net/manual/en/xmlwriter.tomemory.php)
