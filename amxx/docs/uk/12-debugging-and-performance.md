# Налагодження та продуктивність

[EN](../en/12-debugging-and-performance.md) | [UK](../uk/12-debugging-and-performance.md) | [RU](../ru/12-debugging-and-performance.md)

[Зміст](../../README.uk.md)

Спочатку з’ясовуємо, чому плагін не завантажився або де впав, потім дивимося на зайву роботу в частих викликах. Дорогою розбираємо `static`, час і звільнення ресурсів: ці дрібниці часто пояснюють і помилки, і неочікуване навантаження.

- [Помилки й попередження компілятора](#compile)
- [Помилка завантаження та помилка виконання](#load-errors)
- [Пишемо в журнал достатньо даних для розбору помилки](#logging)
- [Зупиняємо плагін, якщо без файлу він не запрацює](#fail-state)
- [У частому хуку спочатку перевіряємо, чи потрібна робота](#hot-paths)
- [Квадрат відстані для перевірки радіуса](#distance)
- [Що зберігає локальний `static` між викликами](#static-locals)
- [Хто має закривати handle](#ownership)
- [Ігровий час, календарні мітки й вимірювання навантаження](#clocks)

<a id="compile"></a>

## Помилки й попередження компілятора

`Undefined symbol` часто означає, що потрібний include не підключений або взята інша його версія. За `tag mismatch` перевіряємо, що передаємо функції: вона може чекати `Float`, тег `enum` або handle, а отримувати значення з іншим тегом. Саме приведення тегу попередження приховає, але неправильне значення не виправить. У прикладі дробовий час справді переводимо в цілі секунди через `floatround()`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:duration = 1.5;
	new seconds = floatround( duration, floatround_ceil );
	server_print( "duration=%.1f, whole seconds=%d", duration, seconds ); // 1.5, 2
}
```

Компілюємо з тим набором includes, під який працює сервер. Успішна збірка перевіряє код, але не підтверджує наявність модулів і файлів, валідність сутностей або правильний момент виклику обробника на сервері.

[Compiler options](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))

<a id="load-errors"></a>

## Помилка завантаження та помилка виконання

Якщо плагін узагалі не запустився, починаємо з `amxx plugins`, `amxx modules` і журналу помилок AMXX. Повідомлення про невідому native зазвичай означає, що її нікому виконати: немає потрібного модуля чи плагіна. Інша часта причина: include під час компіляції новіший, ніж установлений AMXX або модуль.

Якщо плагін завантажився, але падає на дії, вмикаємо йому `debug` у `plugins.ini`, повторюємо дію й дивимося функцію з рядком у журналі. Перевіряємо й сам `.amxx`: він має бути зібраний із того вихідного коду, за яким шукаємо помилку. [Сумісність](COMPATIBILITY.md).

[Debugging](https://wiki.alliedmods.net/Debugging_Plugins_(AMX_Mod_X))

<a id="logging"></a>

## Пишемо в журнал достатньо даних для розбору помилки

Повідомлення «помилка гравця» мало допоможе після його виходу. Записуємо, що відбувалося, слот, `userid` і дані підключення, за якими можна відновити ситуацію. Рядок формату тримаємо постійним, а отриманий текст підставляємо аргументом, щоб `%` усередині нього не став частиною формату.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	log_amx( "Authorized: slot=%d userid=%d auth=%s", id, get_user_userid(id), authid );
}
```

Приклад показує склад корисного повідомлення, а не пропонує записувати кожну авторизацію. Паролям і даним доступу до БД в журналі не місце. Якщо одна помилка сиплеться кожен кадр, обмежуємо повторні записи там, де вона виникає, інакше потрібні повідомлення загубляться в потоці.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="fail-state"></a>

## Зупиняємо плагін, якщо без файлу він не запрацює

Якщо без обов'язкового файлу чи залежності плагін працювати не зможе, краще зупинити його зі зрозумілою причиною через `set_fail_state()`. Функція записує причину й зупиняє поточний плагін. Тут без `required_rules.ini` продовжувати обробку правил немає сенсу, тому роботу завершуємо.

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

Якщо не завантажився лише необов'язковий звук або інша косметична можливість, можна вимкнути одну цю частину. Відхилена команда гравця теж не привід зупиняти весь плагін: зупинку через `set_fail_state()` залишаємо для ситуації, коли його основна робота неможлива.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="hot-paths"></a>

## У частому хуку спочатку перевіряємо, чи потрібна робота

`PreThink` викликається часто, тому спочатку робимо короткі перевірки й виходимо, якщо робота зараз не потрібна. Читання файлів, нове SQL-підключення або перебір усіх гравців у такому місці швидко стануть зайвим навантаженням. У прикладі до основної перевірки доходимо не частіше разу на секунду для кожного живого гравця.

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
	// Перевіряємо раз на секунду, а не на кожному виклику хука.
	return FMRES_IGNORED;
}
```

Хук при цьому продовжує викликатися як раніше, рідше виконується лише наше тіло перевірки. Якщо покадрова точність не потрібна, зрозумілішим може бути відкладене завдання, `Think` сутності або відповідна подія. Але переносити обчислення можна лише тоді, коли це не змінює потрібний момент дії.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime)

<a id="distance"></a>

## Квадрат відстані для перевірки радіуса

Якщо потрібно лише дізнатися, чи потрапила точка в радіус, саму відстань рахувати необов'язково. Складаємо квадрати різниць координат і порівнюємо з квадратом радіуса. Для невід'ємного радіуса відповідь та сама, а добувати корінь не доводиться. Тут відстань дорівнює п'яти, тому точка входить у радіус `5.0`.

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

Така перевірка нічого не каже про стіни, видимість або доступний шлях. Якщо навмисно перевіряємо коло без урахування висоти, використовуємо дві координати; третю не варто втрачати випадково, коли потрібен об'ємний радіус.

[Vector functions](https://www.amxmodx.org/api/vector)

<a id="static-locals"></a>

## Що зберігає локальний `static` між викликами

Локальний `static` залишається між викликами функції. У прикладі `count` не створюється заново з нуля, тому перший виклик дає `1`, наступний `2`. У звичайного локального `new` кожен виклик має свої дані та свій час життя.

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

Під час рекурсії чи повторного входу у функцію той самий `static` буде спільним для всіх викликів. Тимчасові дані окремого виклику тому залишаємо звичайними локальними змінними. Збільшення `#pragma dynamic` не виправить нескінченну рекурсію чи вихід за межі масиву.

[Pawn language](https://wiki.alliedmods.net/Pawn_Tutorial)

<a id="ownership"></a>

## Хто має закривати handle

Ненульове число ще не каже, що handle можна використовувати чи закривати. Дивимося, який API його видав, чим він повідомляє про помилку й хто має звільнити ресурс. Чужий handle закривати так само небезпечно, як забути закрити власний.

- Власні контейнери `Array` і `Trie` закриваємо через `ArrayDestroy()` і `TrieDestroy()`.
- nVault закриваємо через `nvault_close()`. Помилка відкриття тут `INVALID_HANDLE`, тобто `-1`, а не нуль.
- У SQLX свої tuples, з’єднання й підготовлені запити закриваємо через `SQL_FreeHandle()`. Запит, отриманий в обробнику відповіді асинхронного запиту, самі не звільняємо.
- У нового меню враховуємо строк використання. Якщо одне меню спільне для кількох гравців, після відповіді першого його ще не можна знищувати.

[Контейнери](03-arrays-and-containers.md) | [Меню](07-tasks-and-menus.md) | [Сховища](09-files-vault-and-sql.md)

[`ArrayDestroy`](https://www.amxmodx.org/api/cellarray/ArrayDestroy) | [`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery)

<a id="clocks"></a>

## Ігровий час, календарні мітки й вимірювання навантаження

Для затримки дії в межах карти беремо `get_gametime()`. Для строку, який потрібно зберегти як календарну мітку, є `get_systime()` із цілими секундами. Тут окремо рахуємо наступну дію через дві ігрові секунди й строк закінчення через годину реального часу.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:next_action = get_gametime() + 2.0;
	new expires_at = get_systime() + 3600;
	server_print( "Map deadline: %.2f, wall-clock expiry: %d", next_action, expires_at );
}
```

Коротку ділянку коду не варто вимірювати двома читаннями ігрового часу в одному кадрі: для такої точності цей годинник не підходить. Під час порівняння навантаження зберігаємо однакову кількість гравців і сутностей. І окремо дивимося, скільки коштує один виклик і як часто він узагалі відбувається.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime) | [`get_systime`](https://www.amxmodx.org/api/amxmodx/get_systime)
