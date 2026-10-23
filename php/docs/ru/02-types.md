# Типы

[EN](../en/02-types.md) | [UK](../uk/02-types.md) | [RU](../ru/02-types.md)

[Содержание](../../README.ru.md)

- [Типы параметров и результата](#scalar-types)
- [Не подменяем типы через `strict_types`](#strict-types)
- [Разрешаем `null` через `?T`](#nullable)
- [Делаем действие без результата: `void`](#void)
- [Принимаем перебираемые значения и объекты](#iterable-object)
- [Указываем тип у свойства](#typed-properties)
- [Уточняем типы в наследнике](#variance)
- [Разрешаем один из типов: `A|B`](#union)
- [Принимаем любое значение через `mixed`](#mixed)
- [Возвращаем объект нужного наследника: `static`](#static-return)
- [Требуем сразу оба типа: `A&B`](#intersection)
- [После этой функции не продолжаем: `never`](#never)
- [Сочетаем требования и варианты: `(A&B)|C`](#dnf)
- [Возвращаем именно `true`, `false` или `null`](#literal-types)
- [Указываем тип константы класса](#typed-constants)
- [Что проверяет PHP, а что оставляем PHPDoc](#native-type-limits)

<a id="scalar-types"></a>
## Типы параметров и результата

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/migration70.new-features.php)

По объявлению функции сразу видно, что ей передать и что получить обратно. Здесь цена должна быть `float`, количество `int`, а результат `float`. PHP сам проверяет эти типы, поэтому повторять их в PHPDoc ради той же проверки не нужно.

```php
function line_total( float $price, int $quantity ): float
{
	return $price * $quantity;
}

echo line_total( 12.5, 3 ); # 37.5
```

Для обычных значений есть `int`, `float`, `string` и `bool`. Будет ли PHP подгонять значение под указанный тип, зависит от `strict_types`.

<a id="strict-types"></a>
## Не подменяем типы через `strict_types`

**PHP 7.0+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)

Строка `'3'` похожа на число, но всё ещё остаётся строкой. Если не хотим, чтобы PHP молча принял её вместо `int`, включаем `strict_types` в начале файла. В этом примере вызов закончится `TypeError`.

<!-- example: fragment -->
```php
declare( strict_types=1 );

function double_count( int $count ): int
{
	return $count * 2;
}

double_count( '3' ); # TypeError
```

Для аргументов важен файл, из которого вызываем функцию. Для возвращаемого значения важен файл, где она объявлена. Отдельное разрешённое преобразование остаётся: `int` можно передать туда, где нужен `float`.

<a id="nullable"></a>
## Разрешаем `null` через `?T`

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/migration71.new-features.php)

Имя может быть строкой, а может отсутствовать. Запись `?string` прямо разрешает оба случая. А `= null` решает другую задачу: позволяет вообще не передавать этот аргумент при вызове.

```php
function display_name( ?string $name = null ): string
{
	return $name ?? 'Guest';
}

echo display_name(); # Guest
```

Поэтому вызов без имени выводит `Guest`. С PHP 8.0 можно написать тот же тип как `string|null`. Старую неявную запись `string $name = null` лучше заменить: с PHP 8.4 она устарела.

<a id="void"></a>
## Делаем действие без результата: `void`

**PHP 7.1+** | [PHP](https://www.php.net/manual/en/language.types.void.php)

Эта функция только выводит статус, получать от неё значение не нужно. Это и обозначает `void`. Если статус скрыт, обычный `return;` позволяет выйти раньше.

```php
function print_status( bool $visible ): void
{
	if( !$visible )
		return;

	echo 'Ready';
}
```

Вернуть `null` через `return null;` уже нельзя: это тоже явное возвращение значения.

<a id="iterable-object"></a>
## Принимаем перебираемые значения и объекты

**PHP 7.1 / 7.2+** | [PHP](https://www.php.net/manual/en/language.types.iterable.php)

Если функции нужен только `foreach`, можно не требовать именно массив. Тип `iterable` с PHP 7.1 принимает и массивы, и `Traversable`, в том числе генераторы. В примере суммирование от конкретного способа хранения не зависит.

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

Типы элементов `iterable` не проверяет. А отдельный тип `object` с PHP 7.2 означает, что подходит любой объект, без привязки к одному классу.

<a id="typed-properties"></a>
## Указываем тип у свойства

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.properties.php)

Тип можно написать прямо у свойства. Но объявить свойство и записать в него значение не одно и то же. В примере у `id` значения пока нет, а у `email` уже есть `null`. Один только знак `?` значение `null` не присваивает.

```php
class Profile
{
	public int $id;
	public ?string $email = null;
}

$profile = new Profile();
var_dump( isset($profile->id), $profile->email ); # false, null
```

Если прочитать `id` до присваивания, получим `Error`. Проверить через `isset()` можно безопасно, но он вернёт `false` и для такого состояния, и для `null`. Тип `callable` у свойства не поддерживается; если храним замыкание, есть `Closure`.

<a id="variance"></a>
## Уточняем типы в наследнике

**PHP 7.4+** | [PHP](https://www.php.net/manual/en/language.oop5.variance.php)

Метод наследника может принять больше вариантов на входе, а вернуть более конкретный результат. Это называют контравариантностью параметров и ковариантностью результата. Здесь вместо одного `stdClass` разрешаем любой `object`, зато на выходе обещаем именно `stdClass`.

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

Это сохраняет смысл исходного объявления: всё, что подходило старому методу, подходит и новому. С PHP 7.2 можно было просто убрать тип параметра. Для обычных свойств, которые и читают, и записывают, такой свободы нет: их тип остаётся тем же.

<a id="union"></a>
## Разрешаем один из типов: `A|B`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.type-system.php)

Через `|` перечисляем допустимые варианты. Например, поиск позиции возвращает либо число, либо `false`, если ничего не нашлось. Запись `int|false` это прямо показывает.

```php
function find_position( string $text, string $needle ): int|false
{
	return strpos( $text, $needle );
}

echo find_position( 'php', 'p' ); # 0
```

В примере найденная позиция равна нулю, это успешный результат. Отличаем его от ошибки через `!== false`. В составе такого типа `false` разрешён с PHP 8.0, а как самостоятельный тип с PHP 8.2.

<a id="mixed"></a>
## Принимаем любое значение через `mixed`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.mixed.php)

Иногда функция действительно должна принимать что угодно. Например, показать тип переданного значения или сохранить его в универсальном контейнере. Для этого есть `mixed`: он принимает любое значение PHP, в том числе `null`.

```php
function describe_value( mixed $value ): string
{
	return get_debug_type( $value );
}

echo describe_value( ['id' => 42] ); # array
```

Добавлять `null` ещё раз не нужно. Записи `?mixed` и `mixed|null` недопустимы, он уже входит в `mixed`.

<a id="static-return"></a>
## Возвращаем объект нужного наследника: `static`

**PHP 8.0+** | [PHP](https://www.php.net/manual/en/language.types.relative-class-types.php)

Метод создания объявлен в `Record`, но вызываем его через `UserRecord`. Тип результата `static` учитывает именно класс вызова. Поэтому наследнику не нужно переписывать метод только ради правильного типа результата.

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

`self`, в отличие от `static`, указывает на класс, где метод объявлен. С PHP 8.5 при переопределении в `final`-классе результат `static` можно заменить на `self` или имя этого класса.

<a id="intersection"></a>
## Требуем сразу оба типа: `A&B`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

Иногда нужен не один из вариантов, а объект с двумя возможностями сразу. Запись `Countable&Iterator` говорит: объект должен и перебираться через методы итератора, и позволять посчитать элементы.

```php
function item_count( Countable&Iterator $items ): int
{
	return count( $items );
}

echo item_count( new ArrayIterator([10, 20]) ); # 2
```

`ArrayIterator` подходит под оба требования. Такое объединение требований работает для объектов; склеить скалярные типы как `int&string` нельзя.

<a id="never"></a>
## После этой функции не продолжаем: `never`

**PHP 8.1+** | [PHP](https://www.php.net/manual/en/language.types.never.php)

Если функция всегда выбрасывает исключение, завершает процесс или остаётся в бесконечном цикле, указываем `never`. Так сразу видно, что обычного возврата к следующей строке у вызывающего кода не будет.

```php
function reject_request(): never
{
	throw new RuntimeException( 'Request rejected' );
}
```

В примере любой вызов закончится исключением. Даже пустой `return;` здесь запрещён, а если выполнение всё же дойдёт до конца функции, PHP выбросит `TypeError`.

<a id="dnf"></a>
## Сочетаем требования и варианты: `(A&B)|C`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/migration82.new-features.php)

Можно совместить `&` и `|`: принять объект с двумя нужными возможностями либо совсем другой вариант. Это называют DNF. В примере подходит одновременно `Countable` и `Iterator`, а если коллекции нет, разрешён `null`.

```php
function optional_count( (Countable&Iterator)|null $items ): int
{
	return ($items === null) ? 0 : count( $items );
}

echo optional_count( null ); # 0
echo optional_count( new ArrayIterator([1, 2]) ); # 2
```

Группу с `&` обязательно берём в скобки. Произвольно переставлять такие группы нельзя: запись `A&(B|C)` PHP не принимает.

<a id="literal-types"></a>
## Возвращаем именно `true`, `false` или `null`

**PHP 8.2+** | [PHP](https://www.php.net/manual/en/language.types.singleton.php)

Тип результата может разрешать не любой `bool`, а ровно одно значение. Например, `true` подходит для операции, которая при успехе возвращает `true`, а при ошибке выбрасывает исключение.

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

Если подходят и `true`, и `false`, пишем `bool`: запись `true|false` не разрешена. Отдельный тип `null` тоже возвращает конкретное значение, поэтому это не то же самое, что `void`.

<a id="typed-constants"></a>
## Указываем тип константы класса

**PHP 8.3+** | [PHP](https://www.php.net/manual/en/language.oop5.constants.php)

У константы класса тоже можно закрепить тип. Если наследник переопределит такую константу, новый тип должен оставаться совместимым. Это помогает сохранить ожидаемое значение при наследовании.

```php
class Limits
{
	public const int PAGE_SIZE = 50;
	public const array SORT_FIELDS = ['name', 'created_at'];
}
```

То же работает у констант интерфейсов, трейтов и enum. У обычной глобальной `const` такую запись типа сделать нельзя.

<a id="native-type-limits"></a>
## Что проверяет PHP, а что оставляем PHPDoc

**PHP 7.0-8.5 | Контекст** | [PHP](https://www.php.net/manual/en/language.types.declarations.php)

Тип `array` говорит PHP только то, что передан массив. Какие в нём ключи и элементы, он по такому объявлению не проверяет. Уточнение `list<User>` в PHPDoc помогает редактору и статическому анализатору понять, что внутри список пользователей, но сам PHP эту запись не исполняет.

```php
/** @param list<User> $users */
function take_users( array $users ) {}
```

Так же и со `string`: строка может оказаться неверным email, чужим путём или небезопасным HTML. Типы помогают отсеять часть ошибок, а содержимое всё равно проверяем там, где используем значение.
