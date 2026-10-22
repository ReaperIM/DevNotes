# Бази даних

[EN](../en/10-databases.md) | [UK](../uk/10-databases.md) | [RU](../ru/10-databases.md)

[Зміст](../../README.uk.md)

**У цьому розділі**

- [Винятки `PDO` стали типовою поведінкою](#pdo-errors)
- [Суворий режим помилок `mysqli` став типовим](#mysqli-errors)
- [Виконання з масивом параметрів](#mysqli-execute)
- [Пряме читання одного стовпця](#fetch-column)
- [Класи `PDO` для окремих драйверів](#pdo-subclasses)
- [Копіювання SQLite через API резервування](#sqlite-backup)
- [Що означає плейсхолдер](#placeholder-boundaries)

<a id="pdo-errors"></a>

## Винятки `PDO` стали типовою поведінкою

**PHP 8.0+ changed default | PDO** [Документація PHP](https://www.php.net/manual/en/pdo.error-handling.php)

Від PHP 8.0 `PDO` за замовчуванням використовує `ERRMODE_EXCEPTION` замість прихованих помилок. Явне налаштування задає таку саму поведінку помилок і для PHP 7.

<!-- example: fragment -->
```php
# $db is PDO; users(id, name) exists.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

Старі гілки, що перевіряють лише `false`, тепер можуть бути пропущені через `PDOException`.

<a id="mysqli-errors"></a>

## Суворий режим помилок `mysqli` став типовим

**PHP 8.1+ changed default | mysqli** [Документація PHP](https://www.php.net/manual/en/mysqli-driver.report-mode.php)

Від PHP 8.1 `mysqli` за замовчуванням використовує `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`.

<!-- example: fragment -->
```php
# Connection settings come from application configuration; users(id, name) exists.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$db = new mysqli( $host, $username, $password, $database );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

Збої можуть викликати `mysqli_sql_exception` там, де старий код очікував попередження або `false`.

<a id="mysqli-execute"></a>

## Виконання з масивом параметрів

**PHP 8.1+ `execute(array)` / PHP 8.2+ `execute_query()` | mysqli** [Документація PHP](https://www.php.net/manual/en/mysqli.execute-query.php)

`mysqli_stmt::execute()` приймає масив параметрів від 8.1. Від 8.2 `execute_query()` об'єднує prepare, bind і execute для разового запиту.

<!-- example: fragment -->
```php
# $db is mysqli; users(id, name) exists.
$result = $db->execute_query( 'SELECT id, name FROM users WHERE id = ?', [7] );
$user = $result->fetch_assoc();

# Keep the statement when executing the same query repeatedly.
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

`execute_query()` щоразу створює новий підготовлений запит і повертає `mysqli_result` для набору рядків або `boolean` для інших запитів.

<a id="fetch-column"></a>

## Пряме читання одного стовпця

**PHP 8.1+ | mysqli** [Документація PHP](https://www.php.net/manual/en/mysqli-result.fetch-column.php)

`mysqli_result::fetch_column()` читає один вибраний стовпець наступного рядка; нумерація стовпців починається з нуля.

<!-- example: fragment -->
```php
# $db is mysqli; execute_query() requires PHP 8.2+.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
```

Кожен виклик переходить до наступного рядка; `false` означає кінець рядків, а SQL `NULL` стає `null`.

<a id="pdo-subclasses"></a>

## Класи `PDO` для окремих драйверів

**PHP 8.4+ | PDO and matching driver** [Документація PHP](https://www.php.net/manual/en/pdo.connect.php)

`PDO::connect()` повертає підклас на зразок `Pdo\Mysql`, `Pdo\Pgsql` або `Pdo\Sqlite`, надаючи методи драйвера поряд зі спільним API `PDO`.

<!-- example: fragment -->
```php
# DSN and credentials come from application configuration.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

PHP 8.5 позначає константи драйверів на зразок `PDO::MYSQL_*` застарілими на користь констант підкласів; спільні залишаються в `PDO`.

<a id="sqlite-backup"></a>

## Копіювання SQLite через API резервування

**PHP 7.4+ | sqlite3** [Документація PHP](https://www.php.net/manual/en/sqlite3.backup.php)

`SQLite3::backup()` копіює відкриту базу до іншої бази SQLite через API резервного копіювання, без ручного копіювання файла активної бази.

```php
$source = new SQLite3( ':memory:' );
$source->exec( 'CREATE TABLE settings (value TEXT)' );
$source->exec( "INSERT INTO settings VALUES ('ready')" );
$destination = new SQLite3( ':memory:' );

if( !$source->backup($destination) )
	throw new RuntimeException( 'Backup failed' );

echo $destination->querySingle( 'SELECT value FROM settings' ); # ready
```

База призначення перезаписується.

<a id="placeholder-boundaries"></a>

## Що означає плейсхолдер

**PHP 7.0–8.5 | existing PDO/mysqli rule | Контекст** [Документація PHP](https://www.php.net/manual/en/pdo.prepare.php)

Плейсхолдер підготовленого запиту представляє одне повне значення, а не ідентифікатор, ключове слово чи список.

<!-- example: fragment -->
```php
# $db is PDO; the list is non-empty.
$ids = [7, 12, 19];
$placeholders = implode( ', ', array_fill(0, count($ids), '?') );
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')' );
$statement->execute( $ids );
$users = $statement->fetchAll( PDO::FETCH_ASSOC );
```

Обробляйте порожні списки до створення SQL; динамічні ідентифікатори потребують списку дозволених значень замість плейсхолдерів.
