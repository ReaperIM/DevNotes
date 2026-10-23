# Сущности и игровая логика

[EN](../en/08-entities-and-gameplay.md) | [UK](../uk/08-entities-and-gameplay.md) | [RU](../ru/08-entities-and-gameplay.md) | [Содержание](../../README.ru.md)

У сущности есть срок жизни, поля и поведение, заданное игрой. Ниже разберём, как не обратиться к уже удалённой сущности, правильно читать её данные и выбирать между записью поля и игровой функцией. Каждый блок отдельный; функции с параметрами показывают готовую операцию, которую нужно вызвать из своего кода.

- [Как не перепутать удалённую сущность с новой](#entity-identity)
- [Читаем поля сущности с нужным типом](#entvars-and-vectors)
- [Перемещаем сущность через функцию положения](#moving-entities)
- [Создаём сущность и настраиваем её поведение](#create-and-spawn)
- [Одно касание, одна награда](#touch-and-consume)
- [Когда должен сработать следующий `Think`](#think-scheduling)
- [Загружаем модель и задаём размер сущности](#models-and-precache)
- [Находим и удаляем только свои сущности](#search-and-remove)
- [Владелец тоже может смениться в том же слоте](#owner-lifetime)
- [Private data: проверяем поля и смещения](#private-data)
- [Поля по именам через ReAPI](#reapi-members)
- [Выдача оружия, возрождение и смерть](#weapons-spawn-and-death)

<a id="entity-identity"></a>
## Как не перепутать удалённую сущность с новой

После удаления индекс сущности может достаться другой сущности. Одна проверка сохранённого индекса тогда пройдёт, но объект уже будет другим. Для отложенного действия сохраняем вместе с индексом значение `pev_serial`, а перед работой проверяем оба.


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

Серийный номер запоминаем сразу, когда выбрали сущность. Если прочитать его только в отложенном обработчике, проверять будет не с чем. `pev_valid()` возвращает `0`, если сущности нет, `1`, если есть её запись в движке (edict), и `2`, если есть ещё и private data. Значение `2` позволяет обращаться к private data, но нужный класс и подходящие ему offsets всё равно проверяем отдельно.

Источники: [`pev_valid`](https://www.amxmodx.org/api/fakemeta/pev_valid), [`pev_serial`](https://www.amxmodx.org/api/fakemeta/pev_serial).

<a id="entvars-and-vectors"></a>
## Читаем поля сущности с нужным типом

У `pev()` есть разные способы чтения чисел, `Float`, строк и векторов. Для дробных значений и векторов ниже передаём выходные переменные. Если просто получить ячейку через возвращаемое значение, она сама по себе во `Float` не преобразуется.


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

У вектора три ячейки `Float`: дробные координаты не нужно округлять до целых. `velocity` задаёт направление и скорость; для углов есть другое поле. В Engine похожие операции разделены на `entity_get_int`, `entity_get_float`, `entity_get_vector`, `entity_get_string` и `entity_get_edict`.

Источники: [`pev`](https://www.amxmodx.org/api/fakemeta/pev), [`entity_get_vector`](https://www.amxmodx.org/api/engine/entity_get_vector).

<a id="moving-entities"></a>
## Перемещаем сущность через функцию положения

При перемещении движку нужно обновить и положение сущности в своих структурах. Поэтому используем `entity_set_origin()` или `EngFunc_SetOrigin`. Одной прямой записи в `pev_origin` для этого недостаточно. Ниже читаем позицию, прибавляем `32.0` по высоте и задаём новое положение.


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

Сущность поднимется на 32 единицы по Z. Проверки стены в точке назначения здесь нет. Для телепорта игрока ещё нужен подходящий hull trace, а также решение, что делать со скоростью и состоянием на земле. Функция установки позиции сама безопасное место не подбирает.

Источники: [`entity_set_origin`](https://www.amxmodx.org/api/engine/entity_set_origin), [engine function constants](https://www.amxmodx.org/api/fakemeta_const).

<a id="create-and-spawn"></a>
## Создаём сущность и настраиваем её поведение

`create_entity()` создаёт класс, который уже реализован в игровой DLL. Новая строка classname сама по себе новый класс C++ не добавляет. Для своего простого триггера ниже создаём `info_target`, вызываем его spawn, затем задаём имя-маркер, размер и свойства триггера.


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

Получается невидимая область срабатывания. Чтобы показать предмет, отдельно понадобится модель. У сущностей с keyvalues нужные значения могут задаваться до `DispatchSpawn()`: spawn способен изменить поля или удалить непригодную сущность. Порядок зависит от класса, поэтому пример триггера нельзя без проверки переносить на двери, оружие и brush-сущности.

Источники: [`create_entity`](https://www.amxmodx.org/api/engine/create_entity), [`DispatchSpawn`](https://www.amxmodx.org/api/engine/DispatchSpawn), [`DispatchKeyValue`](https://www.amxmodx.org/api/engine/DispatchKeyValue).

<a id="touch-and-consume"></a>
## Одно касание, одна награда

Триггер может получать повторные касания от игрока. Ниже проверяем, что `notes_marker` ещё существует и игрок жив, затем удаляем маркер до выдачи награды. Так один маркер не выдаст лечение повторно. Пример рассчитан на триггер из предыдущего раздела.


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

	// Убираем до награды, чтобы не выдать её повторно при новом касании.
	remove_entity( marker );
	new health = get_user_health( player );

	if( health < 100 )
		set_user_health( player, min( health + 10, 100 ) );
}
```

После удаления читать поля `marker` уже нельзя. Сейчас маркер расходуется даже при 100 HP. Если он должен оставаться для другого игрока, проверку полного здоровья переносим до удаления. Поле `owner` само права на получение награды не проверяет.

Источники: [`register_touch`](https://www.amxmodx.org/api/engine/register_touch), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity), [`set_user_health`](https://www.amxmodx.org/api/fun/set_user_health).

<a id="think-scheduling"></a>
## Когда должен сработать следующий `Think`

В `nextthink` записываем конкретный момент игрового времени. Для вызова через секунду это `get_gametime() + 1.0`. Если сущность должна продолжать работать, в обработчике снова задаём время следующего вызова.


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

Получится интервал примерно в секунду, с привязкой к кадрам сервера. Чем больше активных сущностей, тем чаще суммарно вызываются их обработчики. Если данные можно сохранить при создании или обновить событием, не нужно из каждого `Think` заново обходить всех игроков и все сущности.

Источники: [`register_think`](https://www.amxmodx.org/api/engine/register_think), [`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime).

<a id="models-and-precache"></a>
## Загружаем модель и задаём размер сущности

**Путь ресурса CS 1.6.** Сначала загружаем модель в `plugin_precache()`, затем назначаем ту же модель сущности. Нужный размер области столкновений ставим после модели: её назначение может повлиять на размер.


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
	// Задаём границы после модели, которая может их изменить.
	entity_set_size( marker, Float:{ -16.0, -16.0, -16.0 }, Float:{ 16.0, 16.0, 16.0 } );
}
```

Модель гранаты поменяет внешний вид, но поведение гранаты не добавит. За него отвечают настоящий класс и обработчики сущности. Для своего ресурса проверяем путь и наличие файла на сервере. Список precache держим под контролем, произвольный клиентский ввод в него не добавляем.

Источники: [`precache_model`](https://www.amxmodx.org/api/amxmodx/precache_model), [`entity_set_model`](https://www.amxmodx.org/api/engine/entity_set_model), [`entity_set_size`](https://www.amxmodx.org/api/engine/entity_set_size).

<a id="search-and-remove"></a>
## Находим и удаляем только свои сущности

Свой classname позволяет найти объекты, созданные нашей функцией. Ниже перед удалением текущего маркера сначала находим следующий. Тогда дальнейший поиск не опирается на уже освобождённую сущность.


```pawn
#include <amxmodx>
#include <engine>

public remove_markers()
{
	new entity = find_ent_by_class( 0, "notes_marker" );

	while( entity > 0 )
	{
		// Ищем следующую сущность, пока текущая ещё существует.
		new next = find_ent_by_class( entity, "notes_marker" );
		remove_entity( entity );
		entity = next;
	}
}
```

Такую очистку запускаем по нужному событию, например при завершении режима. Проверять весь мир каждый кадр обычно незачем. Общие игровые классы, игроки и `worldspawn` к нашим маркерам не относятся: массовое удаление таких сущностей сломает игру.

Источники: [`find_ent_by_class`](https://www.amxmodx.org/api/engine/find_ent_by_class), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity).

<a id="owner-lifetime"></a>
## Владелец тоже может смениться в том же слоте

Для своей сущности сохраняем индекс владельца и userid его подключения. Тогда новый игрок в том же слоте не получит чужой старый маркер. Ниже для userid используем поле `iuser1`, которое зарезервировали у собственной сущности.


```pawn
#include <amxmodx>
#include <engine>

public assign_marker_owner( marker, player )
{
	if( !is_valid_ent( marker ) || !is_user_connected( player ) )
		return;

	entity_set_edict( marker, EV_ENT_owner, player );
	// Вместе со слотом владельца сохраняем именно его подключение.
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

Отдельно решаем, что будет с маркером при выходе: удалить, передать другому игроку или оставить без владельца. Чужие поля `iuser*` не перезаписываем. Сам `owner` ещё может влиять на столкновения, поэтому хранить в нём произвольные данные подходит не всегда.

Источники: [`get_user_userid`](https://www.amxmodx.org/api/amxmodx/get_user_userid), [`entity_set_edict`](https://www.amxmodx.org/api/engine/entity_set_edict).

<a id="private-data"></a>
## Private data: проверяем поля и смещения

Поля вроде `pev_origin` находятся в структуре движка. Через `pdata` читаем уже поля объекта C++ из игровой DLL. Здесь нужно знать класс сущности, расположение поля и поправку платформы. Существующей сущности самой по себе для безопасного чтения недостаточно.

- В `get_pdata_int()` и `get_pdata_float()` один шаг смещения равен 4 байтам.
- В функциях с байтовым доступом, например `get_pdata_byte()` и `get_pdata_ent()`, смещение задаётся в байтах.
- `linuxdiff` прибавляется в тех же единицах, что и смещение этой функции. Обычная поправка по умолчанию подходит не каждому классу.
- Смещение в байтах, смещение в ячейках и поправку Linux из разных таблиц не смешиваем. Сначала выясняем, к какому доступу и классу относится каждое число.


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

**Пример gamedata CS 1.6.** `get_ent_data_*` находит поле по имени в gamedata AMXX и проверяет объявленный тип. Числовой offset не приходится зашивать в плагин, но gamedata всё ещё должна соответствовать игре, а поле относиться к нужному классу. Если указателя нет, можно получить `-1`; перед чтением возвращённой сущности проверяем её.

Источники: [`get_pdata_int`](https://www.amxmodx.org/api/fakemeta/get_pdata_int), [`get_pdata_ent`](https://www.amxmodx.org/api/fakemeta/get_pdata_ent), [`get_ent_data_entity`](https://www.amxmodx.org/api/fakemeta/get_ent_data_entity).

<a id="reapi-members"></a>
## Поля по именам через ReAPI

**CS 1.6 + ReAPI + ReGameDLL_CS.** Через `get_member()` читаем именованные поля игровой DLL, а через `get_entvar()` поля движка. Обе функции получают индекс сущности, но обращаются к разным данным. Ниже так читаются деньги и здоровье игрока.


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

Если изменение должно запустить игровую логику, выбираем функцию самой операции. Например, `rg_add_account()` обновит деньги через предназначенный для этого API. Прямая перезапись `m_iAccount` обходит действия игровой функции, даже если вместо числового offset используется понятное имя.

Источники: [ReAPI members](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll.inc), [ReAPI entvars](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_engine.inc).

<a id="weapons-spawn-and-death"></a>
## Выдача оружия, возрождение и смерть

**CS 1.6.** У оружия есть индекс сущности и отдельный ID вида `CSW_*`. Запас патронов и патроны в магазине тоже хранятся отдельно. Ниже выдаём AK-47 через игровую функцию, проверяем успешность выдачи, затем задаём запас патронов.


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

Если у игрока уже есть основное оружие, отдельно решаем, что с ним делать: один `give_item()` полные правила выдачи не определяет. Запас AK-47 относится к общему типу боеприпасов, которым могут пользоваться и другие виды оружия.

Для возрождения в **CS** есть `ExecuteHamB( Ham_CS_RoundRespawn, player )` и, с ReAPI, `rg_round_respawn`. Они ближе к правилам игры, чем простая установка здоровья или общий spawn. Перед вызовом проверяем команду игрока, подключение, состояние жив/мёртв и правила раунда.

Для смерти тоже выбираем нужную операцию. Установка здоровья в ноль, `user_kill()`, нанесение урона и `Ham_Killed` по-разному затрагивают хуки урона, определение убийцы и правила игры. Заменять одно другим без учёта этих эффектов нельзя.

Источники: [`give_item`](https://www.amxmodx.org/api/fun/give_item), [`cs_set_user_bpammo`](https://www.amxmodx.org/api/cstrike/cs_set_user_bpammo), [Ham functions](https://www.amxmodx.org/api/ham_const), [`user_kill`](https://www.amxmodx.org/api/amxmodx/user_kill).
