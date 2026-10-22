# Databases

[English](../en/10-databases.md) | [Українська](../uk/10-databases.md) | [Русский](../ru/10-databases.md)

[Contents](../../README.md)

PHP improves existing database APIs with shorter prepared queries, clearer errors and driver classes. These examples state the connection or schema they require. Credentials belong in your application configuration.

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

**PHP 8.0+ changed default | PDO**

The default changed from silent errors to PDO::ERRMODE_EXCEPTION. Code that only checks false may take a different failure path after upgrading.

<!-- example: fragment -->
```php
# $db is PDO; users(id, name) exists.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

Explicit setup also makes the policy clear on PHP 7. Catch PDOException at a boundary that can handle it meaningfully. false from fetchColumn() may mean no next row, not query failure. Public responses must not expose SQL or connection details. [PHP manual](https://www.php.net/manual/en/pdo.error-handling.php).

<a id="mysqli-errors"></a>

## mysqli strict reporting became the default

**PHP 8.1+ changed default | mysqli**

The default is MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT. Database failures can throw mysqli_sql_exception where older code expected warnings or false.

<!-- example: fragment -->
```php
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
# Set before creating the application's mysqli connection.
```

Set error policy once during connection setup and review old error branches when upgrading. This setting does not validate SQL input or choose the connection character set. [PHP manual](https://www.php.net/manual/en/mysqli-driver.report-mode.php).

<a id="mysqli-execute"></a>

## Execute with an array of parameters

**PHP 8.1+ execute(array) / PHP 8.2+ execute_query() | mysqli**

From PHP 8.1, mysqli_stmt::execute() accepts a parameter array. PHP 8.2 adds mysqli::execute_query() to prepare, bind and execute a simple query in one call.

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

The shortcut returns mysqli_result for result-producing statements and a boolean otherwise. It prepares anew each time; retaining a statement remains useful in loops. Bound values are not manually quoted. For explicit binding types, the older bind_param() API remains available. [PHP manual](https://www.php.net/manual/en/mysqli.execute-query.php).

<a id="fetch-column"></a>

## Read one column directly

**PHP 8.1+ | mysqli**

mysqli_result::fetch_column() reads a selected column from the next row. It is handy for a count, setting or iteration over one field.

<!-- example: fragment -->
```php
# PHP 8.2+ for execute_query(); $db is mysqli.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
# fetch_column(1) would read the second selected column.
```

Column numbers start at zero. Every call advances to the next row. false signals no row remains, while SQL NULL becomes null. Driver/query paths can affect scalar PHP types; normalize at a boundary when strict types matter. [PHP manual](https://www.php.net/manual/en/mysqli-result.fetch-column.php).

<a id="pdo-subclasses"></a>

## Driver-specific PDO classes

**PHP 8.4+ | PDO and matching driver**

PDO::connect() can return the matching subclass, such as Pdo\Mysql, Pdo\Pgsql or Pdo\Sqlite. Driver methods and constants have a home while shared statement operations stay familiar.

<!-- example: fragment -->
```php
# Values come from trusted application configuration.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

The matching driver is still required. new PDO(...) remains valid. PHP 8.5 deprecates old driver-specific PDO::MYSQL_*-style constants in favor of subclass equivalents. Shared constants such as PDO::ATTR_ERRMODE remain on PDO. [PHP manual](https://www.php.net/manual/en/pdo.connect.php).

<a id="sqlite-backup"></a>

## Copy SQLite through its backup API

**PHP 7.4+ | sqlite3**

SQLite3::backup() copies an opened database into another SQLite database. This is useful for backups without designing a raw file-copy procedure around an active database.

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

The destination is overwritten: choose it deliberately. This example uses only memory databases. A real backup normally targets a separate file and must be verified as part of a recovery procedure. [PHP manual](https://www.php.net/manual/en/sqlite3.backup.php).

<a id="placeholder-boundaries"></a>

## What a placeholder represents

**PHP 7.0–8.5 | existing PDO/mysqli rule | Background**

Prepared queries predate PHP 7, but newer shortcuts make their boundaries important. One placeholder means one whole value, not a column name, keyword or complete list.

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

Only generated punctuation is concatenated; values are bound separately. Handle an empty list before producing SQL. Dynamic identifiers require an allowlist mapping, not value placeholders. Parameterization does not decide which rows a user may access. [PHP manual](https://www.php.net/manual/en/pdo.prepare.php).
