# Безопасность и случайные значения

[EN](../en/11-security-and-random.md) | [UK](../uk/11-security-and-random.md) | [RU](../ru/11-security-and-random.md)

[Содержание](../../README.ru.md)

Секретный токен и повторяемые данные для теста требуют разных генераторов. Разбираем оба случая, а рядом проверяем пароли, шифруем сообщение и проверяем входные значения без потери корректных нуля и `false`.

**В этой главе**

- [Безопасные случайные байты и числа](#secure-random)
- [Алгоритмы паролей и проверка возможностей](#password-algorithms)
- [Шифруем сообщение и проверяем его целостность](#sodium)
- [Единый API `Randomizer`](#randomizer)
- [Повторяем случайную последовательность для теста](#seeded-random)
- [Случайные байты из заданного алфавита](#random-alphabet)
- [Случайные дробные числа с явными границами](#random-floats)
- [Проверка дробного числа и диапазона](#float-validation)
- [Исключение при неудачной проверке](#filter-exceptions)
- [Получение длины ключа от OpenSSL](#cipher-key-length)

<a id="secure-random"></a>

## Безопасные случайные байты и числа

**PHP 7.0+ | random / standard в старых версиях PHP** [Документация PHP](https://www.php.net/manual/en/function.random-bytes.php)

Для токена или другого секрета берём `random_bytes()`. Полученные байты можно перевести в удобную для передачи шестнадцатеричную строку через `bin2hex()`: из 32 байт получится 64 символа. Если нужно случайное целое число, есть `random_int()`, обе границы диапазона включены.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
```

Эти функции подходят для криптографических задач. Генератор с заданным seed, время или `uniqid()` не заменяют их при создании секретов: предсказуемый результат здесь не подходит.

<a id="password-algorithms"></a>

## Алгоритмы паролей и проверка возможностей

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ `password_algos()`** [Документация PHP](https://www.php.net/manual/en/function.password-hash.php)

В `password_hash()` появились варианты Argon2, но доступность алгоритма зависит от сборки PHP. `password_algos()` позволяет проверить её прямо в текущем окружении. Здесь выбираем Argon2id, если он есть, иначе оставляем `PASSWORD_DEFAULT`, а потом проверяем пароль по готовому хешу.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;
$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

`PASSWORD_DEFAULT` сам по себе не обещает Argon2id. Если поменяли алгоритм или его настройки, после успешной проверки пароля можно вызвать `password_needs_rehash()` и понять, пора ли пересоздать хеш.

<a id="sodium"></a>

## Шифруем сообщение и проверяем его целостность

**PHP 7.2+ в комплекте | sodium** [Документация PHP](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php)

Secretbox из Sodium не только шифрует сообщение, но и проверяет, что его не изменили. Для расшифровки нужны тот же ключ и тот же `nonce`, отдельное значение для этого шифрования. В примере после шифрования получаем исходную строку обратно, а неверный ключ или изменённые данные дали бы `false`.

```php
$key = sodium_crypto_secretbox_keygen();
$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
$ciphertext = sodium_crypto_secretbox( 'private note', $nonce, $key );
$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

if( $plaintext === false )
	throw new RuntimeException( 'Authentication failed' );

echo $plaintext; # private note
```

Ключ храним в секрете. `nonce` можно хранить рядом с зашифрованными данными, но повторять его с тем же ключом нельзя. Проверку `=== false` после расшифровки не пропускаем: использовать результат до неё нельзя.

<a id="randomizer"></a>

## Единый API `Randomizer`

**PHP 8.2+ | random** [Документация PHP](https://www.php.net/manual/en/class.random-randomizer.php)

`Randomizer` собирает случайные операции в одном объекте. Без явного выбора генератора он использует безопасный вариант. В примере перемешиваем копию массива, выбираем два ключа и получаем целое от 10 до 20. Исходный массив остаётся на месте.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red": исходный массив не меняли
```

`shuffleArray()` возвращает новый список с заново пронумерованными элементами. `pickArrayKeys()` возвращает именно ключи, а не значения. Если подставить свой некриптографический генератор, считать эти операции безопасными для секретов уже нельзя.

<a id="seeded-random"></a>

## Повторяем случайную последовательность для теста

**PHP 8.2+ | random** [Документация PHP](https://www.php.net/manual/en/class.random-engine-mt19937.php)

Для теста бывает полезно получить ту же «случайную» последовательность ещё раз. Задаём одинаковый seed, то есть начальное значение генератора, и повторяем те же вызовы. В примере оба объекта используют `Mt19937` с одним seed, поэтому два массива совпадают.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

Должны совпасть и сам генератор, и seed, и порядок вызовов. `Mt19937`, `PcgOneseq128XslRr64` и `Xoshiro256StarStar` подходят для тестов и симуляций. Для секретов они не подходят именно из-за предсказуемости.

<a id="random-alphabet"></a>

## Случайные байты из заданного алфавита

**PHP 8.3+ | random** [Документация PHP](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php)

Если код должен состоять только из выбранных букв и цифр, есть `getBytesFromString()`. Передаём строку-алфавит и нужную длину. Здесь из алфавита заранее убраны некоторые похожие символы, а обе проверки показывают, что все 12 байт результата взяты из разрешённого набора.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

Функция выбирает байты, а не целые символы Unicode, поэтому произвольный многоязычный текст как алфавит не подойдёт. Если один байт повторён в алфавите несколько раз, выпадать он будет чаще.

<a id="random-floats"></a>

## Случайные дробные числа с явными границами

**PHP 8.3+ | random** [Документация PHP](https://www.php.net/manual/en/random-randomizer.getfloat.php)

`nextFloat()` даёт дробное число от `0.0` включительно до `1.0`, не включая `1.0`. Если нужен свой диапазон, используем `getFloat()`. В примере нижняя граница `1.0` разрешена, а верхняя `2.0` уже не входит в результат.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat(); # [0.0, 1.0)
$weight = $randomizer->getFloat( 1.0, 2.0 ); # [1.0, 2.0)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

Такое правило называется `ClosedOpen` и используется по умолчанию. Через `IntervalBoundary` можно явно выбрать, включать ли каждую из границ, если задаче нужен другой вариант.

<a id="float-validation"></a>

## Проверка дробного числа и диапазона

**PHP 7.4+ проверка диапазона | filter** [Документация PHP](https://www.php.net/manual/en/filter.constants.validation.php)

`FILTER_VALIDATE_FLOAT` может проверить не только запись дробного числа, но и его диапазон. Для этого задаём `min_range` и `max_range`. В примере строка `'0'` корректна и превращается в `float(0)`.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Не проверяем такой результат через обычное `if( !$price )`: корректный ноль тоже туда попадёт. Ошибку отличаем строго через `=== false`, тогда нулевую цену не потеряем.

<a id="filter-exceptions"></a>

## Исключение при неудачной проверке

**PHP 8.5+ | filter** [Документация PHP](https://wiki.php.net/rfc/filter_throw_on_failure)

Иногда `false` является правильным результатом проверки, а не признаком ошибки. Например, строка `'false'` при проверке `bool` должна дать `false`. Флаг `FILTER_THROW_ON_FAILURE` разделяет эти ситуации: корректное значение возвращается, а неверное вызывает `Filter\FilterFailedException`.

```php
$enabled = filter_var( 'false', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
var_dump( $enabled ); # bool(false), это правильное значение

try
{
	filter_var( 'perhaps', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
}
catch( Filter\FilterFailedException $error )
{
	echo 'invalid';
}
```

Поэтому первый вызов спокойно возвращает `false`, а строка `'perhaps'` попадает в `catch`. С `FILTER_NULL_ON_FAILURE` этот флаг сочетать нельзя.

<a id="cipher-key-length"></a>

## Получение длины ключа от OpenSSL

**PHP 8.2+ | openssl** [Документация PHP](https://www.php.net/manual/en/function.openssl-cipher-key-length.php)

Чтобы не прописывать размер ключа отдельно от названия шифра, можно спросить его у OpenSSL через `openssl_cipher_key_length()`. Результат приходит в байтах. Для `aes-256-gcm` в примере получаем 32 байта и генерируем ключ нужной длины.

```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32)
```

Если шифр неизвестен, функция выдаст предупреждение и вернёт `false`. Поэтому сначала проверяем длину, а не передаём результат сразу в `random_bytes()`.
