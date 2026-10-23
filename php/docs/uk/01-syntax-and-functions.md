# Синтаксис і функції

[EN](../en/01-syntax-and-functions.md) | [UK](../uk/01-syntax-and-functions.md) | [RU](../ru/01-syntax-and-functions.md)

[Зміст](../../README.uk.md)

**На цій сторінці**

- [Запасне значення через `??`](#null-coalescing)
- [Доповнюємо налаштування через `??=`](#null-coalescing-assignment)
- [Сортуємо через `<=>`](#spaceship)
- [Короткі функції через `fn`](#arrow-functions)
- [Передаємо аргументи за ім’ям](#named-arguments)
- [Імена аргументів із ключів масиву](#unpacking-named-arguments)
- [Додаємо аргумент після розпакування](#named-after-unpacking)
- [Вибираємо значення через `match`](#match)
- [Читаємо ланцюжок об’єктів через `?->`](#nullsafe)
- [Викидаємо виняток просто у виразі](#throw-expression)
- [Отримуємо замикання через `Closure::fromCallable()`](#closure-from-callable)
- [Посилання на функцію через `function_name(...)`](#first-class-callables)
- [Обробляємо значення крок за кроком через `|>`](#pipe)
- [Функція як значення за замовчуванням](#closures-in-constant-expressions)
- [Замикання викликає саме себе](#recursive-closures)
- [Кома після останнього аргументу](#trailing-commas)
- [Розділяємо довгі числа для читання](#numeric-literals)
- [Збираємо схожі `use` в один рядок](#group-use)
- [Викликаємо замикання з потрібним `$this`](#closure-call)
- [Готуємо значення `static` під час першого виклику](#static-initializers)
- [Передаємо `exit()` як функцію](#exit-callable)
- [Перетворюємо тип під час оголошення константи](#casts-in-constant-expressions)
- [Додаємо атрибут до глобальної константи](#global-constant-attributes)

<a id="null-coalescing"></a>
## Запасне значення через `??`

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce)

Якщо ключа немає або там `null`, беремо запасне значення. Нуль, `false` і порожній рядок не чіпаємо, з ними все гаразд.

```php
$config = ['limit' => 0, 'label' => null];
$limit = $config['limit'] ?? 20;
$label = $config['label'] ?? 'Untitled';

echo $limit, ':', $label; # 0:Untitled
```

У прикладі `limit` залишиться нулем, а замість `null` у `label` отримаємо `Untitled`. Якщо треба розрізняти відсутній ключ і ключ зі значенням `null`, є `array_key_exists()`. Коли поруч із `??` з’єднуємо рядки, краще явно взяти потрібну частину в дужки.

<a id="null-coalescing-assignment"></a>
## Доповнюємо налаштування через `??=`

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.assignment.php)

Те саме запасне значення, тільки одразу записуємо його на місце. Зручно доповнити налаштування без окремого `if` для кожного ключа.

```php
$config = ['retries' => 0];
$config['timeout'] ??= 30;
$config['retries'] ??= 3;

echo $config['timeout'], ':', $config['retries']; # 30:0
```

`timeout` з’явиться зі значенням `30`, а `retries` залишиться нулем. Права частина обчислюється лише тоді, коли значення справді треба записати: ключа ще немає або в ньому `null`.

<a id="spaceship"></a>
## Сортуємо через `<=>`

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.comparison.php)

Для сортування треба визначити, яке з двох значень менше, дорівнює іншому або більше. `<=>` одразу повертає потрібні `-1`, `0` чи `1`, без ланцюжка умов.

```php
$rows = [['score' => 8], ['score' => 2], ['score' => 5]];
usort( $rows, function( array $left, array $right ): int
{
	return $left['score'] <=> $right['score'];
} );

echo implode( ',', array_column($rows, 'score') ); # 2,5,8
```

У прикладі рядки йдуть за зростанням `score`. Поміняємо `$left` і `$right` місцями й отримаємо зворотний порядок. Порівняння тут звичайне для PHP, а не строге `===`, тому зі змішаними типами спочатку варто визначити, що саме порівнюємо.

<a id="arrow-functions"></a>
## Короткі функції через `fn`

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arrow.php)

Коли вся функція вміщується в один вираз, її можна записати через `fn`. Зовнішні змінні доступні одразу, без `use`, але беруться з тим значенням, яке мали під час створення функції.

```php
$factor = 10;
$multiply = fn( int $value ): int => $value * $factor;
$factor = 20;
echo $multiply( 3 ); # 30
```

Тому зміна `$factor` на `20` уже не впливає на результат: отримуємо `30`. Для об’єкта копія не створюється, всередині залишається той самий екземпляр. Якщо потрібна змінна за посиланням, беремо звичайне замикання; `static fn` підходить, коли не потрібен прив’язаний `$this`.

<a id="named-arguments"></a>
## Передаємо аргументи за ім’ям

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

Можна вказати ім’я потрібного параметра й не заповнювати всі необов’язкові перед ним. Тут змінюємо `limit`, а `separator` залишаємо зі значенням за замовчуванням.

```php
function describe_item( string $name, string $separator = ':', int $limit = 20 ): string
{
	return $name . $separator . $limit;
}

$label = describe_item( 'Report', limit: 5 );

echo $label; # Report:5
```

Звичайні аргументи за порядком мають стояти раніше за іменовані. Імена параметрів тепер теж важливі для коду виклику: перейменуємо `limit` у функції, і старий виклик із `limit:` зламається.

<a id="unpacking-named-arguments"></a>
## Імена аргументів із ключів масиву

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/function.call-user-func-array.php)

Під час розпакування через `...` рядкові ключі стають іменами аргументів. Тому `end` і `start` у масиві можна записати у зворотному порядку, функція все одно отримає `start = 3` і `end = 9`. Це стосується й `call_user_func_array()`.

```php
function format_range( int $start, int $end ): string
{
	return $start . '-' . $end;
}

$arguments = ['end' => 9, 'start' => 3];
echo format_range( ...$arguments ); # 3-9
```

Якщо ім’я невідоме функції або один параметр передано двічі, отримаємо помилку. Якщо ключі потрібні лише самому масиву, а передати значення треба за порядком, спочатку прибираємо ключі через `array_values()`.

<a id="named-after-unpacking"></a>
## Додаємо аргумент після розпакування

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arguments.php)

Частину аргументів можна взяти з масиву, а решту передати за ім’ям. У прикладі `left` і `right` надходять із `$parts`, а розділювач дописуємо просто у виклику.

```php
function join_parts( string $left, string $right, string $separator = '/' ): string
{
	return $left . $separator . $right;
}

$parts = ['docs', 'types'];
echo join_parts( ...$parts, separator: ':' ); # docs:types
```

Повторно передати вже заповнений параметр не можна. Якщо `separator` теж надійде з масиву, ще один `separator:` спричинить помилку.

<a id="match"></a>
## Вибираємо значення через `match`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/control-structures.match.php)

`match` зручний, коли за одним значенням треба отримати інше. Він порівнює строго: рядок `'1'` і число `1` тут різні варіанти. Вибрана гілка повертає результат, далі виконання в інші гілки не переходить.

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

Тому в прикладі отримуємо `text`. Якщо жодна гілка не підійшла й немає `default`, PHP викине `UnhandledMatchError`. Під час заміни `switch` це важливо врахувати: у нього порівняння нестроге.

<a id="nullsafe"></a>
## Читаємо ланцюжок об’єктів через `?->`

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.nullsafe)

Профілю може не бути, адреси теж. Через `?->` можна пройти такий ланцюжок без окремого `if` перед кожним кроком: зустріли `null`, далі не йдемо, результат теж `null`. Після цього `??` підставить текст для виведення.

```php
$profile = null;
$city = $profile?->address?->city ?? 'Not provided';

echo $city; # Not provided
```

`?->` ставимо там, де об’єкт справді може бути `null`. Через нього можна читати, але не записувати властивості. Помилки в наявному об’єкті й винятки викликаних методів він не приховує.

<a id="throw-expression"></a>
## Викидаємо виняток просто у виразі

**PHP 8.0+ | Ядро** | [Документація PHP](https://www.php.net/exceptions)

Якщо без значення продовжувати не можна, праворуч від `??` можна одразу поставити `throw`. У прикладі токен є, тому винятку не буде. Без ключа `token` або зі значенням `null` виконання зупиниться на винятку.

```php
$config = ['token' => 'local-example'];
$token = $config['token'] ?? throw new InvalidArgumentException( 'Token is required' );
```

Порожній рядок перевірку пройде. Тут перевіряємо наявність значення, а не те, чи підходить воно для подальшої роботи.

<a id="closure-from-callable"></a>
## Отримуємо замикання через `Closure::fromCallable()`

**PHP 7.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/closure.fromcallable.php)

Функцію або доступний метод можна перетворити на об’єкт `Closure`, зберегти у змінну й викликати пізніше. У прикладі з імені `add_prefix` отримуємо функцію, яку викликаємо через `$format`.

```php
function add_prefix( string $name ): string
{
	return 'note:' . $name;
}

$format = Closure::fromCallable( 'add_prefix' );

echo $format( 'types' ); # note:types
```

PHP перевіряє доступ уже під час створення замикання. Якщо функцію не можна викликати з цього місця, отримаємо `TypeError`. Починаючи з PHP 8.1 те саме можна записати коротше через [посилання на функцію](#first-class-callables).

<a id="first-class-callables"></a>
## Посилання на функцію через `function_name(...)`

**PHP 8.1+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)

Три крапки без аргументів тут означають: взяти функцію, а не викликати її. Отримуємо `Closure`, який можна передати, наприклад, у `array_map()`. Для методів доступ перевіряється під час створення такого посилання й зберігається для подальших викликів.

```php
$normalize = trim(...);
$values = array_map( $normalize, [' one ', ' two '] );

echo implode( ',', $values ); # one,two
```

Не плутаємо цей запис із розпакуванням масиву аргументів. Поєднувати його з `?->` або створювати так конструктор через `new ClassName(...)` не можна.

<a id="pipe"></a>
## Обробляємо значення крок за кроком через `|>`

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.operators.functional.php)

Замість вкладених викликів можна записати обробку згори вниз. `|>` передає значення ліворуч у функцію праворуч, а її результат іде на наступний крок. Тут спочатку прибираємо пробіли з країв, потім змінюємо регістр і замінюємо пробіл усередині.

```php
$slug = ' PHP Notes '
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $text ): string => str_replace(' ', '-', $text));

echo $slug; # php-notes
```

Функція праворуч отримує один аргумент. Якщо потрібні додаткові параметри або значення треба підставити не першим, обгортаємо виклик у замикання, як зроблено зі `str_replace()`. Усі кроки виконуються послідовно й синхронно.

<a id="closures-in-constant-expressions"></a>
## Функція як значення за замовчуванням

**PHP 8.5+ | Ядро** | [Документація PHP](https://wiki.php.net/rfc/closures_in_const_expr)

У значенні за замовчуванням і в константі тепер можна зберегти посилання на функцію або дозволене статичне замикання. Тут `trim(...)` стає готовим обробником, тому у звичайному виклику `normalize_name()` нічого додатково передавати не треба.

```php
function normalize_name( string $name, Closure $normalizer = trim(...) ): string
{
	return $normalizer( $name );
}

echo normalize_name( ' Ada ' ); # Ada
```

Під час оголошення обробник лише створюється, а не викликається. Для замикань є обмеження: вони мають бути `static` і не можуть брати зовнішні змінні через `use`.

<a id="recursive-closures"></a>
## Замикання викликає саме себе

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/closure.getcurrent.php)

Замикання не має звичайного імені функції, але для рекурсії все одно може отримати саме себе через `Closure::getCurrent()`. Не треба заздалегідь захоплювати змінну `$factorial` за посиланням.

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

У прикладі кожен виклик множить число на результат наступного, доки не дійде до `1`. Поза замиканням, яке зараз виконується, `Closure::getCurrent()` спричинить `Error`.

<a id="trailing-commas"></a>
## Кома після останнього аргументу

**PHP 7.3+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/functions.arguments.php)

Останню кому можна залишити: так зручніше додавати нові рядки й переглядати зміни в Git. У викликах це працює з PHP 7.3, в оголошеннях параметрів і списках `use` у замиканнях з PHP 8.0.

```php
echo implode(
	',',
	['one', 'two'],
);
# one,two
```

Зайвого аргументу через кому не з’явиться. У прикладі `implode()` так само отримує два аргументи.

<a id="numeric-literals"></a>
## Розділяємо довгі числа для читання

**PHP 7.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.types.integer.php)

У довгому числі легко загубити один нуль. Із PHP 7.4 між цифрами можна ставити `_`: значення не змінюється, зате читати легше. Із PHP 8.1 вісімкове число можна явно записати як `0o644` замість `0644`; обидва варіанти означають десяткове `420`.

```php
$max_bytes = 10_485_760;
$mask = 0b1111_0000;
```

Це запис чисел у самому коді. Рядок із такими розділювачами не стає автоматично придатним форматом для перетворення на число.

<a id="group-use"></a>
## Збираємо схожі `use` в один рядок

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.namespaces.importing.php)

Якщо кілька класів лежать в одному просторі імен, спільний шлях можна написати один раз. У фігурних дужках залишаємо лише різні частини імен.

```php
use App\Report\{CsvWriter, JsonWriter};

$writers = [CsvWriter::class, JsonWriter::class];
```

`use` тут лише скорочує імена для цього файлу. Сам по собі він не завантажує класи й не створює об’єкти.

<a id="closure-call"></a>
## Викликаємо замикання з потрібним `$this`

**PHP 7.0+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/closure.call.php)

Через `call()` можна на один виклик дати замиканню об’єкт як `$this`. Разом із ним замикання отримує доступ з області його класу, тому в прикладі читає приватне `value`.

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

Прив’язка діє на цей виклик. Зі статичним замиканням так не вийде: у нього не може бути `$this`.

<a id="static-initializers"></a>
## Готуємо значення `static` під час першого виклику

**PHP 8.3+ | Ядро** | [Документація PHP](https://wiki.php.net/rfc/arbitrary_static_variable_initializers)

Початкове значення локальної `static`-змінної тепер можна обчислити через вираз або виклик функції. Воно обчислиться, коли виконання вперше дійде до оголошення, а не під час кожного виклику функції.

```php
function next_number(): int
{
	static $number = strlen( 'seed' );
	return ++$number;
}

echo next_number(), ':', next_number(); # 5:6
```

Тому довжина `seed` береться один раз, а далі лічильник продовжує зростати: `5:6`. Значення зберігається між викликами в поточному виконанні PHP. Спільного кешу між окремими запитами з цього не виходить.

<a id="exit-callable"></a>
## Передаємо `exit()` як функцію

**PHP 8.4+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration84.incompatible.php)

`exit()` і його інше ім’я `die()` тепер можна використовувати там, де потрібна функція для виклику. У прикладі `exit(...)` лише створює замикання, виконання на цьому рядку не завершується.

```php
$terminate = exit(...);
$is_closure = $terminate instanceof Closure;
```

Правила типів теж стали звичайними для функцій, включно зі `strict_types`. Ціле число задає код завершення, рядок виводиться перед виходом. Непридатний тип аргументу тепер спричиняє `TypeError`.

<a id="casts-in-constant-expressions"></a>
## Перетворюємо тип під час оголошення константи

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/language.constants.syntax.php)

У значенні константи тепер можна явно перетворити тип. Тут одразу залишаємо від `3.9` лише цілу частину й зберігаємо `3`.

```php
const WHOLE_SECONDS = (int)3.9;

echo WHOLE_SECONDS; # 3
```

Правила самого перетворення ті самі. Запис `(int)` не округлює число до найближчого цілого, а відкидає дробову частину.

<a id="global-constant-attributes"></a>
## Додаємо атрибут до глобальної константи

**PHP 8.5+ | Ядро** | [Документація PHP](https://www.php.net/manual/en/migration85.new-features.php)

Атрибути тепер можна ставити й перед глобальним `const`. Наприклад, позначаємо старе ім’я константи через `#[Deprecated]`, щоб її використання підказало, на що перейти.

```php
#[Deprecated( 'Use DEFAULT_PAGE_SIZE' )]
const OLD_PAGE_SIZE = 20;
const DEFAULT_PAGE_SIZE = 20;
```

Під час читання `OLD_PAGE_SIZE` PHP видасть повідомлення про застарілість. Для констант, створених через `define()`, такий запис атрибута не підтримується.
