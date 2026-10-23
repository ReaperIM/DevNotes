# Plugin lifecycle and players

[EN](../en/04-plugin-lifecycle-and-players.md) | [UK](../uk/04-plugin-lifecycle-and-players.md) | [RU](../ru/04-plugin-lifecycle-and-players.md)

[Contents](../../README.md)

A player connection has several separate stages: connecting, authorization and entering the game. Below, we work through when to prepare data, when to clear it, and how to avoid confusing a player who left with someone new in the same slot.

- [Register once per map](#registration)
- [When the configs have finished running](#initialization-order)
- [Authorization and entering the game are separate](#authorization-order)
- [First check that the index belongs to a player](#player-index)
- [Find the same connection later](#userid)
- [Read account identity after authorization](#authid)
- [Get the players you need and walk the list](#player-list)
- [Clean up on disconnect, not after a slot is reused](#disconnect-cleanup)
- [What to close when the plugin ends](#handle-cleanup)

<a id="registration"></a>

## Register once per map

Register commands and hooks in `plugin_init()`: this sets up the plugin for the map. The plugin name and version go here too. Prepare a particular player’s data in the client callbacks, once that connection exists.

```pawn
#include <amxmodx>
#define VERSION "1.0.0"

public plugin_init()
{
	register_plugin( "Lifecycle note", VERSION, "DevNotes" );
	register_clcmd( "say /ready", "command_ready" );
}

public command_ready( id )
{
	client_print( id, print_chat, "Ready" );
	return PLUGIN_HANDLED;
}
```

Global variables belong to the current plugin instance. If data must survive a map change, save it to a store separately.

[`plugin_init`](https://www.amxmodx.org/api/amxmodx/plugin_init)

<a id="initialization-order"></a>

## When the configs have finished running

Need to read a cvar after the configs load? Use `OnConfigsExecuted()`. All `plugin_init()` calls have finished by `plugin_cfg()`, but some config commands may still be queued. Below, we read the limit once the normal AMXX configs have executed.

```pawn
#include <amxmodx>
new g_limit;

public plugin_init()
{
	g_limit = create_cvar( "notes_limit", "10" );
	AutoExecConfig( true, "notes" );
}

public OnConfigsExecuted()
{
	server_print( "Configured limit: %d", get_pcvar_num(g_limit) );
}
```

Another plugin may still run its own `exec` later. This callback covers the normal AMXX configuration order. See [cvar bindings and config changes](05-commands-cvars-and-configs.md).

[`OnConfigsExecuted`](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

<a id="authorization-order"></a>

## Authorization and entering the game are separate

Authorization can happen before or after `client_putinserver()`. If an action needs both authorization and entry into the game, track them separately. Each callback below checks the other flag, so the message appears once both conditions are met.

```pawn
#include <amxmodx>
new bool:g_authorized[MAX_PLAYERS + 1];
new bool:g_in_game[MAX_PLAYERS + 1];

public client_connect( id )
{
	g_authorized[id] = false;
	g_in_game[id] = false;
}

public client_authorized( id, const authid[] )
{
	g_authorized[id] = true;
	if( g_in_game[id] )
		server_print( "Player %d is ready, auth: %s", id, authid );
}

public client_putinserver( id )
{
	g_in_game[id] = true;
	if( g_authorized[id] )
		server_print( "Player %d is ready", id );
}
```

Whichever callback runs second does the work. Bots authorize too: these flags alone do not establish that the player is human or has permission to use a command.

[`client_authorized`](https://www.amxmodx.org/api/amxmodx/client_authorized)

<a id="player-index"></a>

## First check that the index belongs to a player

Player slots start at `1`; in many functions, `0` means the server or the world. Before reading health, check the range and then the player state. This command only applies to a living player, so `is_user_alive()` is the state check we need.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /health", "command_health" );
}

public command_health( id )
{
	if( id < 1 || id > MaxClients || !is_user_alive(id) )
		return PLUGIN_HANDLED;

	client_print( id, print_chat, "Health: %d", get_user_health(id) );
	return PLUGIN_HANDLED;
}
```

An entity index is not always suitable for player functions. Writing `id >= 1 && id <= MaxClients` makes the range explicit. Pawn supports chained comparisons, but this form is easier to read and port.

[`is_user_alive`](https://www.amxmodx.org/api/amxmodx/is_user_alive)

<a id="userid"></a>

## Find the same connection later

A player leaves and someone else takes the slot. If we save only `id`, a delayed welcome may reach the new player. Save the current connection’s userid with `get_user_userid()`, then look up its slot before printing. If that connection has ended, `find_player()` will not find it.

```pawn
#include <amxmodx>

public client_putinserver( id )
{
	new data[1];
	// Remember the connection; the slot can later belong to someone else.
	data[0] = get_user_userid( id );
	set_task( 2.0, "show_welcome", 0, data, sizeof data );
}

public show_welcome( const data[] )
{
	new id = find_player( "k", data[0] );
	if( id != 0 )
		client_print( id, print_chat, "Welcome" );
}
```

Userid suits a delayed action within one connection. Saving account progress needs a different key. The same technique helps with SQL replies and menu targets. See [task lifetime](07-tasks-and-menus.md).

[`get_user_userid`](https://www.amxmodx.org/api/amxmodx/get_user_userid)

<a id="authid"></a>

## Read account identity after authorization

For an account key used to save data, take `authid` from `client_authorized()`: the identifier is available by then. Names and IPs can change, so they do not suit that key. Below, we print the authid while skipping bots and HLTV.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	if( is_user_bot(id) || is_user_hltv(id) )
		return;

	server_print( "Authorized slot %d: %s", id, authid );
}
```

How reliable that identifier is depends on the server’s authorization setup. A `STEAM_` prefix alone does not grant permissions. A bot’s auth value is `BOT`.

[`get_user_authid`](https://www.amxmodx.org/api/amxmodx/get_user_authid)

<a id="player-list"></a>

## Get the players you need and walk the list

`get_players_ex()` puts matching indexes next to each other in `players` and writes their count to `count`. Below, we exclude bots and HLTV, then walk only that list. There is no need to check every possible slot again for this loop.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_init()
{
	register_srvcmd( "notes_players", "command_players" );
}

public command_players()
{
	new players[MAX_PLAYERS], count;
	get_players_ex( players, count, GetPlayers_ExcludeBots | GetPlayers_ExcludeHLTV );
	for( new index; index < count; index++ )
		server_print( "Human slot: %d", players[index] );

	return PLUGIN_HANDLED;
}
```

Here, `index` is a position in the list, and `players[index]` is the player slot. If nobody matches, `count` is `0` and the loop does not run.

[`get_players_ex`](https://www.amxmodx.org/api/amxmisc/get_players_ex)

<a id="disconnect-cleanup"></a>

## Clean up on disconnect, not after a slot is reused

Use `client_disconnected()` for cleanup after a player leaves. It also covers interrupted connections and replaces the old `client_disconnect()`. Below, we remove the task and clear the slot’s points so the next player does not inherit them. The entity still exists at this point, but messages can no longer reach the departing client.

```pawn
#include <amxmodx>
#define TASK_HINT 2000
new g_points[MAX_PLAYERS + 1];

public client_connect( id )
{
	g_points[id] = 0;
}

public client_disconnected( id )
{
	remove_task( TASK_HINT + id );
	// The next player in this slot must start with their own state.
	g_points[id] = 0;
}
```

Save anything you need before clearing it. `client_remove()` runs later, after the entity is gone. In `client_disconnected()`, `is_user_connected()` may still return true, so use the disconnect event itself to decide that cleanup is needed.

[`client_disconnected`](https://www.amxmodx.org/api/amxmodx/client_disconnected)

<a id="handle-cleanup"></a>

## What to close when the plugin ends

Keep a map-long `Trie` in a global variable and release it in `plugin_end()`. Its handle refers to a particular resource, so use the matching cleanup function: `TrieDestroy()` here. Handle numbers from different APIs are not interchangeable.

```pawn
#include <amxmodx>
new Trie:g_cache = Invalid_Trie;

public plugin_init()
{
	g_cache = TrieCreate();
}

public plugin_end()
{
	if( g_cache != Invalid_Trie )
		TrieDestroy( g_cache );
}
```

Close resources managed by your plugin. A handle received in a callback may belong to the API itself. For example, SQLX releases the result of a threaded SQL query. See [storage](09-files-vault-and-sql.md).

[`plugin_end`](https://www.amxmodx.org/api/amxmodx/plugin_end)
