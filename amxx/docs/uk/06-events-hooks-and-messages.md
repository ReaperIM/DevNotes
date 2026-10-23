# Події, хуки та повідомлення

[EN](../en/06-events-hooks-and-messages.md) | [UK](../uk/06-events-hooks-and-messages.md) | [RU](../ru/06-events-hooks-and-messages.md) | [Зміст](../../README.uk.md)

Повідомлення про подію та перехоплення самої операції дають різні можливості. Нижче розберемо, коли надходить обробник, що в ньому можна змінити та який зміст має повернуте значення. Приклади запускаються окремо; залежності від гри й додаткових модулів позначені поруч.

- [Що саме потрібно перехопити](#which-hook)
- [Фільтри подій і `read_data`](#filtered-events)
- [Як читати подію з ігрового журналу](#log-events)
- [Змінюємо повідомлення, яке отримає клієнт](#message-hooks)
- [До виклику змінюємо дію, після читаємо результат](#pre-and-post)
- [Як передати змінену шкоду в Ham](#ham-parameters)
- [Статус хука та результат ігрової функції](#ham-return)
- [Fakemeta використовує `FMRES_*`](#fakemeta-status)
- [Вмикаємо хуки за потреби](#hook-lifetime)
- [Перехоплення ReAPI та їхні залежності](#reapi-hookchains)

<a id="which-hook"></a>
## Що саме потрібно перехопити

Треба дізнатися, що гравець помер, зменшити вхідну шкоду чи змінити повідомлення клієнту? Це три різні завдання. Хук дозволяє перехопити потрібну операцію; вибираємо той, який справді керує її поведінкою. Якщо потрібен лише готовий результат, достатньо повідомлення про подію.

| API | Для чого підходить | Доступ до даних |
| --- | --- | --- |
| `register_event_ex` | Дізнатися про ігрове повідомлення, відібравши його за аргументами | `read_data` |
| `register_logevent` | Відреагувати на розібраний запис ігрового журналу | `read_logargv` |
| `register_message` | Прочитати, змінити чи заблокувати вихідне повідомлення | `get_msg_arg_*`, `set_msg_arg_*` |
| Ham Sandwich | Перехопити віртуальну функцію сутності | Параметри обробника, `SetHam*` |
| Fakemeta | Перехопити доступні модулю виклики рушія та ігрової DLL | Параметри обробника, `FMRES_*` |
| ReAPI, окремий модуль | Перехопити функції з API ReHLDS / ReGameDLL | Параметри ланцюжка, `HC_*` |

Перехоплення повідомлення змінює те, що побачить клієнт. Якщо шкоди вже завдано, приховане повідомлення її не скасує. Стан сутностей і правила гри теж залишаться такими, якими їх зробила ігрова логіка.

Джерела: [AMXX events](https://www.amxmodx.org/api/amxmodx/register_event_ex), [message hooks](https://www.amxmodx.org/api/messages/register_message), [Ham](https://www.amxmodx.org/api/hamsandwich/RegisterHam).

<a id="filtered-events"></a>
## Фільтри подій і `read_data`

**CS 1.6.** У `DeathMsg` є вбивця, жертва, ознака влучання в голову та ім’я зброї. Нам потрібні лише вбивства в голову, тому під час реєстрації ставимо фільтр `3=1`. AMXX сам відбере потрібні повідомлення й викличе `on_headshot()`.


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

Аргументи повідомлення рахуються з `1` і читаються всередині цього обробника через `read_data()`. Вони належать поточній події. У ролі вбивці може прийти `0`, наприклад для шкоди від світу: перед функціями гравця перевіряємо індекс. Прапорці `RegisterEvent_*` можна поєднувати через `|`; це прапорці з окремим тегом.

Джерела: [`register_event_ex`](https://www.amxmodx.org/api/amxmodx/register_event_ex), [`read_data`](https://www.amxmodx.org/api/amxmodx/read_data).

<a id="log-events"></a>
## Як читати подію з ігрового журналу

**CS 1.6.** Якщо дію треба виконати один раз на початок раунду, підійде подія журналу `Round_Start`. Вона не прив’язана до кожної окремої появи гравця. Нижче ловимо потрібний рядок і читаємо ім’я дії.


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

Аргументи журналу рахуються з `0`, тому `Round_Start` тут знаходиться під номером `1`. Число `2` під час реєстрації означає кількість розібраних аргументів журналу, а не параметрів функції. Читаємо їх через `read_logargv()`. Події `HLTV`, `Round_Start` та успішна поява гравця відбуваються на різних етапах раунду.

Джерела: [`register_logevent`](https://www.amxmodx.org/api/amxmodx/register_logevent), [`read_logargv`](https://www.amxmodx.org/api/amxmodx/read_logargv).

<a id="message-hooks"></a>
## Змінюємо повідомлення, яке отримає клієнт

**CS 1.6.** Повідомлення `HideWeapon` зберігає прапорці прихованих елементів HUD. Нижче читаємо поточні прапорці та додаємо біт прицілу. Решту бітів зберігаємо, щоб не втратити налаштування гри або іншого хука.


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
	// Зберігаємо всі прапорці, які вже були встановлені.
	set_msg_arg_int( 1, ARG_BYTE, flags | (1 << 6) );
	return PLUGIN_CONTINUE;
}
```

У такому обробнику використовуються `get_msg_arg_*`, а `PLUGIN_HANDLED` заблокує повідомлення повністю. `receiver` під час розсилки може бути `0`, тому це не завжди індекс гравця. Сам хук спрацює лише під час надсилання `HideWeapon`; уже показаний HUD він негайно не оновлює.

Джерела: [`register_message`](https://www.amxmodx.org/api/messages/register_message), [`set_msg_arg_int`](https://www.amxmodx.org/api/messages/set_msg_arg_int), [CS HUD flags](https://github.com/rehlds/ReGameDLL_CS/blob/master/regamedll/dlls/cdll_dll.h).

<a id="pre-and-post"></a>
## До виклику змінюємо дію, після читаємо результат

Pre-хук надходить до основної функції: можна змінити її параметри або зупинити виклик. Post-хук надходить після неї, коли потрібен отриманий результат. У прикладі виводимо повідомлення після появи гравця. Перевірку `is_user_alive()` залишаємо: один виклик обробника ще не гарантує, що гравець живий.


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

У post зміна вхідного параметра вже не повторить операцію, що відбулася. `RegisterHam()` перехоплює віртуальну функцію класу, тому реєстрація стосується всього класу, а не лише гравців, які вже зайшли на сервер. Набір параметрів дивимося у вибраного `Ham_*`: обробник іншої функції може отримувати зовсім інші аргументи.

Джерела: [`RegisterHam`](https://www.amxmodx.org/api/hamsandwich/RegisterHam), [Ham signatures](https://www.amxmodx.org/api/ham_const).

<a id="ham-parameters"></a>
## Як передати змінену шкоду в Ham

Присвоїти нове число локальній `damage` недостатньо: основна функція його не отримає. Для зміни параметра використовуємо `SetHamParamFloat()`. У `Ham_TakeDamage` шкода має номер `4`, тому нижче передаємо туди половину початкового значення й повертаємо статус `HAM_HANDLED`.


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

	// Змінюємо параметр основного виклику, а не лише локальну змінну.
	SetHamParamFloat( 4, damage * 0.5 );
	return HAM_HANDLED;
}
```

В основну функцію піде половинна шкода. Остаточна втрата здоров’я може відрізнятися: далі ще працюють броня, правила шкоди та інші хуки. Функцію `SetHamParam*` вибираємо за типом параметра. Нумерація починається із самої сутності під номером `1`.

Джерела: [`SetHamParamFloat`](https://www.amxmodx.org/api/hamsandwich/SetHamParamFloat), [`Ham_TakeDamage`](https://www.amxmodx.org/api/ham_const).

<a id="ham-return"></a>
## Статус хука та результат ігрової функції

У обробника й перехопленої функції різні результати. З обробника повертаємо статус `HAM_*`, а значення для ігрової функції задаємо окремо. Нижче забороняємо лікування через `Ham_TakeHealth`: основна функція буде пропущена, а код, який її викликав, отримає `0`.


```pawn
#include <amxmodx>
#include <hamsandwich>

public plugin_init()
{
	RegisterHam( Ham_TakeHealth, "player", "on_heal_pre" );
}

public on_heal_pre( player, Float:amount, damage_bits )
{
	// Задаємо результат, який отримає код, що викликав ігрову функцію.
	SetHamReturnInteger( 0 );
	return HAM_SUPERCEDE;
}
```

`HAM_IGNORED` залишає виклик як є. З `HAM_HANDLED` основна функція теж виконається. `HAM_OVERRIDE` замінює її результат, зберігаючи виконання, а `HAM_SUPERCEDE` у pre пропускає саму функцію. Заданий результат має підходити її типу. Пряме встановлення здоров’я з іншого місця все ще можливе: цей хук блокує саме `Ham_TakeHealth`.

Джерела: [Ham return constants](https://www.amxmodx.org/api/ham_const), [`SetHamReturnInteger`](https://www.amxmodx.org/api/hamsandwich/SetHamReturnInteger).

<a id="fakemeta-status"></a>
## Fakemeta використовує `FMRES_*`

У Fakemeta свої статуси `FMRES_*`. У прикладі перехоплюємо `ClientKill` до виконання ігрової DLL і повертаємо `FMRES_SUPERCEDE`, щоб пропустити основний виклик. Зазвичай сюди приводить команда клієнта `kill`.


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

Зміст `FMRES_IGNORED`, `FMRES_HANDLED`, `FMRES_OVERRIDE` і `FMRES_SUPERCEDE` схожий на статуси Ham, але це інший API. Якщо функція повертає значення і його треба замінити, окремо задаємо його через `forward_return( FMV_*, ... )` потрібного типу. Таке перехоплення блокує цей шлях `ClientKill`; інші причини смерті залишаються.

Джерела: [`register_forward`](https://www.amxmodx.org/api/fakemeta/register_forward), [`forward_return`](https://www.amxmodx.org/api/fakemeta/forward_return).

<a id="hook-lifetime"></a>
## Вмикаємо хуки за потреби

Якщо хук потрібен лише частину часу, зберігаємо handle від `RegisterHam()` і вимикаємо його через `DisableHamForward()`. Тоді AMXX узагалі не заходить в обробник Pawn. Нижче обробка появи вмикається серверною командою. Постійно ввімкнений хук із раннім `return` однаково потребував би виклику обробника.


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

Реєструємо один раз, потім вмикаємо й вимикаємо наявний хук. `ExecuteHamB()` знову викличе Ham-хуки; з того самого хука так легко отримати рекурсію. `ExecuteHam()` їх не викликає, тому вибираємо свідомо. `RegisterHamFromEntity()` теж перехоплює віртуальну таблицю класу, а не лише одну передану сутність.

Джерела: [`DisableHamForward`](https://www.amxmodx.org/api/hamsandwich/DisableHamForward), [`ExecuteHamB`](https://www.amxmodx.org/api/hamsandwich/ExecuteHamB), [`RegisterHamFromEntity`](https://www.amxmodx.org/api/hamsandwich/RegisterHamFromEntity).

<a id="reapi-hookchains"></a>
## Перехоплення ReAPI та їхні залежності

**CS 1.6 + ReAPI + ReGameDLL_CS.** Для цього прикладу потрібні окремий модуль ReAPI та підтримувана ігрова DLL; одного AMXX 1.9 недостатньо. Нижче перехоплюємо функцію шкоди гравцю в ReGameDLL. Якщо нападник і жертва збіглися, пропускаємо основний виклик і повертаємо `0`.


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

У pre параметри змінюються через `SetHookChainArg()`, результат функції через `SetHookChainReturn()`. `HC_SUPERCEDE` пропускає основну функцію. `HC_BREAK` до того ж зупинить наступні обробники ланцюжка, що зачепить інші плагіни. Параметри функції й потрібні версії ReAPI / ReGameDLL перевіряємо в її `enum`: переписати Ham-хук заміною імен недостатньо.

Джерела: [ReAPI hookchain API](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi.inc), [game DLL signatures](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll_const.inc).
