# Databases

[English](../en/10-databases.md) | [Українська](../uk/10-databases.md) | [Русский](../ru/10-databases.md)

[Contents](../../README.md)

**In this chapter**

- [PDO exceptions became the default](#pdo-errors)
- [mysqli strict reporting became the default](#mysqli-errors)
- [Execute with an array of parameters](#mysqli-execute)
- [Read one column directly](#fetch-column)
- [Driver-specific PDO classes](#pdo-subclasses)
- [Copy SQLite through its backup API](#sqlite-backup)
- [What a placeholder represents](#placeholder-boundaries)

<a id="pdo-errors"></a>

## PDO exceptions became the default

**PHP 8.0+ changed default | PDO** [PHP manual](https://www.php.net/manual/en/pdo.error-handling.php)

PDO defaults to `ERRMODE_EXCEPTION` rather than silent errors from PHP 8.0. Setting it explicitly also gives PHP 7 the same failure policy.

<!-- example: fragment -->
```php
# $db is PDO; users(id, name) exists.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

Old branches that only check `false` may now be bypassed by `PDOException`.

<a id="mysqli-errors"></a>

## mysqli strict reporting became the default

**PHP 8.1+ changed default | mysqli** [PHP manual](https://www.php.net/manual/en/mysqli-driver.report-mode.php)

mysqli defaults to `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT` from PHP 8.1.

<!-- example: fragment -->
```php
# Connection settings come from application configuration; users(id, name) exists.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$db = new mysqli( $host, $username, $password, $database );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

Failures can throw `mysqli_sql_exception` where older code expected warnings or `false`.

<a id="mysqli-execute"></a>

## Execute with an array of parameters

**PHP 8.1+ execute(array) / PHP 8.2+ execute_query() | mysqli** [PHP manual](https://www.php.net/manual/en/mysqli.execute-query.php)

`mysqli_stmt::execute()` accepts a parameter array from 8.1. From 8.2, `execute_query()` combines prepare, bind and execute for a one-off query.

<!-- example: fragment -->
```php
# $db is mysqli; users(id, name) exists.
$result = $db->execute_query( 'SELECT id, name FROM users WHERE id = ?', [7] );
$user = $result->fetch_assoc();

# Keep the statement when executing the same query repeatedly.
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

`execute_query()` prepares anew each time and returns `mysqli_result` for result sets or a boolean otherwise.

<a id="fetch-column"></a>

## Read one column directly

**PHP 8.1+ | mysqli** [PHP manual](https://www.php.net/manual/en/mysqli-result.fetch-column.php)

`mysqli_result::fetch_column()` reads one selected column from the next row, with column numbering starting at zero.

<!-- example: fragment -->
```php
# $db is mysqli; execute_query() requires PHP 8.2+.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
```

Each call advances the row; `false` means no row remains, while SQL NULL becomes `null`.

<a id="pdo-subclasses"></a>

## Driver-specific PDO classes

**PHP 8.4+ | PDO and matching driver** [PHP manual](https://www.php.net/manual/en/pdo.connect.php)

`PDO::connect()` returns a matching subclass such as `Pdo\Mysql`, `Pdo\Pgsql` or `Pdo\Sqlite`, exposing driver-specific methods alongside the shared PDO API.

<!-- example: fragment -->
```php
# DSN and credentials come from application configuration.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

PHP 8.5 deprecates driver constants such as `PDO::MYSQL_*` in favor of subclass constants; shared constants remain on PDO.

<a id="sqlite-backup"></a>

## Copy SQLite through its backup API

**PHP 7.4+ | sqlite3** [PHP manual](https://www.php.net/manual/en/sqlite3.backup.php)

`SQLite3::backup()` copies an opened database into another SQLite database through its backup API, without manually copying the active database file.

```php
$source = new SQLite3( ':memory:' );
$source->exec( 'CREATE TABLE settings (value TEXT)' );
$source->exec( "INSERT INTO settings VALUES ('ready')" );
$destination = new SQLite3( ':memory:' );

if( !$source->backup($destination) )
	throw new RuntimeException( 'Backup failed' );

echo $destination->querySingle( 'SELECT value FROM settings' ); # ready
```

The destination database is overwritten.

<a id="placeholder-boundaries"></a>

## What a placeholder represents

**PHP 7.0–8.5 | existing PDO/mysqli rule | Background** [PHP manual](https://www.php.net/manual/en/pdo.prepare.php)

A prepared-query placeholder represents one whole value, not an identifier, keyword or list.

<!-- example: fragment -->
```php
# $db is PDO; the list is non-empty.
$ids = [7, 12, 19];
$placeholders = implode( ', ', array_fill(0, count($ids), '?') );
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')' );
$statement->execute( $ids );
$users = $statement->fetchAll( PDO::FETCH_ASSOC );
```

Handle empty lists before generating SQL; dynamic identifiers need an allowlist rather than value placeholders.
