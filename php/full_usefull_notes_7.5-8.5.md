# PHP 7.4 - 8.5: полный практический справочник

Актуальная стабильная ветка на 1 августа 2026 года: **PHP 8.5**, последняя исправляющая версия - **8.5.9**. PHP 8.6 пока проходит испытания и здесь не рассматривается.

Здесь только возможности самого PHP и модулей из официальной поставки. Сторонних библиотек, Composer-пакетов и возможностей фреймворков нет.

Пометки:

- **Ядро** - доступно в самом языке или стандартной части PHP.
- **Встроенный модуль** - поставляется с PHP, но в конкретной сборке может быть выключен.
- Мелкие внутренние изменения движка и узкие функции редких модулей не перечислены, если они почти не влияют на прикладной код.

## Содержание

- [PHP 7.4](#php-74)
- [PHP 8.0](#php-80)
- [PHP 8.1](#php-81)
- [PHP 8.2](#php-82)
- [PHP 8.3](#php-83)
- [PHP 8.4](#php-84)
- [PHP 8.5](#php-85)
- [Что заменить в старом коде](#что-заменить-в-старом-коде)

## PHP 7.4

### Типы у свойств

```php
class User
{
	public int $id;
	public string $name;
	public ?string $email = null;
}
```

PHP проверяет значения при записи; заменяет типы в комментариях и ручные проверки. Типизированное свойство без начального значения нельзя читать до присваивания.

### Короткие функции `fn`

```php
$factor = 10;
$values = array_map( fn( int $value ): int => $value * $factor, [1, 2, 3] );
```

Внешние переменные видны автоматически по значению; заменяет длинную запись `function() use ( ... )`.

### Более точные типы при наследовании

```php
class Factory
{
	public function create(): object {}
}

class UserFactory extends Factory
{
	public function create(): User {}
}
```

Дочерний метод может возвращать более точный тип и принимать более общий; меньше искусственных одинаковых сигнатур.

### Присваивание при отсутствии значения

```php
$config['timeout'] ??= 30;
```

Присваивает значение, только если ключ отсутствует или содержит `null`; заменяет `if( !isset( ... ) )`.

### Распаковка числовых массивов

```php
$users = [...$admins, ...$editors];
```

Вставляет элементы массивов прямо в новый массив; для числовых ключей заменяет `array_merge()`.

### Читаемые большие числа

```php
$max_size = 10_485_760;
$mask = 0b1111_0000;
```

Подчеркивания можно ставить между цифрами без изменения значения.

### Слабые ссылки

```php
$reference = WeakReference::create( $object );
$object = $reference->get(); # объект или null
```

Ссылка не мешает PHP освободить объект; полезно для временных хранилищ и связей, которые не должны удерживать объект в памяти.

### Исключения внутри `__toString()`

```php
public function __toString(): string
{
	throw new RuntimeException( 'Cannot convert object to string' );
}
```

Ошибку преобразования теперь можно нормально перехватить через `try` и `catch`; раньше возникала фатальная ошибка.

### Современная сериализация объектов

```php
public function __serialize(): array
{
	return ['id' => $this->id, 'name' => $this->name];
}

public function __unserialize( array $data ): void
{
	$this->id   = $data['id'];
	$this->name = $data['name'];
}
```

Класс сам задает сохраняемое состояние; заменяет устаревающий интерфейс `Serializable` и неоднозначные `__sleep()` с `__wakeup()`.

### Разрешенные теги массивом

```php
$text = strip_tags( $html, ['p', 'a', 'strong'] );
```

Список тегов читается проще старой строки `'<p><a><strong>'`. Функция не защищает от опасных атрибутов, поэтому для вывода чужого HTML нужен отдельный очиститель.

### Безопаснее запуск внешней команды

```php
$process = proc_open(
	['php', '-r', 'echo PHP_VERSION;'],
	[1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
	$pipes,
);
```

Массив аргументов запускается без командной оболочки; заменяет опасную сборку команды одной строкой.

### Слияние любого количества массивов

```php
$result = array_merge( ...$arrays );
```

`array_merge()` теперь можно вызвать даже без аргументов, поэтому пустой список массивов вернет `[]`, а не ошибку.

### Список доступных способов хеширования паролей

```php
$algorithms = password_algos();
```

Позволяет узнать возможности текущей сборки вместо жесткой проверки констант.

### Разбиение строки по символам - модуль `mbstring`

```php
$letters = mb_str_split( 'Привет' );
```

Разбивает многобайтовую строку по символам, а не по отдельным байтам; заменяет сложные регулярные выражения.

### Проверка диапазона дробного числа - модуль `filter`

```php
$price = filter_var( $value, FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );
```

Проверяет и формат, и диапазон одним вызовом.

### Предварительная загрузка кода - модуль `OPcache`

```ini
opcache.preload=/var/www/preload.php
opcache.preload_user=www-data
```

Классы и функции загружаются один раз при запуске PHP-FPM; уменьшает повторную работу в больших постоянно запущенных приложениях.

### Резервная копия SQLite - модуль `SQLite3`

```php
$source->backup( $destination );
```

Копирует открытую базу средствами SQLite без ручного копирования файла во время записи.

## PHP 8.0

### Аргументы по имени

```php
$html = htmlspecialchars( $text, double_encode: false );
```

Можно передать только нужный аргумент по его названию; заменяет цепочку неясных значений по порядку.

### Свойства прямо в конструкторе

```php
public function __construct(
	public int $id,
	private string $name,
) {}
```

Одновременно объявляет свойства и присваивает им аргументы; заменяет поля и повторяющиеся `$this->name = $name`.

### Объединение типов

```php
function find_user( int|string $id ): User|null {}
```

Допустимые типы записываются в сигнатуре; заменяет пояснения и проверки в начале функции.

### Выражение `match`

```php
$label = match( $status ) {
	1 => 'active',
	2 => 'blocked',
	default => 'unknown',
};
```

Возвращает значение, сравнивает строго и не требует `break`; заменяет громоздкий `switch`.

### Безопасная цепочка `?->`

```php
$city = $user?->profile?->address?->city;
```

Вернет `null` только при `null` слева от очередного `?->`; если вся цепочка существует, вернет настоящее значение.

### Атрибуты без сторонних библиотек

```php
#[Attribute( Attribute::TARGET_METHOD )]
class RequiresRole
{
	public function __construct( public string $role ) {}
}

#[RequiresRole( 'admin' )]
public function delete_user(): void {}
```

`#[...]` - это данные, доступные через отражение, а не комментарий; заменяет разбор особых комментариев. Смысл атрибута задает ваш собственный код.

### `throw` внутри выражения

```php
$token = $request->token ?? throw new RuntimeException( 'Token is required' );
```

Сразу прерывает работу при отсутствии обязательного значения; заменяет отдельный `if`.

### Тип `mixed`

```php
function normalize_value( mixed $value ): string {}
```

Явно обозначает любое значение; понятнее отсутствующего типа и учитывается при переопределении методов.

### Возврат типа `static`

```php
public function copy(): static
{
	return clone $this;
}
```

Возвращаемый объект соответствует классу, на котором вызван метод, включая дочерний класс.

### Завершающая запятая в параметрах

```php
function create_user(
	string $name,
	string $email,
): User {}
```

Добавление новой строки не требует менять предыдущую; чище история изменений.

### `catch` без ненужной переменной

```php
try
{
	load_config();
}
catch( RuntimeException )
{
	return [];
}
```

Если сам объект исключения не нужен, его больше не требуется сохранять в переменную.

### Имя класса объекта

```php
$class_name = $object::class;
```

Короткая замена `get_class( $object )`.

### Слабая карта объектов

```php
$cache = new WeakMap();
$cache[$user] = $permissions;
```

Запись удалится сама после удаления объекта-ключа; удобно для данных, срок жизни которых связан с объектом.

### Общий договор строкового представления

```php
function print_value( Stringable|string $value ): void
{
	echo $value;
}
```

Класс с `__toString()` автоматически считается `Stringable`; не нужен собственный одинаковый интерфейс.

### Отдельная ошибка неправильного значения

```php
try
{
	array_rand( [], 1 );
}
catch( ValueError $error )
{
	# Значение имеет правильный тип, но недопустимо
}
```

`ValueError` отделяет неверное значение от неверного типа `TypeError`.

### Проверка части, начала и конца строки

```php
$has_admin = str_contains( $path, '/admin/' );
$is_api    = str_starts_with( $path, '/api/' );
$is_php    = str_ends_with( $file, '.php' );
```

Заменяет проверки через `strpos() !== false`, `substr()` и ручной подсчет длины.

### Понятное название типа при отладке

```php
$type = get_debug_type( $value );
```

Возвращает точное имя класса или понятный встроенный тип; полезнее сочетания `gettype()` и `get_class()`.

### Сообщение последней ошибки регулярного выражения

```php
if( preg_match( $pattern, $value ) === false )
	throw new RuntimeException( preg_last_error_msg() );
```

Возвращает текст ошибки вместо ручной расшифровки числового кода `preg_last_error()`.

### Деление без предупреждения

```php
$ratio = fdiv( $value, $divider );
```

При делении на ноль возвращает `INF`, `-INF` или `NAN`; полезно в математическом коде, где такие значения ожидаемы.

### Идентификатор ресурса

```php
$id = get_resource_id( $stream );
```

Понятная замена приведению ресурса через `(int)$stream`.

### Преобразование между изменяемой и неизменяемой датой

```php
$mutable   = DateTime::createFromInterface( $immutable );
$immutable = DateTimeImmutable::createFromInterface( $mutable );
```

Заменяет сборку новой даты через форматирование строки.

### Объектный разбор PHP-кода

```php
$tokens = PhpToken::tokenize( $code );

foreach( $tokens as $token )
	echo $token->getTokenName();
```

Класс `PhpToken` удобнее массивов от `token_get_all()` для анализаторов и преобразователей кода.

### Состояния cURL и других модулей стали объектами

```php
$curl = curl_init( $url );

if( $curl instanceof CurlHandle )
	curl_exec( $curl );
```

Многие старые ресурсы заменены объектами с точными типами: `CurlHandle`, `GdImage`, `Socket`, `OpenSSLAsymmetricKey` и другими.

### Компиляция часто выполняемого кода - модуль `OPcache`

```ini
opcache.jit=tracing
opcache.jit_buffer_size=128M
```

JIT может ускорить вычислительный PHP-код; для обычных запросов к базе и сети выигрыш часто небольшой, поэтому нужен замер.

## PHP 8.1

### Перечисления

```php
enum UserStatus: string
{
	case Active  = 'active';
	case Blocked = 'blocked';
}

$status = UserStatus::tryFrom( $value ); # значение или null
```

Создают строгий набор разрешенных значений; заменяют строковые константы и ручную проверку списка. `from()` выбрасывает ошибку, `tryFrom()` возвращает `null`.

### Свойства только для однократной записи

```php
public function __construct( public readonly int $id ) {}
```

После первого присваивания свойство нельзя изменить; заменяет закрытое поле с простым методом чтения.

### Ссылка на функцию или метод

```php
$handler = $service->handle(...);
$trim    = trim(...);
```

Создает объект `Closure` и сразу проверяет доступность метода; заменяет `[$service, 'handle']` и `Closure::fromCallable()`.

### Пересечение типов

```php
function export( Countable&Iterator $items ): void {}
```

Объект обязан соответствовать всем указанным договорам; заменяет несколько проверок `instanceof`.

### Тип `never`

```php
function abort_request(): never
{
	throw new RuntimeException( 'Request aborted' );
}
```

Функция не возвращает управление: завершает процесс, выбрасывает исключение или работает бесконечно.

### Создание объекта в значении по умолчанию

```php
public function __construct( private Logger $logger = new NullLogger() ) {}
```

Запасной объект задается прямо в параметре; заменяет проверку и создание внутри конструктора.

### Окончательные константы класса

```php
final public const TABLE = 'users';
```

Дочерний класс не сможет незаметно изменить важную константу.

### Распаковка массивов со строковыми ключами

```php
$config = [...$defaults, ...$local_config];
```

Позднее значение заменяет раннее по тому же ключу; короткая запись вместо `array_merge()`.

### Явные восьмеричные числа

```php
$permissions = 0o755;
```

Префикс `0o` заметнее старого ведущего нуля `0755` и меньше похож на обычное десятичное число.

### Аргумент по имени после распаковки

```php
create_user( ...$data, notify: false );
```

Позволяет распаковать основные аргументы и явно переопределить дополнительный параметр.

### Волокна выполнения

```php
$fiber = new Fiber( function(): void
{
	Fiber::suspend( 'paused' );
} );

$value = $fiber->start();
$fiber->resume();
```

Позволяют приостанавливать функцию с сохранением состояния; сами по себе не делают код параллельным, но служат основой планировщиков задач.

### Проверка массива-списка

```php
if( array_is_list( $data ) )
	return $data;
```

Проверяет последовательные ключи от нуля; заменяет сравнение с `range()` или ручной цикл.

### Сброс данных на диск

```php
fwrite( $stream, $data );
fsync( $stream );
```

`fsync()` принудительно сохраняет и данные, и служебные сведения файла; `fdatasync()` сохраняет прежде всего данные.

### Файл из строки для cURL - модуль `curl`

```php
$file = new CURLStringFile( $contents, 'report.txt', 'text/plain' );
curl_setopt( $curl, CURLOPT_POSTFIELDS, ['file' => $file] );
```

Отправляет содержимое памяти как файл без временного файла на диске.

### Первый столбец результата - модуль `mysqli`

```php
$value = $result->fetch_column();
```

Короткая замена `fetch_row()[0]`, когда запрос возвращает одно значение.

### Изображения AVIF - модуль `gd`

```php
$image = imagecreatefromavif( $path );
imageavif( $image, $target, 80 );
```

Читает и сохраняет AVIF, если GD собран с поддержкой этого формата.

### Полный путь загруженного каталога

```php
$relative_path = $_FILES['files']['full_path'][$index];
```

Сохраняет относительный путь файла при загрузке каталога. Значение приходит от пользователя, поэтому его обязательно нужно проверять от `..`, абсолютных путей и неожиданных разделителей.

## PHP 8.2

### Неизменяемый класс целиком

```php
readonly class UserData
{
	public function __construct(
		public int $id,
		public string $name,
	) {}
}
```

Все свойства типизированы и доступны только для однократной записи; заменяет `readonly` у каждого свойства.

### Сочетание объединения и пересечения типов

```php
function save( (JsonSerializable&Stringable)|array $value ): void {}
```

Точно описывает «массив или объект сразу двух типов»; заменяет ручные проверки внутри функции.

### Самостоятельные типы `true`, `false` и `null`

```php
function save_user( User $user ): true
{
	# При ошибке функция выбрасывает исключение
	return true;
}
```

Можно обещать конкретное служебное значение, а не общий `bool`; `false` и `null` также разрешены как отдельные типы.

### Скрытие секретов в следе ошибки

```php
function login(
	string $email,
	#[\SensitiveParameter] string $password,
): void {}
```

PHP заменит пароль специальной заглушкой в следе вызовов; снижает риск утечки через журналы ошибок.

### Константы в примесях

```php
trait HasStatus
{
	public const STATUS_ACTIVE = 'active';
}
```

Общие константы можно хранить рядом с общей логикой; отдельный базовый класс не нужен.

### Свойства перечисления в постоянных выражениях

```php
const ACTIVE_STATUS = UserStatus::Active->value;
```

Значение варианта перечисления можно использовать в константах и значениях атрибутов.

### Случайные свойства больше не создаются молча

```php
final class User
{
	public string $name;
}

$user->naem = 'Sanya'; # предупреждение об опечатке
```

PHP предупреждает о необъявленном свойстве. `#[\AllowDynamicProperties]` оставлен только как временная совместимость для старых классов.

### Единый генератор случайных значений

```php
$randomizer = new Random\Randomizer();
$code       = $randomizer->getInt( 100000, 999999 );
$items      = $randomizer->shuffleArray( $items );
$keys       = $randomizer->pickArrayKeys( $items, 2 );
```

Один объект дает числа, байты, перемешивание и выбор ключей; заменяет смесь `rand()`, `mt_rand()`, `shuffle()` и `array_rand()`.

### Воспроизводимая случайность для испытаний

```php
$engine = new Random\Engine\Mt19937( 12345 );
$randomizer = new Random\Randomizer( $engine );
```

Одинаковое начальное значение дает одинаковую последовательность; удобно для повторяемых испытаний. Для секретов используйте стандартный безопасный движок, а не `Mt19937`.

### Выполнение подготовленного запроса одной строкой - модуль `mysqli`

```php
$result = mysqli_execute_query(
	$db,
	'SELECT * FROM users WHERE id = ?',
	[$user_id],
);
```

Подготавливает и выполняет запрос с параметрами; заменяет отдельные `prepare()`, `bind_param()` и `execute()` для простого случая.

### Чтение размеров из `php.ini`

```php
$bytes = ini_parse_quantity( '128M' );
```

Понимает суффиксы настроек PHP; заменяет собственный разбор `K`, `M` и `G`.

### Сброс пикового расхода памяти

```php
memory_reset_peak_usage();
run_heavy_operation();
$peak = memory_get_peak_usage( true );
```

Позволяет измерить отдельный участок, а не максимум за всю жизнь процесса.

### Длина ключа шифра - модуль `openssl`

```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );
```

Возвращает требуемую длину ключа вместо таблицы, записанной вручную.

### Права файла журнала ошибок

```ini
error_log_mode=0640
```

PHP может создавать журнал с заданными правами, не полагаясь только на общую маску процесса.

## PHP 8.3

### Типы у констант класса

```php
public const string ROLE_ADMIN = 'admin';
```

PHP проверяет тип константы и ее переопределений в классах, интерфейсах, примесях и перечислениях.

### Константа класса по переменному имени

```php
$value = UserRole::{$constant_name};
```

Заменяет сборку полного имени строкой и вызов `constant()`.

### Проверка переопределения метода

```php
#[\Override]
public function handle(): void {}
```

PHP проверит, что метод действительно существует у родителя или в реализуемом договоре; ловит опечатки и последствия переименования.

### Окончательный метод из примеси

```php
use HasLogger
{
	log as final;
}
```

Класс может запретить дочерним классам переопределять конкретный метод, полученный из примеси.

### Выражения в начальном значении статической переменной

```php
function formatter(): Formatter
{
	static $formatter = new Formatter( default_locale() );
	return $formatter;
}
```

Начальное значение может вычисляться выражением; заменяет отдельную проверку `$formatter === null`.

### Повторная инициализация неизменяемого свойства при клонировании

```php
public function __clone()
{
	$this->id = new UserId();
}
```

Позволяет клону получить новое значение свойства внутри `__clone()`; заменяет ручное пересоздание всего объекта.

### Неизменяемые безымянные классы

```php
$result = new readonly class( 200 )
{
	public function __construct( public int $status ) {}
};
```

Удобно для небольшого одноразового объекта-значения без отдельного именованного класса.

### Проверка JSON без разбора

```php
public function is_json( string $string ): bool
{
	return json_validate( $string );
}
```

Прямая замена `@json_decode()` вместе с `json_last_error()`; строка `'null'` тоже является правильным JSON.

### Случайная строка из заданных символов

```php
$token = $randomizer->getBytesFromString( 'abcdef0123456789', 32 );
```

Заменяет ручной цикл выбора символов. Строка не должна быть пустой.

### Случайные дробные числа

```php
$ratio = $randomizer->getFloat( 0.0, 1.0 );
$next  = $randomizer->nextFloat();
```

Границы и способ включения концов диапазона задаются явно; надежнее самодельного деления целых чисел.

### Дополнение многобайтовой строки - модуль `mbstring`

```php
$title = mb_str_pad( 'Привет', 12, '.', STR_PAD_BOTH );
```

Дополняет строку по символам, а не по байтам; исправляет перекос `str_pad()` на кириллице.

### Увеличение и уменьшение буквенно-числовой строки

```php
$next = str_increment( 'INV0099' );
$prev = str_decrement( 'INV0100' );
```

Явная замена старому неочевидному `$value++`; работает только с допустимыми буквенно-числовыми строками ASCII.

### Точные исключения даты и времени

```php
try
{
	$date = new DateTimeImmutable( $value );
}
catch( DateMalformedStringException $error )
{
	return null;
}
```

Ошибки разбора даты получили отдельные классы; их можно перехватывать точнее общего `Exception`.

### Период из строки ISO 8601

```php
$period = DatePeriod::createFromISO8601String( 'R4/2026-01-01T00:00:00Z/P1D' );
```

Создает период из готового описания стандарта без ручной сборки начальной даты, интервала и числа повторов.

### Несколько настроек потока одним вызовом

```php
stream_context_set_options( $context, [
	'http' => ['timeout' => 5, 'ignore_errors' => true],
] );
```

Заменяет несколько вызовов `stream_context_set_option()`.

### Резервное значение переменной окружения в `php.ini`

```ini
listen=${PHP_FPM_PORT:-9000}
```

Использует значение окружения или запасное значение после `:-`; уменьшает количество отдельных файлов настроек.

### Проверка нескольких файлов одной командой

```console
php -l src/User.php src/Order.php src/Report.php
```

Один запуск проверяет синтаксис нескольких файлов.

### Удобные операции DOM - модуль `dom`

```php
$element->toggleAttribute( 'hidden' );
$element->insertAdjacentText( 'beforeend', $text );
$contains = $parent->contains( $child );
```

Заменяет ручное создание узлов и обход родителей для частых операций с документом.

## PHP 8.4

### Обработчики чтения и записи свойств

```php
class User
{
	public string $first_name
	{
		set => ucfirst( strtolower( trim( $value ) ) );
	}

	public string $last_name;

	public string $full_name
	{
		get => $this->first_name . ' ' . $this->last_name;
	}
}
```

Проверка, преобразование и вычисление находятся прямо у свойства; заменяет простые методы `get_*()` и `set_*()`.

### Полный обработчик записи

```php
public int $age
{
	set
	{
		if( $value < 0 )
			throw new ValueError( 'Age cannot be negative' );

		$this->age = $value;
	}
}
```

Многострочный `set` подходит для проверки и нескольких действий; запись в то же свойство внутри обработчика обращается к его настоящему значению.

### Разные права на чтение и запись свойства

```php
public private(set) string $name;
```

Снаружи свойство можно читать, а менять только внутри класса; заменяет закрытое поле с простым методом чтения.

### Отложенное создание объекта

```php
$reflection = new ReflectionClass( Report::class );
$report = $reflection->newLazyGhost( $initializer );
```

Инициализация выполняется при первом обращении; стандартный механизм заменяет сложные самодельные объекты-посредники. В основном полезен создателям контейнеров и библиотек.

### Явная отметка устаревшего кода

```php
#[\Deprecated( 'Use find_user() instead', since: '2.0' )]
function get_user( int $id ): User {}
```

PHP сам выдает предупреждение при вызове; заменяет пометку в комментарии, которую легко не заметить.

### Разбор тела запросов `PUT`, `PATCH` и других методов

```php
[$fields, $files] = request_parse_body();
```

Разбирает `multipart/form-data` и обычные поля не только для `POST`; заменяет ручной разбор `php://input`.

### Вызов сразу после `new`

```php
$name = new User( 7 )->get_name();
```

Скобки вокруг `(new User( 7 ))` больше не нужны.

### `exit()` и `die()` стали обычнее

```php
exit( status: 1 );
```

Они теперь ведут себя как функции: учитывают `strict_types`, допускают аргумент по имени и отражение.

### Проверки и поиск в массиве

```php
$all_valid = array_all( $users, fn( User $user ): bool => $user->is_valid() );
$has_admin = array_any( $users, fn( User $user ): bool => $user->is_admin() );
$admin     = array_find( $users, fn( User $user ): bool => $user->is_admin() );
$admin_id  = array_find_key( $users, fn( User $user ): bool => $user->is_admin() );
```

Заменяет четыре частых цикла с флагом, ранним `break`, найденным значением или ключом.

### Точные операции с дробными числами - модуль `bcmath`

```php
$price = new BcMath\Number( '19.995' );
$total = ($price * '3')->round( 2 );
```

`BcMath\Number` хранит десятичное число объектом и поддерживает операторы; уменьшает цепочки `bcadd()`, `bcmul()` и ручную передачу точности.

### Округление BCMath - модуль `bcmath`

```php
$rounded = bcround( '19.995', 2, RoundingMode::HalfAwayFromZero );
$down    = bcfloor( '19.995' );
$up      = bcceil( '19.001' );
```

Встроенные способы округления заменяют ручную работу со строковой частью числа.

### Явный способ округления

```php
$value = round( 2.5, mode: RoundingMode::HalfEven );
```

Перечисление `RoundingMode` делает правило округления заметным и исключает непонятные числовые константы.

### Дата из метки времени и микросекунды

```php
$date = DateTimeImmutable::createFromTimestamp( 1_700_000_000.123456 );
$date = $date->setMicrosecond( 500000 );
$microseconds = $date->getMicrosecond();
```

Заменяет формат `'U.u'` и ручную сборку дробной части времени.

### Операции со строками Unicode - модуль `mbstring`

```php
$value = mb_trim( $value );
$title = mb_ucfirst( $title );
$name  = mb_lcfirst( $name );
```

Корректно обрабатывает многобайтовые пробелы и первую букву; заменяет сочетания `mb_substr()` и `mb_strtoupper()`.

### Разбиение по видимым символам - модуль `intl`

```php
$symbols = grapheme_str_split( '👨‍👩‍👧‍👦Привет' );
```

Работает с видимыми составными символами, а не байтами или отдельными кодовыми точками.

### Современный разбор HTML5 - модуль `dom`

```php
$document = Dom\HTMLDocument::createFromString( $html );
$element  = $document->querySelector( '#content' );
```

Новые классы `Dom` разбирают современный HTML по правилам HTML5 и устраняют многие ограничения старого `DOMDocument`.

### Безопасная строка для XPath - модуль `dom`

```php
$query = '//user[@name=' . DOMXPath::quote( $name ) . ']';
```

Экранирует значение для строкового литерала XPath; заменяет хрупкую ручную работу с одинарными и двойными кавычками.

### Отдельные классы PDO для разных баз - модуль `pdo`

```php
$db = Pdo\Mysql::connect( $dsn, $username, $password );
```

Драйвер получает собственный класс, методы и константы вместо одного перегруженного `PDO` для всех баз.

### Последние заголовки ответа встроенного HTTP-потока

```php
$body    = file_get_contents( $url );
$headers = http_get_last_response_headers();
http_clear_last_response_headers();
```

Заменяет неявную глобальную переменную `$http_response_header` и позволяет явно очистить старое значение.

### Удобное создание XMLReader и XMLWriter - встроенные модули XML

```php
$reader = XMLReader::fromString( $xml );
$writer = XMLWriter::toMemory();
```

Именованные способы создания понятнее старой пары `new` и `openMemory()` или `XML()`.

## PHP 8.5

### Передача значения по цепочке

```php
$slug = $title
	|> trim(...)
	|> strtolower(...)
	|> (fn( string $value ): string => str_replace( ' ', '-', $value ));
```

Передает результат слева в следующую функцию; заменяет вложенные вызовы и промежуточные переменные.

### Клонирование с заменой свойств

```php
public function with_alpha( int $alpha ): self
{
	return clone( $this, ['alpha' => $alpha] );
}
```

Клонирует объект и меняет доступные для записи свойства. У неизменяемого класса вызов размещают внутри его метода, чтобы соблюсти права записи.

### Обязательная обработка результата

```php
#[\NoDiscard]
function save_user( User $user ): SaveResult {}

$result = save_user( $user );
(void)save_user( $user ); # намеренно не используем результат
```

PHP предупреждает о забытом результате; `(void)` явно отмечает намеренное игнорирование.

### Функции в постоянных выражениях

```php
private const NORMALIZE = strtolower(...);

public function __construct(
	private Closure $normalizer = strtolower(...),
) {}
```

Ссылки на функции и статические безымянные функции можно хранить в константах, значениях по умолчанию и аргументах атрибутов.

### Атрибуты у глобальных констант

```php
#[\Deprecated( 'Use NEW_LIMIT instead' )]
const OLD_LIMIT = 100;
```

Атрибуты теперь можно назначать константам, объявленным через `const`; константы от `define()` это не поддерживают.

### Проверка переопределения свойства

```php
class ParentRecord
{
	public string $name
	{
		get => 'parent';
	}
}

class ChildRecord extends ParentRecord
{
	#[\Override]
	public string $name
	{
		get => 'child';
	}
}
```

`#[\Override]` теперь проверяет не только методы, но и свойства с обработчиками.

### Разные права у статического свойства

```php
public private(set) static int $count = 0;
```

Статическое значение можно читать снаружи, но изменять только внутри класса.

### Окончательное свойство прямо в конструкторе

```php
public function __construct( final public string $id ) {}
```

Дочерний класс не сможет переопределить продвинутое свойство конструктора.

### Приведение типа в константе

```php
const RETRY_COUNT = (int)3.9;
```

Постоянное выражение может содержать обычное приведение типа; раньше это вызывало ошибку компиляции.

### След вызовов у фатальных ошибок

```ini
max_execution_time=30
```

Даже фатальные ошибки наподобие превышения времени теперь показывают путь вызовов, что упрощает поиск тяжелого места.

### Встроенная работа с URI и URL

```php
$uri = new Uri\Rfc3986\Uri( 'https://php.net/releases/8.5/en.php' );
$host = $uri->getHost();
$uri  = $uri->withPath( '/manual/' );
```

Встроенный модуль URI включен по умолчанию и работает по правилам RFC 3986 и WHATWG; надежнее массива `parse_url()` и ручной сборки строки.

### Первое и последнее значение массива

```php
$first_user = array_first( $users );
$last_user  = array_last( $users );
```

Возвращают значение или `null` для пустого массива; заменяют получение ключа и отдельное чтение элемента.

### Получение текущих обработчиков ошибок

```php
$error_handler     = get_error_handler();
$exception_handler = get_exception_handler();
```

Позволяет временно заменить обработчик и затем точно вернуть прежний, не храня отдельную глобальную переменную.

### Удобная рекурсия безымянной функции

```php
$factorial = function( int $value ): int
{
	return $value <= 1
		? 1
		: $value * Closure::getCurrent()( $value - 1 );
};
```

Текущая функция получает саму себя без передачи переменной по ссылке через `use`.

### Исключение при ошибке фильтрации - модуль `filter`

```php
$email = filter_var(
	$value,
	FILTER_VALIDATE_EMAIL,
	FILTER_THROW_ON_FAILURE,
);
```

Неверное значение сразу вызывает `Filter\FilterFailedException`; заменяет неоднозначную проверку результата `false`.

### Разделенные сторонние cookie

```php
setcookie( 'session', $token, [
	'secure'      => true,
	'httponly'    => true,
	'samesite'    => 'None',
	'partitioned' => true,
] );
```

Добавляет признак `Partitioned` для cookie в стороннем окружении. Требуется `Secure`; поддержка зависит от браузера.

### Постоянное общее соединение cURL - модуль `curl`

```php
$share = curl_share_init_persistent( [
	CURL_LOCK_DATA_DNS,
	CURL_LOCK_DATA_CONNECT,
] );
```

Общие данные DNS и соединений могут переживать один PHP-запрос; уменьшает стоимость повторных подключений к тем же узлам.

### Все дескрипторы многозадачного cURL - модуль `curl`

```php
$handles = curl_multi_get_handles( $multi_handle );
```

Возвращает добавленные дескрипторы без собственного параллельного массива для их учета.

### Форматирование списка по языковым правилам - модуль `intl`

```php
$formatter = new IntlListFormatter( 'ru', IntlListFormatter::TYPE_AND );
$text = $formatter->format( ['PHP', 'HTML', 'SQL'] );
```

Расставляет запятые и союзы по правилам выбранного языка; заменяет ручное склеивание последнего элемента.

### Направление письма языка - модуль `intl`

```php
$is_rtl = Locale::isRightToLeft( $locale );
```

Определяет направление текста без собственного списка арабских и еврейских языков.

### Расстояние между видимыми символами - модуль `intl`

```php
$distance = grapheme_levenshtein( $first, $second );
```

Сравнивает строки по видимым символам, а не байтам; лучше подходит для Unicode-текста.

### Работа с HTML-фрагментом - модуль `dom`

```php
$html = $element->outerHTML;
$element->insertAdjacentHTML( 'beforeend', '<strong>Text</strong>' );
```

Получает внешний HTML элемента и вставляет фрагмент без ручного создания промежуточного документа.

## Что заменить в старом коде

### Комментарии с типами

```php
# Было: /** @var int $id */
public int $id;
```

Типы свойств, аргументов, результатов и констант лучше указывать средствами языка; комментарий оставлять только для того, что типами не выразить.

### Длинные безымянные функции

```php
# Было: function( $value ) use ( $factor ) { return $value * $factor; }
$callback = fn( int $value ): int => $value * $factor;
```

Для одного выражения используйте `fn`; для нескольких действий обычная функция остается понятнее.

### Проверка и присваивание значения по умолчанию

```php
# Было: if( !isset( $config['timeout'] ) ) $config['timeout'] = 30;
$config['timeout'] ??= 30;
```

Оператор сохраняет существующие `false`, `0` и пустую строку, но заменяет отсутствующее значение и `null`.

### `strpos()` как логическая проверка

```php
# Было: strpos( $text, $needle ) !== false
$found = str_contains( $text, $needle );
```

Новая функция не путает позицию `0` с отсутствием совпадения.

### `substr()` для проверки края строки

```php
$is_php = str_ends_with( $file, '.php' );
$is_api = str_starts_with( $path, '/api/' );
```

Не нужны длина искомой строки, отрицательный сдвиг и отдельное сравнение.

### `switch` только ради получения значения

```php
$label = match( $status ) {
	1 => 'active',
	2 => 'blocked',
	default => 'unknown',
};
```

`match` сравнивает строго, возвращает результат и не проваливается в следующую ветку.

### Лесенка проверок на `null`

```php
$city = $user?->profile?->address?->city;
```

Используйте `?->`, только если отсутствие промежуточного объекта действительно должно дать `null`, а не ошибку.

### Свойства и присваивания вокруг конструктора

```php
public function __construct(
	public readonly int $id,
	public string $name,
) {}
```

Продвижение свойств убирает повторяющиеся объявления и присваивания; `readonly` дополнительно фиксирует значение.

### Строковые состояния

```php
enum Status: string
{
	case Active  = 'active';
	case Blocked = 'blocked';
}
```

Перечисление не даст передать опечатку или неизвестное состояние.

### Массив как ссылка на метод

```php
# Было: [$service, 'handle']
$handler = $service->handle(...);
```

Новая запись сразу проверяет метод и дает настоящий объект `Closure`.

### Самодельная проверка JSON

```php
# Было: @json_decode() и json_last_error()
$valid = json_validate( $payload );
```

Используйте `json_decode()` только если разобранные данные действительно нужны.

### `rand()`, `mt_rand()`, `shuffle()` и `array_rand()` вперемешку

```php
$randomizer = new Random\Randomizer();
$number = $randomizer->getInt( 1, 100 );
```

`Randomizer` дает единый понятный интерфейс и позволяет явно выбрать безопасный или воспроизводимый движок.

### Динамические свойства

```php
final class User
{
	public string $name;
}
```

Объявляйте свойства заранее. Для произвольных ключей используйте массив, `ArrayObject` или собственное внутреннее хранилище, а не случайные `$object->{$name}`.

### Интерфейс `Serializable`

```php
public function __serialize(): array {}
public function __unserialize( array $data ): void {}
```

Новые магические методы безопаснее сочетаются с наследованием и постепенно заменяют `Serializable`, `__sleep()` и `__wakeup()`.

### Простые методы `get_*()` и `set_*()`

```php
public string $name
{
	set => trim( $value );
}
```

В PHP 8.4 простое преобразование или вычисление можно разместить у свойства. Сложную предметную операцию лучше оставить отдельным методом.

### Ручной поиск в массиве

```php
$user = array_find( $users, fn( User $user ): bool => $user->id === $id );
```

Заменяет цикл, временную переменную и `break`; если важна предельная скорость большого массива, лучше заранее построить индекс по ключу.

### Вложенные вызовы преобразования

```php
$slug = $title |> trim(...) |> strtolower(...);
```

В PHP 8.5 цепочка читается слева направо. Для функций с дополнительными аргументами используйте короткую функцию.

### Ручное копирование неизменяемого объекта

```php
public function with_name( string $name ): self
{
	return clone( $this, ['name' => $name] );
}
```

PHP 8.5 позволяет заменить только нужное доступное свойство при клонировании.

### `parse_url()` для сложного изменения адреса

```php
$uri = new Uri\Rfc3986\Uri( $url );
$uri = $uri->withPath( '/new/path' );
```

Для простого чтения `parse_url()` остается нормальным, но новый модуль URI надежнее для проверки, нормализации и изменения адреса.

### Неявно допускающий `null` параметр

```php
# Устаревающая запись: function find( string $name = null )
function find( ?string $name = null ): ?User {}
```

Начиная с PHP 8.4 допустимость `null` нужно указывать явно.

### Старые смещения строк фигурными скобками

```php
# Было и удалено: $text{0}
$first_byte = $text[0];
```

Для настоящего первого символа Unicode используйте `mb_substr( $text, 0, 1 )`.

### `create_function()`

```php
# Было и удалено: create_function( '$value', 'return $value * 2;' )
$double = fn( int $value ): int => $value * 2;
```

Обычные безымянные функции не выполняют строку как код и проверяются синтаксически заранее.

### `each()`

```php
foreach( $items as $key => $value )
	process( $key, $value );
```

`each()` удалена в PHP 8.0; `foreach` короче, быстрее читается и не зависит от внутреннего указателя массива.

### Передача `null` во встроенные строковые функции

```php
$text = trim( $value ?? '' );
```

Передача `null` в параметр, который его не допускает, устаревает с PHP 8.1; решайте явно, чем является отсутствие значения.

### Старые названия приведений

```php
$id = (int)$value;
$enabled = (bool)$value;
```

В PHP 8.5 записи `(integer)`, `(boolean)`, `(double)` и `(binary)` устарели; используйте короткие канонические формы.

### Обратные кавычки для запуска команды

```php
$process = proc_open( ['php', '-v'], $descriptors, $pipes );
```

Обратные кавычки устарели в PHP 8.5. Массив аргументов `proc_open()` не пропускает пользовательские данные через командную оболочку.

## Важные границы

- `htmlspecialchars()` нужен для вывода обычного текста в HTML; `strip_tags()` не является полной защитой чужого HTML.
- Параметры SQL нужно передавать через подготовленные запросы; новые возможности синтаксиса не делают склеивание SQL безопасным.
- `Random\Randomizer` с безопасным стандартным движком подходит для секретов; `Mt19937` нужен для скорости и повторяемости, но не для токенов и паролей.
- `?->` скрывает только ожидаемое отсутствие объекта. Если `null` означает поврежденные данные, лучше выбросить исключение.
- Аргументы по имени связывают код с названиями параметров функции; при вызове чужого кода учитывайте возможность их переименования.
- Встроенный модуль означает часть официальной поставки PHP, но администратор может не включить его в конкретной сборке.

## Официальные материалы

- [PHP 7.4: новые возможности](https://www.php.net/manual/ru/migration74.new-features.php)
- [PHP 8.0: новые возможности](https://www.php.net/manual/ru/migration80.new-features.php)
- [PHP 8.1: новые возможности](https://www.php.net/manual/ru/migration81.new-features.php)
- [PHP 8.2: новые возможности](https://www.php.net/manual/ru/migration82.new-features.php)
- [PHP 8.3: новые возможности](https://www.php.net/manual/ru/migration83.new-features.php)
- [PHP 8.4: новые возможности](https://www.php.net/manual/ru/migration84.new-features.php)
- [PHP 8.5: новые возможности](https://www.php.net/manual/ru/migration85.new-features.php)
- [Текущие стабильные выпуски PHP](https://www.php.net/)
