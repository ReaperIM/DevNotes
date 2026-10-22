# Файли, nVault і SQL

[English](../en/09-files-vault-and-sql.md) | [Українська](../uk/09-files-vault-and-sql.md) | [Русский](../ru/09-files-vault-and-sql.md)

[Зміст](../../README.uk.md)

Конфігураційні файли, структуровані дані та постійні сховища. Приклади запису файлів і відкриття БД показують API; їх не потрібно автоматично переносити в запуск робочого плагіна.

- [Шляхи від каталогів AMXX](#paths)
- [Читання текстового файла через один handle](#read-lines)
- [Додавання тексту без перезапису файла](#append)
- [Розбір JSON і перевірка структури](#json)
- [Відкриття nVault і зберігання handle](#vault-open)
- [Відсутній ключ і збережений нуль](#vault-missing)
- [Час записів і явне очищення](#vault-expiry)
- [Потоковий SQL і callback](#threaded-sql)
- [SQL_PrepareQuery не є прив’язкою параметрів](#sql-quoting)
- [Результат запиту може прийти після виходу гравця](#sql-player-context)

<a id="paths"></a>

## Шляхи від каталогів AMXX

Для конфігурації є `get_configsdir()`, для збережених даних `get_datadir()`. Не потрібно прив’язуватися до шляху конкретного сервера.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	new directory[128], path[192];
	get_datadir( directory, charsmax(directory) );
	formatex( path, charsmax(path), "%s/notes.txt", directory );
	server_print( "Data path: %s", path );
}
```

Базовий каталог не робить довільне ім’я файла безпечним. Контролюйте імена; не додавайте `../` чи абсолютні шляхи з команд.

[get_datadir](https://www.amxmodx.org/api/amxmisc/get_datadir)

<a id="read-lines"></a>

## Читання текстового файла через один handle

Відкрийте файл один раз, читайте послідовно та закрийте. `fgets()` має обмежений буфер, який повинен вміщувати рядки вашого формату.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_cfg()
{
	new directory[128], path[192], line[256];
	get_configsdir( directory, charsmax(directory) );
	formatex( path, charsmax(path), "%s/notes.ini", directory );
	new file = fopen( path, "rt" );
	if( file == 0 )
		return;

	while( fgets(file, line, charsmax(line)) > 0 )
	{
		trim( line );
		if( line[0] == 0 || line[0] == ';' )
			continue;

		server_print( "Entry: %s", line );
	}
	fclose( file );
}
```

Рядок, довший за буфер, може бути обрізаний із втратою хвоста. Обмежуйте довжину рядків у форматі або окремо обробляйте довгі записи. Обрізання пробілів і пропуск `;` тут є правилами прикладу, а не повноцінним INI-парсером.

[fgets](https://www.amxmodx.org/api/file/fgets)

<a id="append"></a>

## Додавання тексту без перезапису файла

Режим `at` додає текст, `wt` очищує наявний файл. `fputs()` записує текст буквально, тому `%` у даних не стає специфікатором формату.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	new directory[128], path[192];
	get_datadir( directory, charsmax(directory) );
	formatex( path, charsmax(path), "%s/notes.txt", directory );
	new file = fopen( path, "at" );
	if( file == 0 )
	{
		log_amx( "Cannot open %s", path );
		return;
	}

	if( fputs(file, "Progress: 100%^n") == -1 )
		log_amx( "Cannot write %s", path );

	fclose( file );
}
```

Відкриття файлів чи скидання буфера щокадру навантажує серверний потік. Зберігайте за змінами або пакетами; `fflush()` не є транзакцією.

[fputs](https://www.amxmodx.org/api/file/fputs)

<a id="json"></a>

## Розбір JSON і перевірка структури

Модуль JSON відокремлює розбір від читання полів. Синтаксично правильний документ усе ще може мати неправильну структуру чи тип поля.

```pawn
#include <amxmodx>
#include <json>

public plugin_init()
{
	new JSON:document = json_parse( "{^"limit^":10}" );
	if( document == Invalid_JSON )
		return;

	if( json_is_object(document) && json_object_has_value(document, "limit", JSONNumber) )
		server_print( "Limit: %d", json_object_get_number(document, "limit") );

	json_free( document );
}
```

Звільняйте власні JSON-значення. Handles вкладених значень і буфери серіалізації мають свої правила часу життя та розміру; батьківський об’єкт не замінює очищення всіх окремо створених handles.

[json_parse](https://www.amxmodx.org/api/json/json_parse) | [json_free](https://www.amxmodx.org/api/json/json_free)

<a id="vault-open"></a>

## Відкриття nVault і зберігання handle

nVault є локальним сховищем ключ/значення. Відкрийте іменоване сховище один раз, перевірте `INVALID_HANDLE` та закрийте наприкінці роботи плагіна.

```pawn
#include <amxmodx>
#include <nvault>
new g_vault = INVALID_HANDLE;

public plugin_init()
{
	g_vault = nvault_open( "devnotes" );
	if( g_vault == INVALID_HANDLE )
		set_fail_state( "Cannot open devnotes vault" );
}

public plugin_end()
{
	if( g_vault != INVALID_HANDLE )
		nvault_close( g_vault );
}
```

Помилка nVault має значення `-1`, а не звичайний false. Значення зберігаються як рядки; структуру ключів та ідентифікацію облікового запису визначає плагін.

[nvault_open](https://www.amxmodx.org/api/nvault/nvault_open)

<a id="vault-missing"></a>

## Відсутній ключ і збережений нуль

`nvault_lookup()` окремо повертає наявність запису та заповнює значення з часом. Це зберігає зміст `0` і порожніх значень.

```pawn
#include <amxmodx>
#include <nvault>

public plugin_init()
{
	new vault = nvault_open( "devnotes" );
	if( vault == INVALID_HANDLE )
		return;

	nvault_set( vault, "example:points", "0" );
	new value[32], timestamp;
	if( nvault_lookup(vault, "example:points", value, charsmax(value), timestamp) == 1 )
		server_print( "Found: %s, timestamp: %d", value, timestamp );

	nvault_close( vault );
}
```

Числовий `nvault_get()` з двома аргументами сам по собі не розрізняє відсутній ключ і збережений числовий нуль.

[nvault_lookup](https://www.amxmodx.org/api/nvault/nvault_lookup)

<a id="vault-expiry"></a>

## Час записів і явне очищення

`nvault_set()` оновлює час запису. `nvault_prune()` видаляє записи за часовим інтервалом; власна політика TTL не застосовується автоматично.

```pawn
#include <amxmodx>
#include <nvault>

public plugin_init()
{
	new vault = nvault_open( "devnotes_cache" );
	if( vault == INVALID_HANDLE )
		return;

	new cutoff = get_systime() - 30 * 24 * 60 * 60;
	new removed = nvault_prune( vault, 0, cutoff );
	server_print( "Removed old cache entries: %d", removed );
	nvault_close( vault );
}
```

Очищуйте лише дані, які справді можна втратити. Постійні записи `nvault_pset()` не видаляються через prune.

[nvault_prune](https://www.amxmodx.org/api/nvault/nvault_prune)

<a id="threaded-sql"></a>

## Потоковий SQL і callback

`SQL_MakeDbTuple()` зберігає налаштування, але не відкриває з’єднання. `SQL_ThreadQuery()` виконує запит асинхронно й викликає public-обробник із помилкою або результатом.

```pawn
#include <amxmodx>
#include <sqlx>
new Handle:g_tuple = Empty_Handle;

public plugin_init()
{
	if( !SQL_SetAffinity("sqlite") )
	{
		set_fail_state( "SQLite driver unavailable" );
		return;
	}

	g_tuple = SQL_MakeDbTuple( "", "", "", "devnotes" );
	SQL_ThreadQuery( g_tuple, "query_ready", "SELECT 42" );
}

public query_ready( failstate, Handle:query, error[], errnum, data[], size, Float:queuetime )
{
	if( failstate != TQUERY_SUCCESS )
	{
		log_amx( "SQL failed (%d): %s", errnum, error );
		return;
	}

	if( SQL_NumResults(query) > 0 )
		server_print( "SQL result: %d", SQL_ReadResult(query, 0) ); // 42
}

public plugin_end()
{
	if( g_tuple != Empty_Handle )
		SQL_FreeHandle( g_tuple );
}
```

Приклад обирає драйвер SQLite. Для MySQL потрібні справжні налаштування з конфігурації сервера. Не звільняйте handle запиту в callback: ним володіє SQLX. Сам обробник також не повинен перевантажувати ігровий потік.

[SQL_ThreadQuery](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [SQL_MakeDbTuple](https://www.amxmodx.org/api/sqlx/SQL_MakeDbTuple)

<a id="sql-quoting"></a>

## SQL_PrepareQuery не є прив’язкою параметрів

Аргументи формату SQLX є форматуванням рядка, а не плейсхолдерами PDO. Екрануйте зовнішні рядкові значення для активного з’єднання перед вставкою в SQL.

```pawn
#include <amxmodx>
#include <sqlx>

public plugin_init()
{
	if( !SQL_SetAffinity("sqlite") )
		return;

	new Handle:tuple = SQL_MakeDbTuple( "", "", "", "devnotes" );
	new error[128], errnum;
	new Handle:db = SQL_Connect( tuple, errnum, error, charsmax(error) );
	if( db == Empty_Handle )
	{
		log_amx( "Connect failed (%d): %s", errnum, error );
		SQL_FreeHandle( tuple );
		return;
	}

	new name[] = "O'Brien", escaped[2 * sizeof name];
	if( SQL_QuoteString(db, escaped, charsmax(escaped), name) != -1 )
	{
		new Handle:query = SQL_PrepareQuery( db, "SELECT '%s'", escaped );
		if( query != Empty_Handle )
		{
			if( SQL_Execute(query) )
			{
				new result[32];
				SQL_ReadResult( query, 0, result, charsmax(result) );
				server_print( "%s", result ); // O'Brien
			}
			SQL_FreeHandle( query );
		}
	}
	SQL_FreeHandle( db );
	SQL_FreeHandle( tuple );
}
```

Синхронний приклад показує екранування зі справжнім з’єднанням; не відкривайте блокувальне з’єднання для кожної ігрової події. Екранування значень не перевіряє назви таблиць чи вирази сортування. `SQL_SetCharset()` для MySQL tuple викликають до з’єднань/запитів.

[SQL_PrepareQuery](https://www.amxmodx.org/api/sqlx/SQL_PrepareQuery) | [SQL_QuoteString](https://www.amxmodx.org/api/sqlx/SQL_QuoteString)

<a id="sql-player-context"></a>

## Результат запиту може прийти після виходу гравця

Передавайте в даних запиту userid, а не лише слот. У callback знайдіть підключення заново й відкиньте результат для гравця, який уже вийшов.

```pawn
#include <amxmodx>
#include <sqlx>
new Handle:g_tuple = Empty_Handle;

public plugin_init()
{
	if( SQL_SetAffinity("sqlite") )
		g_tuple = SQL_MakeDbTuple( "", "", "", "devnotes" );
}

public client_putinserver( id )
{
	if( g_tuple == Empty_Handle )
		return;

	new context[1];
	context[0] = get_user_userid( id );
	SQL_ThreadQuery( g_tuple, "query_player", "SELECT 10", context, sizeof context );
}

public query_player( failstate, Handle:query, error[], errnum, data[], size, Float:queuetime )
{
	if( failstate != TQUERY_SUCCESS )
	{
		log_amx( "Player query failed (%d): %s", errnum, error );
		return;
	}

	new id = find_player( "k", data[0] );
	if( id == 0 || SQL_NumResults(query) == 0 )
		return;

	client_print( id, print_chat, "Loaded value: %d", SQL_ReadResult(query, 0) );
}

public plugin_end()
{
	if( g_tuple != Empty_Handle )
		SQL_FreeHandle( g_tuple );
}
```

Userid не дає застосувати старий результат до нового гравця в слоті. Запити одного підключення все ще можуть завершуватися не по порядку: якщо потрібен найновіший, додавайте номер запиту. Для постійних записів потрібен авторизований ключ облікового запису, а не userid.

[SQL_ThreadQuery](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [find_player](https://www.amxmodx.org/api/amxmodx/find_player)
