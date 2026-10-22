# Бази даних

[English](../en/10-databases.md) | [Українська](../uk/10-databases.md) | [Русский](../ru/10-databases.md)

[Зміст](../../README.uk.md)

PHP поліпшує наявні API баз даних: коротші підготовлені запити, зрозумілі помилки й класи драйверів. Приклади описують потрібне з’єднання або схему. Облікові дані беріть із налаштувань застосунку.

**У цьому розділі**

- [Винятки PDO стали типовою поведінкою](#pdo-errors)
- [Суворий режим помилок mysqli став типовим](#mysqli-errors)
- [Виконання з масивом параметрів](#mysqli-execute)
- [Пряме читання одного стовпця](#fetch-column)
- [Класи PDO для окремих драйверів](#pdo-subclasses)
- [Копіювання SQLite через API резервування](#sqlite-backup)
- [Що означає плейсхолдер](#placeholder-boundaries)

<a id="pdo-errors"></a>

## Винятки PDO стали типовою поведінкою

**PHP 8.0+ changed default | PDO**

Типовий режим змінився з мовчазних помилок на PDO::ERRMODE_EXCEPTION. Код, що лише перевіряє false, після оновлення може обробляти збій іншим шляхом.

<!-- example: fragment -->
```php
# $db is PDO; users(id, name) exists.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

Явне налаштування робить політику зрозумілою і в PHP 7. Перехоплюйте PDOException там, де можна змістовно обробити помилку. false від fetchColumn() може означати відсутність наступного рядка, а не збій. Публічна відповідь не повинна розкривати SQL чи параметри з’єднання. [Документація PHP](https://www.php.net/manual/en/pdo.error-handling.php).

<a id="mysqli-errors"></a>

## Суворий режим помилок mysqli став типовим

**PHP 8.1+ changed default | mysqli**

Типовий режим тепер MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT. Збої бази можуть викликати mysqli_sql_exception там, де старий код очікував попередження або false.

<!-- example: fragment -->
```php
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
# Set before creating the application's mysqli connection.
```

Задавайте політику помилок один раз під час налаштування з’єднання та переглядайте старі гілки обробки під час оновлення. Цей параметр не перевіряє SQL-дані й не вибирає кодування з’єднання. [Документація PHP](https://www.php.net/manual/en/mysqli-driver.report-mode.php).

<a id="mysqli-execute"></a>

## Виконання з масивом параметрів

**PHP 8.1+ execute(array) / PHP 8.2+ execute_query() | mysqli**

Починаючи з PHP 8.1, mysqli_stmt::execute() приймає масив параметрів. PHP 8.2 додає mysqli::execute_query(), щоб підготувати простий запит, прив’язати значення й виконати його одним викликом.

<!-- example: fragment -->
```php
# $db is mysqli; users(id, name) exists.
$user_id = 7;
$result = $db->execute_query(
	'SELECT id, name FROM users WHERE id = ?',
	[$user_id],
);
$user = $result->fetch_assoc();

# Keep a statement when executing the same query repeatedly.
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id = ?' );
$statement->execute( [$user_id] );
```

Скорочення повертає mysqli_result для запиту з набором результатів і логічне значення для інших команд. Воно готує запит заново щоразу; у циклі корисно зберігати підготовлений об’єкт. Значення не потрібно вручну брати в лапки. Для явних типів прив’язування залишається bind_param(). [Документація PHP](https://www.php.net/manual/en/mysqli.execute-query.php).

<a id="fetch-column"></a>

## Пряме читання одного стовпця

**PHP 8.1+ | mysqli**

mysqli_result::fetch_column() читає вибраний стовпець із наступного рядка. Це зручно для кількості, налаштування або читання одного поля.

<!-- example: fragment -->
```php
# PHP 8.2+ for execute_query(); $db is mysqli.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
# fetch_column(1) would read the second selected column.
```

Нумерація починається з нуля. Кожен виклик переходить до наступного рядка. false означає кінець рядків, SQL NULL стає null. Драйвер і спосіб запиту впливають на PHP-типи; нормалізуйте їх на межі, коли важлива сувора типізація. [Документація PHP](https://www.php.net/manual/en/mysqli-result.fetch-column.php).

<a id="pdo-subclasses"></a>

## Класи PDO для окремих драйверів

**PHP 8.4+ | PDO and matching driver**

PDO::connect() може повернути відповідний підклас: Pdo\Mysql, Pdo\Pgsql або Pdo\Sqlite. Методи й константи драйвера мають власне місце, а спільні операції запитів залишаються знайомими.

<!-- example: fragment -->
```php
# Values come from trusted application configuration.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

Відповідний драйвер усе одно потрібний. new PDO(...) залишається чинним. PHP 8.5 позначає старі константи драйверів на кшталт PDO::MYSQL_* застарілими на користь констант підкласу. Спільні константи, наприклад PDO::ATTR_ERRMODE, залишаються в PDO. [Документація PHP](https://www.php.net/manual/en/pdo.connect.php).

<a id="sqlite-backup"></a>

## Копіювання SQLite через API резервування

**PHP 7.4+ | sqlite3**

SQLite3::backup() копіює відкриту базу в іншу базу SQLite. Це зручно для резервування без власної процедури простого копіювання файлу активної бази.

<!-- example: fragment -->
```php
$source = new SQLite3( ':memory:' );
$source->exec( 'CREATE TABLE settings (value TEXT)' );
$source->exec( "INSERT INTO settings VALUES ('ready')" );
$destination = new SQLite3( ':memory:' );

if( !$source->backup($destination) )
	throw new RuntimeException( 'Backup failed' );

echo $destination->querySingle( 'SELECT value FROM settings' ); # ready
$destination->close();
$source->close();
```

Вміст призначення перезаписується: вибирайте його свідомо. Тут використані лише бази в пам’яті. Справжня копія зазвичай зберігається в окремому файлі й потребує перевірки в межах процедури відновлення. [Документація PHP](https://www.php.net/manual/en/sqlite3.backup.php).

<a id="placeholder-boundaries"></a>

## Що означає плейсхолдер

**PHP 7.0–8.5 | existing PDO/mysqli rule | Контекст**

Підготовлені запити старші за PHP 7, але нові скорочення роблять їхні межі важливими. Один плейсхолдер означає одне ціле значення, а не ім’я стовпця, ключове слово чи весь список.

<!-- example: fragment -->
```php
# $db is PDO; this example deliberately uses a non-empty list.
$ids = [7, 12, 19];
$placeholders = implode( ', ', array_fill(0, count($ids), '?') );
$statement = $db->prepare(
	'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')'
);
$statement->execute( $ids );
$users = $statement->fetchAll( PDO::FETCH_ASSOC );
```

У SQL додається лише створена програмою пунктуація; значення передаються окремо. Порожній список обробляйте до створення SQL. Динамічні ідентифікатори потребують таблиці дозволених назв, а не плейсхолдера значення. Параметризація не визначає, які рядки може читати користувач. [Документація PHP](https://www.php.net/manual/en/pdo.prepare.php).
