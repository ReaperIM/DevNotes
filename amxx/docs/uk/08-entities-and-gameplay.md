# Сутності та ігрова логіка

[EN](../en/08-entities-and-gameplay.md) | [UK](../uk/08-entities-and-gameplay.md) | [RU](../ru/08-entities-and-gameplay.md) | [Зміст](../../README.uk.md)

У сутності є час життя, поля й поведінка, задана грою. Нижче розберемо, як не звернутися до вже видаленої сутності, правильно читати її дані та вибирати між записом поля й ігровою функцією. Кожен блок окремий; функції з параметрами показують готову операцію, яку треба викликати зі свого коду.

- [Як не сплутати видалену сутність із новою](#entity-identity)
- [Читаємо поля сутності з потрібним типом](#entvars-and-vectors)
- [Переміщуємо сутність через функцію положення](#moving-entities)
- [Створюємо сутність і налаштовуємо її поведінку](#create-and-spawn)
- [Один дотик, одна нагорода](#touch-and-consume)
- [Коли має спрацювати наступний `Think`](#think-scheduling)
- [Завантажуємо модель і задаємо розмір сутності](#models-and-precache)
- [Знаходимо й видаляємо лише свої сутності](#search-and-remove)
- [Власник теж може змінитися в тому самому слоті](#owner-lifetime)
- [Private data: перевіряємо поля та зміщення](#private-data)
- [Поля за іменами через ReAPI](#reapi-members)
- [Видача зброї, відродження та смерть](#weapons-spawn-and-death)

<a id="entity-identity"></a>
## Як не сплутати видалену сутність із новою

Після видалення індекс сутності може дістатися іншій сутності. Одна перевірка збереженого індексу тоді пройде, але об’єкт уже буде іншим. Для відкладеної дії зберігаємо разом з індексом значення `pev_serial`, а перед роботою перевіряємо обидва.


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

Серійний номер запам’ятовуємо одразу, коли вибрали сутність. Якщо прочитати його лише у відкладеному обробнику, перевіряти буде ні з чим. `pev_valid()` повертає `0`, якщо сутності немає, `1`, якщо є її запис у рушії (edict), і `2`, якщо є ще й private data. Значення `2` дозволяє звертатися до private data, але потрібний клас і відповідні йому offsets усе одно перевіряємо окремо.

Джерела: [`pev_valid`](https://www.amxmodx.org/api/fakemeta/pev_valid), [`pev_serial`](https://www.amxmodx.org/api/fakemeta/pev_serial).

<a id="entvars-and-vectors"></a>
## Читаємо поля сутності з потрібним типом

У `pev()` є різні способи читання чисел, `Float`, рядків і векторів. Для дробових значень і векторів нижче передаємо вихідні змінні. Якщо просто отримати комірку через повернуте значення, вона сама собою у `Float` не перетвориться.


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

У вектора три комірки `Float`: дробові координати не потрібно округляти до цілих. `velocity` задає напрямок і швидкість; для кутів є інше поле. В Engine схожі операції розділені на `entity_get_int`, `entity_get_float`, `entity_get_vector`, `entity_get_string` і `entity_get_edict`.

Джерела: [`pev`](https://www.amxmodx.org/api/fakemeta/pev), [`entity_get_vector`](https://www.amxmodx.org/api/engine/entity_get_vector).

<a id="moving-entities"></a>
## Переміщуємо сутність через функцію положення

Під час переміщення рушію треба оновити й положення сутності у своїх структурах. Тому використовуємо `entity_set_origin()` або `EngFunc_SetOrigin`. Одного прямого запису у `pev_origin` для цього недостатньо. Нижче читаємо позицію, додаємо `32.0` за висотою та задаємо нове положення.


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

Сутність підніметься на 32 одиниці по Z. Перевірки стіни в точці призначення тут немає. Для телепортації гравця ще потрібен відповідний hull trace, а також рішення, що робити зі швидкістю та станом на землі. Функція встановлення позиції сама безпечне місце не добирає.

Джерела: [`entity_set_origin`](https://www.amxmodx.org/api/engine/entity_set_origin), [engine function constants](https://www.amxmodx.org/api/fakemeta_const).

<a id="create-and-spawn"></a>
## Створюємо сутність і налаштовуємо її поведінку

`create_entity()` створює клас, який уже реалізований в ігровій DLL. Новий рядок classname сам собою нового класу C++ не додає. Для власного простого тригера нижче створюємо `info_target`, викликаємо його spawn, потім задаємо ім’я-маркер, розмір і властивості тригера.


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

Виходить невидима область спрацювання. Щоб показати предмет, окремо знадобиться модель. У сутностей із keyvalues потрібні значення можуть задаватися до `DispatchSpawn()`: spawn здатен змінити поля або видалити непридатну сутність. Порядок залежить від класу, тому приклад тригера не можна без перевірки переносити на двері, зброю та brush-сутності.

Джерела: [`create_entity`](https://www.amxmodx.org/api/engine/create_entity), [`DispatchSpawn`](https://www.amxmodx.org/api/engine/DispatchSpawn), [`DispatchKeyValue`](https://www.amxmodx.org/api/engine/DispatchKeyValue).

<a id="touch-and-consume"></a>
## Один дотик, одна нагорода

Тригер може отримувати повторні дотики від гравця. Нижче перевіряємо, що `notes_marker` ще існує і гравець живий, потім видаляємо маркер до видачі нагороди. Так один маркер не видасть лікування повторно. Приклад розрахований на тригер із попереднього розділу.


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

	// Прибираємо до нагороди, щоб не видати її повторно за нового дотику.
	remove_entity( marker );
	new health = get_user_health( player );

	if( health < 100 )
		set_user_health( player, min( health + 10, 100 ) );
}
```

Після видалення читати поля `marker` уже не можна. Зараз маркер витрачається навіть за 100 HP. Якщо він має залишатися для іншого гравця, перевірку повного здоров’я переносимо до видалення. Поле `owner` саме права на отримання нагороди не перевіряє.

Джерела: [`register_touch`](https://www.amxmodx.org/api/engine/register_touch), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity), [`set_user_health`](https://www.amxmodx.org/api/fun/set_user_health).

<a id="think-scheduling"></a>
## Коли має спрацювати наступний `Think`

У `nextthink` записуємо конкретний момент ігрового часу. Для виклику за секунду це `get_gametime() + 1.0`. Якщо сутність має продовжувати роботу, в обробнику знову задаємо час наступного виклику.


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

Вийде інтервал приблизно в секунду, з прив’язкою до кадрів сервера. Що більше активних сутностей, то частіше сумарно викликаються їхні обробники. Якщо дані можна зберегти під час створення або оновити подією, не треба з кожного `Think` заново обходити всіх гравців і всі сутності.

Джерела: [`register_think`](https://www.amxmodx.org/api/engine/register_think), [`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime).

<a id="models-and-precache"></a>
## Завантажуємо модель і задаємо розмір сутності

**Шлях ресурсу CS 1.6.** Спочатку завантажуємо модель у `plugin_precache()`, потім призначаємо ту саму модель сутності. Потрібний розмір області зіткнень ставимо після моделі: її призначення може вплинути на розмір.


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
	// Задаємо межі після моделі, яка може їх змінити.
	entity_set_size( marker, Float:{ -16.0, -16.0, -16.0 }, Float:{ 16.0, 16.0, 16.0 } );
}
```

Модель гранати змінить зовнішній вигляд, але поведінки гранати не додасть. За неї відповідають справжній клас і обробники сутності. Для власного ресурсу перевіряємо шлях і наявність файлу на сервері. Список precache тримаємо під контролем, довільний клієнтський ввід до нього не додаємо.

Джерела: [`precache_model`](https://www.amxmodx.org/api/amxmodx/precache_model), [`entity_set_model`](https://www.amxmodx.org/api/engine/entity_set_model), [`entity_set_size`](https://www.amxmodx.org/api/engine/entity_set_size).

<a id="search-and-remove"></a>
## Знаходимо й видаляємо лише свої сутності

Власний classname дозволяє знайти об’єкти, створені нашою функцією. Нижче перед видаленням поточного маркера спочатку знаходимо наступний. Тоді подальший пошук не спирається на вже звільнену сутність.


```pawn
#include <amxmodx>
#include <engine>

public remove_markers()
{
	new entity = find_ent_by_class( 0, "notes_marker" );

	while( entity > 0 )
	{
		// Шукаємо наступну сутність, поки поточна ще існує.
		new next = find_ent_by_class( entity, "notes_marker" );
		remove_entity( entity );
		entity = next;
	}
}
```

Таке очищення запускаємо за потрібною подією, наприклад після завершення режиму. Перевіряти весь світ кожен кадр зазвичай немає потреби. Спільні ігрові класи, гравці й `worldspawn` до наших маркерів не належать: масове видалення таких сутностей зламає гру.

Джерела: [`find_ent_by_class`](https://www.amxmodx.org/api/engine/find_ent_by_class), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity).

<a id="owner-lifetime"></a>
## Власник теж може змінитися в тому самому слоті

Для власної сутності зберігаємо індекс власника та userid його підключення. Тоді новий гравець у тому самому слоті не отримає чужого старого маркера. Нижче для userid використовуємо поле `iuser1`, яке зарезервували у власної сутності.


```pawn
#include <amxmodx>
#include <engine>

public assign_marker_owner( marker, player )
{
	if( !is_valid_ent( marker ) || !is_user_connected( player ) )
		return;

	entity_set_edict( marker, EV_ENT_owner, player );
	// Разом зі слотом власника зберігаємо саме його підключення.
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

Окремо вирішуємо, що буде з маркером під час виходу: видалити, передати іншому гравцю чи залишити без власника. Чужі поля `iuser*` не перезаписуємо. Сам `owner` ще може впливати на зіткнення, тому зберігати в ньому довільні дані підходить не завжди.

Джерела: [`get_user_userid`](https://www.amxmodx.org/api/amxmodx/get_user_userid), [`entity_set_edict`](https://www.amxmodx.org/api/engine/entity_set_edict).

<a id="private-data"></a>
## Private data: перевіряємо поля та зміщення

Поля на кшталт `pev_origin` розташовані у структурі рушія. Через `pdata` читаємо вже поля об’єкта C++ з ігрової DLL. Тут треба знати клас сутності, розташування поля та поправку платформи. Самої наявної сутності для безпечного читання недостатньо.

- У `get_pdata_int()` і `get_pdata_float()` один крок зміщення дорівнює 4 байтам.
- У функціях із байтовим доступом, наприклад `get_pdata_byte()` і `get_pdata_ent()`, зміщення задається в байтах.
- `linuxdiff` додається в тих самих одиницях, що й зміщення цієї функції. Звичайна поправка за замовчуванням підходить не кожному класу.
- Зміщення в байтах, зміщення в комірках і поправку Linux із різних таблиць не змішуємо. Спочатку з’ясовуємо, до якого доступу та класу належить кожне число.


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

**Приклад gamedata CS 1.6.** `get_ent_data_*` знаходить поле за ім’ям у gamedata AMXX і перевіряє оголошений тип. Числовий offset не доводиться зашивати у плагін, але gamedata все ще має відповідати грі, а поле належати до потрібного класу. Якщо вказівника немає, можна отримати `-1`; перед читанням повернутої сутності перевіряємо її.

Джерела: [`get_pdata_int`](https://www.amxmodx.org/api/fakemeta/get_pdata_int), [`get_pdata_ent`](https://www.amxmodx.org/api/fakemeta/get_pdata_ent), [`get_ent_data_entity`](https://www.amxmodx.org/api/fakemeta/get_ent_data_entity).

<a id="reapi-members"></a>
## Поля за іменами через ReAPI

**CS 1.6 + ReAPI + ReGameDLL_CS.** Через `get_member()` читаємо іменовані поля ігрової DLL, а через `get_entvar()` поля рушія. Обидві функції отримують індекс сутності, але звертаються до різних даних. Нижче так читаються гроші та здоров’я гравця.


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

Якщо зміна має запустити ігрову логіку, вибираємо функцію самої операції. Наприклад, `rg_add_account()` оновить гроші через призначений для цього API. Прямий перезапис `m_iAccount` обходить дії ігрової функції, навіть якщо замість числового offset використовується зрозуміле ім’я.

Джерела: [ReAPI members](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll.inc), [ReAPI entvars](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_engine.inc).

<a id="weapons-spawn-and-death"></a>
## Видача зброї, відродження та смерть

**CS 1.6.** У зброї є індекс сутності й окремий ID виду `CSW_*`. Запас набоїв і набої в магазині теж зберігаються окремо. Нижче видаємо AK-47 через ігрову функцію, перевіряємо успішність видачі, потім задаємо запас набоїв.


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

Якщо в гравця вже є основна зброя, окремо вирішуємо, що з нею робити: один `give_item()` повних правил видачі не визначає. Запас AK-47 належить до спільного типу боєприпасів, яким можуть користуватися й інші види зброї.

Для відродження в **CS** є `ExecuteHamB( Ham_CS_RoundRespawn, player )` і, з ReAPI, `rg_round_respawn`. Вони ближчі до правил гри, ніж просте встановлення здоров’я чи загальний spawn. Перед викликом перевіряємо команду гравця, підключення, стан живий/мертвий і правила раунду.

Для смерті теж вибираємо потрібну операцію. Встановлення здоров’я в нуль, `user_kill()`, завдання шкоди й `Ham_Killed` по-різному зачіпають хуки шкоди, визначення вбивці та правила гри. Замінювати одне іншим без урахування цих ефектів не можна.

Джерела: [`give_item`](https://www.amxmodx.org/api/fun/give_item), [`cs_set_user_bpammo`](https://www.amxmodx.org/api/cstrike/cs_set_user_bpammo), [Ham functions](https://www.amxmodx.org/api/ham_const), [`user_kill`](https://www.amxmodx.org/api/amxmodx/user_kill).
