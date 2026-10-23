# Entities and gameplay

[EN](../en/08-entities-and-gameplay.md) | [UK](../uk/08-entities-and-gameplay.md) | [RU](../ru/08-entities-and-gameplay.md) | [Contents](../../README.md)

An entity has a lifetime, fields and behavior supplied by the game. Below, we look at avoiding removed entities, reading data correctly and choosing between setting a field and calling a game operation. Each block stands on its own; functions with parameters show an operation to call from your own code.

- [Avoid confusing a removed entity with a new one](#entity-identity)
- [Read entity fields with the right type](#entvars-and-vectors)
- [Move through a position-setting function](#moving-entities)
- [Create an entity and configure its behavior](#create-and-spawn)
- [One touch, one reward](#touch-and-consume)
- [Set the next `Think` time](#think-scheduling)
- [Load a model and set the entity bounds](#models-and-precache)
- [Find and remove only your own entities](#search-and-remove)
- [An owner can change in the same slot too](#owner-lifetime)
- [Private data: check the fields and offsets](#private-data)
- [Read named fields through ReAPI](#reapi-members)
- [Giving weapons, respawning and death](#weapons-spawn-and-death)

<a id="entity-identity"></a>
## Avoid confusing a removed entity with a new one

After removal, an entity index can be reused. A saved index may pass a validity check while referring to a different object. For delayed work, save `pev_serial` together with the index and check both before using the entity.


```pawn
#include <amxmodx>
#include <fakemeta>

public bool:is_same_entity( entity, serial )
{
	if( !pev_valid( entity ) )
		return false;

	return pev_serial( entity ) == serial;
}
```

Save the serial when choosing the entity. Reading it only when the delayed handler starts leaves nothing to compare against. `pev_valid()` returns `0` if no entity exists, `1` for an existing engine edict, and `2` when private data exists too. A result of `2` permits private-data access in principle; the expected class and matching offsets still need separate checks.

Sources: [`pev_valid`](https://www.amxmodx.org/api/fakemeta/pev_valid), [`pev_serial`](https://www.amxmodx.org/api/fakemeta/pev_serial).

<a id="entvars-and-vectors"></a>
## Read entity fields with the right type

`pev()` has different forms for integers, floats, strings and vectors. Below, we pass output variables for the floating-point value and vector. Reading a returned cell directly does not automatically convert it to a `Float`.


```pawn
#include <amxmodx>
#include <fakemeta>

public describe_entity( entity )
{
	if( !pev_valid( entity ) )
		return;

	new classname[32];
	new Float:health, Float:velocity[3];
	pev( entity, pev_classname, classname, charsmax( classname ) );
	pev( entity, pev_health, health );
	pev( entity, pev_velocity, velocity );
	server_print( "%s: health %.1f, speed %.1f", classname, health, vector_length( velocity ) );
}
```

A vector uses three `Float` cells; fractional coordinates should not be rounded to integers. `velocity` describes direction and speed; angles belong to a different field. Engine provides the corresponding `entity_get_int`, `entity_get_float`, `entity_get_vector`, `entity_get_string` and `entity_get_edict` functions.

Sources: [`pev`](https://www.amxmodx.org/api/fakemeta/pev), [`entity_get_vector`](https://www.amxmodx.org/api/engine/entity_get_vector).

<a id="moving-entities"></a>
## Move through a position-setting function

Moving an entity also requires the engine to update where it belongs in its own structures. Use `entity_set_origin()` or `EngFunc_SetOrigin()` for that. A direct assignment to `pev_origin` is not enough. Below, we read the position, add `32.0` to its height and set the new origin.


```pawn
#include <amxmodx>
#include <engine>

public move_entity_up( entity )
{
	if( !is_valid_ent( entity ) )
		return;

	new Float:origin[3];
	entity_get_vector( entity, EV_VEC_origin, origin );
	origin[2] += 32.0;
	entity_set_origin( entity, origin );
}
```

This moves the entity 32 units along Z. It does not check for a wall at the destination. Player teleportation also needs a suitable hull trace and a decision about velocity and ground state. A position setter does not find a safe spot for us.

Sources: [`entity_set_origin`](https://www.amxmodx.org/api/engine/entity_set_origin), [engine function constants](https://www.amxmodx.org/api/fakemeta_const).

<a id="create-and-spawn"></a>
## Create an entity and configure its behavior

`create_entity()` needs a class already implemented by the game DLL. A new classname string does not add a C++ class. For a simple custom trigger, we create `info_target`, run its spawn, then assign our marker name, size and trigger properties.


```pawn
#include <amxmodx>
#include <engine>

public create_marker( const Float:origin[3] )
{
	new entity = create_entity( "info_target" );

	if( !entity )
		return 0;

	DispatchSpawn( entity );
	entity_set_string( entity, EV_SZ_classname, "notes_marker" );
	entity_set_int( entity, EV_INT_movetype, MOVETYPE_NONE );
	entity_set_int( entity, EV_INT_solid, SOLID_TRIGGER );
	entity_set_size( entity, Float:{ -16.0, -16.0, -16.0 }, Float:{ 16.0, 16.0, 16.0 } );
	entity_set_origin( entity, origin );
	return entity;
}
```

The result is an invisible trigger area. A visible item needs a model separately. Entities configured through keyvalues may need those values before `DispatchSpawn()`: spawn can change fields or remove an unusable entity. The order depends on the class, so check before applying this trigger example to doors, weapons or brush entities.

Sources: [`create_entity`](https://www.amxmodx.org/api/engine/create_entity), [`DispatchSpawn`](https://www.amxmodx.org/api/engine/DispatchSpawn), [`DispatchKeyValue`](https://www.amxmodx.org/api/engine/DispatchKeyValue).

<a id="touch-and-consume"></a>
## One touch, one reward

A trigger can receive repeated touches from a player. Below, we check that `notes_marker` still exists and the player is alive, then remove the marker before giving the reward. This prevents one marker from healing repeatedly. The example uses the trigger from the previous section.


```pawn
#include <amxmodx>
#include <engine>
#include <fun>

public plugin_init()
{
	register_touch( "notes_marker", "player", "on_marker_touch" );
}

public on_marker_touch( marker, player )
{
	if( !is_valid_ent( marker ) || !is_user_alive( player ) )
		return;

	// Consume it before rewarding to avoid repeated touches.
	remove_entity( marker );
	new health = get_user_health( player );

	if( health < 100 )
		set_user_health( player, min( health + 10, 100 ) );
}
```

After removal, do not read fields from `marker`. This example consumes it even at 100 health. If it should remain for another player, check full health before removal. The `owner` field does not check reward permissions on its own.

Sources: [`register_touch`](https://www.amxmodx.org/api/engine/register_touch), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity), [`set_user_health`](https://www.amxmodx.org/api/fun/set_user_health).

<a id="think-scheduling"></a>
## Set the next `Think` time

Write a particular game-time moment into `nextthink`. For a call one second from now, use `get_gametime() + 1.0`. If the entity should keep working, set its next call time again inside the handler.


```pawn
#include <amxmodx>
#include <engine>

public plugin_init()
{
	register_think( "notes_marker", "on_marker_think" );
}

public start_marker_think( marker )
{
	if( is_valid_ent( marker ) )
		entity_set_float( marker, EV_FL_nextthink, get_gametime() + 1.0 );
}

public on_marker_think( marker )
{
	if( !is_valid_ent( marker ) )
		return;

	entity_set_float( marker, EV_FL_nextthink, get_gametime() + 1.0 );
}
```

This gives a roughly one-second cadence, following server frames. More active entities mean more handler calls overall. If data can be saved at creation or updated by an event, avoid scanning every player and entity again from each `Think`.

Sources: [`register_think`](https://www.amxmodx.org/api/engine/register_think), [`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime).

<a id="models-and-precache"></a>
## Load a model and set the entity bounds

**CS 1.6 asset path.** Precache the model in `plugin_precache()`, then assign that same model to the entity. Set the intended collision bounds afterward because model assignment can affect them.


```pawn
#include <amxmodx>
#include <engine>

new const MARKER_MODEL[] = "models/w_hegrenade.mdl";

public plugin_precache()
{
	precache_model( MARKER_MODEL );
}

public apply_marker_model( marker )
{
	if( !is_valid_ent( marker ) )
		return;

	entity_set_model( marker, MARKER_MODEL );
	// Set bounds after the model, which can change them.
	entity_set_size( marker, Float:{ -16.0, -16.0, -16.0 }, Float:{ 16.0, 16.0, 16.0 } );
}
```

A grenade model changes appearance without adding grenade behavior. The real class and entity handlers still determine that. For a custom resource, check its path and deploy the file to the server. Keep the precache list controlled rather than filling it from arbitrary client input.

Sources: [`precache_model`](https://www.amxmodx.org/api/amxmodx/precache_model), [`entity_set_model`](https://www.amxmodx.org/api/engine/entity_set_model), [`entity_set_size`](https://www.amxmodx.org/api/engine/entity_set_size).

<a id="search-and-remove"></a>
## Find and remove only your own entities

A distinct classname lets us find the objects created by our feature. Below, we find the next marker before removing the current one. The search can continue without depending on an entity that has already been freed.


```pawn
#include <amxmodx>
#include <engine>

public remove_markers()
{
	new entity = find_ent_by_class( 0, "notes_marker" );

	while( entity > 0 )
	{
		// Find the next entity while the current one still exists.
		new next = find_ent_by_class( entity, "notes_marker" );
		remove_entity( entity );
		entity = next;
	}
}
```

Run this cleanup for a relevant event, such as the mode ending. A full-world scan every frame is usually unnecessary. Common game classes, players and `worldspawn` are not our markers; deleting those broadly will break the game.

Sources: [`find_ent_by_class`](https://www.amxmodx.org/api/engine/find_ent_by_class), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity).

<a id="owner-lifetime"></a>
## An owner can change in the same slot too

For your own entity, save the owner index and that connection’s userid. A new player in the same slot will then not inherit an old marker. Below, we reserve `iuser1` on our own entity for the userid.


```pawn
#include <amxmodx>
#include <engine>

public assign_marker_owner( marker, player )
{
	if( !is_valid_ent( marker ) || !is_user_connected( player ) )
		return;

	entity_set_edict( marker, EV_ENT_owner, player );
	// Keep the connection identity alongside the reusable owner slot.
	entity_set_int( marker, EV_INT_iuser1, get_user_userid( player ) );
}

public get_marker_owner( marker )
{
	if( !is_valid_ent( marker ) )
		return 0;

	new player = entity_get_edict( marker, EV_ENT_owner );

	if( !is_user_connected( player ) )
		return 0;

	if( get_user_userid( player ) != entity_get_int( marker, EV_INT_iuser1 ) )
		return 0;

	return player;
}
```

Decide separately what happens to the marker on disconnect: remove it, transfer it or leave it neutral. Do not overwrite another object’s `iuser*` fields. The `owner` field can also affect collisions, so it is not always suitable for arbitrary metadata.

Sources: [`get_user_userid`](https://www.amxmodx.org/api/amxmodx/get_user_userid), [`entity_set_edict`](https://www.amxmodx.org/api/engine/entity_set_edict).

<a id="private-data"></a>
## Private data: check the fields and offsets

Fields such as `pev_origin` live in the engine structure. `pdata` accesses fields in the game DLL’s C++ object. This needs the entity class, field layout and platform adjustment to match. An entity merely existing is not enough for safe access.

- With `get_pdata_int()` and `get_pdata_float()`, one offset step means 4 bytes.
- Byte-access functions such as `get_pdata_byte()` and `get_pdata_ent()` take offsets in bytes.
- `linuxdiff` is added in the same units as that function’s offset. The usual default adjustment does not suit every class.
- Keep byte offsets, cell offsets and Linux adjustments from different tables separate. Check which access method and class each number belongs to first.


```pawn
#include <amxmodx>
#include <fakemeta>

public inspect_active_item( player )
{
	if( !is_user_connected( player ) || pev_valid( player ) != 2 )
		return;

	new item = get_ent_data_entity( player, "CBasePlayer", "m_pActiveItem" );
	server_print( "Active item entity: %d", item );
}
```

**CS 1.6 gamedata example.** `get_ent_data_*` finds the named field in AMXX gamedata and checks its declared type. That keeps numeric offsets out of the plugin, but the gamedata must still match the game, and the class must match the field. A missing pointer can return `-1`; validate the returned entity before reading from it.

Sources: [`get_pdata_int`](https://www.amxmodx.org/api/fakemeta/get_pdata_int), [`get_pdata_ent`](https://www.amxmodx.org/api/fakemeta/get_pdata_ent), [`get_ent_data_entity`](https://www.amxmodx.org/api/fakemeta/get_ent_data_entity).

<a id="reapi-members"></a>
## Read named fields through ReAPI

**CS 1.6 + ReAPI + ReGameDLL_CS.** `get_member()` reads named game DLL fields, while `get_entvar()` reads engine fields. Both accept an entity index, but access different data. Below, we read the player’s money and health this way.


```pawn
#include <amxmodx>
#include <reapi>

public inspect_account( player )
{
	if( !is_user_connected( player ) )
		return;

	new money = get_member( player, m_iAccount );
	new Float:health = get_entvar( player, var_health );
	server_print( "Money: %d, health: %.1f", money, health );
}
```

When a change should run game logic, use the function for that operation. For example, `rg_add_account()` updates money through the intended API. Directly overwriting `m_iAccount` bypasses the game function’s work, even when a readable name replaces the numeric offset.

Sources: [ReAPI members](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll.inc), [ReAPI entvars](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_engine.inc).

<a id="weapons-spawn-and-death"></a>
## Giving weapons, respawning and death

**CS 1.6.** A weapon has an entity index and a separate `CSW_*` ID. Reserve ammunition and magazine ammunition are separate too. Below, we give an AK-47 through game logic, check that it succeeded, then set reserve ammo.


```pawn
#include <amxmodx>
#include <fun>
#include <cstrike>

public give_ak47( player )
{
	if( !is_user_alive( player ) )
		return 0;

	new weapon = give_item( player, "weapon_ak47" );

	if( weapon > 0 )
		cs_set_user_bpammo( player, CSW_AK47, 90 );

	return weapon;
}
```

If the player already has a primary weapon, decide separately what happens to it: one `give_item()` call does not define a full loadout policy. AK-47 reserve ammo belongs to a shared ammunition type that other weapons may use too.

For **CS** respawning, use `ExecuteHamB( Ham_CS_RoundRespawn, player )` or, with ReAPI, `rg_round_respawn`. These follow game behavior more closely than setting health or using a generic spawn. First check team, connection, alive/dead state and round rules.

For death, choose the operation that fits the intended effect. Setting health to zero, `user_kill()`, applying damage and calling `Ham_Killed` affect damage hooks, kill attribution and game rules differently. Account for those effects before replacing one with another.

Sources: [`give_item`](https://www.amxmodx.org/api/fun/give_item), [`cs_set_user_bpammo`](https://www.amxmodx.org/api/cstrike/cs_set_user_bpammo), [Ham functions](https://www.amxmodx.org/api/ham_const), [`user_kill`](https://www.amxmodx.org/api/amxmodx/user_kill).
