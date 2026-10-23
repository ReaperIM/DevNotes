# Бази даних

[EN](../en/10-databases.md) | [UK](../uk/10-databases.md) | [RU](../ru/10-databases.md)

[Зміст](../../README.uk.md)

У нових версій PHP змінилися не лише короткі способи виконати запит, а й поведінка в разі помилок. Дивимося, де тепер виникає виняток, як передавати параметри масивом і які частини SQL не можна замінити плейсхолдером.

**У цьому розділі**

- [Помилки `PDO` тепер викликають винятки](#pdo-errors)
- [Як змінилася обробка помилок `mysqli` за замовчуванням](#mysqli-errors)
- [Виконання з масивом параметрів](#mysqli-execute)
- [Пряме читання одного стовпця](#fetch-column)
- [Методи конкретного драйвера через підкласи `PDO`](#pdo-subclasses)
- [Копіюємо відкриту базу SQLite](#sqlite-backup)
- [Що означає плейсхолдер](#placeholder-boundaries)

<a id="pdo-errors"></a>

## Помилки `PDO` тепер викликають винятки

**PHP 8.0+ | змінилася типова поведінка | PDO** [Документація PHP](https://www.php.net/manual/en/pdo.error-handling.php)

З PHP 8.0 помилка запиту `PDO` за замовчуванням викликає виняток: увімкнено `ERRMODE_EXCEPTION`. Раніше за замовчуванням використовувався тихий режим, і помилку потрібно було перевіряти вручну. Явне налаштування в прикладі дає однакову поведінку і на PHP 7, і на PHP 8.

<!-- example: fragment -->
```php
# $db уже містить PDO-підключення, таблиця users(id, name) існує.
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
$name = $statement->fetchColumn();
```

Під час перенесення старого коду перевіряємо гілки на кшталт «якщо повернувся `false`». До них виконання тепер може не дійти, бо раніше буде викинуто `PDOException`.

<a id="mysqli-errors"></a>

## Як змінилася обробка помилок `mysqli` за замовчуванням

**PHP 8.1+ | змінилася типова поведінка | mysqli** [Документація PHP](https://www.php.net/manual/en/mysqli-driver.report-mode.php)

У `mysqli` поведінка за замовчуванням теж змінилася: з PHP 8.1 увімкнені `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`. Помилки тепер можуть викидати `mysqli_sql_exception`. У прикладі задаємо цей режим явно, щоб він був видимий поряд зі створенням підключення.

<!-- example: fragment -->
```php
# Параметри беремо з конфігурації застосунку; users(id, name) уже існує.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$db = new mysqli( $host, $username, $password, $database );
$statement = $db->prepare( 'SELECT name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

Якщо раніше код очікував попередження або перевіряв результат на `false`, обробку потрібно переглянути. Виняток може перервати виконання ще до такої перевірки.

<a id="mysqli-execute"></a>

## Виконання з масивом параметрів

**PHP 8.1+ `execute(array)` / PHP 8.2+ `execute_query()` | mysqli** [Документація PHP](https://www.php.net/manual/en/mysqli.execute-query.php)

З PHP 8.1 значення можна передати масивом прямо в `mysqli_stmt::execute()`. А з PHP 8.2 для разового запиту є `execute_query()`: він сам готує запит, зв'язує параметри й виконує його. Перший варіант у прикладі отримує користувача однією такою операцією.

<!-- example: fragment -->
```php
# $db уже містить mysqli-підключення, таблиця users(id, name) існує.
$result = $db->execute_query( 'SELECT id, name FROM users WHERE id = ?', [7] );
$user = $result->fetch_assoc();

# Для повторних виконань того самого SQL зберігаємо підготовлений запит.
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id = ?' );
$statement->execute( [7] );
```

`execute_query()` щоразу створює новий підготовлений запит. Якщо той самий SQL виконуємо багато разів із різними значеннями, зберігаємо `$statement`, як у другій частині прикладу. Для запиту з рядками результатом буде `mysqli_result`, для інших запитів `bool`.

<a id="fetch-column"></a>

## Пряме читання одного стовпця

**PHP 8.1+ | mysqli** [Документація PHP](https://www.php.net/manual/en/mysqli-result.fetch-column.php)

Якщо з рядка результату потрібен один стовпець, можна взяти його через `fetch_column()`, не отримуючи спочатку весь масив. За замовчуванням читається перший стовпець, нумерація починається з нуля. Тут запит повертає лише `COUNT(*)`, тому він відразу потрапляє в `$count`.

<!-- example: fragment -->
```php
# $db уже містить mysqli-підключення; execute_query() потрібен PHP 8.2+.
$result = $db->execute_query( 'SELECT COUNT(*) FROM users' );
$count = $result->fetch_column();
```

Кожен виклик переходить до наступного рядка результату. Коли рядки закінчилися, отримаємо `false`, а значення SQL `NULL` повернеться як `null`. Це різні випадки, і перевіряти їх потрібно відповідно.

<a id="pdo-subclasses"></a>

## Методи конкретного драйвера через підкласи `PDO`

**PHP 8.4+ | PDO і потрібний драйвер** [Документація PHP](https://www.php.net/manual/en/pdo.connect.php)

Через `PDO::connect()` можна отримати не просто загальний `PDO`, а клас конкретного драйвера: наприклад, `Pdo\Mysql`, `Pdo\Pgsql` або `Pdo\Sqlite`. Звичайні методи `PDO` залишаються, а поряд доступні додаткові методи драйвера. Тут читаємо кількість попереджень, лише якщо підключилися через MySQL.

<!-- example: fragment -->
```php
# DSN і дані підключення беремо з конфігурації застосунку.
$db = PDO::connect( $dsn, $username, $password, [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] );

if( $db instanceof Pdo\Mysql )
	$warnings = $db->getWarningCount();
```

З PHP 8.5 константи конкретних драйверів на кшталт `PDO::MYSQL_*` оголошені застарілими, їх замінюють константи відповідних підкласів. Спільні константи, наприклад `PDO::ATTR_ERRMODE`, залишаються в `PDO`.

<a id="sqlite-backup"></a>

## Копіюємо відкриту базу SQLite

**PHP 7.4+ | sqlite3** [Документація PHP](https://www.php.net/manual/en/sqlite3.backup.php)

Для копії відкритої бази SQLite є `SQLite3::backup()`. Він переносить вміст через штатний API резервного копіювання, тому не потрібно вручну копіювати файл бази, що працює. У прикладі обидві бази розташовані в пам'яті: записуємо дані в першу, копіюємо й читаємо їх уже з другої.

```php
$source = new SQLite3( ':memory:' );
$source->exec( 'CREATE TABLE settings (value TEXT)' );
$source->exec( "INSERT INTO settings VALUES ('ready')" );
$destination = new SQLite3( ':memory:' );

if( !$source->backup($destination) )
	throw new RuntimeException( 'Backup failed' );

echo $destination->querySingle( 'SELECT value FROM settings' ); # ready
```

Базу призначення буде перезаписано. Перед викликом перевіряємо, що вибрали саме ту базу, яку збиралися замінити.

<a id="placeholder-boundaries"></a>

## Що означає плейсхолдер

**PHP 7.0-8.5 | правило PDO/mysqli з попередніх версій | Для розуміння** [Документація PHP](https://www.php.net/manual/en/pdo.prepare.php)

Один `?` у підготовленому запиті замінює одне значення цілком. Через нього не можна підставити ім'я таблиці, SQL-слово чи відразу весь список для `IN`. Тому тут створюємо по `?` на кожен ідентифікатор, а самі значення передаємо окремо в `execute()`.

<!-- example: fragment -->
```php
# $db уже містить PDO-підключення; порожній список обробляємо окремо.
$ids = [7, 12, 19];
$placeholders = implode( ', ', array_fill(0, count($ids), '?') );
$statement = $db->prepare( 'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')' );
$statement->execute( $ids );
$users = $statement->fetchAll( PDO::FETCH_ASSOC );
```

Порожній список обробляємо до складання SQL. Якщо потрібно обирати ім'я таблиці чи стовпця, використовуємо заздалегідь дозволені імена: плейсхолдер для таких частин запиту не підходить.
