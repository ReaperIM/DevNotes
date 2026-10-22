# Базы данных

[English](../en/10-databases.md) | [Українська](../uk/10-databases.md) | [Русский](../ru/10-databases.md)

[Содержание](../../README.ru.md)

**В этой главе**

- [Исключения PDO стали поведением по умолчанию](#pdo-errors)
- [Строгий режим ошибок mysqli стал стандартным](#mysqli-errors)
- [Выполнение с массивом параметров](#mysqli-execute)
- [Прямое чтение одного столбца](#fetch-column)
- [Классы PDO для отдельных драйверов](#pdo-subclasses)
- [Копирование SQLite через API резервирования](#sqlite-backup)
- [Что означает плейсхолдер](#placeholder-boundaries)

<a id="pdo-errors"></a>

## Исключения PDO стали поведением по умолчанию

**PHP 8.0+ changed default | PDO** [Документация PHP](https://www.php.net/manual/en/pdo.error-handling.php)

С PHP 8.0 PDO по умолчанию использует `ERRMODE_EXCEPTION` вместо скрытых ошибок. Явная настройка задаёт такое же поведение ошибок и для PHP 7.

<!-- example: fragment -->
```php
# $db is PDO; users(id, name) exists.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

Старые ветки, проверяющие только `false`, теперь могут быть пропущены из-за `PDOException`.

<a id="mysqli-errors"></a>

## Строгий режим ошибок mysqli стал стандартным

**PHP 8.1+ changed default | mysqli** [Документация PHP](https://www.php.net/manual/en/mysqli-driver.report-mode.php)

С PHP 8.1 mysqli по умолчанию использует `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`.

<!-- example: fragment -->
```php
# Connection settings come from application configuration; users(id, name) exists.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$db = new mysqli( $host, $username, $password, $database );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

Сбои могут вызвать `mysqli_sql_exception` там, где старый код ожидал предупреждение или `false`.

<a id="mysqli-execute"></a>

## Выполнение с массивом параметров

**PHP 8.1+ execute(array) / PHP 8.2+ execute_query() | mysqli** [Документация PHP](https://www.php.net/manual/en/mysqli.execute-query.php)

`mysqli_stmt::execute()` принимает массив параметров с 8.1. С 8.2 `execute_query()` объединяет prepare, bind и execute для разового запроса.

<!-- example: fragment -->
```php
# $db is mysqli; users(id, name) exists.
$result = $db->execute_query( 'SELECT id, name FROM users WHERE id = ?', [7] );
$user = $result->fetch_assoc();

# Keep the statement when executing the same query repeatedly.
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

`execute_query()` каждый раз создаёт новый подготовленный запрос и возвращает `mysqli_result` для набора строк или boolean для других запросов.

<a id="fetch-column"></a>

## Прямое чтение одного столбца

**PHP 8.1+ | mysqli** [Документация PHP](https://www.php.net/manual/en/mysqli-result.fetch-column.php)

`mysqli_result::fetch_column()` читает один выбранный столбец следующей строки; нумерация столбцов начинается с нуля.

<!-- example: fragment -->
```php
# $db is mysqli; execute_query() requires PHP 8.2+.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
```

Каждый вызов переходит к следующей строке; `false` означает конец строк, а SQL NULL становится `null`.

<a id="pdo-subclasses"></a>

## Классы PDO для отдельных драйверов

**PHP 8.4+ | PDO and matching driver** [Документация PHP](https://www.php.net/manual/en/pdo.connect.php)

`PDO::connect()` возвращает подкласс вроде `Pdo\Mysql`, `Pdo\Pgsql` или `Pdo\Sqlite`, предоставляя методы драйвера вместе с общим API PDO.

<!-- example: fragment -->
```php
# DSN and credentials come from application configuration.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

PHP 8.5 объявляет константы драйверов вроде `PDO::MYSQL_*` устаревшими в пользу констант подклассов; общие остаются в PDO.

<a id="sqlite-backup"></a>

## Копирование SQLite через API резервирования

**PHP 7.4+ | sqlite3** [Документация PHP](https://www.php.net/manual/en/sqlite3.backup.php)

`SQLite3::backup()` копирует открытую базу в другую базу SQLite через API резервного копирования, без ручного копирования файла активной базы.

```php
$source = new SQLite3( ':memory:' );
$source->exec( 'CREATE TABLE settings (value TEXT)' );
$source->exec( "INSERT INTO settings VALUES ('ready')" );
$destination = new SQLite3( ':memory:' );

if( !$source->backup($destination) )
	throw new RuntimeException( 'Backup failed' );

echo $destination->querySingle( 'SELECT value FROM settings' ); # ready
```

База назначения перезаписывается.

<a id="placeholder-boundaries"></a>

## Что означает плейсхолдер

**PHP 7.0–8.5 | existing PDO/mysqli rule | Контекст** [Документация PHP](https://www.php.net/manual/en/pdo.prepare.php)

Плейсхолдер подготовленного запроса представляет одно полное значение, а не идентификатор, ключевое слово или список.

<!-- example: fragment -->
```php
# $db is PDO; the list is non-empty.
$ids = [7, 12, 19];
$placeholders = implode( ', ', array_fill(0, count($ids), '?') );
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')' );
$statement->execute( $ids );
$users = $statement->fetchAll( PDO::FETCH_ASSOC );
```

Обрабатывайте пустые списки до создания SQL; динамическим идентификаторам нужен список разрешённых значений вместо плейсхолдеров.
