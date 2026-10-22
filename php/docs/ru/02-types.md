# Типы

[English](../en/02-types.md) | [Українська](../uk/02-types.md) | [Русский](../ru/02-types.md)

[Содержание](../../README.ru.md)

- [Скалярные параметры и результат](#scalar-types)
- [strict_types](#strict-types)
- [Nullable-типы](#nullable)
- [void](#void)
- [iterable / object](#iterable-object)
- [Типизированные свойства](#typed-properties)
- [Ковариантность / контравариантность](#variance)
- [Union: A|B](#union)
- [mixed](#mixed)
- [Тип результата static](#static-return)
- [Intersection: A&B](#intersection)
- [never](#never)
- [DNF: (A&B)|C](#dnf)
- [Отдельные true / false / null](#literal-types)
- [Типизированные константы](#typed-constants)
- [PHPDoc и нативные типы](#native-type-limits)

<a id="scalar-types"></a>
## Скалярные параметры и результат

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/migration70.new-features.php)

Типы скалярных параметров и результата видны прямо в сигнатуре. PHP проверяет значения при вызове; отдельно дублировать эти типы в PHPDoc уже не нужно.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

Скалярные типы: `int`, `float`, `string` и `bool`; правила преобразования зависят от `strict_types`.

<a id="strict-types"></a>
## strict_types

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

`strict_types` уместен, когда числовая строка не должна незаметно подходить параметру `int`. Директиву ставят в начале PHP-файла.

<!-- example: fragment -->
```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

double_count( '3' ); # TypeError
```

Строгость аргументов зависит от файла вызова, проверка результата от файла объявления. `int` всё ещё разрешён для `float`.

<a id="nullable"></a>
## Nullable-типы

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

`?T` описывает значение, которое может отсутствовать: например, необязательное имя или неудачный поиск. Значение `= null` отдельно позволяет пропустить аргумент.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name(); # Guest
```

`string|null` является другим вариантом записи с 8.0. Неявная форма `string $name = null` устарела с 8.4.

<a id="void"></a>
## void

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/language.types.void.php)

`void` обозначает действие без полезного результата. Досрочный `return` без значения разрешён, но `return null` нет.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}
```

<a id="iterable-object"></a>
## iterable / object

**PHP 7.1 / 7.2+** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

`iterable` (7.1) позволяет передать в одну функцию массив или `Traversable`, включая генератор. Это удобно, когда нужен только обход, а не доступ по индексам массива. `object` (7.2) принимает любой объект.

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

`iterable` не ограничивает типы элементов.

<a id="typed-properties"></a>
## Типизированные свойства

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.properties.php)

Свойство может иметь собственный тип. Без начального значения типизированное свойство остаётся неинициализированным; даже nullable-объявление не присваивает `null` автоматически.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();
var_dump( isset($profile->id), $profile->email ); # false, null
```

Чтение id до присваивания вызывает `Error`. `isset()` безопасен, но не отличает неинициализированное состояние от `null`. `callable` не является типом свойства; для сохранённого замыкания есть `Closure`.

<a id="variance"></a>
## Ковариантность / контравариантность

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.variance.php)

Переопределённый метод может принимать более широкий входной тип и возвращать более конкретный. Здесь реализация принимает любой `object` вместо только `stdClass`, но обещает `stdClass` вместо общего `object`.

```php
interface Factory
{
	public function create( stdClass $data ): object;
}

class CustomFactory implements Factory
{
	public function create( object $data ): stdClass
	{
		return new stdClass();
	}
}
```

7.2 уже позволял убрать тип параметра. Обычные свойства с чтением и записью остаются инвариантными.

<a id="union"></a>
## Union: A|B

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.type-system.php)

Если функция действительно возвращает несколько видов значения, их можно перечислить в типе результата. `int|false` подходит для поиска позиции, где ноль является правильным успешным результатом.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

echo find_position( 'php', 'p' ); # 0
```

`false` разрешён в union с 8.0, отдельно с 8.2. Проверяйте неудачу через !== `false`, чтобы не потерять нулевую позицию.

<a id="mixed"></a>
## mixed

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.mixed.php)

`mixed` явно принимает любое значение PHP, включая `null`. Он подходит универсальному форматировщику или контейнеру, контракт которого действительно не сужает входной тип.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

`mixed` уже включает `null`, поэтому `?mixed` и `mixed|null` недопустимы.

<a id="static-return"></a>
## Тип результата static

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

`static` учитывает класс вызова, а `self` обозначает класс объявления метода. Поэтому наследуемая фабрика может точно описать результат и для наследников.

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

8.5: переопределение в final-классе может заменить `static` на `self` или имя этого класса.

<a id="intersection"></a>
## Intersection: A&B

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

A&B требует, чтобы один объект соответствовал обоим контрактам. `Countable&Iterator` уместен, когда функции нужны методы итерации и количество элементов.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

Скалярные пересечения `int&string` недопустимы.

<a id="never"></a>
## never

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.never.php)

`never` означает, что функция не возвращается обычным путём: она выбрасывает исключение, завершает процесс или работает бесконечно. Так путь прерывания виден прямо из сигнатуры.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}
```

Даже `return` без значения запрещён; достижение конца вызывает `TypeError`.

<a id="dnf"></a>
## DNF: (A&B)|C

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/migration82.new-features.php)

DNF сочетает пересечения с альтернативами. Эта сигнатура принимает объект, который одновременно является `Countable` и `Iterator`, либо `null`, если коллекции нет.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Скобки вокруг групп пересечения обязательны. `A&(B|C)` не поддерживается.

<a id="literal-types"></a>
## Отдельные true / false / null

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/language.types.singleton.php)

Тип результата может обещать ровно `true`, `false` или `null`. Например, `true` уместен для операции, которая возвращает успех, а при ошибке выбрасывает исключение.

```php
function confirm_ready(): true
{
	return true;
}

function missing_value(): null
{
	return null;
}
```

`true|false` недопустим; используйте `bool`. `null` является значением результата, в отличие от `void`.

<a id="typed-constants"></a>
## Типизированные константы

**PHP 8.3+** | [PHP](https://www.php.net/manual/en/language.oop5.constants.php)

Константы класса могут иметь тип как часть наследуемого контракта. Переопределение в наследнике должно оставаться совместимым по типу. Это также относится к константам интерфейсов, трейтов и enum.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}
```

Обычная глобальная `const` не поддерживает такое объявление типа.

<a id="native-type-limits"></a>
## PHPDoc и нативные типы

**PHP 7.0-8.5 | Контекст** | [PHP](https://www.php.net/manual/en/language.types.declarations.php)

Объявление `array` проверяет тип контейнера, а не его ключи или типы элементов. PHPDoc вроде `list<User>` уточняет контракт для IDE или статического анализатора, но сам PHP эту аннотацию не проверяет. Так же `string` не гарантирует корректный email, доверенный путь или безопасный HTML; эти проверки выполняет приложение.

```php
/** @param list<User> $users */
function take_users( array $users ) {}
```
