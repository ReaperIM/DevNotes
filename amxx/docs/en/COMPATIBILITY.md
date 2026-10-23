# Versions and compatibility

[EN](../en/COMPATIBILITY.md) | [UK](../uk/COMPATIBILITY.md) | [RU](../ru/COMPATIBILITY.md)

[Contents](../../README.md)

Found a useful example, but it will not compile or load on the server? Start with the AMXX version it was written for and what else it needs: an include, a module, another plugin or a particular game.

- [Why these notes start from AMXX 1.9](#baseline)
- [1.8.2, 1.9 and 1.10](#versions)
- [What AMXX, the game DLL and ReAPI each do](#layers)
- [Why an `.inc` file may not be enough](#includes)
- [What to revisit in old examples](#legacy)
- [Why an offset from a table needs checking](#game-data)
- [It compiled. What else needs testing?](#verification)

<a id="baseline"></a>

## Why these notes start from AMXX 1.9

The examples target AMXX 1.9 or newer. When that is not enough, the topic names the extra dependency. This is a common starting point for the notes; many of the useful functions here appeared before 1.9.

The language here is the Pawn shipped with AMXX. SourceMod has its own SourcePawn and its own set of functions. An example using `methodmap`, `enum struct` or an `int` declaration cannot simply be copied into AMXX, even if the rest looks familiar.

[AMXX scripting](https://wiki.alliedmods.net/Category:Scripting_(AMX_Mod_X)) | [SourcePawn](https://wiki.alliedmods.net/Introduction_to_SourcePawn_1.7)

<a id="versions"></a>

## 1.8.2, 1.9 and 1.10

An old example may use AMXX 1.8.2 while a downloaded include already declares functions from a newer version. Check the compiler, the includes folder and AMXX on the server separately. Updating one folder does not update the other parts.

| Version | What to check |
| --- | --- |
| 1.8.2 | When bringing newer code over, check for the natives, flags and forwards it uses. The older AMXX may not have them. |
| 1.9 | The starting point for these notes. It has cvar bindings, callbacks after configs, convenient player filtering and `client_disconnected()`. |
| 1.10 | For a particular function or fix, check the build number and changelog. Sometimes “I have 1.10” is not enough detail. |

AMXX publishes new builds within the same branch. Check the official download page for the number you need: a number once written in these notes would soon stop being the latest.

[1.9 notes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes) | [AMXX builds](https://www.amxmodx.org/downloads-new.php)

<a id="layers"></a>

## What AMXX, the game DLL and ReAPI each do

Several parts run on the server, each with its own job. A native, a function AMXX calls from its core, a module or another plugin, may need one of these parts. The AMXX version alone therefore does not tell us whether a plugin has everything it needs.

| Server part | What it does |
| --- | --- |
| HLDS / ReHLDS | The GoldSrc server engine itself. |
| Game DLL / ReGameDLL | Sets the game rules and the behavior of game entities. |
| Metamod | Loads server plugins, including AMXX. |
| AMXX | Runs Pawn plugins and gives them access to core and module functions. |
| ReAPI | An extra AMXX module that exposes supported ReHLDS/ReGameDLL interfaces. |

Check which interface a particular ReAPI function needs. Installing the module alone does not make code for one game work in every other mod.

[ReAPI](https://github.com/rehlds/ReAPI) | [AMXX modules](https://www.amxmodx.org/api/)

<a id="includes"></a>

## Why an `.inc` file may not be enough

Included an `.inc` file, compiled the plugin, then got an unknown-native error on the server? An include can contain native declarations, constants and ready-to-use `stock` functions. A native declaration tells the compiler how to call the function. A module or another plugin still has to provide that function on the server.

Use includes matching the installed module or API version. Copying a declaration or changing `AMXX_VERSION_NUM` by hand will not make a missing function appear. Use `#if` to choose a different working implementation when one actually exists. More in [plugin APIs and modules](11-plugin-api-and-modules.md).

[Official includes](https://github.com/alliedmodders/amxmodx/tree/master/plugins/include)

<a id="legacy"></a>

## What to revisit in old examples

Old code often has things worth simplifying. First understand what a particular call does, then replace it: similar names can still mean different behavior.

- Look at `client_disconnected()` in place of `client_disconnect()`: it covers more disconnect cases. Also check when your cleanup needs to happen.
- If `get_cvar_*()` is called repeatedly by name, keep the pcvar or bind the value with `bind_pcvar_*()`. Decide whether changes to the cvar must reach the plugin immediately.
- `register_event_ex()` uses flag constants in place of the string in `register_event()`. You still need to understand and read the event data correctly.
- `client_print_color()` may replace an old color-chat wrapper when it supports the game and colors you need.
- When reading a whole file, sequential reading is more convenient than repeated `read_file()` calls with line numbers.
- For a raw pdata offset, check gamedata or a named ReAPI member. Using ReAPI also means the server needs that dependency.

[Players](04-plugin-lifecycle-and-players.md) | [Commands and cvars](05-commands-cvars-and-configs.md) | [Hooks](06-events-hooks-and-messages.md)

[1.9 API changes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes)

<a id="game-data"></a>

## Why an offset from a table needs checking

An example using `cstrike`, CS weapon IDs or a Ham class may be written for a particular game. With private data, the entity's internal fields, the game DLL build and platform matter too. Even the offset units and platform adjustment depend on the function used to read the field.

Check a number from an old table against the right game DLL and access method first. This matters especially before writing: the wrong offset means changing a different field. The [entity notes](08-entities-and-gameplay.md) cover these access methods separately.

[Fakemeta](https://www.amxmodx.org/api/fakemeta) | [ReAPI members](https://github.com/rehlds/ReAPI/tree/master/reapi/extra/amxmodx/scripting/include)

<a id="verification"></a>

## It compiled. What else needs testing?

Compile each standalone Pawn block with the intended compiler and includes. This checks the syntax and whether calls match the declared functions.

Then use the server: does the plugin load, does the event arrive at the right time, is a disconnected player's data cleared, and what happens when a menu is cancelled or saving fails? The compiler cannot check whether a sound is in the folder or the module providing a native is installed. A hook's return value also needs testing in the game itself. More in [debugging](12-debugging-and-performance.md).

[Compiling](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))
