# JSON, XML, HTML и сериализация

[English](../en/06-json-xml-and-serialization.md) | [Українська](../uk/06-json-xml-and-serialization.md) | [Русский](../ru/06-json-xml-and-serialization.md)

[Содержание](../../README.ru.md)

Синтаксис JSON и структура данных проверяются отдельно; примеры DOM и XML охватывают разбор, вставку текста и контролируемые изменения документа.

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

**PHP 7.2+ | json** | [PHP](https://www.php.net/manual/en/json.constants.php)

`JSON_INVALID_UTF8_SUBSTITUTE` заменяет некорректный UTF-8 на U+FFFD, `JSON_INVALID_UTF8_IGNORE` удаляет некорректные байты при кодировании или декодировании. Это правила обработки повреждённого текста, а не преобразование кодировки.

```php
$text = "A\xFFB";
$rejected = json_encode( $text ); # false
$error = json_last_error(); # JSON_ERROR_UTF8
$substituted = json_encode( $text, JSON_INVALID_UTF8_SUBSTITUTE ); # "A\ufffdB"
$ignored = json_encode( $text, JSON_INVALID_UTF8_IGNORE ); # "AB"
```

Оба варианта теряют данные, поэтому для точных значений, например идентификаторов, сохраняйте отказ. Исходная строка не меняется; сохраните `json_last_error()` до следующего вызова JSON.

<a id="json-exceptions"></a>

## Обработка ошибок JSON через исключения

**PHP 7.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-decode.php) | [PHP](https://www.php.net/manual/en/class.jsonexception.php)

`JSON_THROW_ON_ERROR` превращает ошибки JSON в `JsonException`, устраняя зависимость от глобального состояния ошибок. Флаг работает для кодирования и декодирования.

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

Корректный JSON `null` по-прежнему декодируется как `null`; с включёнными исключениями его нельзя спутать с ошибкой разбора.

<a id="json-validation"></a>

## Проверка JSON, когда его содержимое не нужно

**PHP 8.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-validate.php)

`json_validate()` проверяет синтаксис без построения декодированного значения. Она подходит для документов, которые нужно лишь сохранить или передать после проверки синтаксиса.

```php
$json = '{"active":true}';
$valid = json_validate( $json ); # true
$invalid = json_validate( '{"active":}' ); # false
```

Если нужны данные, декодируйте один раз с `JSON_THROW_ON_ERROR`; предварительная проверка разбирает документ дважды и всё равно не проверяет схему данных.

<a id="json-shape"></a>

## Сохранение числовых ID и структуры JSON

**PHP 7.3+ | json | Контекст** | [PHP](https://www.php.net/manual/en/json.constants.php) | [PHP](https://www.php.net/manual/en/function.json-decode.php)

Декодирование может вернуть скаляр, список, объект или `null`, поэтому проверяйте структуру, которую принимает API. `JSON_BIGINT_AS_STRING` сохраняет слишком большие для платформы целые числа, не превращая идентификаторы во float.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

$id = $data['id']; # '123456789012345678901234567890'
```

`JSON_BIGINT_AS_STRING` существует до PHP 7; версия 7.3 здесь нужна для флага исключений. Ассоциативное декодирование может превратить пустой объект в пустой список при повторном кодировании.

<a id="json-enums"></a>

## Кодирование backed enum как его значения

**PHP 8.1+ | json / Ядро** | [PHP](https://www.php.net/manual/en/language.enumerations.serialization.php)

Перечисления со скалярной основой кодируются непосредственно своими значениями, задавая явное представление внутренних состояний в JSON.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}
$json = json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR );
# {"status":"ready"}
```

Перечисления без основы вызывают ошибку кодирования JSON. Декодирование возвращает скаляр; восстанавливайте перечисление через `from()` или `tryFrom()`.

<a id="object-serialization"></a>

## Управление состоянием через `__serialize()` и `__unserialize()`

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [PHP](https://www.php.net/manual/en/function.unserialize.php)

`__serialize()` определяет сохраняемое состояние, а `__unserialize()` восстанавливает его без вызова конструктора. Проверяйте восстановленные значения в этом методе, даже если их уже проверяет конструктор.

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

Восстанавливайте только доверенные данные: `allowed_classes` не делает `unserialize()` безопасным для недоверенных данных. Реализация только старого интерфейса `Serializable` устарела с 8.1.

<a id="dom-convenience"></a>

## Операции DOM с атрибутами и текстом

**PHP 8.3+ | dom** | [PHP](https://www.php.net/manual/en/migration83.new-functions.php)

`DOMElement` получает `toggleAttribute()`, `insertAdjacentText()` и `insertAdjacentElement()`, а `DOMNode::contains()` проверяет принадлежность к поддереву. Текстовые операции вставляют экранированный текст, а не разбирают разметку.

```php
$document = new DOMDocument();
$document->loadXML( '<item>PHP</item>' );
$item = $document->documentElement;
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
$xml = $document->saveXML( $item ); # <item hidden="">PHP &amp; XML</item>
```

Логические атрибуты HTML зависят от наличия, поэтому строковое значение `"false"` их не отключает.

<a id="html5-dom"></a>

## Разбор современного HTML через `Dom\HTMLDocument`

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/class.dom-htmldocument.php)

`Dom\HTMLDocument` разбирает документ по современному стандарту HTML и поддерживает селекторы через новый API `Dom`. Он подходит для извлечения или изменения HTML без попыток трактовать его как XML.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><main id="content"><p>PHP</p></main>'
);
$element = $document->querySelector( '#content p' );
$text = $element?->textContent; # PHP
```

`querySelector()` может вернуть `null`; новые объекты `Dom` и старые объекты DOM являются разными API. Разбор не очищает HTML для публикации.

<a id="xpath-quote"></a>

## Правильное экранирование строковых значений XPath

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/domxpath.quote.php)

`DOMXPath::quote()` строит строковый литерал XPath, в том числе для значений с обоими видами кавычек. Это заменяет вставку сырых значений в выражение.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
$id = $xpath->evaluate( 'string(' . $query . ')' ); # '7'
```

Она экранирует значения, а не переданные пользователем пути, операторы или имена функций; структуру выражения контролирует приложение.

<a id="html-fragments"></a>

## Вставка и чтение фрагментов HTML

**PHP 8.5+ | dom** | [PHP](https://www.php.net/manual/en/migration85.new-functions.php) | [PHP](https://www.php.net/manual/en/class.dom-element.php)

`Dom\Element::insertAdjacentHTML()` разбирает фрагмент в контексте элемента. `outerHTML` читает и сам элемент, и его содержимое.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><div id="box"></div>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
$html = $box->outerHTML; # <div id="box"><strong>PHP</strong></div>
```

Новый API принимает варианты `Dom\AdjacentPosition` вместо старых строковых позиций. Для обычного недоверенного текста используйте `textContent` или `insertAdjacentText()`; сырой HTML требует очистки перед отображением.

<a id="xml-factories"></a>

## Создание `XMLReader` и `XMLWriter` именованными фабриками

**PHP 8.4+ | xmlreader / xmlwriter** | [PHP](https://www.php.net/manual/en/xmlreader.fromstring.php) | [PHP](https://www.php.net/manual/en/xmlwriter.tomemory.php)

Именованные фабрики совмещают создание объекта с выбором источника или назначения: `fromString()` / `fromStream()` / `fromUri()` и `toMemory()` / `toStream()` / `toUri()`.

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

`fromString()` держит источник в памяти; для больших данных используйте поток. Для недоверенного XML не включайте внешние сущности или загрузку DTD без контролируемой необходимости.
