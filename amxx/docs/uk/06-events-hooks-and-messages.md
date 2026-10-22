# Події, hooks і повідомлення

[EN](../en/06-events-hooks-and-messages.md) | [UK](../uk/06-events-hooks-and-messages.md) | [RU](../ru/06-events-hooks-and-messages.md) | [Зміст](../../README.uk.md)

Робочі нотатки про момент виклику callback, доступні йому дані та сенс результату. Приклади незалежні; ігрові протоколи й окремі модулі позначені.

- [Обираємо потрібний рівень](#which-hook)
- [Фільтри подій і `read_data`](#filtered-events)
- [Події журналу мають власні аргументи](#log-events)
- [Змінюємо повідомлення, а не ігровий стан](#message-hooks)
- [Pre змінює виклик, post бачить його результат](#pre-and-post)
- [Ham: змінюємо параметри явно](#ham-parameters)
- [Ham: статус hook і результат функції](#ham-return)
- [Fakemeta використовує `FMRES_*`](#fakemeta-status)
- [Вмикаємо hooks за потреби](#hook-lifetime)
- [ReAPI hookchains, окремий модуль](#reapi-hookchains)

<a id="which-hook"></a>
## Обираємо потрібний рівень

Сповіщення про смерть, виклик `TakeDamage` і вихідне повідомлення описують різні моменти. Перехоплюй саме операцію, поведінку якої треба змінити; для спостереження за результатом достатньо події.

| API | Для чого | Дані |
| --- | --- | --- |
| `register_event_ex` | Спостереження за ігровими повідомленнями з фільтрами аргументів | `read_data` |
| `register_logevent` | Спостереження за розібраними подіями ігрового журналу | `read_logargv` |
| `register_message` | Читання, зміна або блокування вихідного повідомлення | `get_msg_arg_*`, `set_msg_arg_*` |
| Ham Sandwich | Перехоплення віртуальної функції сутності | Параметри callback, `SetHam*` |
| Fakemeta | Перехоплення engine / DLL forwards, доступних у модулі | Параметри callback, `FMRES_*` |
| ReAPI, окремий модуль | Функції API ReHLDS / ReGameDLL | Параметри hookchain, `HC_*` |

Перехоплення повідомлення змінює те, що отримає клієнт. Воно не скасовує шкоду, не відновлює сутність і не повертає назад ігрову логіку, що створила повідомлення.

Джерела: [AMXX events](https://www.amxmodx.org/api/amxmodx/register_event_ex), [message hooks](https://www.amxmodx.org/api/messages/register_message), [Ham](https://www.amxmodx.org/api/hamsandwich/RegisterHam).

<a id="filtered-events"></a>
## Фільтри подій і `read_data`

**CS 1.6.** `DeathMsg` містить убивцю, жертву, ознаку headshot і назву зброї. Фільтр `3=1` залишає лише сповіщення про вбивства в голову.


```pawn
#include <amxmodx>

public plugin_init()
{
	register_event_ex( "DeathMsg", "on_headshot", RegisterEvent_Global, "3=1" );
}

public on_headshot()
{
	new killer = read_data( 1 );
	new victim = read_data( 2 );
	new weapon[24];
	read_data( 4, weapon, charsmax( weapon ) );
	server_print( "Headshot: %d -> %d, %s", killer, victim, weapon );
}
```

Аргументи повідомлення починаються з `1`. Читай їх усередині callback: вони належать поточній події. Убивця може дорівнювати `0` для шкоди від світу, тому перевіряй його перед player natives. Типізовані прапорці за потреби поєднуються через `|`.

Джерела: [`register_event_ex`](https://www.amxmodx.org/api/amxmodx/register_event_ex), [`read_data`](https://www.amxmodx.org/api/amxmodx/read_data).

<a id="log-events"></a>
## Події журналу мають власні аргументи

**CS 1.6.** `Round_Start` є подією ігрового журналу. Вона зручна для дій на рівні раунду, які мають виконуватися один раз, а не після кожного spawn гравця.


```pawn
#include <amxmodx>

public plugin_init()
{
	register_logevent( "on_round_start", 2, "1=Round_Start" );
}

public on_round_start()
{
	new action[32];
	read_logargv( 1, action, charsmax( action ) );
	server_print( "Log action: %s", action );
}
```

Аргументи журналу починаються з `0`; тут аргумент `1` дорівнює `Round_Start`. `argsnum = 2` задає кількість розібраних аргументів журналу, а не параметрів callback. Потрібен `read_logargv`, а не `read_data`. `HLTV`, `Round_Start` і успішний spawn гравця відповідають різним етапам раунду.

Джерела: [`register_logevent`](https://www.amxmodx.org/api/amxmodx/register_logevent), [`read_logargv`](https://www.amxmodx.org/api/amxmodx/read_logargv).

<a id="message-hooks"></a>
## Змінюємо повідомлення, а не ігровий стан

**CS 1.6.** `HideWeapon` передає бітову маску HUD. Перехоплення додає біт прицілу, зберігаючи інші біти, вже встановлені грою або попереднім hook.


```pawn
#include <amxmodx>

public plugin_init()
{
	new message_id = get_user_msgid( "HideWeapon" );

	if( message_id )
		register_message( message_id, "on_hide_weapon" );
}

public on_hide_weapon( message_id, destination, receiver )
{
	new flags = get_msg_arg_int( 1 );
	set_msg_arg_int( 1, ARG_BYTE, flags | (1 << 6) );
	return PLUGIN_CONTINUE;
}
```

У message hook використовуй `get_msg_arg_*`, а не `read_data`. Повернення `PLUGIN_HANDLED` блокує повідомлення повністю. `receiver` може дорівнювати `0` для розсилки, тому це не завжди індекс гравця. Приклад працює лише під час надсилання `HideWeapon` і сам не надсилає клієнту негайне оновлення.

Джерела: [`register_message`](https://www.amxmodx.org/api/messages/register_message), [`set_msg_arg_int`](https://www.amxmodx.org/api/messages/set_msg_arg_int), [CS HUD flags](https://github.com/rehlds/ReGameDLL_CS/blob/master/regamedll/dlls/cdll_dll.h).

<a id="pre-and-post"></a>
## Pre змінює виклик, post бачить його результат

Pre підходить для зміни параметрів або блокування оригінального виклику. У post оригінал уже встиг створити потрібний стан. Навіть після spawn потрібна перевірка alive: сам виклик callback не гарантує, що гравець живий.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_Spawn, "player", "on_player_spawn_post", 1 );
}

public on_player_spawn_post( player )
{
	if( !is_user_alive( player ) )
		return HAM_IGNORED;

	client_print( player, print_chat, "Spawn complete" );
	return HAM_IGNORED;
}
```

У post зміна вхідного параметра не поверне оригінальну операцію назад. `RegisterHam` перехоплює таблицю віртуальних функцій класу, а не лише поточних гравців. Сигнатуру визначає обраний `Ham_*`; callback від іншої функції може отримати некоректні аргументи.

Джерела: [`RegisterHam`](https://www.amxmodx.org/api/hamsandwich/RegisterHam), [Ham signatures](https://www.amxmodx.org/api/ham_const).

<a id="ham-parameters"></a>
## Ham: змінюємо параметри явно

Присвоєння нового значення локальній змінній `damage` не змінює оригінальний виклик. `SetHamParamFloat` змінює параметр `4` у `Ham_TakeDamage`; після цього callback повертає статус hook.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_TakeDamage, "player", "on_damage_pre" );
}

public on_damage_pre( victim, inflictor, attacker, Float:damage, damage_bits )
{
	if( damage <= 0.0 )
		return HAM_IGNORED;

	SetHamParamFloat( 4, damage * 0.5 );
	return HAM_HANDLED;
}
```

Це зменшує вдвічі значення, передане оригінальній функції. Фактична втрата здоров’я ще залежить від броні, правил шкоди та інших hooks. Обирай `SetHamParam*` відповідно до типу параметра; сама сутність має номер `1`.

Джерела: [`SetHamParamFloat`](https://www.amxmodx.org/api/hamsandwich/SetHamParamFloat), [`Ham_TakeDamage`](https://www.amxmodx.org/api/ham_const).

<a id="ham-return"></a>
## Ham: статус hook і результат функції

Callback повертає `HAM_*`, а не значення, яке має повернути ігрова функція. Його треба встановити окремо. Тут лікування через `Ham_TakeHealth` блокується, а викликаюча сторона отримує `0`.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_TakeHealth, "player", "on_heal_pre" );
}

public on_heal_pre( player, Float:amount, damage_bits )
{
	SetHamReturnInteger( 0 );
	return HAM_SUPERCEDE;
}
```

`HAM_IGNORED` не втручається у виклик. `HAM_HANDLED` теж дозволяє оригінал. `HAM_OVERRIDE` підміняє результат зі збереженням оригінального виклику, а `HAM_SUPERCEDE` у pre блокує оригінальний виклик. Тип підміни має відповідати типу результату функції. Це не блокує окремі прямі setters здоров’я.

Джерела: [Ham return constants](https://www.amxmodx.org/api/ham_const), [`SetHamReturnInteger`](https://www.amxmodx.org/api/hamsandwich/SetHamReturnInteger).

<a id="fakemeta-status"></a>
## Fakemeta використовує `FMRES_*`

Fakemeta forwards мають власні константи статусу. Цей pre hook блокує виклик `ClientKill` у game DLL, який зазвичай викликається клієнтською командою `kill`.


```pawn
#include <amxmodx>
#include <fakemeta>

public plugin_init()
{
	register_forward( FM_ClientKill, "on_client_kill_pre" );
}

public on_client_kill_pre( player )
{
	return FMRES_SUPERCEDE;
}
```

`FMRES_IGNORED`, `FMRES_HANDLED`, `FMRES_OVERRIDE` і `FMRES_SUPERCEDE` виражають схожі з Ham рішення, але належать іншому API. Для підміни не-void результату потрібні відповідний `forward_return( FMV_*, ... )` і статус. Приклад блокує саме цей callback, а не всі можливі причини самогубства чи смерті.

Джерела: [`register_forward`](https://www.amxmodx.org/api/fakemeta/register_forward), [`forward_return`](https://www.amxmodx.org/api/fakemeta/forward_return).

<a id="hook-lifetime"></a>
## Вмикаємо hooks за потреби

Збережи handle з `RegisterHam`. Вимкнений hook взагалі не входить у Pawn callback; ранній `return` у постійно ввімкненому callback усе одно потребує цього виклику.


```pawn
#include <amxmodx>
#include <hamsandwich>

new HamHook:g_spawn_hook;

public plugin_init()
{
	g_spawn_hook = RegisterHam( Ham_Spawn, "player", "on_spawn_post", 1 );
	DisableHamForward( g_spawn_hook );
	register_srvcmd( "notes_enable_spawn", "enable_spawn_hook" );
}

public enable_spawn_hook()
{
	EnableHamForward( g_spawn_hook );
	return PLUGIN_HANDLED;
}

public on_spawn_post( player )
{
	if( is_user_alive( player ) )
		server_print( "Spawned: %d", player );

	return HAM_IGNORED;
}
```

Реєструй один раз, далі перемикай цю реєстрацію. `ExecuteHamB` викликає Ham hooks і може створити рекурсію всередині того самого hook. `ExecuteHam` ці Ham hooks не викликає; обирай поведінку свідомо. `RegisterHamFromEntity` теж перехоплює таблицю класу, а не лише передану сутність.

Джерела: [`DisableHamForward`](https://www.amxmodx.org/api/hamsandwich/DisableHamForward), [`ExecuteHamB`](https://www.amxmodx.org/api/hamsandwich/ExecuteHamB), [`RegisterHamFromEntity`](https://www.amxmodx.org/api/hamsandwich/RegisterHamFromEntity).

<a id="reapi-hookchains"></a>
## ReAPI hookchains, окремий модуль

**CS 1.6 + ReAPI + ReGameDLL_CS.** ReAPI є окремим модулем, а не новою назвою Ham чи вбудованою можливістю AMXX 1.9. Приклад блокує виклики функції шкоди ReGameDLL, де гравець сам є нападником.


```pawn
#include <amxmodx>
#include <reapi>

public plugin_init()
{
	RegisterHookChain( RG_CBasePlayer_TakeDamage, "on_damage_pre" );
}

public on_damage_pre( victim, inflictor, attacker, Float:damage, damage_bits )
{
	if( victim != attacker )
		return HC_CONTINUE;

	SetHookChainReturn( ATYPE_INTEGER, 0 );
	return HC_SUPERCEDE;
}
```

`SetHookChainArg` змінює вхідні параметри у pre; `SetHookChainReturn` задає результат функції. `HC_SUPERCEDE` блокує оригінальний виклик. `HC_BREAK` також зупиняє виклик наступних callbacks, тому може завадити іншим плагінам. Перевіряй точну сигнатуру в `enum` і потрібні версії ReAPI / ReGameDLL, а не перекладай Ham hook механічно.

Джерела: [ReAPI hookchain API](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi.inc), [game DLL signatures](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll_const.inc).
