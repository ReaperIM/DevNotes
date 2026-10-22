# Сутності та ігрова логіка

[English](../en/08-entities-and-gameplay.md) | [Українська](../uk/08-entities-and-gameplay.md) | [Русский](../ru/08-entities-and-gameplay.md) | [Зміст](../../README.uk.md)

Життєвий цикл сутностей, типізовані поля, triggers і межа між зміною даних та викликом ігрової логіки. Блоки коду незалежні; функції з аргументами показують операції, а не автоматично зареєстровані команди.

- [Індекс не є ідентичністю сутності](#entity-identity)
- [Читаємо entvars з правильним типом](#entvars-and-vectors)
- [Переміщення через API origin](#moving-entities)
- [Створюємо реальний клас і налаштовуємо його](#create-and-spawn)
- [Touch: перевіряємо обидві сторони й забираємо один раз](#touch-and-consume)
- [Think використовує абсолютний ігровий час](#think-scheduling)
- [Моделям потрібні precache і межі](#models-and-precache)
- [Шукаємо точно й видаляємо лише власні сутності](#search-and-remove)
- [Слот власника також може змінитися](#owner-lifetime)
- [Private data: одиниці, клас і платформа](#private-data)
- [Іменовані поля ReAPI, окремий модуль](#reapi-members)
- [Зброя, respawn і смерть є ігровими операціями](#weapons-spawn-and-death)

<a id="entity-identity"></a>
## Індекс не є ідентичністю сутності

Індекси сутностей використовуються повторно після видалення. Збережений індекс може бути валідним, але вже вказувати на іншу сутність. Для відкладеної дії збережи `pev_serial` разом з індексом і перевіряй обидва.


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

Зберігай serial у момент запам’ятовування сутності, а не на початку відкладеного callback. `pev_valid` повертає `0` для невалідної сутності, `1` для наявного edict і `2`, коли є також private data. `pev_valid == 2` дозволяє доступ до private data в принципі, але не підтверджує відповідність класу обраному offset.

Джерела: [pev_valid](https://www.amxmodx.org/api/fakemeta/pev_valid), [pev_serial](https://www.amxmodx.org/api/fakemeta/pev_serial).

<a id="entvars-and-vectors"></a>
## Читаємо entvars з правильним типом

`pev` має різні форми виклику для цілих чисел, float, рядків і векторів. Cell, повернута напряму, не стає автоматично `Float`. Тут float-поля й вектори читаються у вихідні змінні.


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

Вектор містить три `Float` cells, а не три округлені цілі координати. Velocity задає напрямок і швидкість; angles мають інше поле та зміст. В Engine відповідні сімейства: `entity_get_int`, `entity_get_float`, `entity_get_vector`, `entity_get_string` і `entity_get_edict`.

Джерела: [pev](https://www.amxmodx.org/api/fakemeta/pev), [entity_get_vector](https://www.amxmodx.org/api/engine/entity_get_vector).

<a id="moving-entities"></a>
## Переміщення через API origin

Зміна позиції потребує також оновлення зв’язків сутності в engine. Використовуй `entity_set_origin` або `EngFunc_SetOrigin`, а не звичайне присвоєння `pev_origin`.


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

Це переміщує сутність на 32 одиниці по Z, але не перевіряє, чи немає там стіни. Для телепортації гравця потрібні відповідний hull trace і свідоме рішення щодо швидкості та стану на землі; setter позиції сам не шукає безпечне місце.

Джерела: [entity_set_origin](https://www.amxmodx.org/api/engine/entity_set_origin), [engine function constants](https://www.amxmodx.org/api/fakemeta_const).

<a id="create-and-spawn"></a>
## Створюємо реальний клас і налаштовуємо його

`create_entity` потребує клас, реалізований у game DLL. Власний рядок classname не створює новий C++ клас. Для простого trigger створи `info_target`, виконай його spawn, а потім задай власну назву та властивості trigger.


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

Результат є невидимим trigger, а не моделлю предмета. Для ігрових сутностей, налаштованих через keyvalues, потрібні keyvalues задаються до `DispatchSpawn`; spawn може перезаписати поля або видалити непридатну сутність. Порядок залежить від класу, тому не перенось цей рецепт без змін на двері, зброю чи brush-сутності.

Джерела: [create_entity](https://www.amxmodx.org/api/engine/create_entity), [DispatchSpawn](https://www.amxmodx.org/api/engine/DispatchSpawn), [DispatchKeyValue](https://www.amxmodx.org/api/engine/DispatchKeyValue).

<a id="touch-and-consume"></a>
## Touch: перевіряємо обидві сторони й забираємо один раз

Trigger може багато разів торкатися гравця. Handler приймає лише живих гравців і видаляє власний marker до одноразової нагороди. Він призначений для `notes_marker` з попереднього розділу.


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

Після видалення не читай поля `marker`. Приклад забирає marker навіть за 100 здоров’я; перенеси цю перевірку до видалення, якщо повністю здоровий гравець має залишати його на місці. Поле `owner` є ігровими даними, а не автоматичним правилом доступу.

Джерела: [register_touch](https://www.amxmodx.org/api/engine/register_touch), [remove_entity](https://www.amxmodx.org/api/engine/remove_entity), [set_user_health](https://www.amxmodx.org/api/fun/set_user_health).

<a id="think-scheduling"></a>
## Think використовує абсолютний ігровий час

`nextthink` є часовою міткою, а не інтервалом. Перший виклик планується як `get_gametime() + delay`; для повторення наступний виклик потрібно запланувати знову.


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

Це задає приблизно секундний ритм; виклики залежать від кадрів сервера, а не від таймера реального часу. Враховуй кількість активних сутностей. Не обходь усіх гравців чи всі сутності в кожному thinker, якщо дані можна зберегти під час створення або оновлювати подіями.

Джерела: [register_think](https://www.amxmodx.org/api/engine/register_think), [get_gametime](https://www.amxmodx.org/api/amxmodx/get_gametime).

<a id="models-and-precache"></a>
## Моделям потрібні precache і межі

**Шлях ресурсу CS 1.6.** Виконай precache моделі у `plugin_precache`, а потім признач цей самий ресурс сутності. Потрібні межі зіткнень задавай після моделі, оскільки її призначення може впливати на bounds.


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

Модель гранати не робить сутність гранатою: поведінку досі визначають її реальний клас і callbacks. Для власного ресурсу перевір шлях і додай файл на сервер. Не створюй необмежений список precache з довільного клієнтського вводу.

Джерела: [precache_model](https://www.amxmodx.org/api/amxmodx/precache_model), [entity_set_model](https://www.amxmodx.org/api/engine/entity_set_model), [entity_set_size](https://www.amxmodx.org/api/engine/entity_set_size).

<a id="search-and-remove"></a>
## Шукаємо точно й видаляємо лише власні сутності

Власний classname робить очищення явним. Знайди наступний індекс до видалення поточної сутності, щоб пошук не залежав від уже звільненої сутності.


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

Роби це у відповідній події життєвого циклу, наприклад після завершення власного режиму. Глобальний пошук кожного кадру рідко виправданий. Не видаляй гравців, worldspawn або всі сутності загального ігрового classname заради очищення однієї можливості плагіна.

Джерела: [find_ent_by_class](https://www.amxmodx.org/api/engine/find_ent_by_class), [remove_entity](https://www.amxmodx.org/api/engine/remove_entity).

<a id="owner-lifetime"></a>
## Слот власника також може змінитися

Для власної сутності збережи індекс гравця-власника та userid саме цього з’єднання. Так новий гравець у тому самому слоті не успадкує старий marker. Приклад резервує для цього `iuser1` лише на власній сутності.


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

Окремо виріши, чи disconnect має видалити marker, передати його чи залишити нейтральним. Не перезаписуй `iuser*` ігрових сутностей або іншого плагіна. Ownership також може впливати на зіткнення, тому це не завжди придатне поле для довільних метаданих.

Джерела: [get_user_userid](https://www.amxmodx.org/api/amxmodx/get_user_userid), [entity_set_edict](https://www.amxmodx.org/api/engine/entity_set_edict).

<a id="private-data"></a>
## Private data: одиниці, клас і платформа

Entvars на кшталт `pev_origin` належать структурі engine. `pdata` звертається до полів C++ об’єкта game DLL. Валідного edict недостатньо: мають збігатися клас, структура даних і platform offset.

- `get_pdata_int` / `get_pdata_float` використовують одиниці offset по 4 байти.
- Byte-addressable natives, зокрема `get_pdata_byte` або `get_pdata_ent`, використовують байтові offsets.
- `linuxdiff` додається до offset в одиницях конкретної native; поширений default не підходить усім класам.
- Не змішуй байтовий offset, cell offset і поправку Linux з різних таблиць.


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

**Приклад gamedata CS 1.6.** `get_ent_data_*` знаходить іменоване поле в AMXX gamedata та перевіряє оголошений тип. Числові offsets не потрібно вшивати у плагін, але відповідні gamedata і правильний клас усе одно потрібні. Відсутній pointer може дати `-1`; перевір результат перед читанням сутності.

Джерела: [get_pdata_int](https://www.amxmodx.org/api/fakemeta/get_pdata_int), [get_pdata_ent](https://www.amxmodx.org/api/fakemeta/get_pdata_ent), [get_ent_data_entity](https://www.amxmodx.org/api/fakemeta/get_ent_data_entity).

<a id="reapi-members"></a>
## Іменовані поля ReAPI, окремий модуль

**CS 1.6 + ReAPI + ReGameDLL_CS.** `get_member` читає іменовані поля game DLL; `get_entvar` читає entvars. Це різні рівні даних, хоча обидві функції приймають індекс сутності.


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

Якщо операція має супутні дії, обирай native рівня поведінки: наприклад `rg_add_account` для грошей замість простого перезапису `m_iAccount`. Іменоване поле прибирає magic offset, але пряме присвоєння все одно обходить логіку ігрової функції.

Джерела: [ReAPI members](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll.inc), [ReAPI entvars](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_engine.inc).

<a id="weapons-spawn-and-death"></a>
## Зброя, respawn і смерть є ігровими операціями

**CS 1.6.** Індекс сутності зброї не дорівнює її `CSW_*` ID, а запас патронів не є магазином. Видай предмет через ігрову логіку, перевір результат, потім налаштуй потрібний запас патронів.


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

Визнач, що робити з наявною основною зброєю: один give-виклик не задає повну політику спорядження. Запас AK-47 використовує спільний CS ammo pool зі зброєю того самого типу боєприпасів.

Для respawn **специфічний для CS** `ExecuteHamB( Ham_CS_RoundRespawn, player )` або окремий ReAPI `rg_round_respawn` ближчі до ігрової поведінки, ніж проста зміна здоров’я чи загальний spawn. Спочатку перевір команду, стан connected/dead і правила раунду.

Для смерті присвоєння нульового здоров’я, `user_kill`, нанесення шкоди та виклик `Ham_Killed` по-різному впливають на damage hooks, авторство вбивства й ігрові правила. Обирай потрібну операцію: універсальної взаємозаміни немає.

Джерела: [give_item](https://www.amxmodx.org/api/fun/give_item), [cs_set_user_bpammo](https://www.amxmodx.org/api/cstrike/cs_set_user_bpammo), [Ham functions](https://www.amxmodx.org/api/ham_const), [user_kill](https://www.amxmodx.org/api/amxmodx/user_kill).
