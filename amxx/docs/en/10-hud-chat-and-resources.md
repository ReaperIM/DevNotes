# HUD, chat and resources

[EN](../en/10-hud-chat-and-resources.md) | [UK](../uk/10-hud-chat-and-resources.md) | [RU](../ru/10-hud-chat-and-resources.md)

[Contents](../../README.md)

Show text to the right player, update HUD without piling up old messages, and prepare sounds and models ahead of time. The examples use AMXX 1.9. Colored chat and `StatusIcon` here are specific to CS 1.6 / Condition Zero.

**In this chapter**

- [Colored chat: who receives it and where the color comes from](#colored-chat)
- [Pass player names through `%s`](#format-player-text)
- [The four classic HUD channels](#classic-hud)
- [Update and clear our HUD with a sync object](#hud-sync)
- [Short announcements with DHUD](#director-hud)
- [Build a game message in the required order](#user-messages)
- [Show the translation in the recipient’s language](#localized-output)
- [Precache a WAV using a path without `sound/`](#precache-sound)
- [Precache models and sprites before the map starts](#precache-models)
- [Register a download with `precache_generic()`](#generic-downloads)

<a id="colored-chat"></a>

## Colored chat: who receives it and where the color comes from

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print_color)

For CS 1.6 and Condition Zero, AMXX 1.9 includes `client_print_color()`. The first argument selects who sees the message; the second selects the team-color source. These are separate choices: `print_team_red` makes the team color red, but does not select Terrorists as recipients.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /colors", "command_colors" );
}

public command_colors( id )
{
	// Red selects the color, not recipients; only id gets the message.
	client_print_color( id, print_team_red, "^4[Notes] ^3Red ^1default" );
	return PLUGIN_HANDLED;
}
```

Only the player who entered the command sees this example's message. `^4` selects green, `^3` the chosen team color, and `^1` the default color. A first argument of `0` sends to everyone; select particular recipients separately. There is no need to include an old custom `ColorChat` implementation with the same native name.

<a id="format-player-text"></a>

## Pass player names through `%s`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print)

A player name may contain `%`, so the name must not become the format string itself. Keep the format fixed and pass the name as a separate `%s` argument. The same applies to console output, logs, HUD text and text read from files.

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
	// Any % characters in the name must stay text.
	client_print( id, print_chat, "Your name: %s", name );
	return PLUGIN_HANDLED;
}
```

A name such as `100% ready` stays ordinary text here. Changing the call to `client_print( id, print_chat, name )` would let the name be interpreted as a format. `%s` does not remove chat color or control codes, though; handle those separately if they also need restricting.

<a id="classic-hud"></a>

## The four classic HUD channels

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_hudmessage)

Classic HUD gives each client four channels, `1..4`. A new message replaces the old one on the same channel; `-1` lets the engine choose automatically. `set_hudmessage()` sets up the next display rather than creating a permanent UI element. Here we show health on channel `2`.

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

The `-1.0` position centers the text horizontally, while `0.2` puts it near the top. Settings are shared across plugins, so set them immediately before showing the message. A fixed channel can help with updates, but other plugins use it too. A longer hold time only keeps the text visible; it does not refresh the health value.

<a id="hud-sync"></a>

## Update and clear our HUD with a sync object

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/ShowSyncHudMsg)

For text that needs updating, create a sync object with `CreateHudSyncObj()`. It remembers the previous message so the next `ShowSyncHudMsg()` can replace our old output. Create it once; one object can serve the same kind of status for several players.

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

Here `/status` shows the status and `/clearstatus` clears it through the same object. `ShowSyncHudMsg()` chooses the channel itself, ignoring the number in `set_hudmessage()`. The object does not add channels or reserve screen space. `ClearSyncHud()` clears the tracked message without taking another channel for the clear operation.

<a id="director-hud"></a>

## Short announcements with DHUD

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/show_dhudmessage)

Director HUD works well for a short announcement such as a practice starting soon. It supports up to eight simultaneous messages, with new ones replacing old ones in send order. Keep this in mind when repeating a display: DHUD does not offer a channel where we simply replace one label.

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

Text is limited to 128 characters. Calling it every frame stacks announcements or pushes older ones out. A sync object cannot clear one particular DHUD message, so classic synchronized HUD is a better fit for a constantly changing status.

<a id="user-messages"></a>

## Build a game message in the required order

[AMX Mod X API](https://www.amxmodx.org/api/messages/message_begin)

Build a game message between `message_begin()` and `message_end()`. The client expects specific field types in a specific order, so the `write_*` calls are not interchangeable. This CS example sends `StatusIcon`: an enabled byte, the sprite name and three color bytes.

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

	// Checks are done; finish this message through message_end().
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

Only the displayed icon changes; server-side buying permission stays the same. Check everything before `message_begin()` so we do not return from an unfinished message. `MSG_ONE_UNRELIABLE` allows this cosmetic update to be lost. Reliable delivery is no reason to send every frame either: send on changes or limit the refresh rate. [`StatusIcon` layout](https://wiki.alliedmods.net/Half-Life_1_Game_Events#StatusIcon).

<a id="localized-output"></a>

## Show the translation in the recipient’s language

[AMX Mod X API](https://www.amxmodx.org/api/lang/register_dictionary)

Register a dictionary from `addons/amxmodx/data/lang/` to send each player a message in their language. After `%L`, pass the language selector, translation key and its arguments. Here the recipient's `id` selects the language, then the name is inserted into that translation. `LANG_SERVER` would select the server language instead.

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

Translations in `notes.txt` must keep matching placeholders, `%s` in this case. In a custom send loop, translate for each player; one finished translation reused for everyone ignores their language choices. Multibyte text must still fit the buffer and the client's message limits.

```ini
[en]
NOTE_WELCOME = Welcome, %s!

[uk]
NOTE_WELCOME = Вітаємо, %s!

[ru]
NOTE_WELCOME = Добро пожаловать, %s!
```

<a id="precache-sound"></a>

## Precache a WAV using a path without `sound/`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_sound)

Register a WAV with `precache_sound()` inside `plugin_precache()` before using it. The path is relative to `sound/`, without that prefix: `buttons/button9.wav` here. Pass the same path to `emit_sound()`, which plays the sound from the specified entity.

```pawn
#include <amxmodx>

new const NOTE_SOUND[] = "buttons/button9.wav";

public plugin_precache()
{
	// Register before the map starts; the path is already relative to sound/.
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

Player `id` is the sound source here, not its only listener. Nearby clients can hear it too. This example uses a standard Half-Life file. Put custom sounds on the server before the map loads, keeping identical path spelling and letter case in the download tree.

<a id="precache-models"></a>

## Precache models and sprites before the map starts

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_model)

Call `precache_model()` for a model or sprite to get its resource index for the current map. The path starts at the mod directory: `models/...mdl` for a model or `sprites/...spr` for a sprite. Keep the index for effects that need it, but do not treat it as a permanent ID across maps.

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

Precaching happens before `plugin_init()` and final configs. A model selected by a late cvar change cannot simply be precached in the middle of a map. Choose resources ahead of time or register a small known set, since the engine's resource tables have limits.

<a id="generic-downloads"></a>

## Register a download with `precache_generic()`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_generic)

Use `precache_generic()` when a file needs to be registered for client download. Unlike a sound used with `emit_sound()`, it takes the full mod-relative path, including `sound/` for an MP3. The file must exist and be supported by the client. Here we check that optional music is present first.

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

Registration does not play the music or guarantee every client downloaded it. Use fixed or explicitly allowed paths and add only needed files; keep matching files on FastDL when using it. Registering a WAV generically does not put it in the engine sound table, so this is not enough for `emit_sound()`.
