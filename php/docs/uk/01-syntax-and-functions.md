# Синтаксис і функції

[EN](../en/01-syntax-and-functions.md) | [UK](../uk/01-syntax-and-functions.md) | [RU](../ru/01-syntax-and-functions.md)

[Зміст](../../README.uk.md)

**На цій сторінці**

- [Значення за замовчуванням через `??`](#null-coalescing)
- [Доповнення налаштувань через `??=`](#null-coalescing-assignment)
- [Сортування оператором `<=>`](#spaceship)
- [Стрілкові функції та захоплені значення](#arrow-functions)
- [Іменовані аргументи](#named-arguments)
- [Рядкові ключі стають іменованими аргументами](#unpacking-named-arguments)
- [Іменовані аргументи після розпакування](#named-after-unpacking)
- [`match` повертає значення та порівнює строго](#match)
- [Ланцюжок необов’язкових об’єктів через `?->`](#nullsafe)
- [`throw` усередині виразу](#throw-expression)
- [Перетворення callable через `Closure::fromCallable()`](#closure-from-callable)
- [Синтаксис посилання на функцію: `function_name(...)`](#first-class-callables)
- [Оператор послідовної обробки `|>`](#pipe)
- [Callback у константних виразах](#closures-in-constant-expressions)
- [Рекурсія через `Closure::getCurrent()`](#recursive-closures)
- [Кінцева кома у викликах та оголошеннях](#trailing-commas)
- [Читабельні числові літерали](#numeric-literals)
- [Групування імпортів простору імен](#group-use)
- [Тимчасова прив’язка замикання через `call()`](#closure-call)
- [Вирази в ініціалізаторах статичних локальних змінних](#static-initializers)
- [`exit()` і `die()` поводяться ближче до звичайних функцій](#exit-callable)
- [Приведення типу в константних виразах](#casts-in-constant-expressions)
- [Атрибути глобальних констант](#global-constant-attributes)

<a id="null-coalescing"></a>
## Значення за замовчуванням через `??`

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce)

`??` підставляє значення для відсутніх або `null`-значень, зберігаючи `0`, `false` і `''`.

```php
$config = ['limit' => 0, 'label' => null];
$limit = $config['limit'] ?? 20;
$label = $config['label'] ?? 'Untitled';

echo $limit, ':', $label; # 0:Untitled
```

`array_key_exists()` розрізняє відсутній ключ і `null`; при поєднанні `??` зі склеюванням рядків ставте дужки.

<a id="null-coalescing-assignment"></a>
## Доповнення налаштувань через `??=`

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.assignment.php)

`??=` записує значення за замовчуванням, якщо поточне відсутнє або дорівнює `null`.

```php
$config = ['retries' => 0];
$config['timeout'] ??= 30;
$config['retries'] ??= 3;

echo $config['timeout'], ':', $config['retries']; # 30:0
```

Права частина обчислюється лише за потреби присвоєння; наявні нульові значення зберігаються.

<a id="spaceship"></a>
## Сортування оператором `<=>`

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.comparison.php)

`<=>` повертає `-1`, `0` або `1` для сортування; перестановка операндів змінює порядок на зворотний.

```php
$rows = [['score' => 8], ['score' => 2], ['score' => 5]];
usort( $rows, function( array $left, array $right ): int
{
	return $left['score'] <=> $right['score'];
} );

echo implode( ',', array_column($rows, 'score') ); # 2,5,8
```

Оператор використовує правила порівняння PHP, а не строгу тотожність, тому змішані типи можуть потребувати нормалізації.

<a id="arrow-functions"></a>
## Стрілкові функції та захоплені значення

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arrow.php)

`fn` оголошує замикання з одним виразом і захоплює зовнішні змінні за значенням.

```php
$factor = 10;
$multiply = fn( int $value ): int => $value * $factor;
$factor = 20;
echo $multiply( 3 ); # 30
```

Об’єкти залишаються тими самими екземплярами; для захоплення за посиланням потрібне звичайне замикання, а `static fn` не прив’язує `$this`.

<a id="named-arguments"></a>
## Іменовані аргументи

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

Іменовані аргументи вибирають параметри за назвою та дозволяють пропускати необов’язкові.

```php
function describe_item( string $name, string $separator = ':', int $limit = 20 ): string
{
	return $name . $separator . $limit;
}

$label = describe_item( 'Report', limit: 5 );

echo $label; # Report:5
```

Позиційні аргументи мають іти першими, а перейменування параметра може зламати виклики з його назвою.

<a id="unpacking-named-arguments"></a>
## Рядкові ключі стають іменованими аргументами

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.call-user-func-array.php)

Рядкові ключі розпакованого масиву аргументів стають назвами параметрів, зокрема в `call_user_func_array()`.

```php
function format_range( int $start, int $end ): string
{
	return $start . '-' . $end;
}

$arguments = ['end' => 9, 'start' => 3];
echo format_range( ...$arguments ); # 3-9
```

Невідомі назви та повторне передавання параметра спричиняють помилки; для позиційного порядку з довільними ключами використовуйте `array_values()`.

<a id="named-after-unpacking"></a>
## Іменовані аргументи після розпакування

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arguments.php)

Іменовані аргументи можуть іти після розпакування аргументів.

```php
function join_parts( string $left, string $right, string $separator = '/' ): string
{
	return $left . $separator . $right;
}

$parts = ['docs', 'types'];
echo join_parts( ...$parts, separator: ':' ); # docs:types
```

Вони не можуть повторно передавати параметр, уже заповнений розпакованим масивом.

<a id="match"></a>
## `match` повертає значення та порівнює строго

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/control-structures.match.php)

`match` повертає результат виразу, порівнює строго та не переходить у наступну гілку.

```php
$status = '1';
$label = match( $status )
{
	1 => 'numeric',
	'1' => 'text',
	2, 3 => 'known',
	default => 'unknown',
};

echo $label; # text
```

Без відповідної гілки або `default` виникає `UnhandledMatchError`; це відрізняється від нестрогих порівнянь у `switch`.

<a id="nullsafe"></a>
## Ланцюжок необов’язкових об’єктів через `?->`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.nullsafe)

`?->` зупиняє ланцюжок доступу до об’єктів, коли лівий операнд дорівнює `null`; позначайте кожен крок, на якому можливий `null`.

```php
$profile = null;
$city = $profile?->address?->city ?? 'Not provided';

echo $city; # Not provided
```

Доступний лише для читання та не приховує некоректних звернень чи винятків у ненульових об’єктах.

<a id="throw-expression"></a>
## `throw` усередині виразу

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/exceptions)

`throw` може бути частиною виразу, зокрема запасним варіантом у `??`.

```php
$config = ['token' => 'local-example'];
$token = $config['token'] ?? throw new InvalidArgumentException( 'Token is required' );
```

Тут порожній рядок усе ще дозволений, бо `??` перевіряє лише відсутність і `null`.

<a id="closure-from-callable"></a>
## Перетворення callable через `Closure::fromCallable()`

**PHP 7.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/closure.fromcallable.php)

`Closure::fromCallable()` створює замикання та перевіряє доступність callable у поточній області.

```php
function add_prefix( string $name ): string
{
	return 'note:' . $name;
}

$format = Closure::fromCallable( 'add_prefix' );

echo $format( 'types' ); # note:types
```

Некоректний або недоступний callable спричиняє `TypeError`; [синтаксис посилання на функцію](#first-class-callables) має ту саму семантику з PHP 8.1.

<a id="first-class-callables"></a>
## Синтаксис посилання на функцію: `function_name(...)`

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)

`function_name(...)` створює `Closure` і перевіряє доступ під час створення; callable методу зберігає цю область видимості.

```php
$normalize = trim(...);
$values = array_map( $normalize, [' one ', ' two '] );

echo implode( ',', $values ); # one,two
```

Це не розпакування аргументів; синтаксис не поєднується з `?->` або `new ClassName(...)`.

<a id="pipe"></a>
## Оператор послідовної обробки `|>`

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.functional.php)

`|>` передає ліве значення callable праворуч і повертає результат виклику.

```php
$slug = ' PHP Notes '
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $text ): string => str_replace(' ', '-', $text));

echo $slug; # php-notes
```

Додаткові аргументи або іншу позицію аргументу задавайте через замикання; ланцюжок виконується синхронно.

<a id="closures-in-constant-expressions"></a>
## Callback у константних виразах

**PHP 8.5+ | Ядро** | [Документація PHP](https://wiki.php.net/rfc/closures_in_const_expr)

Посилання на функції та обмежені статичні замикання дозволені в константних виразах, зокрема в значеннях за замовчуванням і константах.

```php
function normalize_name( string $name, Closure $normalizer = trim(...) ): string
{
	return $normalizer( $name );
}

echo normalize_name( ' Ada ' ); # Ada
```

Вираз створює callback, а не виконує його; замикання має бути статичним і не може захоплювати змінні через `use`.

<a id="recursive-closures"></a>
## Рекурсія через `Closure::getCurrent()`

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/closure.getcurrent.php)

`Closure::getCurrent()` повертає поточне замикання для рекурсії без захоплення його змінної за посиланням.

```php
$factorial = static function( int $value ): int
{
	if( $value < 0 )
		throw new InvalidArgumentException( 'Expected a non-negative integer' );

	if( $value <= 1 )
		return 1;

	$current = Closure::getCurrent();
	return $value * $current( $value - 1 );
};

echo $factorial( 5 ); # 120
```

Виклик поза замиканням спричиняє `Error`.

<a id="trailing-commas"></a>
## Кінцева кома у викликах та оголошеннях

**PHP 7.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arguments.php)

Завершальна кома підтримується у викликах з PHP 7.3, а в оголошеннях параметрів і списках `use` замикань з PHP 8.0.

```php
echo implode(
	',',
	['one', 'two'],
);
# one,two
```

Вона не додає ще одного аргументу.

<a id="numeric-literals"></a>
## Читабельні числові літерали

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.types.integer.php)

PHP 7.4 дозволяє розділювачі `_` між цифрами; PHP 8.1 додає явний вісімковий запис `0o644`, еквівалентний `0644` (десяткове `420`).

```php
$max_bytes = 10_485_760;
$mask = 0b1111_0000;
```

Розділювачі є синтаксисом коду, а не загальним форматом перетворення рядків на числа.

<a id="group-use"></a>
## Групування імпортів простору імен

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.namespaces.importing.php)

Групове імпортування використовує спільний префікс простору імен для кількох назв.

```php
use App\Report\{CsvWriter, JsonWriter};

$writers = [CsvWriter::class, JsonWriter::class];
```

Імпорт назви не завантажує клас і не створює його екземпляр.

<a id="closure-call"></a>
## Тимчасова прив’язка замикання через `call()`

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/closure.call.php)

`Closure::call()` викликає замикання з тимчасовим об’єктом `$this` і його областю класу.

```php
class Counter
{
	private $value = 7;
}

$read = function(): int
{
	return $this->value;
};
echo $read->call( new Counter() ); # 7
```

Статичне замикання не можна прив’язати до `$this`.

<a id="static-initializers"></a>
## Вирази в ініціалізаторах статичних локальних змінних

**PHP 8.3+ | Ядро** | [Документація PHP](https://wiki.php.net/rfc/arbitrary_static_variable_initializers)

Ініціалізатори локальних статичних змінних можуть містити динамічні вирази, які обчислюються при першому досягненні оголошення.

```php
function next_number(): int
{
	static $number = strlen( 'seed' );
	return ++$number;
}

echo next_number(), ':', next_number(); # 5:6
```

Значення зберігається між викликами функції в поточному процесі; між окремими PHP-запитами спільного кешу немає.

<a id="exit-callable"></a>
## `exit()` і `die()` поводяться ближче до звичайних функцій

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration84.incompatible.php)

`exit()` і його псевдонім `die()` підтримують callable та звичайні правила типів аргументів, включно зі `strict_types`.

```php
$terminate = exit(...);
$is_closure = $terminate instanceof Closure;
```

Ціле число задає код виходу, рядок виводиться; некоректні типи аргументів тепер спричиняють `TypeError`.

<a id="casts-in-constant-expressions"></a>
## Приведення типу в константних виразах

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.constants.syntax.php)

Явні приведення типів дозволені в константних виразах.

```php
const WHOLE_SECONDS = (int)3.9;

echo WHOLE_SECONDS; # 3
```

Правила перетворення не змінилися: `(int)3.9` відкидає дробову частину та дає `3`.

<a id="global-constant-attributes"></a>
## Атрибути глобальних констант

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration85.new-features.php)

Атрибути можна додавати до глобальних констант, оголошених через `const`; читання цієї застарілої константи спричиняє діагностичне повідомлення.

```php
#[Deprecated( 'Use DEFAULT_PAGE_SIZE' )]
const OLD_PAGE_SIZE = 20;
const DEFAULT_PAGE_SIZE = 20;
```

Константи, створені через `define()`, не підтримують цей синтаксис.
