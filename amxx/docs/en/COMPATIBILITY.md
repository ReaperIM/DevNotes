# Versions and compatibility

[EN](../en/COMPATIBILITY.md) | [UK](../uk/COMPATIBILITY.md) | [RU](../ru/COMPATIBILITY.md)

[Contents](../../README.md)

Choose the API contract and server dependencies before reusing a snippet.

- [What the baseline means](#baseline)
- [1.8.2, 1.9 and 1.10](#versions)
- [AMXX, game DLL and ReAPI are different layers](#layers)
- [An include is a compile-time contract](#includes)
- [Useful updates to old snippets](#legacy)
- [Entity fields depend on the game and build](#game-data)
- [What compilation proves](#verification)

<a id="baseline"></a>

## What the baseline means

Examples target AMXX 1.9 or newer unless a section states an additional dependency. This is an API baseline, not a claim that every listed feature first appeared in 1.9. The language is the Pawn dialect shipped with AMXX.

SourceMod uses SourcePawn and a different runtime/API. Its `methodmap`, `enum struct` or `int` declaration examples cannot simply be copied into an AMXX plugin.

[AMXX scripting](https://wiki.alliedmods.net/Category:Scripting_(AMX_Mod_X)) | [SourcePawn](https://wiki.alliedmods.net/Introduction_to_SourcePawn_1.7)

<a id="versions"></a>

## 1.8.2, 1.9 and 1.10

Old snippets often assume 1.8.2, while current includes expose later functions and callback signatures. Check the compiler, the include set and the server runtime as separate versions.

| Branch | Practical meaning here |
| --- | --- |
| 1.8.2 | Legacy target. Later natives, flags and forwards must be reviewed before reuse. |
| 1.9 | Baseline for these notes: cvar bindings, config forwards, filtered player helpers and modern disconnect handling are available. |
| 1.10 | Check the exact build and changelog for later APIs and fixes. A branch number alone does not identify a build. |

AMXX publishes rolling builds. Use the official download/build pages for current numbers rather than treating a hard-coded number in notes as latest.

[1.9 notes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes) | [AMXX builds](https://www.amxmodx.org/downloads-new.php)

<a id="layers"></a>

## AMXX, game DLL and ReAPI are different layers

The AMXX version does not describe the whole server. A native can require a module, a particular game DLL or an API exported by another plugin.

| Layer | Role |
| --- | --- |
| HLDS / ReHLDS | GoldSrc server engine. |
| Game DLL / ReGameDLL | Game rules and game-specific entity behavior. |
| Metamod | Loads server plugins such as AMXX. |
| AMXX | Executes Pawn plugins and exposes natives through its core and modules. |
| ReAPI | Additional AMXX module with APIs for supported ReHLDS/ReGameDLL interfaces. |

Check which interface the particular ReAPI function requires. Installing ReAPI does not make all game-specific examples portable to every mod.

[ReAPI](https://github.com/rehlds/ReAPI) | [AMXX modules](https://www.amxmodx.org/api/)

<a id="includes"></a>

## An include is a compile-time contract

An `.inc` can declare natives, constants and stocks. Declaring a native makes compilation possible; its provider must still be available when the plugin loads.

Use includes matching the deployed module/API version. Copying a declaration or forcing `AMXX_VERSION_NUM` does not add the native implementation. Use `#if` for a genuine alternate code path, not to hide an unmet requirement. [Plugin APIs and modules](11-plugin-api-and-modules.md).

[Official includes](https://github.com/alliedmodders/amxmodx/tree/master/plugins/include)

<a id="legacy"></a>

## Useful updates to old snippets

A newer spelling helps only when its behavior matches the old code. These are places worth reviewing rather than automatic replacements.

- `client_disconnect()` -> `client_disconnected()`: broader disconnect coverage; review the cleanup timing.
- Repeated `get_cvar_*()` by name -> cached pcvar or `bind_pcvar_*()`; decide whether live changes must be reflected.
- `register_event()` string flags -> `register_event_ex()` flags; event data semantics remain important.
- Old color-chat wrappers -> built-in `client_print_color()` where its game support and color semantics fit.
- Repeated line-number `read_file()` -> sequential file API for a full scan.
- Raw pdata offsets -> verified gamedata or named ReAPI members when that dependency is appropriate.

[Players](04-plugin-lifecycle-and-players.md) | [Commands and cvars](05-commands-cvars-and-configs.md) | [Hooks](06-events-hooks-and-messages.md)

[1.9 API changes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes)

<a id="game-data"></a>

## Entity fields depend on the game and build

`cstrike`, CS weapon IDs, Ham classes and private data examples can be game-specific. Offset units and platform adjustments belong to the exact access function and class layout.

A numeric offset from an old table is not a verified field for another game DLL. Use the [entity notes](08-entities-and-gameplay.md) and verify the actual server dependency before writing private state.

[Fakemeta](https://www.amxmodx.org/api/fakemeta) | [ReAPI members](https://github.com/rehlds/ReAPI/tree/master/reapi/extra/amxmodx/scripting/include)

<a id="verification"></a>

## What compilation proves

Compile each independent Pawn block with the intended compiler and includes. Then test the relevant plugin behavior on the target server: loading, event timing, disconnects, menu cancellation and persistence errors.

A compile result proves syntax and declared signatures. It does not prove that a sound exists, a native provider is loaded or a hook returns at the right point in that game. [Debugging](12-debugging-and-performance.md).

[Compiling](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))
