# Файлы, nVault и SQL

[English](../en/09-files-vault-and-sql.md) | [Українська](../uk/09-files-vault-and-sql.md) | [Русский](../ru/09-files-vault-and-sql.md)

[Содержание](../../README.ru.md)

Конфигурационные файлы, структурированные данные и постоянные хранилища. Примеры записи файлов и открытия БД показывают API; их не нужно автоматически переносить в запуск рабочего плагина.

- [Пути от каталогов AMXX](#paths)
- [Чтение текстового файла через один handle](#read-lines)
- [Добавление текста без перезаписи файла](#append)
- [Разбор JSON и проверка структуры](#json)
- [Открытие nVault и хранение handle](#vault-open)
- [Отсутствующий ключ и сохранённый ноль](#vault-missing)
- [Время записей и явная очистка](#vault-expiry)
- [Потоковый SQL и callback](#threaded-sql)
- [SQL_PrepareQuery не является привязкой параметров](#sql-quoting)
- [Результат запроса может прийти после выхода игрока](#sql-player-context)

<a id="paths"></a>

## Пути от каталогов AMXX

Для конфигурации есть `get_configsdir()`, для сохранённых данных `get_datadir()`. Не нужно привязываться к пути конкретного сервера.

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

Базовый каталог не делает произвольное имя файла безопасным. Контролируйте имена; не добавляйте `../` или абсолютные пути из команд.

[get_datadir](https://www.amxmodx.org/api/amxmisc/get_datadir)

<a id="read-lines"></a>

## Чтение текстового файла через один handle

Откройте файл один раз, читайте последовательно и закройте. `fgets()` ограничен буфером, который должен вмещать строки вашего формата.

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

Строка длиннее буфера может быть обрезана с потерей хвоста. Ограничивайте длину строк в формате или отдельно обрабатывайте длинные записи. Удаление пробелов и пропуск `;` здесь являются правилами примера, а не полноценным INI-парсером.

[fgets](https://www.amxmodx.org/api/file/fgets)

<a id="append"></a>

## Добавление текста без перезаписи файла

Режим `at` добавляет текст, `wt` очищает существующий файл. `fputs()` записывает текст буквально, поэтому `%` в данных не становится спецификатором формата.

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

Открытие файлов или сброс буфера каждый кадр нагружает серверный поток. Сохраняйте по изменениям или пакетами; `fflush()` не является транзакцией.

[fputs](https://www.amxmodx.org/api/file/fputs)

<a id="json"></a>

## Разбор JSON и проверка структуры

Модуль JSON отделяет разбор от чтения полей. Синтаксически правильный документ всё ещё может иметь неправильную структуру или тип поля.

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

Освобождайте собственные JSON-значения. Handles вложенных значений и буферы сериализации имеют свои правила времени жизни и размера; родительский объект не заменяет очистку всех отдельно созданных handles.

[json_parse](https://www.amxmodx.org/api/json/json_parse) | [json_free](https://www.amxmodx.org/api/json/json_free)

<a id="vault-open"></a>

## Открытие nVault и хранение handle

nVault является локальным хранилищем ключ/значение. Откройте именованное хранилище один раз, проверьте `INVALID_HANDLE` и закройте при завершении плагина.

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

Ошибка nVault имеет значение `-1`, а не обычный false. Значения хранятся как строки; структуру ключей и идентификацию аккаунта определяет плагин.

[nvault_open](https://www.amxmodx.org/api/nvault/nvault_open)

<a id="vault-missing"></a>

## Отсутствующий ключ и сохранённый ноль

`nvault_lookup()` отдельно возвращает наличие записи и заполняет значение со временем. Это сохраняет смысл `0` и пустых значений.

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

Числовой `nvault_get()` с двумя аргументами сам по себе не различает отсутствующий ключ и сохранённый числовой ноль.

[nvault_lookup](https://www.amxmodx.org/api/nvault/nvault_lookup)

<a id="vault-expiry"></a>

## Время записей и явная очистка

`nvault_set()` обновляет время записи. `nvault_prune()` удаляет записи по временному интервалу; собственная политика TTL не применяется автоматически.

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

Очищайте только данные, которые действительно можно потерять. Постоянные записи `nvault_pset()` не удаляются через prune.

[nvault_prune](https://www.amxmodx.org/api/nvault/nvault_prune)

<a id="threaded-sql"></a>

## Потоковый SQL и callback

`SQL_MakeDbTuple()` сохраняет настройки, но не открывает соединение. `SQL_ThreadQuery()` выполняет запрос асинхронно и вызывает public-обработчик с ошибкой или результатом.

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

Пример выбирает драйвер SQLite. Для MySQL нужны настоящие настройки из конфигурации сервера. Не освобождайте handle запроса в callback: им владеет SQLX. Сам обработчик также не должен перегружать игровой поток.

[SQL_ThreadQuery](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [SQL_MakeDbTuple](https://www.amxmodx.org/api/sqlx/SQL_MakeDbTuple)

<a id="sql-quoting"></a>

## SQL_PrepareQuery не является привязкой параметров

Аргументы формата SQLX являются форматированием строки, а не плейсхолдерами PDO. Экранируйте внешние строковые значения для активного соединения перед вставкой в SQL.

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

Синхронный пример показывает экранирование с настоящим соединением; не открывайте блокирующее соединение для каждого игрового события. Экранирование значений не проверяет имена таблиц или выражения сортировки. `SQL_SetCharset()` для MySQL tuple вызывают до соединений/запросов.

[SQL_PrepareQuery](https://www.amxmodx.org/api/sqlx/SQL_PrepareQuery) | [SQL_QuoteString](https://www.amxmodx.org/api/sqlx/SQL_QuoteString)

<a id="sql-player-context"></a>

## Результат запроса может прийти после выхода игрока

Передавайте в данных запроса userid, а не только слот. В callback найдите подключение заново и отбросьте результат для игрока, который уже вышел.

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

Userid не даёт применить старый результат к новому игроку в слоте. Запросы одного подключения всё ещё могут завершаться не по порядку: если нужен последний, добавляйте номер запроса. Для постоянных записей нужен авторизованный ключ аккаунта, а не userid.

[SQL_ThreadQuery](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [find_player](https://www.amxmodx.org/api/amxmodx/find_player)
