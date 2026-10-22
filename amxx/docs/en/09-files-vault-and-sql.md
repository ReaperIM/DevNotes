# Files, nVault and SQL

[EN](../en/09-files-vault-and-sql.md) | [UK](../uk/09-files-vault-and-sql.md) | [RU](../ru/09-files-vault-and-sql.md)

[Contents](../../README.md)

Configuration files, structured data and persistent storage. Examples that write files or open databases are API examples, not automatic startup code for a production plugin.

- [Build paths from AMXX directories](#paths)
- [Read a text file with one open handle](#read-lines)
- [Append text without replacing the file](#append)
- [Parse JSON and check its structure](#json)
- [Open nVault and keep the handle](#vault-open)
- [Distinguish a missing key from a stored zero](#vault-missing)
- [Timestamps and pruning are explicit](#vault-expiry)
- [Threaded SQL and its callback](#threaded-sql)
- [`SQL_PrepareQuery` is not parameter binding](#sql-quoting)
- [A query result can outlive the player connection](#sql-player-context)

<a id="paths"></a>

## Build paths from AMXX directories

Use `get_configsdir()` for configuration and `get_datadir()` for stored data instead of assuming an installation path.

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

A base directory does not make a user-supplied filename safe. Keep names controlled; do not append arbitrary `../` or absolute paths from commands.

[`get_datadir`](https://www.amxmodx.org/api/amxmisc/get_datadir)

<a id="read-lines"></a>

## Read a text file with one open handle

Open once, read sequentially, then close. `fgets()` reads a bounded piece, so the buffer must accommodate the intended line format.

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

A line longer than the buffer can be truncated with its tail discarded. Limit line lengths in the format or use a reader that handles long records explicitly. Trimming and skipping `;` here are example rules, not a full INI parser.

[`fgets`](https://www.amxmodx.org/api/file/fgets)

<a id="append"></a>

## Append text without replacing the file

Mode `at` appends; `wt` truncates an existing file. Use `fputs()` for literal text so `%` in the data is not treated as a format string.

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

Opening files or flushing on every frame is expensive work for the server thread. Save on meaningful changes or in batches; `fflush()` is not a transaction.

[`fputs`](https://www.amxmodx.org/api/file/fputs)

<a id="json"></a>

## Parse JSON and check its structure

The JSON module separates parsing from reading fields. A syntactically valid document can still have the wrong shape or field type.

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

Free JSON values you own. Native handles for nested values and serialization buffers have their own lifetime and size rules; do not assume that a parent frees every independently created handle.

[`json_parse`](https://www.amxmodx.org/api/json/json_parse) | [`json_free`](https://www.amxmodx.org/api/json/json_free)

<a id="vault-open"></a>

## Open nVault and keep the handle

nVault is a local key/value store. Open a named vault once, check `INVALID_HANDLE`, and close it when the plugin ends.

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

nVault failure is `-1`, not a generic false handle. Values are strings; key design and account identity remain application concerns.

[`nvault_open`](https://www.amxmodx.org/api/nvault/nvault_open)

<a id="vault-missing"></a>

## Distinguish a missing key from a stored zero

`nvault_lookup()` returns whether the entry exists and fills the value and timestamp separately. This preserves meaningful `0` and empty values.

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

The two-argument numeric `nvault_get()` cannot by itself distinguish a missing key from a stored numeric zero.

[`nvault_lookup`](https://www.amxmodx.org/api/nvault/nvault_lookup)

<a id="vault-expiry"></a>

## Timestamps and pruning are explicit

`nvault_set()` updates an entry timestamp. `nvault_prune()` removes entries in a time interval; nVault does not apply your own TTL policy automatically.

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

Use pruning for data that is actually disposable. Permanent entries created with `nvault_pset()` are not removed by prune.

[`nvault_prune`](https://www.amxmodx.org/api/nvault/nvault_prune)

<a id="threaded-sql"></a>

## Threaded SQL and its callback

`SQL_MakeDbTuple()` stores connection settings; it does not connect. `SQL_ThreadQuery()` runs a query asynchronously and calls a `public` handler with either failure details or a result.

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

This example selects the SQLite driver. MySQL needs real connection settings from server configuration. Do not free the callback query handle: SQLX owns it. The handler still must avoid expensive work on the game thread.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`SQL_MakeDbTuple`](https://www.amxmodx.org/api/sqlx/SQL_MakeDbTuple)

<a id="sql-quoting"></a>

## `SQL_PrepareQuery` is not parameter binding

The format arguments of SQLX are string formatting, not PDO-style placeholders. Escape external string values for the active connection before inserting them into SQL.

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

This is deliberately synchronous to show escaping with a real connection; do not open a blocking connection for each gameplay event. Escaping values does not validate table names or sort expressions. `SQL_SetCharset()` on a MySQL tuple belongs before connections/queries.

[`SQL_PrepareQuery`](https://www.amxmodx.org/api/sqlx/SQL_PrepareQuery) | [`SQL_QuoteString`](https://www.amxmodx.org/api/sqlx/SQL_QuoteString)

<a id="sql-player-context"></a>

## A query result can outlive the player connection

Put a userid in the query data, not just the slot. Resolve it in the callback and reject results for a connection that has already ended.

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

Userid prevents applying an old result to a new occupant. Multiple requests within the same connection can still arrive out of order: add a request generation if the latest result must win. Persistent writes use an authorized account key, not the userid.

[`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery) | [`find_player`](https://www.amxmodx.org/api/amxmodx/find_player)
