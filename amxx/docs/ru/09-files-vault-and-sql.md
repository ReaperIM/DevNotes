# Файлы, nVault и SQL

[EN](../en/09-files-vault-and-sql.md) | [UK](../uk/09-files-vault-and-sql.md) | [RU](../ru/09-files-vault-and-sql.md)

[Содержание](../../README.ru.md)

Конфиги читаем из файлов, очки можно сохранять в nVault, а общие данные держать в базе. Ниже разобраны чтение, запись и очистка ресурсов. Примеры с файлами и БД показывают отдельный вызов API; момент сохранения для своего плагина выбираем по его задаче.

- [Пути от каталогов AMXX](#paths)
- [Читаем файл последовательно и закрываем его](#read-lines)
- [Добавление текста без перезаписи файла](#append)
- [Разбор JSON и проверка структуры](#json)
- [Открываем nVault один раз на плагин](#vault-open)
- [Отсутствующий ключ и сохранённый ноль](#vault-missing)
- [Удаляем устаревшие записи по времени](#vault-expiry)
- [Отправляем SQL-запрос и получаем ответ позже](#threaded-sql)
- [Безопасно подставляем строковое значение в SQL](#sql-quoting)
- [Игрок вышел, а ответ базы ещё идёт](#sql-player-context)

<a id="paths"></a>

## Пути от каталогов AMXX

Не привязываемся к тому, в какую папку установлен сервер. Каталог конфигов получаем через `get_configsdir()`, а каталог данных через `get_datadir()`. Ниже добавляем к каталогу данных имя `notes.txt` и выводим готовый путь.

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

Если имя файла приходит из команды, его всё равно проверяем. Правильная базовая папка не помешает строке с `../` или абсолютным путём обратиться в другое место. Проще держать допустимые имена под контролем плагина.

[`get_datadir`](https://www.amxmodx.org/api/amxmisc/get_datadir)

<a id="read-lines"></a>

## Читаем файл последовательно и закрываем его

Чтобы пройти весь текстовый файл, один раз открываем его, читаем строки через `fgets()`, затем закрываем. В примере пропускаем пустые строки и строки с `;` в начале. Буфер `line` подбираем под допустимую длину записи.

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

Слишком длинная строка может обрезаться, а её конец потеряться. Поэтому ограничиваем длину в своём формате или отдельно обрабатываем длинные записи. Один `trim()` и пропуск `;` ещё не дают полноценный разбор INI.

[`fgets`](https://www.amxmodx.org/api/file/fgets)

<a id="append"></a>

## Добавление текста без перезаписи файла

Чтобы добавить текст в конец файла, открываем его с `at`. Режим `wt` очистил бы прежнее содержимое. Сам текст пишем через `fputs()`: знак `%` в `Progress: 100%` тогда остаётся обычным символом.

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

Открытие файла и принудительный сброс данных каждый кадр задерживают серверный поток. Лучше сохранять при значимых изменениях или собирать записи в пакет. `fflush()` сбрасывает буфер, но целостную транзакцию из нескольких действий не создаёт.

[`fputs`](https://www.amxmodx.org/api/file/fputs)

<a id="json"></a>

## Разбор JSON и проверка структуры

Успешный `json_parse()` говорит, что JSON удалось разобрать. Но внутри ещё может оказаться строка вместо объекта или текст вместо числа. Поэтому ниже отдельно проверяем объект и числовое поле `limit`, затем читаем значение.

```pawn
#include <amxmodx>
#include <json>

public plugin_init()
{
	new JSON:document = json_parse( "{^"limit^":10}" );
	if( document == Invalid_JSON )
		return;

	// Успешный разбор ещё не гарантирует нужный тип поля.
	if( json_is_object(document) && json_object_has_value(document, "limit", JSONNumber) )
		server_print( "Limit: %d", json_object_get_number(document, "limit") );

	json_free( document );
}
```

Созданный документ освобождаем через `json_free()`. Если отдельно получаем или создаём вложенные значения, смотрим правила их handles: закрытие родителя не заменяет освобождение всех отдельно созданных ресурсов. Для преобразования обратно в текст ещё учитываем размер выходного буфера.

[`json_parse`](https://www.amxmodx.org/api/json/json_parse) | [`json_free`](https://www.amxmodx.org/api/json/json_free)

<a id="vault-open"></a>

## Открываем nVault один раз на плагин

nVault хранит локальные записи по ключу. Открываем хранилище с выбранным именем, сохраняем handle и пользуемся им дальше. В `plugin_end()` закрываем. Если открытие не удалось, получим `INVALID_HANDLE`, поэтому эту проверку делаем сразу.

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

У ошибки здесь значение `-1`; обычная проверка на ложь её не поймает. Значения nVault сохраняет строками. Как составить ключ и привязать его к нужному аккаунту, определяем в своём плагине.

[`nvault_open`](https://www.amxmodx.org/api/nvault/nvault_open)

<a id="vault-missing"></a>

## Отсутствующий ключ и сохранённый ноль

Если у игрока `0` очков, это ещё не значит, что записи нет. `nvault_lookup()` отдельно сообщает, найден ли ключ, затем заполняет строку значения и время записи. Ниже сохранённый `"0"` успешно находится. Пустая строка тоже может быть сохранённым значением.

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

Числовой `nvault_get()` с двумя аргументами сам по себе не отличит отсутствующий ключ от записанного нуля. Если это различие важно для начальных данных, используем отдельную проверку наличия.

[`nvault_lookup`](https://www.amxmodx.org/api/nvault/nvault_lookup)

<a id="vault-expiry"></a>

## Удаляем устаревшие записи по времени

При `nvault_set()` время записи обновляется. Для удаления старых записей вызываем `nvault_prune()` с нужным интервалом. Ниже удаляем записи кеша, попавшие в период до границы 30 дней назад. Собственный срок хранения сам по себе nVault не применит: очистку запускаем явно.

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

Так удаляем только те данные, которые разрешено потерять, например кеш. Постоянные записи через `nvault_pset()` эта очистка не затронет.

[`nvault_prune`](https://www.amxmodx.org/api/nvault/nvault_prune)

<a id="threaded-sql"></a>

## Отправляем SQL-запрос и получаем ответ позже

Чтобы сервер не ждал ответа базы в этом месте, используем `SQL_ThreadQuery()`. Когда запрос завершится, AMXX вызовет `query_ready()`: проверим ошибку и прочитаем результат. `SQL_MakeDbTuple()` заранее хранит настройки подключения; соединение при его вызове ещё не открывается.

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
	// Проверяем успешность запроса до чтения результата.
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

В примере выбран SQLite. Для MySQL подставляем настоящие настройки из конфигурации сервера. Handle `query` в обработчике освобождает сам SQLX, вручную закрывать его не нужно. Сам обработчик работает в игровом потоке, поэтому тяжёлую работу туда тоже не переносим.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`SQL_MakeDbTuple`](https://www.amxmodx.org/api/sqlx/SQL_MakeDbTuple)

<a id="sql-quoting"></a>

## Безопасно подставляем строковое значение в SQL

В `SQL_PrepareQuery()` запись `%s` только форматирует текст запроса. Отдельной привязки параметров, как в PDO, здесь нет. Поэтому перед подстановкой чужой строки вызываем `SQL_QuoteString()` для активного соединения. Имя `O'Brien` ниже проходит через экранирование и читается обратно без потери апострофа.

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
		// Подставляем значение только после экранирования для этого соединения.
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

Для показа используется синхронное соединение. Открывать такое при каждом игровом событии не нужно: ожидание задерживает сервер. Экранирование значения не проверяет имена таблиц и выражения сортировки. Для MySQL вызов `SQL_SetCharset()` у tuple ставим до соединений и запросов.

[`SQL_PrepareQuery`](https://www.amxmodx.org/api/sqlx/SQL_PrepareQuery) | [`SQL_QuoteString`](https://www.amxmodx.org/api/sqlx/SQL_QuoteString)

<a id="sql-player-context"></a>

## Игрок вышел, а ответ базы ещё идёт

К моменту ответа базы игрок может уже отключиться. Поэтому передаём в данных запроса userid его подключения, затем ищем игрока заново в обработчике. Если не нашли, результат этому подключению больше не нужен.

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

	// Ответ может прийти уже после выхода этого игрока.
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

Проверка userid не даст применить старый ответ к новому человеку в том же слоте. Но несколько запросов одного подключения ещё могут завершиться в другом порядке. Если нужен только последний, дополнительно храним номер запроса. Для постоянной записи аккаунта используем авторизованный ключ, а не userid.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`find_player`](https://www.amxmodx.org/api/amxmodx/find_player)
