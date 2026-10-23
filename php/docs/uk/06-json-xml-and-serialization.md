# JSON, XML, HTML та серіалізація

[EN](../en/06-json-xml-and-serialization.md) | [UK](../uk/06-json-xml-and-serialization.md) | [RU](../ru/06-json-xml-and-serialization.md)

[Зміст](../../README.uk.md)

Розбираємо дані, зберігаємо об’єкти й змінюємо документи. Окремо дивимося, де досить перевірити сам JSON, а де треба ще перевірити його поля, і коли вставляємо звичайний текст, а коли розмітку.

- [Що робити з пошкодженим UTF-8 у JSON](#invalid-utf8-json)
- [Ловимо помилку JSON як виняток](#json-exceptions)
- [Перевіряємо JSON без отримання його даних](#json-validation)
- [Не втрачаємо довгий ID й перевіряємо поля](#json-shape)
- [Записуємо enum у JSON його значенням](#json-enums)
- [Вибираємо, що зберігати й відновлювати в об’єкта](#object-serialization)
- [Змінюємо атрибути й додаємо текст через DOM](#dom-convenience)
- [Читаємо HTML через `Dom\HTMLDocument`](#html5-dom)
- [Підставляємо рядок у XPath без зламаних лапок](#xpath-quote)
- [Вставляємо готовий фрагмент HTML](#html-fragments)
- [Одразу вибираємо, звідки читати XML і куди писати](#xml-factories)

<a id="invalid-utf8-json"></a>
## Що робити з пошкодженим UTF-8 у JSON

**PHP 7.2+ | json** | [PHP](https://www.php.net/manual/en/json.constants.php)

Якщо в рядку трапилися непридатні для UTF-8 байти, звичайний `json_encode()` поверне `false`. Можна замінити пошкоджене місце символом U+FFFD через `JSON_INVALID_UTF8_SUBSTITUTE` або прибрати його через `JSON_INVALID_UTF8_IGNORE`. Ці прапорці працюють під час кодування й декодування.

```php
$text = "A\xFFB";
$rejected = json_encode( $text ); # false
$error = json_last_error(); # JSON_ERROR_UTF8
$substituted = json_encode( $text, JSON_INVALID_UTF8_SUBSTITUTE ); # "A\ufffdB"
$ignored = json_encode( $text, JSON_INVALID_UTF8_IGNORE ); # "AB"
```

У прикладі один варіант залишає позначку на місці помилки, інший перетворює текст на `AB`. Початковий рядок не змінюється, але в результаті частину даних уже втрачено. Для ідентифікатора чи іншого точного значення краще обробити помилку, ніж непомітно змінювати вміст. Це не переведення з іншого кодування в UTF-8. Якщо читаємо `json_last_error()`, робимо це до наступного виклику JSON.

<a id="json-exceptions"></a>
## Ловимо помилку JSON як виняток

**PHP 7.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-decode.php) | [PHP](https://www.php.net/manual/en/class.jsonexception.php)

Із `JSON_THROW_ON_ERROR` не треба після кожного виклику окремо читати останню помилку JSON. Якщо кодування чи декодування не вдалося, отримуємо `JsonException` й обробляємо його в `catch`.

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

У прикладі JSON `null` цілком коректний і дає звичайний `null`. А зламаний JSON потрапляє в `catch`. Так ці два випадки більше не змішуються в одному результаті.

<a id="json-validation"></a>
## Перевіряємо JSON без отримання його даних

**PHP 8.3+ | json** | [PHP](https://www.php.net/manual/en/function.json-validate.php)

Якщо JSON треба лише зберегти чи передати далі, `json_validate()` перевірить його запис без створення масиву або об’єкта з вмістом. У прикладі перший документ підходить, другий із незаповненим `active` ні.

```php
$json = '{"active":true}';
$valid = json_validate( $json ); # true
$invalid = json_validate( '{"active":}' ); # false
```

Якщо вміст усе одно треба читати, простіше один раз викликати `json_decode()` із `JSON_THROW_ON_ERROR`. Перевірка перед декодуванням розбере той самий документ двічі. І коректний запис JSON іще не означає, що всередині є потрібні поля та правильні типи.

<a id="json-shape"></a>
## Не втрачаємо довгий ID й перевіряємо поля

**PHP 7.3+ | json | Контекст** | [PHP](https://www.php.net/manual/en/json.constants.php) | [PHP](https://www.php.net/manual/en/function.json-decode.php)

JSON може містити не лише об’єкт із полями, а й список, окреме значення чи `null`. Тому після декодування перевіряємо, що отримали потрібну структуру. А для завеликого цілого ID додаємо `JSON_BIGINT_AS_STRING`, щоб воно збереглося рядком, а не втратило точність у `float`.

```php
$json = '{"id":123456789012345678901234567890,"name":"Ada"}';
$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
if( !is_array($data) || !isset($data['name']) || !is_string($data['name']) )
	throw new InvalidArgumentException( 'Expected a record with a name' );

$id = $data['id']; # '123456789012345678901234567890'
```

Прапорець `JSON_BIGINT_AS_STRING` існував іще до PHP 7; PHP 7.3 потрібен тут для винятків. Є й нюанс зворотного кодування: під час розбору в асоціативні масиви порожній об’єкт може перетворитися на порожній список і потім потрапити в JSON як `[]` замість `{}`.

<a id="json-enums"></a>
## Записуємо enum у JSON його значенням

**PHP 8.1+ | json / Ядро** | [PHP](https://www.php.net/manual/en/language.enumerations.serialization.php)

Backed enum має базове рядкове чи числове значення. Під час кодування JSON береться саме воно. Тому `ExportStatus::Ready` у прикладі перетворюється на звичайне `"ready"`.

```php
enum ExportStatus: string
{
	case Ready = 'ready';
	case Failed = 'failed';
}
$json = json_encode( ['status' => ExportStatus::Ready], JSON_THROW_ON_ERROR );
# {"status":"ready"}
```

Enum без базового значення спричинить помилку кодування. Після декодування готового об’єкта enum теж не буде: отримаємо рядок чи число. Якщо потрібен сам варіант enum, відновлюємо його через `from()` або `tryFrom()`.

<a id="object-serialization"></a>
## Вибираємо, що зберігати й відновлювати в об’єкта

**PHP 7.4+ | Ядро** | [PHP](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) | [PHP](https://www.php.net/manual/en/function.unserialize.php)

`__serialize()` повертає дані, які треба зберегти. `__unserialize()` отримує їх назад і заповнює об’єкт без виклику конструктора. Тому перевірки відновлених значень потрібні тут теж, навіть якщо схожі вже були в конструкторі.

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

У прикладі зберігаємо лише `value` й приймаємо назад лише ціле число. Відновлюємо так тільки довірені дані: список `allowed_classes` сам по собі не робить `unserialize()` безпечним для чужого вмісту. Реалізовувати лише старий інтерфейс `Serializable` застаріло з PHP 8.1.

<a id="dom-convenience"></a>
## Змінюємо атрибути й додаємо текст через DOM

**PHP 8.3+ | dom** | [PHP](https://www.php.net/manual/en/migration83.new-functions.php)

У `DOMElement` з’явилися `toggleAttribute()` для вмикання й видалення атрибута, `insertAdjacentText()` для тексту та `insertAdjacentElement()` для готового елемента. `DOMNode::contains()` допомагає перевірити належність вузла потрібному піддереву. У прикладі вмикаємо `hidden` і додаємо текст у кінець `item`.

```php
$document = new DOMDocument();
$document->loadXML( '<item>PHP</item>' );
$item = $document->documentElement;
$item->toggleAttribute( 'hidden', true );
$item->insertAdjacentText( 'beforeend', ' & XML' );
$xml = $document->saveXML( $item ); # <item hidden="">PHP &amp; XML</item>
```

Знак `&` зберігається як `&amp;`: текст вставлено як текст, а не розібрано як розмітку. Для логічних атрибутів HTML важливий сам факт наявності. Записати рядок `"false"` не означає вимкнути такий атрибут, його треба прибрати.

<a id="html5-dom"></a>
## Читаємо HTML через `Dom\HTMLDocument`

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/class.dom-htmldocument.php)

Для сучасного HTML є окремий `Dom\HTMLDocument`. Він розбирає документ за правилами HTML, а не намагається працювати з ним як з XML. Потім потрібний елемент можна знайти знайомим CSS-селектором: тут беремо `p` усередині `#content`.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><main id="content"><p>PHP</p></main>'
);
$element = $document->querySelector( '#content p' );
$text = $element?->textContent; # PHP
```

Якщо нічого не знайдено, `querySelector()` поверне `null`. Нові класи `Dom` і старі DOM-класи належать до різних API, змішувати їх як один набір не можна. І сам розбір документа не робить HTML безпечним для публікації.

<a id="xpath-quote"></a>
## Підставляємо рядок у XPath без зламаних лапок

**PHP 8.4+ | dom** | [PHP](https://www.php.net/manual/en/domxpath.quote.php)

В імені `O'Brien` уже є апостроф. Якщо просто вклеїти його в XPath між такими самими лапками, вираз зламається. `DOMXPath::quote()` готує правильний запис рядкового значення, навіть якщо всередині обидва види лапок.

```php
$document = new DOMDocument();
$document->loadXML( '<users><user name="O&apos;Brien">7</user></users>' );
$xpath = new DOMXPath( $document );
$name = "O'Brien";
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
$id = $xpath->evaluate( 'string(' . $query . ')' ); # '7'
```

Так захищаємо саме значення. Шляхи, оператори й імена функцій від користувача ця функція не перевіряє: структуру XPath так само задаємо самі.

<a id="html-fragments"></a>
## Вставляємо готовий фрагмент HTML

**PHP 8.5+ | dom** | [PHP](https://www.php.net/manual/en/migration85.new-functions.php) | [PHP](https://www.php.net/manual/en/class.dom-element.php)

`Dom\Element::insertAdjacentHTML()` розбирає HTML-фрагмент з урахуванням елемента, у який вставляємо. Тут додаємо `strong` усередину `div`. А `outerHTML` повертає весь елемент разом із його власним тегом і вмістом.

```php
$document = Dom\HTMLDocument::createFromString(
	'<!doctype html><title>Notes</title><div id="box"></div>'
);
$box = $document->querySelector( '#box' );
$box->insertAdjacentHTML( Dom\AdjacentPosition::BeforeEnd, '<strong>PHP</strong>' );
$html = $box->outerHTML; # <div id="box"><strong>PHP</strong></div>
```

У новому API положення задається варіантом `Dom\AdjacentPosition`, а не старим рядком. Для звичайного тексту від користувача беремо `textContent` або `insertAdjacentText()`. Якщо вставляємо саме HTML, перед показом його треба очистити від небезпечного вмісту.

<a id="xml-factories"></a>
## Одразу вибираємо, звідки читати XML і куди писати

**PHP 8.4+ | xmlreader / xmlwriter** | [PHP](https://www.php.net/manual/en/xmlreader.fromstring.php) | [PHP](https://www.php.net/manual/en/xmlwriter.tomemory.php)

Нові методи одразу створюють об’єкт із потрібним джерелом чи місцем запису. Для читання є `fromString()`, `fromStream()` і `fromUri()`, для запису `toMemory()`, `toStream()` і `toUri()`. У прикладі читаємо атрибут із рядка, а новий XML збираємо в пам’яті.

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

`fromString()` тримає початковий текст у пам’яті, для великих даних краще потік. Якщо XML надійшов ззовні, не вмикаємо зовнішні сутності й завантаження DTD без конкретної контрольованої потреби: це дозволяє документу звертатися до додаткових джерел.
