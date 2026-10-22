# Типы и контракты

[English](../en/02-types.md) | [Українська](../uk/02-types.md) | [Русский](../ru/02-types.md)

[Содержание](../../README.ru.md) | [PHP 7.0-8.5](VERSIONS.md) | [Обновление кода](MIGRATION.md)

От скалярных объявлений PHP 7 до типизированных констант и точных контрактов. Каждый раздел объясняет, что проверяет PHP и что остаётся проверять приложению. Версии обозначают доступность возможностей, а не рекомендацию запускать старый интерпретатор.

## Содержание

- [Скалярные параметры и тип результата](#scalar-types)
- [strict_types: управление преобразованием типов](#strict-types)
- [Nullable-типы](#nullable)
- [void: завершение без результата](#void)
- [iterable и object](#iterable-object)
- [Типизированные свойства и неинициализированное состояние](#typed-properties)
- [Ковариантность и контравариантность](#variance)
- [Union-типы: один из нескольких вариантов](#union)
- [mixed: явно произвольное значение](#mixed)
- [Тип результата static](#static-return)
- [Intersection-типы: все контракты одновременно](#intersection)
- [never: без обычного возврата](#never)
- [DNF: пересечения и альтернативы](#dnf)
- [Отдельные типы true, false и null](#literal-types)
- [Типизированные константы класса](#typed-constants)
- [Что нативный тип не проверяет](#native-type-limits)

<a id="scalar-types"></a>
## Скалярные параметры и тип результата

**PHP 7.0+**

Контракт виден из сигнатуры: цена является дробным числом, количество целым, результат тоже дробный. PHP проверяет значения на границе функции. Это убирает повторяющиеся проверки внутри обычного прикладного кода.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

Скалярные типы записывают как int, float, string и bool. Пишите bool, а не boolean. Тип определяет вид значения, но не гарантирует, что количество положительное или строка содержит правильный адрес почты. Это отдельные правила проверки.

[Документация PHP](https://www.php.net/manual/en/migration70.new-features.php)

<a id="strict-types"></a>
## strict_types: управление преобразованием типов

**PHP 7.0+**

По умолчанию PHP может преобразовать совместимый скалярный аргумент, например числовую строку, в объявленный тип. Строгий режим полезен, когда случайное преобразование скроет ошибку в коде. Директиву ставят в начале PHP-файла.

```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

try
{
	double_count( '3' );
}
catch( TypeError $error )
{
	echo 'Expected an integer';
}
```

Строгость скалярных аргументов определяет файл, из которого вызывают функцию. Проверка результата зависит от файла с её объявлением. Для параметра float можно передать int. strict_types не заменяет проверку входных данных, не меняет === и не делает все внутренние операции строгими.

[Документация PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

<a id="nullable"></a>
## Nullable-типы

**PHP 7.1+**

Используйте ?T, если значением может быть T или null. Например, поиск может не найти запись. Nullable-параметр не становится необязательным автоматически.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name();          # Guest
echo display_name( '' );      # empty string
echo display_name( 'Olena' ); # Olena
```

Значение по умолчанию = null позволяет пропустить аргумент. Без него значение всё равно нужно передать, даже если это null. С PHP 8.0 можно писать string|null. Неявная nullable-запись string $name = null устарела в PHP 8.4; обозначайте null явно.

[Документация PHP](https://www.php.net/manual/en/migration71.new-features.php)

<a id="void"></a>
## void: завершение без результата

**PHP 7.1+**

void означает, что функция выполняет действие и не возвращает полезного значения. Для досрочного выхода можно написать return без значения. return null не разрешён.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}

print_status( true ); # Ready
```

Выбирайте void для обычной операции, которая может нормально завершиться. never нужен, когда нормальное завершение невозможно. Эти типы не описывают значения свойств.

[Документация PHP](https://www.php.net/manual/en/language.types.void.php)

<a id="iterable-object"></a>
## iterable и object

**PHP 7.1 / 7.2+**

iterable (7.1) принимает массив или объект Traversable: функция работает и с готовым списком, и с генератором. object (7.2) принимает любой объект. Если нужны конкретные методы, лучше указать соответствующий интерфейс.

```php
function sum_values( iterable $values ): int
{
	$total = 0;

	foreach( $values as $value )
		$total += $value;

	return $total;
}

echo sum_values( [2, 3, 5] ); # 10
```

iterable не проверяет тип каждого элемента. Генераторы и потоки с ключами разобраны в [главе о массивах и итерации](04-arrays-and-iteration.md). object полезен в универсальных инструментах, а не вместо каждого предметного интерфейса.

[Документация PHP](https://www.php.net/manual/en/language.types.iterable.php)

<a id="typed-properties"></a>
## Типизированные свойства и неинициализированное состояние

**PHP 7.4+**

Свойство может иметь собственный тип. Неинициализированное типизированное свойство отличается от свойства со значением null. Даже nullable-свойству нужно начальное значение перед чтением.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();

var_dump( isset($profile->id) ); # false
var_dump( $profile->email );    # NULL

$profile->id = 42;
echo $profile->id;              # 42
```

Чтение id до присваивания вызовет Error. isset() безопасен, но не отличает неинициализированное свойство от null; для этого есть ReflectionProperty::isInitialized(). callable не разрешён как тип свойства; для сохранённого замыкания часто подходит Closure.

[Документация PHP](https://www.php.net/manual/en/language.oop5.properties.php)

<a id="variance"></a>
## Ковариантность и контравариантность

**PHP 7.4+**

Переопределённый метод может обещать более конкретный результат и принимать более общий аргумент. Объект наследника должен оставаться пригодным везде, где ожидается родительский контракт.

```php
class Animal {}
class Cat extends Animal {}

interface Shelter
{
	public function adopt( Cat $candidate ): Animal;
}

class CatShelter implements Shelter
{
	public function adopt( Animal $candidate ): Cat
	{
		return new Cat();
	}
}

echo get_class( (new CatShelter())->adopt(new Cat()) ); # Cat
```

Полная поддержка появилась в 7.4; версия 7.2 уже позволяла убрать тип параметра при переопределении. Обратное изменение может сузить допустимые аргументы или ослабить обещанный результат. Обычные свойства с чтением и записью преимущественно инвариантны; для property hooks есть отдельные правила.

[Документация PHP](https://www.php.net/manual/en/language.oop5.variance.php)

<a id="union"></a>
## Union-типы: один из нескольких вариантов

**PHP 8.0+**

Объединение типов записывают через |. Оно уместно, когда API действительно имеет несколько видов результата, например целое число при успехе или false при неудаче.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

$position = find_position( 'php', 'p' );

if( $position !== false )
	echo $position; # 0 is a successful position
```

Это контракт, проверяемый во время выполнения, в отличие от PHPDoc. Не превращайте каждый неопределённый параметр в большое объединение: если операции нужен один тип, нормализуйте внешние данные. false внутри union разрешён с 8.0; отдельный тип false появился позже.

[Документация PHP](https://www.php.net/manual/en/language.types.type-system.php)

<a id="mixed"></a>
## mixed: явно произвольное значение

**PHP 8.0+**

mixed явно разрешает любое значение PHP, включая null. Это уместно для универсального контейнера или передачи неизвестного значения дальше. Тип не объясняет, какие операции над значением безопасны.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

mixed уже включает null, поэтому ?mixed и mixed|null недопустимы. Он не проверяет декодированный JSON и не делает чтение ключей массива безопасным. Как только контракт понятен, используйте более точный тип.

[Документация PHP](https://www.php.net/manual/en/language.types.mixed.php)

<a id="static-return"></a>
## Тип результата static

**PHP 8.0+**

self обозначает класс, в котором объявлен метод; static учитывает класс вызова. Это позволяет точнее описать наследуемые именованные конструкторы и цепочки методов.

```php
class Record
{
	public static function create(): static
	{
		return new static();
	}
}

class UserRecord extends Record {}

echo get_class( UserRecord::create() ); # UserRecord
```

static можно указать для результата, но не для параметра или свойства. Нужно вернуть объект, совместимый с классом вызова. В 8.5 переопределённый метод final-класса может заменить родительский результат static на self или название этого final-класса.

[Документация PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

<a id="intersection"></a>
## Intersection-типы: все контракты одновременно

**PHP 8.1+**

Пересечение записывают через &: один объект должен соответствовать всем перечисленным классам или интерфейсам. Countable&Iterator уместен, если нужны и методы итерации, и количество элементов.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

Это один объект с обеими возможностями, а не любая из них и не пара объектов. Скалярные пересечения int&string недопустимы. Для сочетания пересечения с null или другим вариантом нужен DNF-синтаксис с 8.2.

[Документация PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

<a id="never"></a>
## never: без обычного возврата

**PHP 8.1+**

Функция never должна выбросить исключение, завершить процесс или работать бесконечно. Такой контракт объясняет операцию прерывания читателю и анализатору. Её исключение можно перехватить снаружи.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}

try
{
	reject_request();
}
catch( RuntimeException $error )
{
	echo $error->getMessage(); # Request rejected
}
```

Даже return без значения запрещён. Достижение конца функции вызывает TypeError. Не используйте never лишь потому, что функция часто завершается ошибкой: обычный успешный путь требует другого типа.

[Документация PHP](https://www.php.net/manual/en/language.types.never.php)

<a id="dnf"></a>
## DNF: пересечения и альтернативы

**PHP 8.2+**

DNF позволяет объединить целые группы пересечений. Значением может быть объект, который одновременно является Countable и Iterator, либо null.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Скобки вокруг групп пересечения обязательны. Произвольные выражения вроде A&(B|C) не поддерживаются. Если контракт становится сложным, подходящий интерфейс часто понятнее всё более длинного выражения типа.

[Документация PHP](https://www.php.net/manual/en/migration82.new-features.php)

<a id="literal-types"></a>
## Отдельные типы true, false и null

**PHP 8.2+**

Литеральный тип обещает ровно одно значение. Он уместен для узких контрактов и совместимого переопределения; для обычного результата да/нет подходит bool.

```php
function confirm_ready(): true
{
	return true;
}

function missing_value(): null
{
	return null;
}

var_dump( confirm_ready() ); # bool(true)
var_dump( missing_value() ); # NULL
```

false и null входили в соответствующие union-типы с 8.0; в 8.2 они стали отдельными типами и появился true. true|false недопустим: пишите bool. null является значением результата, а void означает отсутствие полезного результата.

[Документация PHP](https://www.php.net/manual/en/language.types.singleton.php)

<a id="typed-constants"></a>
## Типизированные константы класса

**PHP 8.3+**

Константы могут иметь тип вместе со значением. Это полезно для наследуемых контрактов: переопределение должно оставаться совместимым по типу.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}

echo Limits::PAGE_SIZE; # 50
```

Это относится к константам классов, интерфейсов, трейтов и enum. Обычная глобальная const от этого не получает синтаксис объявления типа. Тип array также не описывает каждый элемент массива.

[Документация PHP](https://www.php.net/manual/en/language.oop5.constants.php)

<a id="native-type-limits"></a>
## Что нативный тип не проверяет

**PHP 7.0-8.5 | Контекст**

array обещает только массив, а не ключи id/name, список или элементы User. string не означает адрес почты, доверенный путь или безопасный HTML. Проверяйте структуру и предметные правила там, где внешние данные попадают в код.

```php
function require_positive_id( array $input ): int
{
	$id = $input['id'] ?? null;

	if( !is_int($id) || $id <= 0 )
		throw new InvalidArgumentException( 'Positive integer id required' );

	return $id;
}

echo require_positive_id( ['id' => 42] ); # 42
```

PHPDoc вроде `list<User>` помогает IDE или статическому анализатору, но PHP 7.0-8.5 не проверяет его во время выполнения. Осознанно решайте, принимать ли числовую строку из HTTP с преобразованием или отклонять. Приведение типа не должно незаметно делать из некорректных данных правдоподобный ноль.

[Документация PHP](https://www.php.net/manual/en/language.types.declarations.php)

[Содержание](../../README.ru.md)
