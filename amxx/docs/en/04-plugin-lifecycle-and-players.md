# Plugin lifecycle and players

[English](../en/04-plugin-lifecycle-and-players.md) | [Українська](../uk/04-plugin-lifecycle-and-players.md) | [Русский](../ru/04-plugin-lifecycle-and-players.md)

[Contents](../../README.md)

Map setup, player identity and cleanup. The most important distinction is between a reusable slot and a particular connection.

- [Register once per map](#registration)
- [plugin_cfg is not the end of configuration](#initialization-order)
- [Authorization and entering the game are separate](#authorization-order)
- [Validate a player index before using player state](#player-index)
- [Use userid for the current connection](#userid)
- [Read account identity after authorization](#authid)
- [Iterate a filtered list of players](#player-list)
- [Clean up on disconnect, not after a slot is reused](#disconnect-cleanup)
- [Close resources owned by the plugin](#handle-cleanup)

<a id="registration"></a>

## Register once per map

`plugin_init()` registers commands, hooks and plugin metadata. Keep per-player initialization in the player forwards.

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

Globals describe the current plugin instance, not persistent storage. A map change is not a substitute for saving data.

[plugin_init](https://www.amxmodx.org/api/amxmodx/plugin_init)

<a id="initialization-order"></a>

## plugin_cfg is not the end of configuration

`plugin_cfg()` runs after all `plugin_init()` callbacks. To use final AMXX configuration values, use `OnConfigsExecuted()` rather than assuming buffered commands have already run.

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

This covers the normal AMXX config phase, not an arbitrary later `exec` issued by another plugin. See [cvar bindings and config changes](05-commands-cvars-and-configs.md).

[OnConfigsExecuted](https://www.amxmodx.org/api/amxmodx/OnConfigsExecuted)

<a id="authorization-order"></a>

## Authorization and entering the game are separate

`client_authorized()` may run before or after `client_putinserver()`. If an action needs both conditions, store both and check them together.

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

The action runs in whichever forward arrives second. Bots are also authorized; this alone does not identify a human or grant access.

[client_authorized](https://www.amxmodx.org/api/amxmodx/client_authorized)

<a id="player-index"></a>

## Validate a player index before using player state

Player slots start at 1; 0 is the server or world in many APIs. First check the range, then connection or life state required by the operation.

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

Pawn permits chained comparisons, but explicit `id >= 1 && id <= MaxClients` is easier to transfer to other languages. Do not treat every entity index as a player.

[is_user_alive](https://www.amxmodx.org/api/amxmodx/is_user_alive)

<a id="userid"></a>

## Use userid for the current connection

A slot can be occupied by another player after disconnect. `get_user_userid()` identifies the current connection; resolve it again when a delayed result arrives.

```pawn
#include <amxmodx>

public client_putinserver( id )
{
	new data[1];
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

Userid is not an account key for saved progress. The same rule applies to SQL callbacks and menu targets. [Task lifecycle](07-tasks-and-menus.md).

[get_user_userid](https://www.amxmodx.org/api/amxmodx/get_user_userid)

<a id="authid"></a>

## Read account identity after authorization

Use the `authid` supplied by `client_authorized()` when creating a persistent account key. Names and IP addresses are not stable substitutes.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	if( is_user_bot(id) || is_user_hltv(id) )
		return;

	server_print( "Authorized slot %d: %s", id, authid );
}
```

The identity guarantees depend on the server authentication setup. Do not award privileges merely because a string starts with `STEAM_`; bot auth is `BOT`.

[get_user_authid](https://www.amxmodx.org/api/amxmodx/get_user_authid)

<a id="player-list"></a>

## Iterate a filtered list of players

`get_players_ex()` produces a dense list of matching slot indexes. Iterate its count, not all possible slots, when you already need that list.

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

`players[index]` is a slot; `index` is just the list position. An empty result means the loop does not run.

[get_players_ex](https://www.amxmodx.org/api/amxmisc/get_players_ex)

<a id="disconnect-cleanup"></a>

## Clean up on disconnect, not after a slot is reused

`client_disconnected()` also covers aborted connections and replaces deprecated `client_disconnect()`. The entity is still available at this point, but client messages can no longer reach it.

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
	g_points[id] = 0;
}
```

`client_remove()` is later, after the entity is removed. Save the data you need before clearing it; do not assume that `is_user_connected()` is already false inside `client_disconnected()`.

[client_disconnected](https://www.amxmodx.org/api/amxmodx/client_disconnected)

<a id="handle-cleanup"></a>

## Close resources owned by the plugin

Keep long-lived handles in globals and release them in `plugin_end()`. The correct destroy function depends on the API; handles are not interchangeable integers.

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

Closing an owned handle is different from freeing a borrowed callback handle. In particular, a threaded SQL query handle is owned by SQLX. [Storage](09-files-vault-and-sql.md).

[plugin_end](https://www.amxmodx.org/api/amxmodx/plugin_end)
