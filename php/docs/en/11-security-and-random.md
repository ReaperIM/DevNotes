# Security and random values

[EN](../en/11-security-and-random.md) | [UK](../uk/11-security-and-random.md) | [RU](../ru/11-security-and-random.md)

[Contents](../../README.md)

A secret token and repeatable test data need different random generators. We cover both, then check passwords, encrypt a message and validate input without rejecting valid zero or `false` values.

**In this chapter**

- [Secure bytes and integers](#secure-random)
- [Password algorithms and capability checks](#password-algorithms)
- [Encrypt a message and check that it is unchanged](#sodium)
- [One `Randomizer` API](#randomizer)
- [Repeat a random sequence for a test](#seeded-random)
- [Random bytes from a chosen alphabet](#random-alphabet)
- [Random floats with explicit bounds](#random-floats)
- [Validate a `float` and its range](#float-validation)
- [Throw when validation fails](#filter-exceptions)
- [Ask OpenSSL for the key length](#cipher-key-length)

<a id="secure-random"></a>

## Secure bytes and integers

**PHP 7.0+ | random / standard in older PHP** [PHP manual](https://www.php.net/manual/en/function.random-bytes.php)

Use `random_bytes()` for a token or another secret. `bin2hex()` turns the bytes into a convenient hexadecimal string: 32 bytes become 64 characters. For a random integer, use `random_int()`; both range boundaries are included.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
```

These functions are suitable for cryptographic use. A seeded simulation generator, a timestamp or `uniqid()` is not a substitute when creating secrets: a predictable result is not acceptable here.

<a id="password-algorithms"></a>

## Password algorithms and capability checks

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ `password_algos()`** [PHP manual](https://www.php.net/manual/en/function.password-hash.php)

`password_hash()` supports Argon2 variants, but their availability depends on the PHP build. `password_algos()` tells us what is available in the current environment. Here we choose Argon2id when present, fall back to `PASSWORD_DEFAULT` otherwise, then verify the password against the hash.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;
$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

`PASSWORD_DEFAULT` does not promise Argon2id. After changing the chosen algorithm or its settings, call `password_needs_rehash()` following a successful password check to see whether the stored hash needs replacing.

<a id="sodium"></a>

## Encrypt a message and check that it is unchanged

**PHP 7.2+ bundled | sodium** [PHP manual](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php)

Sodium's secretbox both encrypts a message and checks that it has not been changed. Decryption needs the same key and the same `nonce`, a separate value for this encryption. Here we recover the original string; a wrong key or modified ciphertext would give `false` instead.

```php
$key = sodium_crypto_secretbox_keygen();
$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
$ciphertext = sodium_crypto_secretbox( 'private note', $nonce, $key );
$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

if( $plaintext === false )
	throw new RuntimeException( 'Authentication failed' );

echo $plaintext; # private note
```

Keep the key secret. The nonce can be stored alongside the ciphertext, but must not be reused with the same key. Keep the `=== false` check after decryption and only use the plaintext once it has passed.

<a id="randomizer"></a>

## One `Randomizer` API

**PHP 8.2+ | random** [PHP manual](https://www.php.net/manual/en/class.random-randomizer.php)

`Randomizer` gathers random operations in one object. Without an explicitly selected engine, it uses a secure one. Here we shuffle a copy of an array, choose two keys and get an integer from 10 to 20. The original array is left alone.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red": the input stays unchanged
```

`shuffleArray()` returns a new list with reindexed elements. `pickArrayKeys()` returns keys, not values. If we supply a non-cryptographic engine, these operations are no longer suitable for generating secrets.

<a id="seeded-random"></a>

## Repeat a random sequence for a test

**PHP 8.2+ | random** [PHP manual](https://www.php.net/manual/en/class.random-engine-mt19937.php)

For a test, it can help to get the same random sequence again. Set the same seed, the engine's initial value, and repeat the same calls. Both objects here use `Mt19937` with the same seed, so the two arrays match.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

The engine, seed and order of calls all need to match. `Mt19937`, `PcgOneseq128XslRr64` and `Xoshiro256StarStar` work for tests and simulations. Their predictability makes them unsuitable for secrets.

<a id="random-alphabet"></a>

## Random bytes from a chosen alphabet

**PHP 8.3+ | random** [PHP manual](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php)

Use `getBytesFromString()` when a code should contain only selected letters and digits. Pass the alphabet string and the required length. This alphabet leaves out some similar-looking characters, and the checks show that all 12 result bytes belong to the allowed set.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

The function chooses bytes, not whole Unicode characters, so arbitrary multilingual text does not work as an alphabet. Repeating a byte in the alphabet makes it more likely to be selected.

<a id="random-floats"></a>

## Random floats with explicit bounds

**PHP 8.3+ | random** [PHP manual](https://www.php.net/manual/en/random-randomizer.getfloat.php)

`nextFloat()` returns a number from `0.0` inclusive up to, but not including, `1.0`. Use `getFloat()` for a custom range. Here the lower bound `1.0` is allowed, while the upper bound `2.0` is excluded.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat(); # [0.0, 1.0)
$weight = $randomizer->getFloat( 1.0, 2.0 ); # [1.0, 2.0)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

This boundary rule is called `ClosedOpen` and is the default. Use `IntervalBoundary` to choose explicitly whether each boundary is included when the task needs another rule.

<a id="float-validation"></a>

## Validate a `float` and its range

**PHP 7.4+ range options | filter** [PHP manual](https://www.php.net/manual/en/filter.constants.validation.php)

`FILTER_VALIDATE_FLOAT` can check both the number format and its range with `min_range` and `max_range`. The string `'0'` is valid in this example and becomes `float(0)`.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Do not use a plain `if( !$price )` check here: it would reject a valid zero too. Test for failure with `=== false` so a zero price stays valid.

<a id="filter-exceptions"></a>

## Throw when validation fails

**PHP 8.5+ | filter** [PHP manual](https://wiki.php.net/rfc/filter_throw_on_failure)

Sometimes `false` is a valid result, not a failure. For example, the string `'false'` should become `false` when validating a `bool`. `FILTER_THROW_ON_FAILURE` separates these cases: valid values are returned, while invalid input throws `Filter\FilterFailedException`.

```php
$enabled = filter_var( 'false', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
var_dump( $enabled ); # bool(false), a valid value

try
{
	filter_var( 'perhaps', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
}
catch( Filter\FilterFailedException $error )
{
	echo 'invalid';
}
```

The first call therefore returns `false` normally, while `'perhaps'` reaches `catch`. This flag cannot be combined with `FILTER_NULL_ON_FAILURE`.

<a id="cipher-key-length"></a>

## Ask OpenSSL for the key length

**PHP 8.2+ | openssl** [PHP manual](https://www.php.net/manual/en/function.openssl-cipher-key-length.php)

Use `openssl_cipher_key_length()` to ask OpenSSL for the key size instead of hardcoding it separately from the cipher name. The result is in bytes. For `aes-256-gcm`, the example gets 32 bytes and generates a key of that length.

```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32)
```

An unknown cipher emits a warning and returns `false`. Check the length before passing it to `random_bytes()`.
