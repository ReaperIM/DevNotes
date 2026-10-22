# Безопасность и случайные значения

[English](../en/11-security-and-random.md) | [Українська](../uk/11-security-and-random.md) | [Русский](../ru/11-security-and-random.md)

[Содержание](../../README.ru.md)

PHP имеет специальные API для случайных секретов, хешей паролей и проверки данных. Опирайтесь на их документированные гарантии вместо самодельных сочетаний строковых функций. Примеры различают безопасную случайность и воспроизводимые тестовые данные.

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

**PHP 7.0+ | random / standard in older PHP**

random_bytes() даёт криптографически безопасные байты; random_int() выбирает безопасное целое число с включительными границами. Токен и выбор числа являются разными задачами с разным объёмом результата.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
var_dump( $number >= 1 && $number <= 6 ); # bool(true)
```

32 случайных байта становятся 64 шестнадцатеричными символами; кодирование меняет запись, а не энтропию. Для секретов не заменяйте эти API временем, uniqid() или тестовым генератором с зерном. Шестизначный код всё равно требует срока действия и ограничения попыток. [Документация PHP](https://www.php.net/manual/en/function.random-bytes.php).

<a id="password-algorithms"></a>

## Алгоритмы паролей и проверка возможностей

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ password_algos()**

Поддержка Argon2 расширяет существующий API password_hash(). password_algos() показывает алгоритмы, зарегистрированные в текущей сборке, вместо предположения, что номер версии гарантирует их наличие.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;

$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

Сохранённый хеш содержит алгоритм, параметры и соль для проверки. Не шифруйте пароли и не добавляйте собственную соль. После успешного входа проверяйте password_needs_rehash(), если политика изменилась; стоимость вычисления подбирайте измерениями на своём сервере. PASSWORD_DEFAULT является поддерживаемым выбором по умолчанию, а не обещанием выбрать Argon2id. [Документация PHP](https://www.php.net/manual/en/function.password-hash.php).

<a id="sodium"></a>

## Аутентифицированное шифрование через Sodium

**PHP 7.2+ bundled | sodium**

Sodium поставляется с PHP начиная с 7.2. Secretbox шифрует сообщение и проверяет его подлинность: неверный ключ или изменённый шифротекст не могут незаметно стать принятым открытым текстом.

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

Расширение должно быть включено. Ключ храните в секрете, а nonce сохраняйте или передавайте вместе с шифротекстом; nonce не является паролем и не должен повторяться с тем же ключом. Новый случайный ключ здесь показывает операцию, а не постоянное хранение ключей. Для паролей входа не используйте обратимое шифрование. [Документация PHP](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php).

<a id="randomizer"></a>

## Единый API Randomizer

**PHP 8.2+ | random**

Randomizer объединяет выбор чисел, байты, перемешивание и выбор ключей массива в одном объекте. Без явно переданного движка используется безопасный движок.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red"
```

shuffleArray() возвращает новый список, не меняет исходный массив и не сохраняет ключи. pickArrayKeys() возвращает ключи, а не значения. getInt() включает обе границы. Выбор небезопасного движка меняет свойства безопасности всех операций с ним. [Документация PHP](https://www.php.net/manual/en/class.random-randomizer.php).

<a id="seeded-random"></a>

## Воспроизводимая случайность для тестов

**PHP 8.2+ | random**

Движок с явно заданным зерном создаёт воспроизводимые тестовые данные. Два движка с одинаковым зерном и одинаковыми вызовами дают одинаковую последовательность.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

Mt19937, PcgOneseq128XslRr64 и Xoshiro256StarStar подходят для симуляций и тестов, а не секретов. Зерно не является криптографическим ключом. Воспроизведение зависит от движка и порядка вызовов, а не только от зерна. [Документация PHP](https://www.php.net/manual/en/class.random-engine-mt19937.php).

<a id="random-alphabet"></a>

## Случайные байты из заданного алфавита

**PHP 8.3+ | random**

getBytesFromString() выбирает байты из заданного алфавита. Это удобно для коротких идентификаторов или читаемых кодов с явно выбранными символами.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

Операция работает с байтами, а не символами Unicode: используйте ASCII-алфавит. Повторение байтов в алфавите увеличивает их вероятность. Длина и размер алфавита определяют пространство поиска, поэтому удобный короткий код не становится автоматически сильным долговременным секретом. [Документация PHP](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php).

<a id="random-floats"></a>

## Случайные дробные числа с явными границами

**PHP 8.3+ | random**

nextFloat() даёт значение в [0.0, 1.0). getFloat() выбирает число из нужного интервала; IntervalBoundary управляет включением каждой границы.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat();
$weight = $randomizer->getFloat( 1.0, 2.0 );
var_dump( $unit >= 0.0 && $unit < 1.0 ); # bool(true)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

Граница по умолчанию ClosedOpen. Методы позволяют не придумывать преобразование случайных целых через деление. Результат всё равно является двоичным дробным числом, а не точным десятичным денежным значением. [Документация PHP](https://www.php.net/manual/en/random-randomizer.getfloat.php).

<a id="float-validation"></a>

## Проверка дробного числа и диапазона

**PHP 7.4+ range options | filter**

FILTER_VALIDATE_FLOAT принимает min_range и max_range. Формат и диапазон можно проверить вместе, не превращая некорректный текст в число заранее.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Проверяйте сбой через === false, чтобы сохранить корректный ноль. Десятичный формат и правила локали должны соответствовать параметрам фильтра. Проверенный float всё равно имеет обычные ограничения точности. [Документация PHP](https://www.php.net/manual/en/filter.constants.validation.php).

<a id="filter-exceptions"></a>

## Исключение при неудачной проверке

**PHP 8.5+ | filter**

FILTER_THROW_ON_FAILURE превращает неудачную проверку в Filter\FilterFailedException. Это особенно удобно, когда false само является корректным результатом фильтрации.

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

Флаг нельзя сочетать с FILTER_NULL_ON_FAILURE. Детали исключения могут содержать отклонённое значение; не показывайте их пользователям и не записывайте секреты бездумно. Проверка не заменяет экранирование для конкретного контекста вывода. [Документация PHP](https://wiki.php.net/rfc/filter_throw_on_failure).

<a id="cipher-key-length"></a>

## Получение длины ключа от OpenSSL

**PHP 8.2+ | openssl**

openssl_cipher_key_length() возвращает нужную длину ключа поддерживаемого шифра. Это заменяет собственную таблицу названий алгоритмов и количества байтов.

<!-- example: fragment -->
```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32) for aes-256-gcm
```

Возвращаются байты, а не биты. Неизвестный шифр вызывает предупреждение и возвращает false. Одна длина ключа не проектирует шифрование: nonce/IV, теги подлинности и хранение ключа остаются отдельными требованиями. [Документация PHP](https://www.php.net/manual/en/function.openssl-cipher-key-length.php).
