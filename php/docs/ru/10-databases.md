# Базы данных

[English](../en/10-databases.md) | [Українська](../uk/10-databases.md) | [Русский](../ru/10-databases.md)

[Содержание](../../README.ru.md)

PHP улучшает существующие API баз данных: короткие подготовленные запросы, понятные ошибки и классы драйверов. Примеры описывают нужное соединение или схему. Учётные данные берите из настроек приложения.

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

**PHP 8.0+ changed default | PDO**

Режим по умолчанию изменился с молчаливых ошибок на PDO::ERRMODE_EXCEPTION. Код, проверяющий только false, после обновления может обрабатывать сбой другим путём.

<!-- example: fragment -->
```php
# $db is PDO; users(id, name) exists.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

Явная настройка делает политику понятной и в PHP 7. Перехватывайте PDOException там, где можно осмысленно обработать ошибку. false у fetchColumn() может означать отсутствие следующей строки, а не сбой. Публичный ответ не должен раскрывать SQL или параметры соединения. [Документация PHP](https://www.php.net/manual/en/pdo.error-handling.php).

<a id="mysqli-errors"></a>

## Строгий режим ошибок mysqli стал стандартным

**PHP 8.1+ changed default | mysqli**

Режим по умолчанию теперь MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT. Сбои базы могут вызывать mysqli_sql_exception там, где старый код ожидал предупреждения или false.

<!-- example: fragment -->
```php
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
# Set before creating the application's mysqli connection.
```

Задавайте политику ошибок один раз при настройке соединения и пересматривайте старые ветки обработки при обновлении. Этот параметр не проверяет SQL-данные и не выбирает кодировку соединения. [Документация PHP](https://www.php.net/manual/en/mysqli-driver.report-mode.php).

<a id="mysqli-execute"></a>

## Выполнение с массивом параметров

**PHP 8.1+ execute(array) / PHP 8.2+ execute_query() | mysqli**

Начиная с PHP 8.1, mysqli_stmt::execute() принимает массив параметров. PHP 8.2 добавляет mysqli::execute_query(), чтобы подготовить простой запрос, привязать значения и выполнить его одним вызовом.

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

Сокращение возвращает mysqli_result для запроса с набором результатов и логическое значение для остальных команд. Оно готовит запрос заново каждый раз; в цикле полезно сохранять подготовленный объект. Значения не нужно вручную заключать в кавычки. Для явных типов привязки остаётся bind_param(). [Документация PHP](https://www.php.net/manual/en/mysqli.execute-query.php).

<a id="fetch-column"></a>

## Прямое чтение одного столбца

**PHP 8.1+ | mysqli**

mysqli_result::fetch_column() читает выбранный столбец из следующей строки. Это удобно для количества, настройки или чтения одного поля.

<!-- example: fragment -->
```php
# PHP 8.2+ for execute_query(); $db is mysqli.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
# fetch_column(1) would read the second selected column.
```

Нумерация начинается с нуля. Каждый вызов переходит к следующей строке. false означает конец строк, SQL NULL становится null. Драйвер и способ запроса влияют на PHP-типы; нормализуйте их на границе, когда важна строгая типизация. [Документация PHP](https://www.php.net/manual/en/mysqli-result.fetch-column.php).

<a id="pdo-subclasses"></a>

## Классы PDO для отдельных драйверов

**PHP 8.4+ | PDO and matching driver**

PDO::connect() может вернуть нужный подкласс: Pdo\Mysql, Pdo\Pgsql или Pdo\Sqlite. Методы и константы драйвера имеют собственное место, а общие операции запросов остаются знакомыми.

<!-- example: fragment -->
```php
# Values come from trusted application configuration.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

Соответствующий драйвер всё равно нужен. new PDO(...) остаётся допустимым. PHP 8.5 объявляет старые константы драйверов вида PDO::MYSQL_* устаревшими в пользу констант подкласса. Общие константы, например PDO::ATTR_ERRMODE, остаются в PDO. [Документация PHP](https://www.php.net/manual/en/pdo.connect.php).

<a id="sqlite-backup"></a>

## Копирование SQLite через API резервирования

**PHP 7.4+ | sqlite3**

SQLite3::backup() копирует открытую базу в другую базу SQLite. Это удобно для резервирования без собственной процедуры простого копирования файла активной базы.

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

Содержимое назначения перезаписывается: выбирайте его осознанно. Здесь используются только базы в памяти. Настоящая копия обычно сохраняется в отдельном файле и требует проверки в рамках процедуры восстановления. [Документация PHP](https://www.php.net/manual/en/sqlite3.backup.php).

<a id="placeholder-boundaries"></a>

## Что означает плейсхолдер

**PHP 7.0–8.5 | existing PDO/mysqli rule | Контекст**

Подготовленные запросы появились до PHP 7, но новые сокращения делают их границы важными. Один плейсхолдер означает одно целое значение, а не имя столбца, ключевое слово или весь список.

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

В SQL добавляется только созданная программой пунктуация; значения передаются отдельно. Пустой список обрабатывайте до создания SQL. Динамические идентификаторы требуют таблицы разрешённых имён, а не плейсхолдера значения. Параметризация не определяет, какие строки может читать пользователь. [Документация PHP](https://www.php.net/manual/en/pdo.prepare.php).
