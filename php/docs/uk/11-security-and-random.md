# Безпека й випадкові значення

[English](../en/11-security-and-random.md) | [Українська](../uk/11-security-and-random.md) | [Русский](../ru/11-security-and-random.md)

[Зміст](../../README.uk.md)

PHP має спеціальні API для випадкових секретів, хешів паролів і перевірки даних. Спирайтеся на їхні задокументовані гарантії замість саморобних поєднань рядкових функцій. Приклади розрізняють безпечну випадковість і відтворювані тестові дані.

**У цьому розділі**

- [Безпечні випадкові байти й числа](#secure-random)
- [Алгоритми паролів і перевірка можливостей](#password-algorithms)
- [Автентифіковане шифрування через Sodium](#sodium)
- [Єдиний API Randomizer](#randomizer)
- [Відтворювана випадковість для тестів](#seeded-random)
- [Випадкові байти із заданого алфавіту](#random-alphabet)
- [Випадкові дробові числа з явними межами](#random-floats)
- [Перевірка дробового числа й діапазону](#float-validation)
- [Виняток при невдалій перевірці](#filter-exceptions)
- [Отримання довжини ключа від OpenSSL](#cipher-key-length)

<a id="secure-random"></a>

## Безпечні випадкові байти й числа

**PHP 7.0+ | random / standard in older PHP**

random_bytes() дає криптографічно безпечні байти; random_int() вибирає безпечне ціле число з включними межами. Токен і вибір числа є різними задачами з різним обсягом результату.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
var_dump( $number >= 1 && $number <= 6 ); # bool(true)
```

32 випадкові байти стають 64 шістнадцятковими символами; кодування змінює запис, а не ентропію. Для секретів не замінюйте ці API часом, uniqid() або тестовим генератором із зерном. Шестизначний код усе одно потребує строку дії й обмеження спроб. [Документація PHP](https://www.php.net/manual/en/function.random-bytes.php).

<a id="password-algorithms"></a>

## Алгоритми паролів і перевірка можливостей

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ password_algos()**

Підтримка Argon2 розширює наявний API password_hash(). password_algos() показує алгоритми, зареєстровані в поточній збірці, замість припущення, що номер версії гарантує їх наявність.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;

$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

Збережений хеш містить алгоритм, параметри й сіль для перевірки. Не шифруйте паролі та не додавайте власну сіль. Після успішного входу перевіряйте password_needs_rehash(), якщо політика змінилася; вартість обчислення підбирайте вимірюваннями на своєму сервері. PASSWORD_DEFAULT є підтримуваним типовим вибором, а не обіцянкою вибрати Argon2id. [Документація PHP](https://www.php.net/manual/en/function.password-hash.php).

<a id="sodium"></a>

## Автентифіковане шифрування через Sodium

**PHP 7.2+ bundled | sodium**

Sodium постачається з PHP починаючи з 7.2. Secretbox шифрує повідомлення й перевіряє його автентичність: неправильний ключ або змінений шифротекст не можуть непомітно стати прийнятим відкритим текстом.

<!-- example: fragment -->
```php
$key = sodium_crypto_secretbox_keygen();
$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
$ciphertext = sodium_crypto_secretbox( 'private note', $nonce, $key );
$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

if( $plaintext === false )
	throw new RuntimeException( 'Authentication failed' );

echo $plaintext; # private note
```

Розширення має бути ввімкнене. Ключ тримайте в секреті, а nonce зберігайте або передавайте разом із шифротекстом; nonce не є паролем і не має повторюватися з тим самим ключем. Новий випадковий ключ тут показує операцію, а не постійне зберігання ключів. Для паролів входу не використовуйте зворотне шифрування. [Документація PHP](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php).

<a id="randomizer"></a>

## Єдиний API Randomizer

**PHP 8.2+ | random**

Randomizer поєднує вибір чисел, байти, перемішування та вибір ключів масиву в одному об’єкті. Без явно переданого рушія використовується безпечний рушій.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red"
```

shuffleArray() повертає новий список, не змінює вхідний масив і не зберігає ключі. pickArrayKeys() повертає ключі, а не значення. getInt() включає обидві межі. Вибір небезпечного рушія змінює властивості безпеки всіх операцій із ним. [Документація PHP](https://www.php.net/manual/en/class.random-randomizer.php).

<a id="seeded-random"></a>

## Відтворювана випадковість для тестів

**PHP 8.2+ | random**

Рушій із явно заданим зерном створює відтворювані тестові дані. Два рушії з однаковим зерном і однаковими викликами дають однакову послідовність.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

Mt19937, PcgOneseq128XslRr64 та Xoshiro256StarStar підходять для симуляцій і тестів, а не секретів. Зерно не є криптографічним ключем. Відтворення залежить від рушія й порядку викликів, а не лише від зерна. [Документація PHP](https://www.php.net/manual/en/class.random-engine-mt19937.php).

<a id="random-alphabet"></a>

## Випадкові байти із заданого алфавіту

**PHP 8.3+ | random**

getBytesFromString() вибирає байти із заданого алфавіту. Це зручно для коротких ідентифікаторів або читабельних кодів із явно вибраними символами.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

Операція працює з байтами, а не символами Unicode: використовуйте ASCII-алфавіт. Повторення байтів в алфавіті збільшує їхню ймовірність. Довжина й розмір алфавіту визначають простір пошуку, тому зручний короткий код не є автоматично сильним довготривалим секретом. [Документація PHP](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php).

<a id="random-floats"></a>

## Випадкові дробові числа з явними межами

**PHP 8.3+ | random**

nextFloat() дає значення в [0.0, 1.0). getFloat() вибирає число з потрібного інтервалу; IntervalBoundary керує включенням кожної межі.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat();
$weight = $randomizer->getFloat( 1.0, 2.0 );
var_dump( $unit >= 0.0 && $unit < 1.0 ); # bool(true)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

Типова межа є ClosedOpen. Методи дозволяють не вигадувати перетворення випадкових цілих через ділення. Результат усе одно є двійковим дробовим числом, а не точним десятковим грошовим значенням. [Документація PHP](https://www.php.net/manual/en/random-randomizer.getfloat.php).

<a id="float-validation"></a>

## Перевірка дробового числа й діапазону

**PHP 7.4+ range options | filter**

FILTER_VALIDATE_FLOAT приймає min_range і max_range. Формат і діапазон можна перевірити разом, не перетворюючи некоректний текст на число заздалегідь.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Перевіряйте збій через === false, щоб зберегти правильний нуль. Десятковий формат і правила локалі мають відповідати параметрам фільтра. Перевірений float усе одно має звичайні обмеження точності. [Документація PHP](https://www.php.net/manual/en/filter.constants.validation.php).

<a id="filter-exceptions"></a>

## Виняток при невдалій перевірці

**PHP 8.5+ | filter**

FILTER_THROW_ON_FAILURE перетворює невдалу перевірку на Filter\FilterFailedException. Це особливо зручно, коли false саме є правильним результатом фільтрації.

```php
$enabled = filter_var( 'false', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
var_dump( $enabled ); # bool(false), valid input

try
{
	filter_var( 'perhaps', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
}
catch( Filter\FilterFailedException $error )
{
	echo 'invalid';
}
```

Прапорець не можна поєднувати з FILTER_NULL_ON_FAILURE. Деталі винятку можуть містити відхилене значення; не показуйте їх користувачам і не записуйте секрети бездумно. Перевірка не замінює екранування для конкретного контексту виводу. [Документація PHP](https://wiki.php.net/rfc/filter_throw_on_failure).

<a id="cipher-key-length"></a>

## Отримання довжини ключа від OpenSSL

**PHP 8.2+ | openssl**

openssl_cipher_key_length() повертає потрібну довжину ключа підтримуваного шифру. Це замінює власну таблицю назв алгоритмів і кількості байтів.

<!-- example: fragment -->
```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32) for aes-256-gcm
```

Повертаються байти, а не біти. Невідомий шифр викликає попередження й повертає false. Сама довжина ключа не проєктує шифрування: nonce/IV, теги автентичності та зберігання ключа залишаються окремими вимогами. [Документація PHP](https://www.php.net/manual/en/function.openssl-cipher-key-length.php).
