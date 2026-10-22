# HUD, chat and resources

[EN](../en/10-hud-chat-and-resources.md) | [UK](../uk/10-hud-chat-and-resources.md) | [RU](../ru/10-hud-chat-and-resources.md)

[Contents](../../README.md)

Output that reaches the intended client, survives repeated updates and uses the correct resource paths. Examples use AMXX 1.9. Colored chat and the `StatusIcon` example are specific to CS 1.6 / Condition Zero.

**In this chapter**

- [Colored chat: recipient and color source are different](#colored-chat)
- [Player text is data, not a format string](#format-player-text)
- [Classic HUD has four channels per client](#classic-hud)
- [Update and clear one HUD area with a sync object](#hud-sync)
- [DHUD is a stack, not a reusable channel](#director-hud)
- [A user message must match its protocol](#user-messages)
- [Translate for the actual recipient](#localized-output)
- [WAV sounds use paths relative to `sound/`](#precache-sound)
- [Precache models and keep map-local indexes](#precache-models)
- [Download registration is not sound precaching](#generic-downloads)

<a id="colored-chat"></a>

## Colored chat: recipient and color source are different

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print_color)

In CS 1.6 and Condition Zero, `client_print_color()` is built into AMXX 1.9. The first argument chooses who receives the text; the second chooses the team-color source. Setting the sender to `print_team_red` does not limit delivery to Terrorists.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /colors", "command_colors" );
}

public command_colors( id )
{
	client_print_color( id, print_team_red, "^4[Notes] ^3Red ^1default" );
	return PLUGIN_HANDLED;
}
```

Only the caller receives this message. `^4` is green, `^3` is the selected team color and `^1` returns to the default color. Recipient `0` sends to everyone; filtering recipients is a separate operation. Do not include an old custom `ColorChat` implementation with the same native name.

<a id="format-player-text"></a>

## Player text is data, not a format string

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print)

A player name can contain `%` and other formatting characters. Keep the format string fixed and pass the name through `%s`. The same rule applies to console output, logs, HUD messages and text loaded from a file.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /name", "command_name" );
}

public command_name( id )
{
	new name[32];
	get_user_name( id, name, charsmax( name ) );
	client_print( id, print_chat, "Your name: %s", name );
	return PLUGIN_HANDLED;
}
```

A name such as `100% ready` stays text. Do not replace the final call with `client_print( id, print_chat, name )`. Formatting safety does not remove chat color/control codes; normalize those separately when displaying arbitrary text in colored chat.

<a id="classic-hud"></a>

## Classic HUD has four channels per client

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_hudmessage)

`set_hudmessage()` sets parameters for the next output, not a permanent HUD object. Channels `1..4` overwrite the previous message in that channel. Channel `-1` chooses automatically; a fixed channel can make periodic text more stable, but other plugins still use the same client channels.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /hud", "command_hud" );
}

public command_hud( id )
{
	set_hudmessage( 0, 180, 255, -1.0, 0.2, 0, 0.0, 3.0, 0.1, 0.2, 2 );
	show_hudmessage( id, "Health: %d", get_user_health( id ) );
	return PLUGIN_HANDLED;
}
```

`-1.0` centers the horizontal position, while `0.2` places the text near the top. Set parameters immediately before showing the message: these settings are shared across plugins. A longer hold time does not update the health automatically.

<a id="hud-sync"></a>

## Update and clear one HUD area with a sync object

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/ShowSyncHudMsg)

A sync object tracks the previous message for a logical HUD area. Reusing it replaces your old message cleanly even when auto channels are used. Create it once; one object can serve the same kind of display for several clients.

```pawn
#include <amxmodx>

new g_status_hud;

public plugin_init()
{
	g_status_hud = CreateHudSyncObj();
	register_clcmd( "say /status", "command_status" );
	register_clcmd( "say /clearstatus", "command_clear_status" );
}

public command_status( id )
{
	set_hudmessage( 100, 220, 100, 0.02, 0.25, 0, 0.0, 5.0 );
	ShowSyncHudMsg( id, g_status_hud, "Players: %d", get_playersnum() );
	return PLUGIN_HANDLED;
}

public command_clear_status( id )
{
	ClearSyncHud( id, g_status_hud );
	return PLUGIN_HANDLED;
}
```

`ShowSyncHudMsg()` manages the channel itself and ignores the channel chosen by `set_hudmessage()`. The object does not reserve a screen region or create more client channels. `ClearSyncHud()` clears the tracked display without allocating a new message channel.

<a id="director-hud"></a>

## DHUD is a stack, not a reusable channel

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/show_dhudmessage)

Director HUD is convenient for a short announcement. It supports up to eight simultaneous messages; new ones overwrite old ones in send order. You cannot clear a particular DHUD message with a sync object.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /notice", "command_notice" );
}

public command_notice( id )
{
	set_dhudmessage( 255, 180, 20, -1.0, 0.3, 0, 0.0, 3.0, 0.1, 0.2 );
	show_dhudmessage( id, "Practice starts soon" );
	return PLUGIN_HANDLED;
}
```

The API caps the message at 128 characters. Repeated calls every frame will stack or replace announcements, not update one persistent label. Use classic HUD with synchronization for a frequently changing status.

<a id="user-messages"></a>

## A user message must match its protocol

[AMX Mod X API](https://www.amxmodx.org/api/messages/message_begin)

`message_begin()` and `message_end()` enclose one protocol message. The `write_*` order and types must match the game message exactly. This CS example sends a `StatusIcon`: enabled byte, sprite name, then three color bytes.

```pawn
#include <amxmodx>

new g_status_icon;

public plugin_init()
{
	g_status_icon = get_user_msgid( "StatusIcon" );
	register_clcmd( "say /icon", "command_icon" );
}

public command_icon( id )
{
	if( !g_status_icon )
		return PLUGIN_HANDLED;

	message_begin( MSG_ONE_UNRELIABLE, g_status_icon, _, id );
	write_byte( 1 );
	write_string( "buyzone" );
	write_byte( 0 );
	write_byte( 255 );
	write_byte( 0 );
	message_end();
	return PLUGIN_HANDLED;
}
```

This changes the displayed icon, not server-side buying permission. Do all validation before `message_begin()` and never return halfway through a message. Unreliable delivery may drop cosmetic updates; reliable delivery is not a reason to flood the channel. Send on meaningful changes or at a controlled refresh rate. [`StatusIcon` layout](https://wiki.alliedmods.net/Half-Life_1_Game_Events#StatusIcon).

<a id="localized-output"></a>

## Translate for the actual recipient

[AMX Mod X API](https://www.amxmodx.org/api/lang/register_dictionary)

Register a dictionary from `addons/amxmodx/data/lang/`. `%L` takes a language selector, then the key, then the key's format arguments. Passing the recipient ID selects their language; `LANG_SERVER` explicitly selects the server language.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_dictionary( "notes.txt" );
	register_clcmd( "say /welcome", "command_welcome" );
}

public command_welcome( id )
{
	new name[32];
	get_user_name( id, name, charsmax( name ) );
	client_print( id, print_chat, "%L", id, "NOTE_WELCOME", name );
	return PLUGIN_HANDLED;
}
```

Provide `notes.txt` with matching placeholders in every language. For a custom loop, format per recipient instead of translating once and reusing the result for everyone. Multibyte text still has to fit the output buffer and the game client's message limits.

```ini
[en]
NOTE_WELCOME = Welcome, %s!

[uk]
NOTE_WELCOME = Вітаємо, %s!

[ru]
NOTE_WELCOME = Добро пожаловать, %s!
```

<a id="precache-sound"></a>

## WAV sounds use paths relative to `sound/`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_sound)

`precache_sound()` registers a WAV in the engine sound table during `plugin_precache()`. Its path omits the `sound/` prefix. `emit_sound()` uses that same relative path and plays a positional sound from an entity.

```pawn
#include <amxmodx>

new const NOTE_SOUND[] = "buttons/button9.wav";

public plugin_precache()
{
	precache_sound( NOTE_SOUND );
}

public plugin_init()
{
	register_clcmd( "say /sound", "command_sound" );
}

public command_sound( id )
{
	if( !is_user_alive( id ) )
		return PLUGIN_HANDLED;

	emit_sound( id, CHAN_ITEM, NOTE_SOUND, VOL_NORM, ATTN_NORM, 0, PITCH_NORM );
	return PLUGIN_HANDLED;
}
```

The player index is the sound source, not an exclusive recipient: nearby clients can hear it. The sample uses a standard Half-Life sound. Custom files must exist on the server before map load, with the same spelling and case used in the download tree.

<a id="precache-models"></a>

## Precache models and keep map-local indexes

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_model)

Models and sprites use paths such as `models/...mdl` and `sprites/...spr`, relative to the mod directory. `precache_model()` returns an engine resource index. Cache it for effects that need the index, but do not save it as a permanent resource ID across maps.

```pawn
#include <amxmodx>

new g_beam_sprite;

public plugin_precache()
{
	g_beam_sprite = precache_model( "sprites/white.spr" );
}

public plugin_init()
{
	server_print( "Beam sprite index on this map: %d", g_beam_sprite );
}
```

The precache phase comes before `plugin_init()` and final config execution. A model selected by a later cvar change cannot simply be precached on demand mid-map. Choose the required resources before this phase or register a small known set; engine resource tables are finite.

<a id="generic-downloads"></a>

## Download registration is not sound precaching

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_generic)

`precache_generic()` registers a file for download without adding a WAV to the engine sound table. Use the full mod-relative path here, including `sound/` for an MP3. The file still needs to exist and be supported by the client that uses it.

```pawn
#include <amxmodx>

public plugin_precache()
{
	new const music_path[] = "sound/notes/intro.mp3";

	if( !file_exists( music_path ) )
	{
		log_amx( "Optional music is missing: %s", music_path );
		return;
	}

	precache_generic( music_path );
}
```

This only registers an optional file; it does not play music or guarantee that every client downloaded it. Keep custom paths fixed or allowlisted, maintain matching FastDL files when used, and ship only resources the feature actually needs. Registering a sound generically does not make it usable in `emit_sound()`.
