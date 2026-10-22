# Налагодження та продуктивність

[English](../en/12-debugging-and-performance.md) | [Українська](../uk/12-debugging-and-performance.md) | [Русский](../ru/12-debugging-and-performance.md)

[Зміст](../../README.uk.md)

Завантаження, діагностика виконання, володіння ресурсами та часті callbacks. Оптимізуйте підтверджену гарячу ділянку зі збереженням контракту події й даних.

- [Помилки й попередження компілятора](#compile)
- [Помилка завантаження та помилка виконання](#load-errors)
- [Журналювання помилки з контекстом](#logging)
- [Зупинка плагіна без обов’язкової залежності](#fail-state)
- [Дешеві перевірки перед важкою роботою](#hot-paths)
- [Квадрат відстані для перевірки радіуса](#distance)
- [Static зберігає стан між викликами](#static-locals)
- [Володіння handle важливіше за ненульове значення](#ownership)
- [Ігровий час не є точним профайлером](#clocks)

<a id="compile"></a>

## Помилки й попередження компілятора

Невизначений символ часто означає відсутній або неправильний include; tag mismatch може означати передачу Float, enum чи handle як іншого виду cell. Виправляйте контракт, а не приховуйте попередження приведенням тегу.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:duration = 1.5;
	new seconds = floatround( duration, floatround_ceil );
	server_print( "duration=%.1f, whole seconds=%d", duration, seconds ); // 1.5, 2
}
```

Компілюйте з потрібним набором includes. Успішна збірка не перевіряє валідність сутностей, доступність модулів, шляхи чи час виклику callbacks на сервері.

[Compiler options](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))

<a id="load-errors"></a>

## Помилка завантаження та помилка виконання

Якщо плагін не запускається, перевірте `amxx plugins`, `amxx modules` та журнал помилок AMXX. Невідома native часто означає відсутнього постачальника або старіший runtime/модуль, ніж include під час компіляції.

Для помилок виконання додайте плагіну прапорець debug у `plugins.ini`, відтворіть подію та перевірте функцію й рядок у журналі. Переконайтеся, що завантажений `.amxx` відповідає вихідному коду. [Сумісність](COMPATIBILITY.md).

[Debugging](https://wiki.alliedmods.net/Debugging_Plugins_(AMX_Mod_X))

<a id="logging"></a>

## Журналювання помилки з контекстом

Використовуйте сталий рядок формату, а зовнішній текст передавайте аргументом. Додайте операцію та контекст підключення, щоб повідомлення залишалося зрозумілим після виходу гравця.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	log_amx( "Authorized: slot=%d userid=%d auth=%s", id, get_user_userid(id), authid );
}
```

Це діагностичний приклад, а не вимога журналювати кожну авторизацію. Не виводьте паролі, доступ до БД чи кожен кадр. Повторні помилки варто обмежувати в місці їх виникнення.

[amxmodx.inc](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="fail-state"></a>

## Зупинка плагіна без обов’язкової залежності

`set_fail_state()` зупиняє поточний плагін і записує причину. Це підходить для відсутньої обов’язкової залежності чи файла, без яких коректна робота неможлива.

```pawn
#include <amxmodx>
#include <amxmisc>

public plugin_cfg()
{
	new directory[128], path[192];
	get_configsdir( directory, charsmax(directory) );
	formatex( path, charsmax(path), "%s/required_rules.ini", directory );
	if( !file_exists(path) )
	{
		set_fail_state( "Required rules file is missing: %s", path );
		return;
	}

	server_print( "Required file found" );
}
```

Необов’язкову косметичну можливість можна вимкнути окремо. Fail state потрібен, коли плагін справді не може виконати свою задачу, а не для кожної відхиленої команди.

[amxmodx.inc](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="hot-paths"></a>

## Дешеві перевірки перед важкою роботою

PreThink може викликатися дуже часто. Спочатку відсіюйте вимкнений або непотрібний стан; не читайте файли, не відкривайте SQL і не скануйте всіх гравців заради локальної дії.

```pawn
#include <amxmodx>
#include <fakemeta>
new bool:g_enabled;
new Float:g_next_check[MAX_PLAYERS + 1];

public plugin_init()
{
	bind_pcvar_num( create_cvar("notes_enabled", "1"), g_enabled );
	register_forward( FM_PlayerPreThink, "player_prethink" );
}

public client_putinserver( id )
{
	g_next_check[id] = 0.0;
}

public player_prethink( id )
{
	if( !g_enabled || !is_user_alive(id) )
		return FMRES_IGNORED;

	new Float:now = get_gametime();
	if( now < g_next_check[id] )
		return FMRES_IGNORED;

	g_next_check[id] = now + 1.0;
	// Perform the periodic check here, not on every call.
	return FMRES_IGNORED;
}
```

Це обмежує частоту тіла, а не самого хука. Якщо покадровий доступ не потрібен, task, think сутності чи конкретна подія можуть бути зрозумілішими. Не переносьте обчислення, якщо це змінює потрібний момент виконання.

[get_gametime](https://www.amxmodx.org/api/amxmodx/get_gametime)

<a id="distance"></a>

## Квадрат відстані для перевірки радіуса

Для перевірки радіуса порівняйте суму квадратів різниць координат із квадратом радіуса. Для невід’ємного радіуса рішення те саме, без обчислення самої відстані.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:first[3] = {0.0, 0.0, 0.0};
	new Float:second[3] = {3.0, 4.0, 0.0};
	new Float:radius = 5.0, Float:distance_squared;
	for( new axis; axis < 3; axis++ )
	{
		new Float:delta = second[axis] - first[axis];
		distance_squared += delta * delta;
	}

	server_print( "Inside: %d", distance_squared <= radius * radius ); // 1
}
```

Це геометрична перевірка, а не видимість чи доступність шляху. Двовимірний радіус свідомо не враховує висоту; не втрачайте її випадково.

[Vector functions](https://www.amxmodx.org/api/vector)

<a id="static-locals"></a>

## Static зберігає стан між викликами

Локальний `static` повторно використовує буфер між викликами; звичайний локальний `new` має окремий час життя для кожного виклику. Рекурсія чи повторний вхід у функцію спільно використовують static.

```pawn
#include <amxmodx>

public plugin_init()
{
	new first = next_counter();
	new second = next_counter();
	server_print( "%d, %d", first, second ); // 1, 2
}

next_counter()
{
	static count;
	return ++count;
}
```

Static зберігає лічильник між викликами. У рекурсивному коді чи при повторному вході тимчасові дані окремого виклику тримайте у звичайних локальних змінних. Збільшення `#pragma dynamic` не виправляє нескінченну рекурсію чи неправильні індекси.

[Pawn language](https://wiki.alliedmods.net/Pawn_Tutorial)

<a id="ownership"></a>

## Володіння handle важливіше за ненульове значення

Handle має сенс лише для свого API. Важливо знати, хто його створює, хто закриває та яке значення означає помилку.

- Array / Trie: `ArrayDestroy()` / `TrieDestroy()` для власних контейнерів.
- nVault: `nvault_close()`; помилка відкриття `INVALID_HANDLE` (`-1`).
- SQLX: власні tuples, з’єднання й підготовлені запити закривають через `SQL_FreeHandle()`; запит потокового callback не звільняють.
- New menu: знищення залежить від часу життя; спільне меню не можна видаляти після першого гравця.

[Контейнери](03-arrays-and-containers.md) | [Меню](07-tasks-and-menus.md) | [Сховища](09-files-vault-and-sql.md)

[ArrayDestroy](https://www.amxmodx.org/api/cellarray/ArrayDestroy) | [SQL_ThreadQuery](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery)

<a id="clocks"></a>

## Ігровий час не є точним профайлером

`get_gametime()` зручний для cooldown і планування в межах мапи. `get_systime()` повертає цілі секунди календарного часу для постійних позначок. Це різні задачі.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:next_action = get_gametime() + 2.0;
	new expires_at = get_systime() + 3600;
	server_print( "Map deadline: %.2f, wall-clock expiry: %d", next_action, expires_at );
}
```

Не вимірюйте короткий блок відніманням двох читань ігрового часу в одному кадрі. Порівнюйте однакове навантаження з контрольованою кількістю гравців/сутностей; частота callback і вартість одного виклику є різними величинами.

[get_gametime](https://www.amxmodx.org/api/amxmodx/get_gametime) | [get_systime](https://www.amxmodx.org/api/amxmodx/get_systime)
