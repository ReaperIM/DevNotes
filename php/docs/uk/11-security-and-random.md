# Безпека й випадкові значення

[EN](../en/11-security-and-random.md) | [UK](../uk/11-security-and-random.md) | [RU](../ru/11-security-and-random.md)

[Зміст](../../README.uk.md)

Секретний токен і повторювані дані для тесту потребують різних генераторів. Розбираємо обидва випадки, а поряд перевіряємо паролі, шифруємо повідомлення й перевіряємо вхідні значення без втрати правильних нуля та `false`.

**У цьому розділі**

- [Безпечні випадкові байти й числа](#secure-random)
- [Алгоритми паролів і перевірка можливостей](#password-algorithms)
- [Шифруємо повідомлення й перевіряємо його цілісність](#sodium)
- [Єдиний API `Randomizer`](#randomizer)
- [Повторюємо випадкову послідовність для тесту](#seeded-random)
- [Випадкові байти із заданого алфавіту](#random-alphabet)
- [Випадкові дробові числа з явними межами](#random-floats)
- [Перевірка дробового числа й діапазону](#float-validation)
- [Виняток при невдалій перевірці](#filter-exceptions)
- [Отримання довжини ключа від OpenSSL](#cipher-key-length)

<a id="secure-random"></a>

## Безпечні випадкові байти й числа

**PHP 7.0+ | random / standard у старих версіях PHP** [Документація PHP](https://www.php.net/manual/en/function.random-bytes.php)

Для токена чи іншого секрету беремо `random_bytes()`. Отримані байти можна перевести у зручний для передачі шістнадцятковий рядок через `bin2hex()`: із 32 байтів вийде 64 символи. Якщо потрібне випадкове ціле число, є `random_int()`, обидві межі діапазону включені.

```php
$token = bin2hex( random_bytes(32) );
$number = random_int( 1, 6 );
var_dump( strlen($token) ); # int(64)
```

Ці функції підходять для криптографічних задач. Генератор із заданим seed, час або `uniqid()` не замінюють їх під час створення секретів: передбачуваний результат тут не підходить.

<a id="password-algorithms"></a>

## Алгоритми паролів і перевірка можливостей

**PHP 7.2+ Argon2i / 7.3+ Argon2id / 7.4+ `password_algos()`** [Документація PHP](https://www.php.net/manual/en/function.password-hash.php)

У `password_hash()` з'явилися варіанти Argon2, але доступність алгоритму залежить від збірки PHP. `password_algos()` дозволяє перевірити її прямо в поточному середовищі. Тут обираємо Argon2id, якщо він є, інакше залишаємо `PASSWORD_DEFAULT`, а потім перевіряємо пароль за готовим хешем.

```php
$password = 'example passphrase';
$algorithm = (in_array('argon2id', password_algos(), true))
	? PASSWORD_ARGON2ID
	: PASSWORD_DEFAULT;
$hash = password_hash( $password, $algorithm );
var_dump( password_verify($password, $hash) ); # bool(true)
var_dump( password_needs_rehash($hash, $algorithm) ); # bool(false)
```

`PASSWORD_DEFAULT` сам собою не обіцяє Argon2id. Якщо змінили алгоритм або його налаштування, після успішної перевірки пароля можна викликати `password_needs_rehash()` і зрозуміти, чи час створити хеш заново.

<a id="sodium"></a>

## Шифруємо повідомлення й перевіряємо його цілісність

**PHP 7.2+ у комплекті | sodium** [Документація PHP](https://www.php.net/manual/en/function.sodium-crypto-secretbox.php)

Secretbox із Sodium не лише шифрує повідомлення, а й перевіряє, що його не змінили. Для розшифрування потрібні той самий ключ і той самий `nonce`, окреме значення для цього шифрування. У прикладі після шифрування отримуємо вихідний рядок назад, а неправильний ключ або змінені дані дали б `false`.

```php
$key = sodium_crypto_secretbox_keygen();
$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
$ciphertext = sodium_crypto_secretbox( 'private note', $nonce, $key );
$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

if( $plaintext === false )
	throw new RuntimeException( 'Authentication failed' );

echo $plaintext; # private note
```

Ключ зберігаємо в секреті. `nonce` можна зберігати поряд із зашифрованими даними, але повторювати його з тим самим ключем не можна. Перевірку `=== false` після розшифрування не пропускаємо: використовувати результат до неї не можна.

<a id="randomizer"></a>

## Єдиний API `Randomizer`

**PHP 8.2+ | random** [Документація PHP](https://www.php.net/manual/en/class.random-randomizer.php)

`Randomizer` збирає випадкові операції в одному об'єкті. Без явного вибору генератора він використовує безпечний варіант. У прикладі перемішуємо копію масиву, обираємо два ключі й отримуємо ціле від 10 до 20. Вихідний масив залишається на місці.

```php
$randomizer = new Random\Randomizer();
$items = ['red', 'green', 'blue'];
$shuffled = $randomizer->shuffleArray( $items );
$keys = $randomizer->pickArrayKeys( $items, 2 );
$number = $randomizer->getInt( 10, 20 );
var_dump( count($shuffled), count($keys) ); # int(3), int(2)
var_dump( $items[0] ); # string(3) "red": вихідний масив не змінювали
```

`shuffleArray()` повертає новий список із заново пронумерованими елементами. `pickArrayKeys()` повертає саме ключі, а не значення. Якщо підставити свій некриптографічний генератор, вважати ці операції безпечними для секретів уже не можна.

<a id="seeded-random"></a>

## Повторюємо випадкову послідовність для тесту

**PHP 8.2+ | random** [Документація PHP](https://www.php.net/manual/en/class.random-engine-mt19937.php)

Для тесту буває корисно отримати ту саму «випадкову» послідовність ще раз. Задаємо однаковий seed, тобто початкове значення генератора, і повторюємо ті самі виклики. У прикладі обидва об'єкти використовують `Mt19937` з одним seed, тому два масиви збігаються.

```php
$first = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$second = new Random\Randomizer( new Random\Engine\Mt19937(12345) );
$a = [$first->getInt(1, 100), $first->getInt(1, 100)];
$b = [$second->getInt(1, 100), $second->getInt(1, 100)];
var_dump( $a === $b ); # bool(true)
```

Мають збігтися й сам генератор, і seed, і порядок викликів. `Mt19937`, `PcgOneseq128XslRr64` і `Xoshiro256StarStar` підходять для тестів і симуляцій. Для секретів вони не підходять саме через передбачуваність.

<a id="random-alphabet"></a>

## Випадкові байти із заданого алфавіту

**PHP 8.3+ | random** [Документація PHP](https://www.php.net/manual/en/random-randomizer.getbytesfromstring.php)

Якщо код має складатися лише з вибраних літер і цифр, є `getBytesFromString()`. Передаємо рядок-алфавіт і потрібну довжину. Тут з алфавіту заздалегідь прибрані деякі схожі символи, а обидві перевірки показують, що всі 12 байтів результату взяті з дозволеного набору.

```php
$randomizer = new Random\Randomizer();
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = $randomizer->getBytesFromString( $alphabet, 12 );
var_dump( strlen($code) ); # int(12)
var_dump( strspn($code, $alphabet) === 12 ); # bool(true)
```

Функція обирає байти, а не цілі символи Unicode, тому довільний багатомовний текст як алфавіт не підійде. Якщо один байт повторений в алфавіті кілька разів, випадати він буде частіше.

<a id="random-floats"></a>

## Випадкові дробові числа з явними межами

**PHP 8.3+ | random** [Документація PHP](https://www.php.net/manual/en/random-randomizer.getfloat.php)

`nextFloat()` дає дробове число від `0.0` включно до `1.0`, не включаючи `1.0`. Якщо потрібен свій діапазон, використовуємо `getFloat()`. У прикладі нижня межа `1.0` дозволена, а верхня `2.0` уже не входить у результат.

```php
$randomizer = new Random\Randomizer();
$unit = $randomizer->nextFloat(); # [0.0, 1.0)
$weight = $randomizer->getFloat( 1.0, 2.0 ); # [1.0, 2.0)
var_dump( $weight >= 1.0 && $weight < 2.0 ); # bool(true)
```

Таке правило називається `ClosedOpen` і використовується за замовчуванням. Через `IntervalBoundary` можна явно обрати, чи включати кожну з меж, якщо задачі потрібен інший варіант.

<a id="float-validation"></a>

## Перевірка дробового числа й діапазону

**PHP 7.4+ перевірка діапазону | filter** [Документація PHP](https://www.php.net/manual/en/filter.constants.validation.php)

`FILTER_VALIDATE_FLOAT` може перевірити не лише запис дробового числа, а й його діапазон. Для цього задаємо `min_range` і `max_range`. У прикладі рядок `'0'` правильний і перетворюється на `float(0)`.

```php
$price = filter_var( '0', FILTER_VALIDATE_FLOAT, [
	'options' => ['min_range' => 0, 'max_range' => 1000],
] );

if( $price === false )
	throw new InvalidArgumentException( 'Invalid price' );

var_dump( $price ); # float(0)
```

Не перевіряємо такий результат через звичайне `if( !$price )`: правильний нуль теж туди потрапить. Помилку відрізняємо строго через `=== false`, тоді нульову ціну не загубимо.

<a id="filter-exceptions"></a>

## Виняток при невдалій перевірці

**PHP 8.5+ | filter** [Документація PHP](https://wiki.php.net/rfc/filter_throw_on_failure)

Іноді `false` є правильним результатом перевірки, а не ознакою помилки. Наприклад, рядок `'false'` під час перевірки `bool` має дати `false`. Прапорець `FILTER_THROW_ON_FAILURE` розділяє ці ситуації: правильне значення повертається, а неправильне викликає `Filter\FilterFailedException`.

```php
$enabled = filter_var( 'false', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
var_dump( $enabled ); # bool(false), це правильне значення

try
{
	filter_var( 'perhaps', FILTER_VALIDATE_BOOL, FILTER_THROW_ON_FAILURE );
}
catch( Filter\FilterFailedException $error )
{
	echo 'invalid';
}
```

Тому перший виклик спокійно повертає `false`, а рядок `'perhaps'` потрапляє в `catch`. Із `FILTER_NULL_ON_FAILURE` цей прапорець поєднувати не можна.

<a id="cipher-key-length"></a>

## Отримання довжини ключа від OpenSSL

**PHP 8.2+ | openssl** [Документація PHP](https://www.php.net/manual/en/function.openssl-cipher-key-length.php)

Щоб не прописувати розмір ключа окремо від назви шифру, можна запитати його в OpenSSL через `openssl_cipher_key_length()`. Результат приходить у байтах. Для `aes-256-gcm` у прикладі отримуємо 32 байти й генеруємо ключ потрібної довжини.

```php
$length = openssl_cipher_key_length( 'aes-256-gcm' );

if( $length === false )
	throw new RuntimeException( 'Cipher unavailable' );

$key = random_bytes( $length );
var_dump( strlen($key) ); # int(32)
```

Якщо шифр невідомий, функція видасть попередження й поверне `false`. Тому спочатку перевіряємо довжину, а не передаємо результат відразу в `random_bytes()`.
