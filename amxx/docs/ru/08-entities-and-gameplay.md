# Сущности и игровая логика

[EN](../en/08-entities-and-gameplay.md) | [UK](../uk/08-entities-and-gameplay.md) | [RU](../ru/08-entities-and-gameplay.md) | [Содержание](../../README.ru.md)

Жизненный цикл сущностей, типизированные поля, triggers и граница между изменением данных и вызовом игровой логики. Блоки кода независимы; функции с аргументами показывают операции, а не автоматически зарегистрированные команды.

- [Индекс не является идентичностью сущности](#entity-identity)
- [Читаем entvars с правильным типом](#entvars-and-vectors)
- [Перемещение через API origin](#moving-entities)
- [Создаём реальный класс и настраиваем его](#create-and-spawn)
- [`Touch`: проверяем обе стороны и забираем один раз](#touch-and-consume)
- [`Think` использует абсолютное игровое время](#think-scheduling)
- [Моделям нужны precache и границы](#models-and-precache)
- [Ищем точно и удаляем только свои сущности](#search-and-remove)
- [Слот владельца тоже может смениться](#owner-lifetime)
- [Private data: единицы, класс и платформа](#private-data)
- [Именованные поля ReAPI, отдельный модуль](#reapi-members)
- [Оружие, respawn и смерть являются игровыми операциями](#weapons-spawn-and-death)

<a id="entity-identity"></a>
## Индекс не является идентичностью сущности

Индексы сущностей используются повторно после удаления. Сохранённый индекс может быть валидным, но уже указывать на другую сущность. Для отложенного действия сохрани `pev_serial` вместе с индексом и проверяй оба.


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

Сохраняй serial в момент запоминания сущности, а не в начале отложенного callback. `pev_valid` возвращает `0` для невалидной сущности, `1` для существующего edict и `2`, когда есть ещё и private data. `pev_valid == 2` допускает доступ к private data в принципе, но не подтверждает соответствие класса выбранному offset.

Источники: [`pev_valid`](https://www.amxmodx.org/api/fakemeta/pev_valid), [`pev_serial`](https://www.amxmodx.org/api/fakemeta/pev_serial).

<a id="entvars-and-vectors"></a>
## Читаем entvars с правильным типом

У `pev` разные формы вызова для целых чисел, float, строк и векторов. Cell, возвращённая напрямую, не становится автоматически `Float`. Здесь float-поля и векторы читаются в выходные переменные.


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

Вектор содержит три `Float` cells, а не три округлённые целые координаты. Velocity задаёт направление и скорость; angles имеют другое поле и смысл. В Engine соответствующие семейства: `entity_get_int`, `entity_get_float`, `entity_get_vector`, `entity_get_string` и `entity_get_edict`.

Источники: [`pev`](https://www.amxmodx.org/api/fakemeta/pev), [`entity_get_vector`](https://www.amxmodx.org/api/engine/entity_get_vector).

<a id="moving-entities"></a>
## Перемещение через API origin

Смена позиции требует также обновления связей сущности в engine. Используй `entity_set_origin` или `EngFunc_SetOrigin`, а не обычное присваивание `pev_origin`.


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

Это перемещает сущность на 32 единицы по Z, но не проверяет, нет ли там стены. Для телепортации игрока нужны подходящий hull trace и осознанное решение о скорости и состоянии на земле; setter позиции сам не ищет безопасное место.

Источники: [`entity_set_origin`](https://www.amxmodx.org/api/engine/entity_set_origin), [engine function constants](https://www.amxmodx.org/api/fakemeta_const).

<a id="create-and-spawn"></a>
## Создаём реальный класс и настраиваем его

`create_entity` требует класс, реализованный в game DLL. Собственная строка classname не создаёт новый C++ класс. Для простого trigger создай `info_target`, выполни его spawn, затем задай свою метку и свойства trigger.


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

Результат является невидимым trigger, а не моделью предмета. Для игровых сущностей, настраиваемых через keyvalues, нужные keyvalues задаются до `DispatchSpawn`; spawn может перезаписать поля или удалить неподходящую сущность. Порядок зависит от класса, поэтому не переноси этот рецепт без изменений на двери, оружие или brush-сущности.

Источники: [`create_entity`](https://www.amxmodx.org/api/engine/create_entity), [`DispatchSpawn`](https://www.amxmodx.org/api/engine/DispatchSpawn), [`DispatchKeyValue`](https://www.amxmodx.org/api/engine/DispatchKeyValue).

<a id="touch-and-consume"></a>
## `Touch`: проверяем обе стороны и забираем один раз

Trigger может многократно касаться игрока. Handler принимает только живых игроков и удаляет свой marker до одноразовой награды. Он предназначен для `notes_marker` из предыдущего раздела.


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

После удаления не читай поля `marker`. Пример забирает marker даже при 100 здоровья; перенеси эту проверку до удаления, если полностью здоровый игрок должен оставлять его на месте. Поле `owner` является игровыми данными, а не автоматическим правилом доступа.

Источники: [`register_touch`](https://www.amxmodx.org/api/engine/register_touch), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity), [`set_user_health`](https://www.amxmodx.org/api/fun/set_user_health).

<a id="think-scheduling"></a>
## `Think` использует абсолютное игровое время

`nextthink` является временной меткой, а не интервалом. Первый вызов планируется как `get_gametime() + delay`; для повторения следующий вызов нужно запланировать заново.


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

Это задаёт примерно секундный ритм; вызовы зависят от кадров сервера, а не от таймера реального времени. Учитывай число активных сущностей. Не обходи всех игроков или все сущности в каждом thinker, если данные можно сохранить при создании или обновлять событиями.

Источники: [`register_think`](https://www.amxmodx.org/api/engine/register_think), [`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime).

<a id="models-and-precache"></a>
## Моделям нужны precache и границы

**Путь ресурса CS 1.6.** Выполни precache модели в `plugin_precache`, затем назначь тот же ресурс сущности. Нужные границы столкновений задавай после модели, поскольку её назначение может влиять на bounds.


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

Модель гранаты не делает сущность гранатой: поведение по-прежнему задают её реальный класс и callbacks. Для своего ресурса проверь путь и добавь файл на сервер. Не создавай неограниченный список precache из произвольного клиентского ввода.

Источники: [`precache_model`](https://www.amxmodx.org/api/amxmodx/precache_model), [`entity_set_model`](https://www.amxmodx.org/api/engine/entity_set_model), [`entity_set_size`](https://www.amxmodx.org/api/engine/entity_set_size).

<a id="search-and-remove"></a>
## Ищем точно и удаляем только свои сущности

Свой classname делает очистку явной. Найди следующий индекс до удаления текущей сущности, чтобы поиск не зависел от уже освобождённой сущности.


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

Делай это в подходящем событии жизненного цикла, например после завершения своего режима. Глобальный поиск каждый кадр редко оправдан. Не удаляй игроков, `worldspawn` или все сущности общего игрового classname ради очистки одной возможности плагина.

Источники: [`find_ent_by_class`](https://www.amxmodx.org/api/engine/find_ent_by_class), [`remove_entity`](https://www.amxmodx.org/api/engine/remove_entity).

<a id="owner-lifetime"></a>
## Слот владельца тоже может смениться

Для своей сущности сохрани индекс игрока-владельца и userid именно этого соединения. Так новый игрок в том же слоте не унаследует старый marker. Пример резервирует для этого `iuser1` только на своей сущности.


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

Отдельно реши, должен ли disconnect удалить marker, передать его или оставить нейтральным. Не перезаписывай `iuser*` игровых сущностей или другого плагина. Ownership также может влиять на столкновения, поэтому это не всегда подходящее поле для произвольных метаданных.

Источники: [`get_user_userid`](https://www.amxmodx.org/api/amxmodx/get_user_userid), [`entity_set_edict`](https://www.amxmodx.org/api/engine/entity_set_edict).

<a id="private-data"></a>
## Private data: единицы, класс и платформа

Entvars вроде `pev_origin` принадлежат структуре engine. `pdata` обращается к полям C++ объекта game DLL. Валидного edict недостаточно: должны совпадать класс, структура данных и platform offset.

- `get_pdata_int` / `get_pdata_float` используют единицы offset по 4 байта.
- Byte-addressable natives, в том числе `get_pdata_byte` или `get_pdata_ent`, используют байтовые offsets.
- `linuxdiff` добавляется к offset в единицах конкретной native; распространённый default подходит не всем классам.
- Не смешивай байтовый offset, cell offset и поправку Linux из разных таблиц.


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

**Пример gamedata CS 1.6.** `get_ent_data_*` находит именованное поле в AMXX gamedata и проверяет объявленный тип. Числовые offsets не нужно вшивать в плагин, но подходящие gamedata и правильный класс всё равно нужны. Отсутствующий pointer может дать `-1`; проверь результат перед чтением сущности.

Источники: [`get_pdata_int`](https://www.amxmodx.org/api/fakemeta/get_pdata_int), [`get_pdata_ent`](https://www.amxmodx.org/api/fakemeta/get_pdata_ent), [`get_ent_data_entity`](https://www.amxmodx.org/api/fakemeta/get_ent_data_entity).

<a id="reapi-members"></a>
## Именованные поля ReAPI, отдельный модуль

**CS 1.6 + ReAPI + ReGameDLL_CS.** `get_member` читает именованные поля game DLL; `get_entvar` читает entvars. Это разные уровни данных, хотя обе функции принимают индекс сущности.


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

Если операция имеет сопутствующие действия, выбирай native уровня поведения: например `rg_add_account` для денег вместо простого перезаписывания `m_iAccount`. Именованное поле убирает magic offset, но прямое присваивание всё равно обходит логику игровой функции.

Источники: [ReAPI members](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_gamedll.inc), [ReAPI entvars](https://github.com/rehlds/ReAPI/blob/master/reapi/extra/amxmodx/scripting/include/reapi_engine.inc).

<a id="weapons-spawn-and-death"></a>
## Оружие, respawn и смерть являются игровыми операциями

**CS 1.6.** Индекс сущности оружия не равен его `CSW_*` ID, а запас патронов не является магазином. Выдай предмет через игровую логику, проверь результат, затем настрой нужный запас патронов.


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

Определи, что делать с имеющимся основным оружием: один give-вызов не задаёт полную политику снаряжения. Запас AK-47 использует общий CS ammo pool с оружием того же типа боеприпасов.

Для respawn **специфичный для CS** `ExecuteHamB( Ham_CS_RoundRespawn, player )` или отдельный ReAPI `rg_round_respawn` ближе к игровому поведению, чем простая смена здоровья или общий spawn. Сначала проверь команду, состояние connected/dead и правила раунда.

Для смерти присваивание нулевого здоровья, `user_kill`, нанесение урона и вызов `Ham_Killed` по-разному влияют на damage hooks, авторство убийства и игровые правила. Выбирай нужную операцию: универсальной взаимозамены нет.

Источники: [`give_item`](https://www.amxmodx.org/api/fun/give_item), [`cs_set_user_bpammo`](https://www.amxmodx.org/api/cstrike/cs_set_user_bpammo), [Ham functions](https://www.amxmodx.org/api/ham_const), [`user_kill`](https://www.amxmodx.org/api/amxmodx/user_kill).
