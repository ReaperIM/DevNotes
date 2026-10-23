# События, хуки и сообщения

[EN](../en/06-events-hooks-and-messages.md) | [UK](../uk/06-events-hooks-and-messages.md) | [RU](../ru/06-events-hooks-and-messages.md) | [Содержание](../../README.ru.md)

Уведомление о событии и перехват самой операции дают разные возможности. Ниже разберём, когда приходит обработчик, что в нём можно изменить и какой смысл у возвращаемого значения. Примеры запускаются отдельно; зависимости от игры и дополнительных модулей отмечены рядом.

- [Что именно нужно перехватить](#which-hook)
- [Фильтры событий и `read_data`](#filtered-events)
- [Как читать событие из игрового журнала](#log-events)
- [Меняем сообщение, которое получит клиент](#message-hooks)
- [До вызова меняем действие, после читаем результат](#pre-and-post)
- [Как передать изменённый урон в Ham](#ham-parameters)
- [Статус хука и результат игровой функции](#ham-return)
- [Fakemeta использует `FMRES_*`](#fakemeta-status)
- [Включаем хуки по необходимости](#hook-lifetime)
- [Перехваты ReAPI и их зависимости](#reapi-hookchains)

<a id="which-hook"></a>
## Что именно нужно перехватить

Нужно узнать, что игрок умер, уменьшить входящий урон или поменять сообщение клиенту? Это три разные задачи. Хук позволяет перехватить нужную операцию; выбираем тот, который действительно управляет её поведением. Если нужен только готовый результат, достаточно уведомления о событии.

| API | Для чего подходит | Доступ к данным |
| --- | --- | --- |
| `register_event_ex` | Узнать об игровом сообщении, отобрав его по аргументам | `read_data` |
| `register_logevent` | Отреагировать на разобранную запись игрового журнала | `read_logargv` |
| `register_message` | Прочитать, изменить или заблокировать исходящее сообщение | `get_msg_arg_*`, `set_msg_arg_*` |
| Ham Sandwich | Перехватить виртуальную функцию сущности | Параметры обработчика, `SetHam*` |
| Fakemeta | Перехватить доступные модулю вызовы движка и игровой DLL | Параметры обработчика, `FMRES_*` |
| ReAPI, отдельный модуль | Перехватить функции из API ReHLDS / ReGameDLL | Параметры цепочки, `HC_*` |

Перехват сообщения меняет то, что увидит клиент. Если урон уже нанесён, скрытое сообщение его не отменит. Состояние сущностей и правила игры тоже останутся такими, какими их сделала игровая логика.

Источники: [AMXX events](https://www.amxmodx.org/api/amxmodx/register_event_ex), [message hooks](https://www.amxmodx.org/api/messages/register_message), [Ham](https://www.amxmodx.org/api/hamsandwich/RegisterHam).

<a id="filtered-events"></a>
## Фильтры событий и `read_data`

**CS 1.6.** В `DeathMsg` есть убийца, жертва, признак попадания в голову и имя оружия. Нам нужны только убийства в голову, поэтому при регистрации ставим фильтр `3=1`. AMXX сам отберёт подходящие сообщения и вызовет `on_headshot()`.


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

Аргументы сообщения считаются с `1` и читаются внутри этого обработчика через `read_data()`. Они относятся к текущему событию. В роли убийцы может прийти `0`, например для урона от мира: перед функциями игрока проверяем индекс. Флаги `RegisterEvent_*` можно объединять через `|`; это флаги с отдельным тегом.

Источники: [`register_event_ex`](https://www.amxmodx.org/api/amxmodx/register_event_ex), [`read_data`](https://www.amxmodx.org/api/amxmodx/read_data).

<a id="log-events"></a>
## Как читать событие из игрового журнала

**CS 1.6.** Если действие нужно выполнить один раз на начало раунда, подойдёт событие журнала `Round_Start`. Оно не привязано к каждому отдельному появлению игрока. Ниже ловим нужную строку и читаем имя действия.


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

Аргументы журнала считаются с `0`, поэтому `Round_Start` здесь находится под номером `1`. Число `2` при регистрации означает количество разобранных аргументов журнала, а не параметров функции. Читаем их через `read_logargv()`. События `HLTV`, `Round_Start` и успешное появление игрока происходят на разных этапах раунда.

Источники: [`register_logevent`](https://www.amxmodx.org/api/amxmodx/register_logevent), [`read_logargv`](https://www.amxmodx.org/api/amxmodx/read_logargv).

<a id="message-hooks"></a>
## Меняем сообщение, которое получит клиент

**CS 1.6.** Сообщение `HideWeapon` хранит флаги скрытых элементов HUD. Ниже читаем текущие флаги и добавляем бит прицела. Остальные биты сохраняем, чтобы не потерять настройки игры или другого хука.


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
	// Сохраняем все флаги, которые уже были установлены.
	set_msg_arg_int( 1, ARG_BYTE, flags | (1 << 6) );
	return PLUGIN_CONTINUE;
}
```

В таком обработчике используются `get_msg_arg_*`, а `PLUGIN_HANDLED` заблокирует сообщение целиком. `receiver` при рассылке может быть `0`, поэтому это не всегда индекс игрока. Сам хук сработает только при отправке `HideWeapon`; уже показанный HUD он немедленно не обновляет.

Источники: [`register_message`](https://www.amxmodx.org/api/messages/register_message), [`set_msg_arg_int`](https://www.amxmodx.org/api/messages/set_msg_arg_int), [CS HUD flags](https://github.com/rehlds/ReGameDLL_CS/blob/master/regamedll/dlls/cdll_dll.h).

<a id="pre-and-post"></a>
## До вызова меняем действие, после читаем результат

Pre-хук приходит до основной функции: можно изменить её параметры или остановить вызов. Post-хук приходит после неё, когда нужен получившийся результат. В примере выводим сообщение после появления игрока. Проверку `is_user_alive()` оставляем: один вызов обработчика ещё не гарантирует, что игрок жив.


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

В post изменение входного параметра уже не повторит прошедшую операцию. `RegisterHam()` перехватывает виртуальную функцию класса, поэтому регистрация касается класса целиком, а не только игроков, уже зашедших на сервер. Набор параметров смотрим у выбранного `Ham_*`: обработчик другой функции может получать совсем другие аргументы.

Источники: [`RegisterHam`](https://www.amxmodx.org/api/hamsandwich/RegisterHam), [Ham signatures](https://www.amxmodx.org/api/ham_const).

<a id="ham-parameters"></a>
## Как передать изменённый урон в Ham

Присвоить новое число локальной `damage` недостаточно: основная функция его не получит. Для изменения параметра используем `SetHamParamFloat()`. У `Ham_TakeDamage` урон имеет номер `4`, поэтому ниже передаём туда половину исходного значения и возвращаем статус `HAM_HANDLED`.


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

	// Меняем параметр основного вызова, а не только локальную переменную.
	SetHamParamFloat( 4, damage * 0.5 );
	return HAM_HANDLED;
}
```

В основную функцию уйдёт половинный урон. Итоговая потеря здоровья может отличаться: дальше ещё работают броня, правила урона и другие хуки. Функцию `SetHamParam*` выбираем по типу параметра. Нумерация начинается с самой сущности под номером `1`.

Источники: [`SetHamParamFloat`](https://www.amxmodx.org/api/hamsandwich/SetHamParamFloat), [`Ham_TakeDamage`](https://www.amxmodx.org/api/ham_const).

<a id="ham-return"></a>
## Статус хука и результат игровой функции

У обработчика и перехваченной функции разные результаты. Из обработчика возвращаем статус `HAM_*`, а значение для игровой функции задаём отдельно. Ниже запрещаем лечение через `Ham_TakeHealth`: основная функция будет пропущена, а вызвавший её код получит `0`.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_TakeHealth, "player", "on_heal_pre" );
}

public on_heal_pre( player, Float:amount, damage_bits )
{
	// Задаём результат, который получит код, вызвавший игровую функцию.
	SetHamReturnInteger( 0 );
	return HAM_SUPERCEDE;
}
```

`HAM_IGNORED` оставляет вызов как есть. С `HAM_HANDLED` основная функция тоже выполнится. `HAM_OVERRIDE` заменяет её результат, сохраняя выполнение, а `HAM_SUPERCEDE` в pre пропускает саму функцию. Заданный результат должен подходить её типу. Прямая установка здоровья из другого места всё ещё возможна: этот хук блокирует именно `Ham_TakeHealth`.

Источники: [Ham return constants](https://www.amxmodx.org/api/ham_const), [`SetHamReturnInteger`](https://www.amxmodx.org/api/hamsandwich/SetHamReturnInteger).

<a id="fakemeta-status"></a>
## Fakemeta использует `FMRES_*`

У Fakemeta свои статусы `FMRES_*`. В примере перехватываем `ClientKill` до выполнения игровой DLL и возвращаем `FMRES_SUPERCEDE`, чтобы пропустить основной вызов. Обычно сюда приводит команда клиента `kill`.


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

Смысл `FMRES_IGNORED`, `FMRES_HANDLED`, `FMRES_OVERRIDE` и `FMRES_SUPERCEDE` похож на статусы Ham, но это другой API. Если функция возвращает значение и его нужно заменить, отдельно задаём его через `forward_return( FMV_*, ... )` подходящего типа. Такой перехват блокирует этот путь `ClientKill`; другие причины смерти остаются.

Источники: [`register_forward`](https://www.amxmodx.org/api/fakemeta/register_forward), [`forward_return`](https://www.amxmodx.org/api/fakemeta/forward_return).

<a id="hook-lifetime"></a>
## Включаем хуки по необходимости

Если хук нужен только часть времени, сохраняем handle от `RegisterHam()` и выключаем его через `DisableHamForward()`. Тогда AMXX вообще не заходит в обработчик Pawn. Ниже обработка появления включается серверной командой. Постоянно включённый хук с ранним `return` всё равно потребовал бы вызова обработчика.


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

Регистрируем один раз, затем включаем и выключаем существующий хук. `ExecuteHamB()` снова вызовет Ham-хуки; из того же хука так легко получить рекурсию. `ExecuteHam()` их не вызывает, поэтому выбираем осознанно. `RegisterHamFromEntity()` тоже перехватывает виртуальную таблицу класса, а не только одну переданную сущность.

Источники: [`DisableHamForward`](https://www.amxmodx.org/api/hamsandwich/DisableHamForward), [`ExecuteHamB`](https://www.amxmodx.org/api/hamsandwich/ExecuteHamB), [`RegisterHamFromEntity`](https://www.amxmodx.org/api/hamsandwich/RegisterHamFromEntity).

<a id="reapi-hookchains"></a>
## Перехваты ReAPI и их зависимости

**CS 1.6 + ReAPI + ReGameDLL_CS.** Для этого примера нужны отдельный модуль ReAPI и поддерживаемая игровая DLL; одного AMXX 1.9 недостаточно. Ниже перехватываем функцию урона игроку в ReGameDLL. Если атакующий и жертва совпали, пропускаем основной вызов и возвращаем `0`.


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

В pre параметры меняются через `SetHookChainArg()`, результат функции через `SetHookChainReturn()`. `HC_SUPERCEDE` пропускает основную функцию. `HC_BREAK` вдобавок остановит следующие обработчики цепочки, что затронет другие плагины. Параметры функции и нужные версии ReAPI / ReGameDLL проверяем в её `enum`: переписать Ham-хук заменой имён недостаточно.

Источники: [ReAPI hookchain API](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi.inc), [game DLL signatures](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll_const.inc).
