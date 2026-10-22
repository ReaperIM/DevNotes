# Security and random values

[EN](../en/11-security-and-random.md) | [UK](../uk/11-security-and-random.md) | [RU](../ru/11-security-and-random.md)

[Contents](../../README.md)

**In this chapter**

- [Secure bytes and integers](#secure-random)
- [Password algorithms and capability checks](#password-algorithms)
- [Authenticated encryption with Sodium](#sodium)
- [One `Randomizer` API](#randomizer)
- [Reproducible randomness for tests](#seeded-random)
- [Random bytes from a chosen alphabet](#random-alphabet)
- [Random floats with explicit bounds](#random-floats)
- [Validate a `float` and its range](#float-validation)
- [Throw when validation fails](#filter-exceptions)
- [Ask OpenSSL for the key length](#cipher-key-length)

<a id="secure-random"></a>

## Secure bytes and integers

**PHP 7.0+ | random / standard in older PHP** [PHP manual](https://www.php.net/manual/en/function.random-bytes.php)

`random_bytes()` creates cryptographically secure bytes; `random_int()` selects a secure integer with inclusive bounds.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
```

Seeded simulation engines, timestamps and `uniqid()` are unsuitable substitutes for secrets.

<a id="password-algorithms"></a>

## Password algorithms and capability checks

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ `password_algos()`** [PHP manual](https://www.php.net/manual/en/function.password-hash.php)

Argon2 variants extend `password_hash()`; `password_algos()` lists algorithms actually available in the running build.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;
$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

`PASSWORD_DEFAULT` does not promise Argon2id; check `password_needs_rehash()` after successful verification when policy changes.

<a id="sodium"></a>

## Authenticated encryption with Sodium

**PHP 7.2+ bundled | sodium** [PHP manual](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php)

Sodium secretbox encrypts and authenticates a message, rejecting a wrong key or altered ciphertext.

```php
$key = sodium_crypto_secretbox_keygen();
$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
$ciphertext = sodium_crypto_secretbox( 'private note', $nonce, $key );
$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

if( $plaintext === false )
	throw new RuntimeException( 'Authentication failed' );

echo $plaintext; # private note
```

Keep the key secret and store the nonce with the ciphertext; never reuse a nonce with the same key.

<a id="randomizer"></a>

## One `Randomizer` API

**PHP 8.2+ | random** [PHP manual](https://www.php.net/manual/en/class.random-randomizer.php)

`Randomizer` groups random operations with a secure default engine. `shuffleArray()` returns a new reindexed list; `pickArrayKeys()` returns keys rather than values.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red": input unchanged
```

Selecting a non-cryptographic engine changes the security properties of its operations.

<a id="seeded-random"></a>

## Reproducible randomness for tests

**PHP 8.2+ | random** [PHP manual](https://www.php.net/manual/en/class.random-engine-mt19937.php)

A seeded engine reproduces a sequence when the engine, seed and sequence of calls match.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

`Mt19937`, `PcgOneseq128XslRr64` and `Xoshiro256StarStar` suit simulations and tests, not secrets.

<a id="random-alphabet"></a>

## Random bytes from a chosen alphabet

**PHP 8.3+ | random** [PHP manual](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php)

`getBytesFromString()` builds a random string from a chosen alphabet, useful for readable codes or identifiers.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

This operates on bytes, not Unicode characters; repeated alphabet bytes increase their probability.

<a id="random-floats"></a>

## Random floats with explicit bounds

**PHP 8.3+ | random** [PHP manual](https://www.php.net/manual/en/random-randomizer.getfloat.php)

`nextFloat()` returns `[0.0, 1.0)`; `getFloat()` accepts bounds and an `IntervalBoundary` mode.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat(); # [0.0, 1.0)
$weight = $randomizer->getFloat( 1.0, 2.0 ); # [1.0, 2.0)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

The default mode is `ClosedOpen`.

<a id="float-validation"></a>

## Validate a `float` and its range

**PHP 7.4+ range options | filter** [PHP manual](https://www.php.net/manual/en/filter.constants.validation.php)

`FILTER_VALIDATE_FLOAT` accepts `min_range` and `max_range` to check format and range together.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Compare failure with `=== false` so valid zero survives.

<a id="filter-exceptions"></a>

## Throw when validation fails

**PHP 8.5+ | filter** [PHP manual](https://wiki.php.net/rfc/filter_throw_on_failure)

`FILTER_THROW_ON_FAILURE` throws `Filter\FilterFailedException` on invalid input while allowing a valid `false` result.

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

The flag cannot be combined with `FILTER_NULL_ON_FAILURE`.

<a id="cipher-key-length"></a>

## Ask OpenSSL for the key length

**PHP 8.2+ | openssl** [PHP manual](https://www.php.net/manual/en/function.openssl-cipher-key-length.php)

`openssl_cipher_key_length()` returns a supported cipher's required key length in bytes.

```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32)
```

An unknown cipher emits a warning and returns `false`.
