# Security and random values

[English](../en/11-security-and-random.md) | [Українська](../uk/11-security-and-random.md) | [Русский](../ru/11-security-and-random.md)

[Contents](../../README.md)

PHP provides purpose-built APIs for random secrets, password hashes and validation. Use their documented guarantees rather than assembling security properties from unrelated string functions. Examples distinguish secure randomness from reproducible test data.

**In this chapter**

- [Secure bytes and integers](#secure-random)
- [Password algorithms and capability checks](#password-algorithms)
- [Authenticated encryption with Sodium](#sodium)
- [One Randomizer API](#randomizer)
- [Reproducible randomness for tests](#seeded-random)
- [Random bytes from a chosen alphabet](#random-alphabet)
- [Random floats with explicit bounds](#random-floats)
- [Validate a float and its range](#float-validation)
- [Throw when validation fails](#filter-exceptions)
- [Ask OpenSSL for the key length](#cipher-key-length)

<a id="secure-random"></a>

## Secure bytes and integers

**PHP 7.0+ | random / standard in older PHP**

random_bytes() supplies cryptographically secure bytes; random_int() chooses a secure integer in an inclusive range. A token and a numeric selection are different tasks, with different output sizes.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
var_dump( $number >= 1 && $number <= 6 ); # bool(true)
```

32 random bytes become 64 hex characters; encoding changes representation, not entropy. Do not replace these APIs with timestamps, uniqid() or a seeded simulation engine for secrets. A six-digit code still needs expiry and attempt limits. [PHP manual](https://www.php.net/manual/en/function.random-bytes.php).

<a id="password-algorithms"></a>

## Password algorithms and capability checks

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ password_algos()**

Argon2 support extends the existing password_hash() API. password_algos() lists algorithms actually registered in the running build rather than assuming that a version number guarantees availability.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;

$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

The stored hash contains the algorithm, options and salt needed for verification. Do not encrypt passwords or add your own salt. Check password_needs_rehash() after a successful login when policy changes, and select cost settings using measurements on your deployment. PASSWORD_DEFAULT is a maintained default, not a promise that it selects Argon2id. [PHP manual](https://www.php.net/manual/en/function.password-hash.php).

<a id="sodium"></a>

## Authenticated encryption with Sodium

**PHP 7.2+ bundled | sodium**

Sodium became a bundled extension in PHP 7.2. Secretbox encrypts a message and authenticates it: a wrong key or changed ciphertext cannot silently turn into accepted plaintext.

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

The extension must be enabled. Keep the key secret and store/transmit the nonce with the ciphertext; a nonce is not a password and must not be reused with the same key. A fresh random key in this example demonstrates an operation, not durable key storage. Do not use reversible encryption to store login passwords. [PHP manual](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php).

<a id="randomizer"></a>

## One Randomizer API

**PHP 8.2+ | random**

Randomizer combines integer selection, bytes, shuffling and array-key selection in one object. Without an explicit engine it uses the secure engine.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red"
```

shuffleArray() returns a new list and does not mutate the input; it does not preserve keys. pickArrayKeys() returns keys, not values. getInt() includes both bounds. Choosing a non-secure engine changes the security properties of all operations that use it. [PHP manual](https://www.php.net/manual/en/class.random-randomizer.php).

<a id="seeded-random"></a>

## Reproducible randomness for tests

**PHP 8.2+ | random**

An explicitly seeded engine produces repeatable test data. Two engines starting from the same seed and receiving the same calls stay in step.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

Mt19937, PcgOneseq128XslRr64 and Xoshiro256StarStar serve simulations or testing, not secrets. A seed is not a cryptographic key. Reproduction depends on the engine and sequence of calls, not only the seed value. [PHP manual](https://www.php.net/manual/en/class.random-engine-mt19937.php).

<a id="random-alphabet"></a>

## Random bytes from a chosen alphabet

**PHP 8.3+ | random**

getBytesFromString() selects bytes from a provided alphabet. It is convenient for compact identifiers or human-readable codes with an explicitly chosen alphabet.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

This is byte-based, not a Unicode-character operation: use an ASCII alphabet. Duplicate alphabet bytes increase their probability. Code length and alphabet size determine the search space, so a convenient short code is not automatically a strong long-lived secret. [PHP manual](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php).

<a id="random-floats"></a>

## Random floats with explicit bounds

**PHP 8.3+ | random**

nextFloat() gives a value in [0.0, 1.0). getFloat() selects from a chosen interval, with an IntervalBoundary argument to control whether each endpoint is included.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat();
$weight = $randomizer->getFloat( 1.0, 2.0 );
var_dump( $unit >= 0.0 && $unit < 1.0 ); # bool(true)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

The default boundary is ClosedOpen. These methods avoid inventing a division-based conversion from random integers. They still return binary floating-point values, not exact decimal money amounts. [PHP manual](https://www.php.net/manual/en/random-randomizer.getfloat.php).

<a id="float-validation"></a>

## Validate a float and its range

**PHP 7.4+ range options | filter**

FILTER_VALIDATE_FLOAT accepts min_range and max_range options. Format and range can be checked together without converting invalid text to a number first.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Test failure with === false so valid zero survives. Decimal formatting and locale conventions must match the selected filter options. A validated float still has ordinary floating-point precision limits. [PHP manual](https://www.php.net/manual/en/filter.constants.validation.php).

<a id="filter-exceptions"></a>

## Throw when validation fails

**PHP 8.5+ | filter**

FILTER_THROW_ON_FAILURE makes failed validation throw Filter\FilterFailedException. It is especially useful when false is itself a valid filtered result.

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

The flag cannot be combined with FILTER_NULL_ON_FAILURE. Exception details can contain the rejected value; do not expose them to users or log secrets blindly. Validation does not replace context-specific output escaping. [PHP manual](https://wiki.php.net/rfc/filter_throw_on_failure).

<a id="cipher-key-length"></a>

## Ask OpenSSL for the key length

**PHP 8.2+ | openssl**

openssl_cipher_key_length() returns the required key length for a supported cipher. It replaces a manually maintained table of algorithm names and byte lengths.

<!-- example: fragment -->
```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32) for aes-256-gcm
```

This returns bytes, not bits. An unknown cipher emits a warning and returns false. Key length alone does not design encryption: nonce/IV handling, authentication tags and key storage remain separate requirements. [PHP manual](https://www.php.net/manual/en/function.openssl-cipher-key-length.php).
