# Файли, nVault і SQL

[EN](../en/09-files-vault-and-sql.md) | [UK](../uk/09-files-vault-and-sql.md) | [RU](../ru/09-files-vault-and-sql.md)

[Зміст](../../README.uk.md)

Конфіги читаємо з файлів, очки можна зберігати у nVault, а спільні дані тримати в базі. Нижче розібрані читання, запис і звільнення ресурсів. Приклади з файлами та БД показують окремий виклик API; момент збереження для свого плагіна вибираємо за його завданням.

- [Шляхи від каталогів AMXX](#paths)
- [Читаємо файл послідовно й закриваємо його](#read-lines)
- [Додавання тексту без перезапису файла](#append)
- [Розбір JSON і перевірка структури](#json)
- [Відкриваємо nVault один раз на плагін](#vault-open)
- [Відсутній ключ і збережений нуль](#vault-missing)
- [Видаляємо застарілі записи за часом](#vault-expiry)
- [Надсилаємо SQL-запит і отримуємо відповідь пізніше](#threaded-sql)
- [Безпечно підставляємо рядкове значення в SQL](#sql-quoting)
- [Гравець вийшов, а відповідь бази ще йде](#sql-player-context)

<a id="paths"></a>

## Шляхи від каталогів AMXX

Не прив’язуємося до того, в яку папку встановлений сервер. Каталог конфігів отримуємо через `get_configsdir()`, а каталог даних через `get_datadir()`. Нижче додаємо до каталогу даних ім’я `notes.txt` і виводимо готовий шлях.

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

Якщо ім’я файлу надходить із команди, його однаково перевіряємо. Правильна базова папка не завадить рядку з `../` або абсолютним шляхом звернутися в інше місце. Простіше тримати допустимі імена під контролем плагіна.

[`get_datadir`](https://www.amxmodx.org/api/amxmisc/get_datadir)

<a id="read-lines"></a>

## Читаємо файл послідовно й закриваємо його

Щоб пройти весь текстовий файл, один раз відкриваємо його, читаємо рядки через `fgets()`, потім закриваємо. У прикладі пропускаємо порожні рядки й рядки з `;` на початку. Буфер `line` добираємо під допустиму довжину запису.

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

Задовгий рядок може обрізатися, а його кінець загубитися. Тому обмежуємо довжину у своєму форматі або окремо обробляємо довгі записи. Один `trim()` і пропуск `;` ще не дають повноцінного розбору INI.

[`fgets`](https://www.amxmodx.org/api/file/fgets)

<a id="append"></a>

## Додавання тексту без перезапису файла

Щоб додати текст у кінець файлу, відкриваємо його з `at`. Режим `wt` очистив би попередній вміст. Сам текст пишемо через `fputs()`: знак `%` у `Progress: 100%` тоді залишається звичайним символом.

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

Відкриття файлу й примусовий запис даних кожен кадр затримують серверний потік. Краще зберігати за значущих змін або збирати записи в пакет. `fflush()` скидає буфер, але цілісної транзакції з кількох дій не створює.

[`fputs`](https://www.amxmodx.org/api/file/fputs)

<a id="json"></a>

## Розбір JSON і перевірка структури

Успішний `json_parse()` каже, що JSON вдалося розібрати. Але всередині ще може виявитися рядок замість об’єкта або текст замість числа. Тому нижче окремо перевіряємо об’єкт і числове поле `limit`, потім читаємо значення.

```pawn
#include <amxmodx>
#include <json>

public plugin_init()
{
	new JSON:document = json_parse( "{^"limit^":10}" );
	if( document == Invalid_JSON )
		return;

	// Успішний розбір ще не гарантує потрібного типу поля.
	if( json_is_object(document) && json_object_has_value(document, "limit", JSONNumber) )
		server_print( "Limit: %d", json_object_get_number(document, "limit") );

	json_free( document );
}
```

Створений документ звільняємо через `json_free()`. Якщо окремо отримуємо чи створюємо вкладені значення, дивимося правила їхніх handles: закриття батьківського об’єкта не замінює звільнення всіх окремо створених ресурсів. Для перетворення назад у текст ще враховуємо розмір вихідного буфера.

[`json_parse`](https://www.amxmodx.org/api/json/json_parse) | [`json_free`](https://www.amxmodx.org/api/json/json_free)

<a id="vault-open"></a>

## Відкриваємо nVault один раз на плагін

nVault зберігає локальні записи за ключем. Відкриваємо сховище з вибраним ім’ям, зберігаємо handle й користуємося ним далі. У `plugin_end()` закриваємо. Якщо відкриття не вдалося, отримаємо `INVALID_HANDLE`, тому цю перевірку робимо одразу.

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

У помилки тут значення `-1`; звичайна перевірка на хибність її не виявить. Значення nVault зберігає рядками. Як скласти ключ і прив’язати його до потрібного акаунта, визначаємо у своєму плагіні.

[`nvault_open`](https://www.amxmodx.org/api/nvault/nvault_open)

<a id="vault-missing"></a>

## Відсутній ключ і збережений нуль

Якщо у гравця `0` очок, це ще не означає, що запису немає. `nvault_lookup()` окремо повідомляє, чи знайдений ключ, потім заповнює рядок значення та час запису. Нижче збережений `"0"` успішно знаходиться. Порожній рядок теж може бути збереженим значенням.

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

Числовий `nvault_get()` із двома аргументами сам собою не відрізнить відсутній ключ від записаного нуля. Якщо це розрізнення важливе для початкових даних, використовуємо окрему перевірку наявності.

[`nvault_lookup`](https://www.amxmodx.org/api/nvault/nvault_lookup)

<a id="vault-expiry"></a>

## Видаляємо застарілі записи за часом

Під час `nvault_set()` час запису оновлюється. Для видалення старих записів викликаємо `nvault_prune()` з потрібним інтервалом. Нижче видаляємо записи кешу, що потрапили в період до межі 30 днів тому. Власний строк зберігання сам собою nVault не застосує: очищення запускаємо явно.

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

Так видаляємо лише ті дані, які дозволено втратити, наприклад кеш. Постійні записи через `nvault_pset()` це очищення не зачепить.

[`nvault_prune`](https://www.amxmodx.org/api/nvault/nvault_prune)

<a id="threaded-sql"></a>

## Надсилаємо SQL-запит і отримуємо відповідь пізніше

Щоб сервер не чекав відповіді бази в цьому місці, використовуємо `SQL_ThreadQuery()`. Коли запит завершиться, AMXX викличе `query_ready()`: перевіримо помилку й прочитаємо результат. `SQL_MakeDbTuple()` заздалегідь зберігає налаштування підключення; з’єднання під час його виклику ще не відкривається.

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
	// Перевіряємо успішність запиту до читання результату.
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

У прикладі вибраний SQLite. Для MySQL підставляємо справжні налаштування з конфігурації сервера. Handle `query` в обробнику звільняє сам SQLX, вручну закривати його не потрібно. Сам обробник працює в ігровому потоці, тому важку роботу туди теж не переносимо.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`SQL_MakeDbTuple`](https://www.amxmodx.org/api/sqlx/SQL_MakeDbTuple)

<a id="sql-quoting"></a>

## Безпечно підставляємо рядкове значення в SQL

У `SQL_PrepareQuery()` запис `%s` лише форматує текст запиту. Окремої прив’язки параметрів, як у PDO, тут немає. Тому перед підстановкою чужого рядка викликаємо `SQL_QuoteString()` для активного з’єднання. Ім’я `O'Brien` нижче проходить через екранування й читається назад без втрати апострофа.

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
		// Підставляємо значення лише після екранування для цього з’єднання.
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

Для показу використовується синхронне з’єднання. Відкривати таке під час кожної ігрової події не потрібно: очікування затримує сервер. Екранування значення не перевіряє імена таблиць і вирази сортування. Для MySQL виклик `SQL_SetCharset()` у tuple ставимо до з’єднань і запитів.

[`SQL_PrepareQuery`](https://www.amxmodx.org/api/sqlx/SQL_PrepareQuery) | [`SQL_QuoteString`](https://www.amxmodx.org/api/sqlx/SQL_QuoteString)

<a id="sql-player-context"></a>

## Гравець вийшов, а відповідь бази ще йде

На момент відповіді бази гравець може вже відключитися. Тому передаємо в даних запиту userid його підключення, потім шукаємо гравця заново в обробнику. Якщо не знайшли, результат цьому підключенню більше не потрібен.

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

	// Відповідь може надійти вже після виходу цього гравця.
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

Перевірка userid не дасть застосувати стару відповідь до нової людини в тому самому слоті. Але кілька запитів одного підключення ще можуть завершитися в іншому порядку. Якщо потрібен лише останній, додатково зберігаємо номер запиту. Для постійного запису акаунта використовуємо авторизований ключ, а не userid.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`find_player`](https://www.amxmodx.org/api/amxmodx/find_player)
