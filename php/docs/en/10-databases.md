# Databases

[EN](../en/10-databases.md) | [UK](../uk/10-databases.md) | [RU](../ru/10-databases.md)

[Contents](../../README.md)

Newer PHP versions change both how concisely we can run queries and what happens when they fail. Here we look at exceptions, parameter arrays and the parts of SQL that value placeholders cannot replace.

**In this chapter**

- [`PDO` errors now throw exceptions](#pdo-errors)
- [How default `mysqli` error handling changed](#mysqli-errors)
- [Execute with an array of parameters](#mysqli-execute)
- [Read one column directly](#fetch-column)
- [Driver methods through `PDO` subclasses](#pdo-subclasses)
- [Copy an open SQLite database](#sqlite-backup)
- [What a placeholder represents](#placeholder-boundaries)

<a id="pdo-errors"></a>

## `PDO` errors now throw exceptions

**PHP 8.0+ changed default | PDO** [PHP manual](https://www.php.net/manual/en/pdo.error-handling.php)

Since PHP 8.0, a failed `PDO` query throws an exception by default: `ERRMODE_EXCEPTION` is enabled. The older default was silent mode, where the caller had to check for failure. Setting the mode explicitly gives this example the same error handling on PHP 7 and PHP 8.

<!-- example: fragment -->
```php
# $db is an open PDO connection; users(id, name) already exists.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

When updating old code, check branches that only test for a `false` return value. Execution may now throw `PDOException` before reaching that check.

<a id="mysqli-errors"></a>

## How default `mysqli` error handling changed

**PHP 8.1+ changed default | mysqli** [PHP manual](https://www.php.net/manual/en/mysqli-driver.report-mode.php)

The default changed for `mysqli` too: PHP 8.1 enables `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`. Failures can now throw `mysqli_sql_exception`. The example sets this explicitly so the selected mode is visible beside the connection setup.

<!-- example: fragment -->
```php
# Use connection settings from the app configuration; users(id, name) already exists.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$db = new mysqli( $host, $username, $password, $database );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

Review old handling that expects a warning or only checks for `false`. An exception can interrupt execution before that check runs.

<a id="mysqli-execute"></a>

## Execute with an array of parameters

**PHP 8.1+ `execute(array)` / PHP 8.2+ `execute_query()` | mysqli** [PHP manual](https://www.php.net/manual/en/mysqli.execute-query.php)

Since PHP 8.1, `mysqli_stmt::execute()` accepts parameter values as an array. For a one-off query, PHP 8.2 adds `execute_query()`, which prepares the query, binds the values and executes it. The first part of the example fetches a user with that single operation.

<!-- example: fragment -->
```php
# $db is an open mysqli connection; users(id, name) already exists.
$result = $db->execute_query( 'SELECT id, name FROM users WHERE id = ?', [7] );
$user = $result->fetch_assoc();

# Keep the prepared statement when running the same SQL again.
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

`execute_query()` creates a new prepared statement every time. When running the same SQL repeatedly with different values, keep `$statement`, as in the second part. Queries returning rows give a `mysqli_result`; other queries return `bool`.

<a id="fetch-column"></a>

## Read one column directly

**PHP 8.1+ | mysqli** [PHP manual](https://www.php.net/manual/en/mysqli-result.fetch-column.php)

Use `fetch_column()` when you only need one column from a result row, without fetching the whole row into an array first. It reads the first column by default, and column indexes start at zero. This query only selects `COUNT(*)`, so that value goes straight into `$count`.

<!-- example: fragment -->
```php
# $db is an open mysqli connection; execute_query() needs PHP 8.2+.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
```

Each call advances to the next result row. `false` means there are no rows left, while an SQL `NULL` comes back as `null`. Treat these as different cases.

<a id="pdo-subclasses"></a>

## Driver methods through `PDO` subclasses

**PHP 8.4+ | PDO and matching driver** [PHP manual](https://www.php.net/manual/en/pdo.connect.php)

`PDO::connect()` can return a driver-specific class such as `Pdo\Mysql`, `Pdo\Pgsql` or `Pdo\Sqlite`. The ordinary `PDO` methods remain available, alongside extra driver methods. Here we read the warning count only when the connection uses MySQL.

<!-- example: fragment -->
```php
# Read the DSN and credentials from the app configuration.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

PHP 8.5 deprecates driver-specific constants such as `PDO::MYSQL_*` in favor of constants on the matching subclasses. Shared constants such as `PDO::ATTR_ERRMODE` remain on `PDO`.

<a id="sqlite-backup"></a>

## Copy an open SQLite database

**PHP 7.4+ | sqlite3** [PHP manual](https://www.php.net/manual/en/sqlite3.backup.php)

Use `SQLite3::backup()` to copy an open SQLite database through its backup API, without manually copying an active database file. Both databases in this example are in memory: we write to the first, copy it, then read the same data from the second.

```php
$source = new SQLite3( ':memory:' );
$source->exec( 'CREATE TABLE settings (value TEXT)' );
$source->exec( "INSERT INTO settings VALUES ('ready')" );
$destination = new SQLite3( ':memory:' );

if( !$source->backup($destination) )
	throw new RuntimeException( 'Backup failed' );

echo $destination->querySingle( 'SELECT value FROM settings' ); # ready
```

The destination database is overwritten. Make sure it is the database you intended to replace before calling the method.

<a id="placeholder-boundaries"></a>

## What a placeholder represents

**PHP 7.0-8.5 | existing PDO/mysqli rule | Background** [PHP manual](https://www.php.net/manual/en/pdo.prepare.php)

One `?` in a prepared query stands for one complete value. It cannot substitute a table name, an SQL keyword or an entire `IN` list. Here we create one `?` for each ID and pass the values separately to `execute()`.

<!-- example: fragment -->
```php
# $db is an open PDO connection; handle an empty list separately.
$ids = [7, 12, 19];
$placeholders = implode( ', ', array_fill(0, count($ids), '?') );
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')' );
$statement->execute( $ids );
$users = $statement->fetchAll( PDO::FETCH_ASSOC );
```

Handle an empty list before building the SQL. If the table or column name must vary, choose it from explicitly allowed names; value placeholders do not work for those parts of a query.
