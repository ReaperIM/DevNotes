# События, hooks и сообщения

[English](../en/06-events-hooks-and-messages.md) | [Українська](../uk/06-events-hooks-and-messages.md) | [Русский](../ru/06-events-hooks-and-messages.md) | [Содержание](../../README.ru.md)

Рабочие заметки о моменте вызова callback, доступных ему данных и смысле результата. Примеры независимы; игровые протоколы и отдельные модули отмечены.

- [Выбираем нужный уровень](#which-hook)
- [Фильтры событий и read_data](#filtered-events)
- [У событий журнала свои аргументы](#log-events)
- [Меняем сообщение, а не игровое состояние](#message-hooks)
- [Pre меняет вызов, post видит его результат](#pre-and-post)
- [Ham: меняем параметры явно](#ham-parameters)
- [Ham: статус hook и результат функции](#ham-return)
- [Fakemeta использует FMRES_*](#fakemeta-status)
- [Включаем hooks по необходимости](#hook-lifetime)
- [ReAPI hookchains, отдельный модуль](#reapi-hookchains)

<a id="which-hook"></a>
## Выбираем нужный уровень

Уведомление о смерти, вызов `TakeDamage` и исходящее сообщение описывают разные моменты. Перехватывай саму операцию, поведение которой нужно изменить; для наблюдения за результатом достаточно события.

| API | Для чего | Данные |
| --- | --- | --- |
| `register_event_ex` | Наблюдение за игровыми сообщениями с фильтрами аргументов | `read_data` |
| `register_logevent` | Наблюдение за разобранными событиями игрового журнала | `read_logargv` |
| `register_message` | Чтение, изменение или блокировка исходящего сообщения | `get_msg_arg_*`, `set_msg_arg_*` |
| Ham Sandwich | Перехват виртуальной функции сущности | Параметры callback, `SetHam*` |
| Fakemeta | Перехват engine / DLL forwards, доступных в модуле | Параметры callback, `FMRES_*` |
| ReAPI, отдельный модуль | Функции API ReHLDS / ReGameDLL | Параметры hookchain, `HC_*` |

Перехват сообщения меняет то, что получит клиент. Он не отменяет урон, не восстанавливает сущность и не возвращает назад игровую логику, создавшую сообщение.

Источники: [AMXX events](https://www.amxmodx.org/api/amxmodx/register_event_ex), [message hooks](https://www.amxmodx.org/api/messages/register_message), [Ham](https://www.amxmodx.org/api/hamsandwich/RegisterHam).

<a id="filtered-events"></a>
## Фильтры событий и read_data

**CS 1.6.** `DeathMsg` содержит убийцу, жертву, признак headshot и название оружия. Фильтр `3=1` оставляет только уведомления об убийствах в голову.


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

Аргументы сообщения начинаются с `1`. Читай их внутри callback: они относятся к текущему событию. Убийца может быть равен `0` при уроне от мира, поэтому проверяй его перед player natives. Типизированные флаги при необходимости объединяются через `|`.

Источники: [register_event_ex](https://www.amxmodx.org/api/amxmodx/register_event_ex), [read_data](https://www.amxmodx.org/api/amxmodx/read_data).

<a id="log-events"></a>
## У событий журнала свои аргументы

**CS 1.6.** `Round_Start` является событием игрового журнала. Оно удобно для действий на уровне раунда, которые должны выполняться один раз, а не после каждого spawn игрока.


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

Аргументы журнала начинаются с `0`; здесь аргумент `1` равен `Round_Start`. `argsnum = 2` задаёт число разобранных аргументов журнала, а не параметров callback. Нужен `read_logargv`, а не `read_data`. `HLTV`, `Round_Start` и успешный spawn игрока соответствуют разным этапам раунда.

Источники: [register_logevent](https://www.amxmodx.org/api/amxmodx/register_logevent), [read_logargv](https://www.amxmodx.org/api/amxmodx/read_logargv).

<a id="message-hooks"></a>
## Меняем сообщение, а не игровое состояние

**CS 1.6.** `HideWeapon` передаёт битовую маску HUD. Перехват добавляет бит прицела, сохраняя остальные биты, уже установленные игрой или предыдущим hook.


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

В message hook используй `get_msg_arg_*`, а не `read_data`. Возврат `PLUGIN_HANDLED` блокирует сообщение целиком. `receiver` может быть равен `0` при рассылке, поэтому это не всегда индекс игрока. Пример действует только при отправке `HideWeapon` и сам не отправляет клиенту немедленное обновление.

Источники: [register_message](https://www.amxmodx.org/api/messages/register_message), [set_msg_arg_int](https://www.amxmodx.org/api/messages/set_msg_arg_int), [CS HUD flags](https://github.com/rehlds/ReGameDLL_CS/blob/master/regamedll/dlls/cdll_dll.h).

<a id="pre-and-post"></a>
## Pre меняет вызов, post видит его результат

Pre подходит для изменения параметров или блокировки оригинального вызова. В post оригинал уже успел создать нужное состояние. Даже после spawn нужна проверка alive: сам вызов callback не гарантирует, что игрок жив.


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

В post изменение входного параметра не вернёт оригинальную операцию назад. `RegisterHam` перехватывает таблицу виртуальных функций класса, а не только текущих игроков. Сигнатуру задаёт выбранный `Ham_*`; callback от другой функции может получить некорректные аргументы.

Источники: [RegisterHam](https://www.amxmodx.org/api/hamsandwich/RegisterHam), [Ham signatures](https://www.amxmodx.org/api/ham_const).

<a id="ham-parameters"></a>
## Ham: меняем параметры явно

Присваивание нового значения локальной переменной `damage` не меняет оригинальный вызов. `SetHamParamFloat` меняет параметр `4` у `Ham_TakeDamage`; после этого callback возвращает статус hook.


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

Это вдвое уменьшает значение, переданное оригинальной функции. Фактическая потеря здоровья ещё зависит от брони, правил урона и других hooks. Выбирай `SetHamParam*` по типу параметра; сама сущность имеет номер `1`.

Источники: [SetHamParamFloat](https://www.amxmodx.org/api/hamsandwich/SetHamParamFloat), [Ham_TakeDamage](https://www.amxmodx.org/api/ham_const).

<a id="ham-return"></a>
## Ham: статус hook и результат функции

Callback возвращает `HAM_*`, а не значение, которое должна вернуть игровая функция. Его нужно установить отдельно. Здесь лечение через `Ham_TakeHealth` блокируется, а вызывающая сторона получает `0`.


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

`HAM_IGNORED` не вмешивается в вызов. `HAM_HANDLED` тоже разрешает оригинал. `HAM_OVERRIDE` подменяет результат с сохранением оригинального вызова, а `HAM_SUPERCEDE` в pre блокирует оригинальный вызов. Тип подмены должен соответствовать типу результата функции. Это не блокирует отдельные прямые setters здоровья.

Источники: [Ham return constants](https://www.amxmodx.org/api/ham_const), [SetHamReturnInteger](https://www.amxmodx.org/api/hamsandwich/SetHamReturnInteger).

<a id="fakemeta-status"></a>
## Fakemeta использует FMRES_*

У Fakemeta forwards свои константы статуса. Этот pre hook блокирует вызов `ClientKill` в game DLL, который обычно вызывается клиентской командой `kill`.


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

`FMRES_IGNORED`, `FMRES_HANDLED`, `FMRES_OVERRIDE` и `FMRES_SUPERCEDE` выражают похожие на Ham решения, но относятся к другому API. Для подмены не-void результата нужны соответствующий `forward_return( FMV_*, ... )` и статус. Пример блокирует именно этот callback, а не все возможные причины самоубийства или смерти.

Источники: [register_forward](https://www.amxmodx.org/api/fakemeta/register_forward), [forward_return](https://www.amxmodx.org/api/fakemeta/forward_return).

<a id="hook-lifetime"></a>
## Включаем hooks по необходимости

Сохрани handle из `RegisterHam`. Выключенный hook вообще не входит в Pawn callback; ранний `return` в постоянно включённом callback всё равно требует этого вызова.


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

Регистрируй один раз, затем переключай эту регистрацию. `ExecuteHamB` вызывает Ham hooks и может создать рекурсию внутри того же hook. `ExecuteHam` эти Ham hooks не вызывает; выбирай поведение осознанно. `RegisterHamFromEntity` тоже перехватывает таблицу класса, а не только переданную сущность.

Источники: [DisableHamForward](https://www.amxmodx.org/api/hamsandwich/DisableHamForward), [ExecuteHamB](https://www.amxmodx.org/api/hamsandwich/ExecuteHamB), [RegisterHamFromEntity](https://www.amxmodx.org/api/hamsandwich/RegisterHamFromEntity).

<a id="reapi-hookchains"></a>
## ReAPI hookchains, отдельный модуль

**CS 1.6 + ReAPI + ReGameDLL_CS.** ReAPI является отдельным модулем, а не новым названием Ham или встроенной возможностью AMXX 1.9. Пример блокирует вызовы функции урона ReGameDLL, где игрок сам является атакующим.


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

`SetHookChainArg` меняет входные параметры в pre; `SetHookChainReturn` задаёт результат функции. `HC_SUPERCEDE` блокирует оригинальный вызов. `HC_BREAK` также останавливает вызов следующих callbacks, поэтому может помешать другим плагинам. Проверяй точную сигнатуру в enum и нужные версии ReAPI / ReGameDLL, а не переводи Ham hook механически.

Источники: [ReAPI hookchain API](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi.inc), [game DLL signatures](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll_const.inc).
