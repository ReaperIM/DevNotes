# Files, nVault and SQL

[EN](../en/09-files-vault-and-sql.md) | [UK](../uk/09-files-vault-and-sql.md) | [RU](../ru/09-files-vault-and-sql.md)

[Contents](../../README.md)

Read configs from files, save points in nVault, or keep shared data in a database. Below, we cover reading, writing and releasing resources. File and database examples show individual API calls; choose when to save according to your own plugin’s job.

- [Build paths from AMXX directories](#paths)
- [Read a file sequentially and close it](#read-lines)
- [Append text without replacing the file](#append)
- [Parse JSON and check its structure](#json)
- [Open nVault once for the plugin](#vault-open)
- [Distinguish a missing key from a stored zero](#vault-missing)
- [Remove outdated records by timestamp](#vault-expiry)
- [Send a SQL query and receive the reply later](#threaded-sql)
- [Insert a string value into SQL safely](#sql-quoting)
- [The player left while the database reply was pending](#sql-player-context)

<a id="paths"></a>

## Build paths from AMXX directories

Avoid depending on the server’s installation path. Get the config directory with `get_configsdir()` and the data directory with `get_datadir()`. Below, we append `notes.txt` to the data directory and print the resulting path.

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

A filename received from a command still needs checking. A correct base directory does not stop `../` or an absolute path from reaching elsewhere. Keeping the allowed names under the plugin’s control is simpler.

[`get_datadir`](https://www.amxmodx.org/api/amxmisc/get_datadir)

<a id="read-lines"></a>

## Read a file sequentially and close it

To read a whole text file, open it once, read lines through `fgets()`, then close it. The example skips empty lines and lines beginning with `;`. Choose the `line` buffer size for the allowed record length.

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

An oversized line can be truncated and its tail discarded. Limit the format’s line length or handle long records explicitly. Trimming and skipping `;` alone do not provide a complete INI parser.

[`fgets`](https://www.amxmodx.org/api/file/fgets)

<a id="append"></a>

## Append text without replacing the file

To add text at the end of a file, open it with `at`. Using `wt` would clear its existing contents. Write the text through `fputs()` so the `%` in `Progress: 100%` remains an ordinary character.

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

Opening files or forcing buffered data out every frame delays the server thread. Save on meaningful changes or collect writes into batches. `fflush()` flushes a buffer; it does not make several operations an atomic transaction.

[`fputs`](https://www.amxmodx.org/api/file/fputs)

<a id="json"></a>

## Parse JSON and check its structure

A successful `json_parse()` means the JSON could be parsed. It can still contain a string instead of an object, or text where a number was expected. Below, we check for an object and a numeric `limit` field before reading the value.

```pawn
#include <amxmodx>
#include <json>

public plugin_init()
{
	new JSON:document = json_parse( "{^"limit^":10}" );
	if( document == Invalid_JSON )
		return;

	// Successful parsing alone does not guarantee the expected field type.
	if( json_is_object(document) && json_object_has_value(document, "limit", JSONNumber) )
		server_print( "Limit: %d", json_object_get_number(document, "limit") );

	json_free( document );
}
```

Release the created document through `json_free()`. When obtaining or creating nested values separately, check their handle rules: closing a parent does not replace cleanup of all independently created resources. When serializing back to text, account for the output buffer size too.

[`json_parse`](https://www.amxmodx.org/api/json/json_parse) | [`json_free`](https://www.amxmodx.org/api/json/json_free)

<a id="vault-open"></a>

## Open nVault once for the plugin

nVault keeps local records under keys. Open a named vault, save its handle and reuse it. Close it in `plugin_end()`. A failed open returns `INVALID_HANDLE`, so check that immediately.

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

The failure value is `-1`; an ordinary false check will miss it. nVault stores values as strings. Your plugin defines the key format and how it identifies the intended account.

[`nvault_open`](https://www.amxmodx.org/api/nvault/nvault_open)

<a id="vault-missing"></a>

## Distinguish a missing key from a stored zero

A player having `0` points does not mean their record is missing. `nvault_lookup()` reports whether the key was found separately from the value string and timestamp it fills. Below, the stored `"0"` is found successfully. An empty string can be a stored value too.

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

The two-argument numeric `nvault_get()` cannot distinguish a missing key from a stored zero on its own. Use a separate existence check if that distinction matters when initializing data.

[`nvault_lookup`](https://www.amxmodx.org/api/nvault/nvault_lookup)

<a id="vault-expiry"></a>

## Remove outdated records by timestamp

`nvault_set()` updates a record’s timestamp. To remove old records, call `nvault_prune()` with the intended interval. Below, we remove cache entries from the period before the cutoff 30 days ago. nVault does not apply a custom retention period automatically; run that cleanup explicitly.

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

Only prune data that can be discarded, such as cache entries. This cleanup leaves permanent records created with `nvault_pset()` intact.

[`nvault_prune`](https://www.amxmodx.org/api/nvault/nvault_prune)

<a id="threaded-sql"></a>

## Send a SQL query and receive the reply later

Use `SQL_ThreadQuery()` so the server does not wait for the database here. When the query finishes, AMXX calls `query_ready()`, where we check for failure and read the result. `SQL_MakeDbTuple()` keeps connection settings ready; calling it does not open a connection.

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
	// Check query success before reading the result.
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

The example chooses SQLite. For MySQL, use real settings from the server configuration. SQLX releases the callback’s `query` handle, so do not close it yourself. The handler itself runs on the game thread; keep expensive work out of it too.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`SQL_MakeDbTuple`](https://www.amxmodx.org/api/sqlx/SQL_MakeDbTuple)

<a id="sql-quoting"></a>

## Insert a string value into SQL safely

In `SQL_PrepareQuery()`, `%s` only formats query text. It does not bind a parameter as PDO does. Before inserting an external string, call `SQL_QuoteString()` for the active connection. The name `O'Brien` below is escaped and read back with its apostrophe intact.

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
		// Only insert the value after escaping it for this connection.
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

The example uses a synchronous connection to demonstrate this. Opening one for every game event would make the server wait. Escaping a value does not validate table names or sort expressions. For MySQL, call `SQL_SetCharset()` on the tuple before opening connections or sending queries.

[`SQL_PrepareQuery`](https://www.amxmodx.org/api/sqlx/SQL_PrepareQuery) | [`SQL_QuoteString`](https://www.amxmodx.org/api/sqlx/SQL_QuoteString)

<a id="sql-player-context"></a>

## The player left while the database reply was pending

By the time the database replies, the player may have disconnected. Put the connection userid into the query data, then find the player again in the handler. If the connection cannot be found, it no longer needs the result.

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

	// The reply may arrive after the original player has left.
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

Checking userid prevents an old reply from reaching a new person in the same slot. Several requests for one connection can still finish out of order. Keep a request number too if only the latest result should apply. Persistent account data uses an authorized account key rather than a userid.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`find_player`](https://www.amxmodx.org/api/amxmodx/find_player)
