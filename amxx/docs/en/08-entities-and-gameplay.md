# Entities and gameplay

[English](../en/08-entities-and-gameplay.md) | [Українська](../uk/08-entities-and-gameplay.md) | [Русский](../ru/08-entities-and-gameplay.md) | [Contents](../../README.md)

Entity lifetime, typed fields, triggers and the boundary between changing data and calling game behavior. Each code block is independent; functions with arguments show reusable operations rather than automatic command registration.

- [An index is not an entity identity](#entity-identity)
- [Read entvars with the matching type](#entvars-and-vectors)
- [Move through an origin API](#moving-entities)
- [Create a real class, then configure it](#create-and-spawn)
- [Touch: validate both sides and consume once](#touch-and-consume)
- [Think uses an absolute game time](#think-scheduling)
- [Models need precache and bounds](#models-and-precache)
- [Search narrowly and remove only your entities](#search-and-remove)
- [An owner slot can be reused too](#owner-lifetime)
- [Private data: units, class and platform matter](#private-data)
- [ReAPI named members, optional](#reapi-members)
- [Weapons, respawn and death are game operations](#weapons-spawn-and-death)

<a id="entity-identity"></a>
## An index is not an entity identity

Entity indices are reused after removal. A valid index saved earlier may now refer to another entity. For delayed work, save `pev_serial` together with the index and verify both before using the entity.


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

Save the serial when capturing the entity, not when the delayed callback begins. `pev_valid` returns `0` for invalid, `1` for an existing edict and `2` when private data also exists. `pev_valid == 2` permits private-data access in principle; it does not prove that the entity belongs to the class expected by an offset.

Sources: [pev_valid](https://www.amxmodx.org/api/fakemeta/pev_valid), [pev_serial](https://www.amxmodx.org/api/fakemeta/pev_serial).

<a id="entvars-and-vectors"></a>
## Read entvars with the matching type

`pev` has different calling forms for integers, floats, strings and vectors. A Pawn cell returned directly is not automatically a `Float`. Here float fields and vectors are read into output variables.


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

Vectors use three `Float` cells, not three rounded integer coordinates. Velocity is a direction-and-speed vector; angles use a different field and meaning. With Engine, the equivalent families are `entity_get_int`, `entity_get_float`, `entity_get_vector`, `entity_get_string` and `entity_get_edict`.

Sources: [pev](https://www.amxmodx.org/api/fakemeta/pev), [entity_get_vector](https://www.amxmodx.org/api/engine/entity_get_vector).

<a id="moving-entities"></a>
## Move through an origin API

Changing an entity position also requires updating its engine linkage. Use `entity_set_origin` or `EngFunc_SetOrigin` instead of treating `pev_origin` as an ordinary field assignment.


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

This moves the entity by 32 units on Z. It does not check whether the destination is inside a wall. Player teleportation additionally needs a suitable hull trace and a deliberate policy for velocity and ground state; a position setter alone does not find a safe destination.

Sources: [entity_set_origin](https://www.amxmodx.org/api/engine/entity_set_origin), [engine function constants](https://www.amxmodx.org/api/fakemeta_const).

<a id="create-and-spawn"></a>
## Create a real class, then configure it

`create_entity` needs a class implemented by the game DLL. A custom classname string does not create a new C++ class. For a simple custom trigger, create `info_target`, run its spawn, then assign the marker name and trigger properties.


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

The result is an invisible trigger, not a visible pickup model. For game entities configured by keyvalues, set the required keyvalues before `DispatchSpawn`; spawn may overwrite fields or remove an unusable entity. Order depends on that class, so do not reuse the trigger recipe unchanged for doors, weapons or brush entities.

Sources: [create_entity](https://www.amxmodx.org/api/engine/create_entity), [DispatchSpawn](https://www.amxmodx.org/api/engine/DispatchSpawn), [DispatchKeyValue](https://www.amxmodx.org/api/engine/DispatchKeyValue).

<a id="touch-and-consume"></a>
## Touch: validate both sides and consume once

A trigger may touch a player repeatedly. This handler accepts only living players and removes the custom marker before applying its one-time reward. It is meant for the `notes_marker` trigger from the previous section.


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

	remove_entity( marker );
	new health = get_user_health( player );

	if( health < 100 )
		set_user_health( player, min( health + 10, 100 ) );
}
```

After removal, do not read fields from `marker`. The example consumes the marker even when the player already has 100 health; move that policy check before removal if full-health players should leave it behind. An `owner` field is game data, not an automatic access-control rule.

Sources: [register_touch](https://www.amxmodx.org/api/engine/register_touch), [remove_entity](https://www.amxmodx.org/api/engine/remove_entity), [set_user_health](https://www.amxmodx.org/api/fun/set_user_health).

<a id="think-scheduling"></a>
## Think uses an absolute game time

`nextthink` is a timestamp, not an interval. Schedule the first call with `get_gametime() + delay`; schedule the next call again if the entity should keep thinking.


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

This schedules a roughly one-second cadence; execution follows server frames, not a hard real-time timer. Keep work proportional to the number of active entities. Do not scan every player or every entity from every thinker when the needed data can be saved at creation or updated by events.

Sources: [register_think](https://www.amxmodx.org/api/engine/register_think), [get_gametime](https://www.amxmodx.org/api/amxmodx/get_gametime).

<a id="models-and-precache"></a>
## Models need precache and bounds

**CS 1.6 asset path.** Precache a model in `plugin_precache`, then assign that same resource to the entity. Set the desired collision bounds after the model, because model assignment can affect bounds.


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
	entity_set_size( marker, Float:{ -16.0, -16.0, -16.0 }, Float:{ 16.0, 16.0, 16.0 } );
}
```

The grenade model does not make the entity a grenade: behavior still comes from its real class and callbacks. For a custom resource, verify its path and deploy it to the server. Do not build an unbounded precache list from arbitrary client input.

Sources: [precache_model](https://www.amxmodx.org/api/amxmodx/precache_model), [entity_set_model](https://www.amxmodx.org/api/engine/entity_set_model), [entity_set_size](https://www.amxmodx.org/api/engine/entity_set_size).

<a id="search-and-remove"></a>
## Search narrowly and remove only your entities

A custom classname makes cleanup explicit. Cache the next matching index before removing the current entity so the search does not depend on a freed entity.


```pawn
#include <amxmodx>
#include <engine>

public remove_markers()
{
	new entity = find_ent_by_class( 0, "notes_marker" );

	while( entity > 0 )
	{
		new next = find_ent_by_class( entity, "notes_marker" );
		remove_entity( entity );
		entity = next;
	}
}
```

Do this on a meaningful lifecycle event, such as your mode ending. A world-wide scan every frame is rarely justified. Do not remove players, worldspawn or all entities of a common game classname just to clean up a plugin feature.

Sources: [find_ent_by_class](https://www.amxmodx.org/api/engine/find_ent_by_class), [remove_entity](https://www.amxmodx.org/api/engine/remove_entity).

<a id="owner-lifetime"></a>
## An owner slot can be reused too

For a custom entity, save the owner player index and that connection’s userid. This prevents a reconnecting player in the same slot from inheriting an old marker. The example reserves `iuser1` for this purpose on your own entity.


```pawn
#include <amxmodx>
#include <engine>

public assign_marker_owner( marker, player )
{
	if( !is_valid_ent( marker ) || !is_user_connected( player ) )
		return;

	entity_set_edict( marker, EV_ENT_owner, player );
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

Decide separately whether disconnect should remove the marker, transfer it or leave it neutral. Do not overwrite `iuser*` fields belonging to game entities or another plugin. Entity ownership can also affect collision behavior, so it is not always suitable as an arbitrary metadata slot.

Sources: [get_user_userid](https://www.amxmodx.org/api/amxmodx/get_user_userid), [entity_set_edict](https://www.amxmodx.org/api/engine/entity_set_edict).

<a id="private-data"></a>
## Private data: units, class and platform matter

Entvars such as `pev_origin` belong to the engine structure. `pdata` accesses fields of the game DLL’s C++ object. A valid edict is not enough: the expected class layout and platform offset must match.

- `get_pdata_int` / `get_pdata_float` use 4-byte offset units.
- Byte-addressable natives such as `get_pdata_byte` or `get_pdata_ent` use byte offsets.
- `linuxdiff` is added to the offset in that native’s units; the common default is not correct for every class.
- Never mix a byte offset, a cell offset and a Linux adjustment from different tables.


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

**CS 1.6 gamedata example.** `get_ent_data_*` resolves a named member from AMXX gamedata and checks its declared type. It avoids baking raw offsets into the plugin, but still needs matching gamedata and the correct class. A missing pointer can return `-1`; validate the returned entity before reading it.

Sources: [get_pdata_int](https://www.amxmodx.org/api/fakemeta/get_pdata_int), [get_pdata_ent](https://www.amxmodx.org/api/fakemeta/get_pdata_ent), [get_ent_data_entity](https://www.amxmodx.org/api/fakemeta/get_ent_data_entity).

<a id="reapi-members"></a>
## ReAPI named members, optional

**CS 1.6 + ReAPI + ReGameDLL_CS.** `get_member` reads named game DLL members; `get_entvar` reads entvars. These are different data layers even though both functions accept an entity index.


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

Prefer a behavior-level native when the operation has side effects: for example `rg_add_account` for money updates instead of only overwriting `m_iAccount`. A named member removes a magic offset, but direct assignment still bypasses the logic that a game function would execute.

Sources: [ReAPI members](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll.inc), [ReAPI entvars](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_engine.inc).

<a id="weapons-spawn-and-death"></a>
## Weapons, respawn and death are game operations

**CS 1.6.** A weapon entity index is not its `CSW_*` ID, and reserve ammunition is not the weapon’s clip. Grant an item through game logic, check the result, then set the appropriate ammunition pool.


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

Define what happens to an existing primary weapon; a give call is not a complete loadout policy. AK-47 reserve ammo shares the CS ammunition pool used by other weapons of that ammo type.

For respawn, **CS-specific** `ExecuteHamB( Ham_CS_RoundRespawn, player )` or optional ReAPI `rg_round_respawn` is closer to game behavior than merely setting health or calling a generic spawn. Verify team, connected/dead state and round rules first.

For death, setting health to zero, `user_kill`, applying damage and calling `Ham_Killed` have different effects on damage hooks, attribution and game rules. Choose the operation you need; none is a universal substitute for the others.

Sources: [give_item](https://www.amxmodx.org/api/fun/give_item), [cs_set_user_bpammo](https://www.amxmodx.org/api/cstrike/cs_set_user_bpammo), [Ham functions](https://www.amxmodx.org/api/ham_const), [user_kill](https://www.amxmodx.org/api/amxmodx/user_kill).
