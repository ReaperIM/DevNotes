# Безпека й випадкові значення

[EN](../en/11-security-and-random.md) | [UK](../uk/11-security-and-random.md) | [RU](../ru/11-security-and-random.md)

[Зміст](../../README.uk.md)

**У цьому розділі**

- [Безпечні випадкові байти й числа](#secure-random)
- [Алгоритми паролів і перевірка можливостей](#password-algorithms)
- [Автентифіковане шифрування через Sodium](#sodium)
- [Єдиний API `Randomizer`](#randomizer)
- [Відтворювана випадковість для тестів](#seeded-random)
- [Випадкові байти із заданого алфавіту](#random-alphabet)
- [Випадкові дробові числа з явними межами](#random-floats)
- [Перевірка дробового числа й діапазону](#float-validation)
- [Виняток при невдалій перевірці](#filter-exceptions)
- [Отримання довжини ключа від OpenSSL](#cipher-key-length)

<a id="secure-random"></a>

## Безпечні випадкові байти й числа

**PHP 7.0+ | random / standard in older PHP** [Документація PHP](https://www.php.net/manual/en/function.random-bytes.php)

`random_bytes()` створює криптографічно стійкі байти; `random_int()` вибирає стійке випадкове ціле з включеними межами.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
```

Симуляційні генератори з seed, часові мітки та `uniqid()` не підходять як заміна для секретів.

<a id="password-algorithms"></a>

## Алгоритми паролів і перевірка можливостей

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ `password_algos()`** [Документація PHP](https://www.php.net/manual/en/function.password-hash.php)

Варіанти Argon2 розширюють `password_hash()`; `password_algos()` показує алгоритми, реально доступні в поточній збірці.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;
$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

`PASSWORD_DEFAULT` не гарантує Argon2id; за зміни політики перевіряйте `password_needs_rehash()` після успішної перевірки пароля.

<a id="sodium"></a>

## Автентифіковане шифрування через Sodium

**PHP 7.2+ bundled | sodium** [Документація PHP](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php)

Secretbox у Sodium шифрує та автентифікує повідомлення, відхиляючи неправильний ключ або змінений шифротекст.

```php
$key = sodium_crypto_secretbox_keygen();
$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
$ciphertext = sodium_crypto_secretbox( 'private note', $nonce, $key );
$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

if( $plaintext === false )
	throw new RuntimeException( 'Authentication failed' );

echo $plaintext; # private note
```

Зберігайте ключ у таємниці, а nonce разом із шифротекстом; ніколи не використовуйте nonce повторно з тим самим ключем.

<a id="randomizer"></a>

## Єдиний API `Randomizer`

**PHP 8.2+ | random** [Документація PHP](https://www.php.net/manual/en/class.random-randomizer.php)

`Randomizer` об'єднує випадкові операції з безпечним типовим генератором. `shuffleArray()` повертає новий список із новими індексами; `pickArrayKeys()` повертає ключі, а не значення.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red": input unchanged
```

Вибір некриптографічного генератора змінює властивості безпеки його операцій.

<a id="seeded-random"></a>

## Відтворювана випадковість для тестів

**PHP 8.2+ | random** [Документація PHP](https://www.php.net/manual/en/class.random-engine-mt19937.php)

Генератор із seed відтворює послідовність, якщо збігаються генератор, seed і послідовність викликів.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

`Mt19937`, `PcgOneseq128XslRr64` і `Xoshiro256StarStar` підходять для симуляцій і тестів, а не для секретів.

<a id="random-alphabet"></a>

## Випадкові байти із заданого алфавіту

**PHP 8.3+ | random** [Документація PHP](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php)

`getBytesFromString()` створює випадковий рядок із вибраного алфавіту, що зручно для читабельних кодів або ідентифікаторів.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

Операція працює з байтами, а не символами Unicode; повторені байти алфавіту мають більшу ймовірність.

<a id="random-floats"></a>

## Випадкові дробові числа з явними межами

**PHP 8.3+ | random** [Документація PHP](https://www.php.net/manual/en/random-randomizer.getfloat.php)

`nextFloat()` повертає `[0.0, 1.0)`; `getFloat()` приймає межі та режим `IntervalBoundary`.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat(); # [0.0, 1.0)
$weight = $randomizer->getFloat( 1.0, 2.0 ); # [1.0, 2.0)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

Типовий режим: `ClosedOpen`.

<a id="float-validation"></a>

## Перевірка дробового числа й діапазону

**PHP 7.4+ range options | filter** [Документація PHP](https://www.php.net/manual/en/filter.constants.validation.php)

`FILTER_VALIDATE_FLOAT` приймає `min_range` і `max_range` для спільної перевірки формату та діапазону.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Перевіряйте збій через `=== false`, щоб зберегти коректний нуль.

<a id="filter-exceptions"></a>

## Виняток при невдалій перевірці

**PHP 8.5+ | filter** [Документація PHP](https://wiki.php.net/rfc/filter_throw_on_failure)

`FILTER_THROW_ON_FAILURE` викликає `Filter\FilterFailedException` для некоректного вводу, дозволяючи коректний результат `false`.

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

Прапорець не можна поєднувати з `FILTER_NULL_ON_FAILURE`.

<a id="cipher-key-length"></a>

## Отримання довжини ключа від OpenSSL

**PHP 8.2+ | openssl** [Документація PHP](https://www.php.net/manual/en/function.openssl-cipher-key-length.php)

`openssl_cipher_key_length()` повертає потрібну довжину ключа підтримуваного шифру в байтах.

```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32)
```

Невідомий шифр викликає попередження та повертає `false`.
