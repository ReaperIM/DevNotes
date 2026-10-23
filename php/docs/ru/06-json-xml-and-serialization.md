# JSON, XML, HTML и сериализация

[EN](../en/06-json-xml-and-serialization.md) | [UK](../uk/06-json-xml-and-serialization.md) | [RU](../ru/06-json-xml-and-serialization.md)

[Содержание](../../README.ru.md)

Разбираем данные, сохраняем объекты и меняем документы. Отдельно смотрим, где достаточно проверить сам JSON, а где нужно ещё проверить его поля, и когда вставляем обычный текст, а когда разметку.

- [Что делать с повреждённым UTF-8 в JSON](#invalid-utf8-json)
- [Ловим ошибку JSON как исключение](#json-exceptions)
- [Проверяем JSON без получения его данных](#json-validation)
- [Не теряем длинный ID и проверяем поля](#json-shape)
- [Записываем enum в JSON его значением](#json-enums)
- [Выбираем, что сохранять и восстанавливать у объекта](#object-serialization)
- [Меняем атрибуты и добавляем текст через DOM](#dom-convenience)
- [Читаем HTML через `Dom\HTMLDocument`](#html5-dom)
- [Подставляем строку в XPath без сломанных кавычек](#xpath-quote)
- [Вставляем готовый фрагмент HTML](#html-fragments)
- [Сразу выбираем, откуда читать XML и куда писать](#xml-factories)

<a id="invalid-utf8-json"></a>
## Что делать с повреждённым UTF-8 в JSON

**PHP 7.2+ | json** | [PHP](https://www.php.net/manual/en/json.constants.php)

Если в строке попались неподходящие для UTF-8 байты, обычный `json_encode()` вернёт `false`. Можно заменить повреждённое место символом U+FFFD через `JSON_INVALID_UTF8_SUBSTITUTE` или убрать его через `JSON_INVALID_UTF8_IGNORE`. Эти флаги работают при кодировании и декодировании.

```php
$text = "A\xFFB";
$rejected = json_encode( $text ); # false
$error = json_last_error(); # JSON_ERROR_UTF8
$substituted = json_encode( $text, JSON_INVALID_UTF8_SUBSTITUTE ); # "A\ufffdB"
$ignored = json_encode( $text, JSON_INVALID_UTF8_IGNORE ); # "AB"
```

В примере один вариант оставляет отметку на месте ошибки, другой превращает текст в `AB`. Исходная строка не меняется, но в результате часть данных уже потеряна. Для идентификатора или другого точного значения лучше обработать ошибку, чем незаметно менять содержимое. Это не перевод из другой кодировки в UTF-8. Если читаем `json_last_error()`, делаем это до следующего вызова JSON.

<a id="json-exceptions"></a>
## Ловим ошибку JSON как исключение

**PHP 7.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-decode.php) | [PHP](https://www.php.net/manual/en/class.jsonexception.php)

С `JSON_THROW_ON_ERROR` не нужно после каждого вызова отдельно читать последнюю ошибку JSON. Если кодирование или декодирование не удалось, получаем `JsonException` и обрабатываем его в `catch`.

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

В примере JSON `null` совершенно корректен и даёт обычный `null`. А сломанный JSON попадает в `catch`. Так эти два случая больше не смешиваются в одном результате.

<a id="json-validation"></a>
## Проверяем JSON без получения его данных

**PHP 8.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-validate.php)

Если JSON нужно только сохранить или передать дальше, `json_validate()` проверит его запись без создания массива или объекта с содержимым. В примере первый документ подходит, второй с незаполненным `active` нет.

```php
$json = '{"active":true}';
$valid = json_validate( $json ); # true
$invalid = json_validate( '{"active":}' ); # false
```

Если содержимое всё равно нужно читать, проще один раз вызвать `json_decode()` с `JSON_THROW_ON_ERROR`. Проверка перед декодированием разберёт тот же документ дважды. И корректная запись JSON ещё не означает, что внутри есть нужные поля и правильные типы.

<a id="json-shape"></a>
## Не теряем длинный ID и проверяем поля

**PHP 7.3+ | json | Контекст** | [PHP](https://www.php.net/manual/en/json.constants.php) | [PHP](https://www.php.net/manual/en/function.json-decode.php)

JSON может содержать не только объект с полями, но и список, отдельное значение или `null`. Поэтому после декодирования проверяем, что получили нужную структуру. А для слишком большого целого ID добавляем `JSON_BIGINT_AS_STRING`, чтобы оно сохранилось строкой, а не потеряло точность во `float`.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

$id = $data['id']; # '123456789012345678901234567890'
```

Флаг `JSON_BIGINT_AS_STRING` существовал ещё до PHP 7; PHP 7.3 нужен здесь для исключений. Есть и нюанс обратного кодирования: при разборе в ассоциативные массивы пустой объект может превратиться в пустой список и затем попасть в JSON как `[]` вместо `{}`.

<a id="json-enums"></a>
## Записываем enum в JSON его значением

**PHP 8.1+ | json / Ядро** | [PHP](https://www.php.net/manual/en/language.enumerations.serialization.php)

У backed enum есть базовое строковое или числовое значение. При кодировании JSON берётся именно оно. Поэтому `ExportStatus::Ready` в примере превращается в обычное `"ready"`.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}
$json = json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR );
# {"status":"ready"}
```

Enum без базового значения вызовет ошибку кодирования. После декодирования готового объекта enum тоже не будет: получим строку или число. Если нужен сам вариант enum, восстанавливаем его через `from()` или `tryFrom()`.

<a id="object-serialization"></a>
## Выбираем, что сохранять и восстанавливать у объекта

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [PHP](https://www.php.net/manual/en/function.unserialize.php)

`__serialize()` возвращает данные, которые нужно сохранить. `__unserialize()` получает их обратно и заполняет объект без вызова конструктора. Поэтому проверки восстановленных значений нужны здесь тоже, даже если похожие уже были в конструкторе.

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

В примере сохраняем только `value` и принимаем обратно только целое число. Восстанавливаем таким способом лишь доверенные данные: список `allowed_classes` сам по себе не делает `unserialize()` безопасным для чужого содержимого. Реализовывать только старый интерфейс `Serializable` устарело с PHP 8.1.

<a id="dom-convenience"></a>
## Меняем атрибуты и добавляем текст через DOM

**PHP 8.3+ | dom** | [PHP](https://www.php.net/manual/en/migration83.new-functions.php)

В `DOMElement` появились `toggleAttribute()` для включения и удаления атрибута, `insertAdjacentText()` для текста и `insertAdjacentElement()` для готового элемента. `DOMNode::contains()` помогает проверить принадлежность узла нужному поддереву. В примере включаем `hidden` и добавляем текст в конец `item`.

```php
$document = new DOMDocument();
$document->loadXML( '<item>PHP</item>' );
$item = $document->documentElement;
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
$xml = $document->saveXML( $item ); # <item hidden="">PHP &amp; XML</item>
```

Знак `&` сохраняется как `&amp;`: текст вставлен как текст, а не разобран как разметка. У логических атрибутов HTML важен сам факт наличия. Записать строку `"false"` не значит выключить такой атрибут, его нужно убрать.

<a id="html5-dom"></a>
## Читаем HTML через `Dom\HTMLDocument`

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/class.dom-htmldocument.php)

Для современного HTML есть отдельный `Dom\HTMLDocument`. Он разбирает документ по правилам HTML, а не пытается обращаться с ним как с XML. Затем нужный элемент можно найти знакомым CSS-селектором: здесь берём `p` внутри `#content`.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><main id="content"><p>PHP</p></main>'
);
$element = $document->querySelector( '#content p' );
$text = $element?->textContent; # PHP
```

Если ничего не найдено, `querySelector()` вернёт `null`. Новые классы `Dom` и старые DOM-классы относятся к разным API, смешивать их как один набор нельзя. И сам разбор документа не делает HTML безопасным для публикации.

<a id="xpath-quote"></a>
## Подставляем строку в XPath без сломанных кавычек

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/domxpath.quote.php)

У имени `O'Brien` уже есть апостроф. Если просто вклеить его в XPath между такими же кавычками, выражение сломается. `DOMXPath::quote()` готовит правильную запись строкового значения, даже если внутри оба вида кавычек.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
$id = $xpath->evaluate( 'string(' . $query . ')' ); # '7'
```

Так защищаем именно значение. Пути, операторы и имена функций от пользователя эта функция не проверяет: структуру XPath по-прежнему задаём сами.

<a id="html-fragments"></a>
## Вставляем готовый фрагмент HTML

**PHP 8.5+ | dom** | [PHP](https://www.php.net/manual/en/migration85.new-functions.php) | [PHP](https://www.php.net/manual/en/class.dom-element.php)

`Dom\Element::insertAdjacentHTML()` разбирает HTML-фрагмент с учётом элемента, в который вставляем. Здесь добавляем `strong` внутрь `div`. А `outerHTML` возвращает весь элемент вместе с его собственным тегом и содержимым.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><div id="box"></div>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
$html = $box->outerHTML; # <div id="box"><strong>PHP</strong></div>
```

В новом API положение задаётся вариантом `Dom\AdjacentPosition`, а не старой строкой. Для обычного текста от пользователя берём `textContent` или `insertAdjacentText()`. Если вставляем именно HTML, перед показом его нужно очистить от опасного содержимого.

<a id="xml-factories"></a>
## Сразу выбираем, откуда читать XML и куда писать

**PHP 8.4+ | xmlreader / xmlwriter** | [PHP](https://www.php.net/manual/en/xmlreader.fromstring.php) | [PHP](https://www.php.net/manual/en/xmlwriter.tomemory.php)

Новые методы сразу создают объект с нужным источником или местом записи. Для чтения есть `fromString()`, `fromStream()` и `fromUri()`, для записи `toMemory()`, `toStream()` и `toUri()`. В примере читаем атрибут из строки, а новый XML собираем в памяти.

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

`fromString()` держит исходный текст в памяти, для больших данных лучше поток. Если XML пришёл извне, не включаем внешние сущности и загрузку DTD без конкретной контролируемой необходимости: это разрешает документу обращаться к дополнительным источникам.
