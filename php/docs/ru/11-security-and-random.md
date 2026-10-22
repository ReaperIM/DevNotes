# Безопасность и случайные значения

[English](../en/11-security-and-random.md) | [Українська](../uk/11-security-and-random.md) | [Русский](../ru/11-security-and-random.md)

[Содержание](../../README.ru.md)

**В этой главе**

- [Безопасные случайные байты и числа](#secure-random)
- [Алгоритмы паролей и проверка возможностей](#password-algorithms)
- [Аутентифицированное шифрование через Sodium](#sodium)
- [Единый API Randomizer](#randomizer)
- [Воспроизводимая случайность для тестов](#seeded-random)
- [Случайные байты из заданного алфавита](#random-alphabet)
- [Случайные дробные числа с явными границами](#random-floats)
- [Проверка дробного числа и диапазона](#float-validation)
- [Исключение при неудачной проверке](#filter-exceptions)
- [Получение длины ключа от OpenSSL](#cipher-key-length)

<a id="secure-random"></a>

## Безопасные случайные байты и числа

**PHP 7.0+ | random / standard in older PHP** [Документация PHP](https://www.php.net/manual/en/function.random-bytes.php)

`random_bytes()` создаёт криптографически стойкие байты; `random_int()` выбирает стойкое случайное целое с включёнными границами.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
```

Генераторы симуляций с seed, временные метки и `uniqid()` не подходят как замена для секретов.

<a id="password-algorithms"></a>

## Алгоритмы паролей и проверка возможностей

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ password_algos()** [Документация PHP](https://www.php.net/manual/en/function.password-hash.php)

Варианты Argon2 расширяют `password_hash()`; `password_algos()` показывает алгоритмы, реально доступные в текущей сборке.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;
$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

`PASSWORD_DEFAULT` не гарантирует Argon2id; при изменении политики проверяйте `password_needs_rehash()` после успешной проверки пароля.

<a id="sodium"></a>

## Аутентифицированное шифрование через Sodium

**PHP 7.2+ bundled | sodium** [Документация PHP](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php)

Secretbox в Sodium шифрует и проверяет подлинность сообщения, отклоняя неправильный ключ или изменённый шифротекст.

```php
$key = sodium_crypto_secretbox_keygen();
$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
$ciphertext = sodium_crypto_secretbox( 'private note', $nonce, $key );
$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

if( $plaintext === false )
	throw new RuntimeException( 'Authentication failed' );

echo $plaintext; # private note
```

Храните ключ в секрете, а nonce вместе с шифротекстом; никогда не используйте nonce повторно с тем же ключом.

<a id="randomizer"></a>

## Единый API Randomizer

**PHP 8.2+ | random** [Документация PHP](https://www.php.net/manual/en/class.random-randomizer.php)

`Randomizer` объединяет случайные операции с безопасным генератором по умолчанию. `shuffleArray()` возвращает новый список с новыми индексами; `pickArrayKeys()` возвращает ключи, а не значения.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red": input unchanged
```

Выбор некриптографического генератора меняет свойства безопасности его операций.

<a id="seeded-random"></a>

## Воспроизводимая случайность для тестов

**PHP 8.2+ | random** [Документация PHP](https://www.php.net/manual/en/class.random-engine-mt19937.php)

Генератор с seed воспроизводит последовательность, если совпадают генератор, seed и последовательность вызовов.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

`Mt19937`, `PcgOneseq128XslRr64` и `Xoshiro256StarStar` подходят для симуляций и тестов, а не для секретов.

<a id="random-alphabet"></a>

## Случайные байты из заданного алфавита

**PHP 8.3+ | random** [Документация PHP](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php)

`getBytesFromString()` создаёт случайную строку из выбранного алфавита, что удобно для читаемых кодов или идентификаторов.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

Операция работает с байтами, а не символами Unicode; повторённые байты алфавита имеют большую вероятность.

<a id="random-floats"></a>

## Случайные дробные числа с явными границами

**PHP 8.3+ | random** [Документация PHP](https://www.php.net/manual/en/random-randomizer.getfloat.php)

`nextFloat()` возвращает [0.0, 1.0); `getFloat()` принимает границы и режим `IntervalBoundary`.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat(); # [0.0, 1.0)
$weight = $randomizer->getFloat( 1.0, 2.0 ); # [1.0, 2.0)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

Режим по умолчанию: `ClosedOpen`.

<a id="float-validation"></a>

## Проверка дробного числа и диапазона

**PHP 7.4+ range options | filter** [Документация PHP](https://www.php.net/manual/en/filter.constants.validation.php)

`FILTER_VALIDATE_FLOAT` принимает `min_range` и `max_range` для совместной проверки формата и диапазона.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Проверяйте сбой через `=== false`, чтобы сохранить корректный ноль.

<a id="filter-exceptions"></a>

## Исключение при неудачной проверке

**PHP 8.5+ | filter** [Документация PHP](https://wiki.php.net/rfc/filter_throw_on_failure)

`FILTER_THROW_ON_FAILURE` выбрасывает `Filter\FilterFailedException` для некорректного ввода, допуская корректный результат `false`.

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

Флаг нельзя сочетать с `FILTER_NULL_ON_FAILURE`.

<a id="cipher-key-length"></a>

## Получение длины ключа от OpenSSL

**PHP 8.2+ | openssl** [Документация PHP](https://www.php.net/manual/en/function.openssl-cipher-key-length.php)

`openssl_cipher_key_length()` возвращает нужную длину ключа поддерживаемого шифра в байтах.

```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32)
```

Неизвестный шифр вызывает предупреждение и возвращает `false`.
