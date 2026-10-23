# Базы данных

[EN](../en/10-databases.md) | [UK](../uk/10-databases.md) | [RU](../ru/10-databases.md)

[Содержание](../../README.ru.md)

У новых версий PHP изменились не только короткие способы выполнить запрос, но и поведение при ошибках. Смотрим, где теперь прилетает исключение, как передавать параметры массивом и какие части SQL нельзя заменить плейсхолдером.

**В этой главе**

- [Ошибки `PDO` теперь вызывают исключения](#pdo-errors)
- [Как изменились ошибки `mysqli` по умолчанию](#mysqli-errors)
- [Выполнение с массивом параметров](#mysqli-execute)
- [Прямое чтение одного столбца](#fetch-column)
- [Методы конкретного драйвера через подклассы `PDO`](#pdo-subclasses)
- [Копируем открытую базу SQLite](#sqlite-backup)
- [Что означает плейсхолдер](#placeholder-boundaries)

<a id="pdo-errors"></a>

## Ошибки `PDO` теперь вызывают исключения

**PHP 8.0+ | изменилось поведение по умолчанию | PDO** [Документация PHP](https://www.php.net/manual/en/pdo.error-handling.php)

С PHP 8.0 ошибка запроса `PDO` по умолчанию вызывает исключение: включён `ERRMODE_EXCEPTION`. Раньше по умолчанию использовался тихий режим, и ошибку нужно было проверять вручную. Явная настройка в примере даёт одинаковое поведение и на PHP 7, и на PHP 8.

<!-- example: fragment -->
```php
# $db уже содержит PDO-подключение, таблица users(id, name) существует.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

При переносе старого кода проверяем ветки вида «если вернулся `false`». До них выполнение теперь может не дойти, потому что раньше будет выброшен `PDOException`.

<a id="mysqli-errors"></a>

## Как изменились ошибки `mysqli` по умолчанию

**PHP 8.1+ | изменилось поведение по умолчанию | mysqli** [Документация PHP](https://www.php.net/manual/en/mysqli-driver.report-mode.php)

У `mysqli` поведение по умолчанию тоже изменилось: с PHP 8.1 включены `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`. Ошибки теперь могут выбрасывать `mysqli_sql_exception`. В примере задаём этот режим явно, чтобы он был виден рядом с созданием подключения.

<!-- example: fragment -->
```php
# Параметры берём из конфигурации приложения; users(id, name) уже существует.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$db = new mysqli( $host, $username, $password, $database );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

Если раньше код ожидал предупреждение или проверял результат на `false`, обработку нужно пересмотреть. До проверки результата дело уже может не дойти: выполнение перейдёт к обработчику исключения.

<a id="mysqli-execute"></a>

## Выполнение с массивом параметров

**PHP 8.1+ `execute(array)` / PHP 8.2+ `execute_query()` | mysqli** [Документация PHP](https://www.php.net/manual/en/mysqli.execute-query.php)

С PHP 8.1 значения можно передать массивом прямо в `mysqli_stmt::execute()`. А с PHP 8.2 для разового запроса есть `execute_query()`: он сам подготавливает запрос, связывает параметры и выполняет его. Первый вариант в примере получает пользователя одной такой операцией.

<!-- example: fragment -->
```php
# $db уже содержит mysqli-подключение, таблица users(id, name) существует.
$result = $db->execute_query( 'SELECT id, name FROM users WHERE id = ?', [7] );
$user = $result->fetch_assoc();

# Для повторных выполнений того же SQL сохраняем подготовленный запрос.
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

`execute_query()` каждый раз создаёт новый подготовленный запрос. Если один и тот же SQL выполняем много раз с разными значениями, сохраняем `$statement`, как во второй части примера. Для запроса со строками результатом будет `mysqli_result`, для других запросов `bool`.

<a id="fetch-column"></a>

## Прямое чтение одного столбца

**PHP 8.1+ | mysqli** [Документация PHP](https://www.php.net/manual/en/mysqli-result.fetch-column.php)

Если из строки результата нужен один столбец, можно взять его через `fetch_column()`, не получая сначала весь массив. По умолчанию читается первый столбец, нумерация начинается с нуля. Здесь запрос возвращает только `COUNT(*)`, поэтому он сразу попадает в `$count`.

<!-- example: fragment -->
```php
# $db уже содержит mysqli-подключение; execute_query() нужен PHP 8.2+.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
```

Каждый вызов переходит к следующей строке результата. Когда строки закончились, получим `false`, а значение SQL `NULL` вернётся как `null`. Это разные случаи, и проверять их нужно соответственно.

<a id="pdo-subclasses"></a>

## Методы конкретного драйвера через подклассы `PDO`

**PHP 8.4+ | PDO и нужный драйвер** [Документация PHP](https://www.php.net/manual/en/pdo.connect.php)

Через `PDO::connect()` можно получить не просто общий `PDO`, а класс конкретного драйвера: например, `Pdo\Mysql`, `Pdo\Pgsql` или `Pdo\Sqlite`. Обычные методы `PDO` остаются, а рядом доступны дополнительные методы драйвера. Здесь читаем число предупреждений, только если подключились через MySQL.

<!-- example: fragment -->
```php
# DSN и данные подключения берём из конфигурации приложения.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

С PHP 8.5 константы конкретных драйверов вроде `PDO::MYSQL_*` объявлены устаревшими, их заменяют константы соответствующих подклассов. Общие константы, например `PDO::ATTR_ERRMODE`, остаются у `PDO`.

<a id="sqlite-backup"></a>

## Копируем открытую базу SQLite

**PHP 7.4+ | sqlite3** [Документация PHP](https://www.php.net/manual/en/sqlite3.backup.php)

Для копии открытой базы SQLite есть `SQLite3::backup()`. Он переносит содержимое через штатный API резервного копирования, поэтому не нужно вручную копировать файл работающей базы. В примере обе базы находятся в памяти: записываем данные в первую, копируем и читаем их уже из второй.

```php
$source = new SQLite3( ':memory:' );
$source->exec( 'CREATE TABLE settings (value TEXT)' );
$source->exec( "INSERT INTO settings VALUES ('ready')" );
$destination = new SQLite3( ':memory:' );

if( !$source->backup($destination) )
	throw new RuntimeException( 'Backup failed' );

echo $destination->querySingle( 'SELECT value FROM settings' ); # ready
```

База назначения будет перезаписана. Перед вызовом проверяем, что выбрали именно ту базу, которую собирались заменить.

<a id="placeholder-boundaries"></a>

## Что означает плейсхолдер

**PHP 7.0-8.5 | правило PDO/mysqli из предыдущих версий | Для понимания** [Документация PHP](https://www.php.net/manual/en/pdo.prepare.php)

Один `?` в подготовленном запросе заменяет одно значение целиком. Через него нельзя подставить имя таблицы, SQL-слово или сразу весь список для `IN`. Поэтому здесь создаём по `?` на каждый идентификатор, а сами значения передаём отдельно в `execute()`.

<!-- example: fragment -->
```php
# $db уже содержит PDO-подключение; пустой список обрабатываем отдельно.
$ids = [7, 12, 19];
$placeholders = implode( ', ', array_fill(0, count($ids), '?') );
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')' );
$statement->execute( $ids );
$users = $statement->fetchAll( PDO::FETCH_ASSOC );
```

Пустой список обрабатываем до сборки SQL. Если нужно выбирать имя таблицы или столбца, используем заранее разрешённые имена: плейсхолдер для таких частей запроса не подходит.
