# Отладка и производительность

[EN](../en/12-debugging-and-performance.md) | [UK](../uk/12-debugging-and-performance.md) | [RU](../ru/12-debugging-and-performance.md)

[Содержание](../../README.ru.md)

Сначала выясняем, почему плагин не загрузился или где упал, потом смотрим на лишнюю работу в частых вызовах. По пути разбираем `static`, время и освобождение ресурсов: эти мелочи часто объясняют и ошибки, и неожиданную нагрузку.

- [Ошибки и предупреждения компилятора](#compile)
- [Ошибка загрузки и ошибка выполнения](#load-errors)
- [Пишем в журнал достаточно данных для разбора ошибки](#logging)
- [Останавливаем плагин, если без файла он не заработает](#fail-state)
- [В частом хуке сначала проверяем, нужна ли работа](#hot-paths)
- [Квадрат расстояния для проверки радиуса](#distance)
- [Что сохраняет локальный `static` между вызовами](#static-locals)
- [Кто должен закрывать handle](#ownership)
- [Игровое время, календарные метки и измерение нагрузки](#clocks)

<a id="compile"></a>

## Ошибки и предупреждения компилятора

`Undefined symbol` часто означает, что нужный include не подключён или взята другая его версия. При `tag mismatch` проверяем, что передаём функции: она может ждать `Float`, тег `enum` или handle, а получать значение с другим тегом. Само приведение тега предупреждение спрячет, но неверное значение не исправит. В примере дробное время действительно переводим в целые секунды через `floatround()`.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:duration = 1.5;
	new seconds = floatround( duration, floatround_ceil );
	server_print( "duration=%.1f, whole seconds=%d", duration, seconds ); // 1.5, 2
}
```

Компилируем с тем набором includes, под который работает сервер. Успешная сборка проверяет код, но не подтверждает наличие модулей и файлов, валидность сущностей или правильный момент вызова обработчика на сервере.

[Compiler options](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))

<a id="load-errors"></a>

## Ошибка загрузки и ошибка выполнения

Если плагин вообще не запустился, начинаем с `amxx plugins`, `amxx modules` и журнала ошибок AMXX. Сообщение о неизвестной native обычно значит, что её некому выполнить: нет нужного модуля или плагина. Другая частая причина: include при компиляции новее, чем установленный AMXX или модуль.

Если плагин загрузился, но падает на действии, включаем ему `debug` в `plugins.ini`, повторяем действие и смотрим функцию со строкой в журнале. Проверяем и сам `.amxx`: он должен быть собран из того исходника, по которому ищем ошибку. [Совместимость](COMPATIBILITY.md).

[Debugging](https://wiki.alliedmods.net/Debugging_Plugins_(AMX_Mod_X))

<a id="logging"></a>

## Пишем в журнал достаточно данных для разбора ошибки

Сообщение «ошибка игрока» мало поможет после его выхода. Записываем, что происходило, слот, `userid` и данные подключения, по которым можно восстановить ситуацию. Строку формата держим постоянной, а пришедший текст подставляем аргументом, чтобы `%` внутри него не стал частью формата.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	log_amx( "Authorized: slot=%d userid=%d auth=%s", id, get_user_userid(id), authid );
}
```

Пример показывает состав полезного сообщения, а не предлагает записывать каждую авторизацию. Паролям и данным доступа к БД в журнале не место. Если одна ошибка сыплется каждый кадр, ограничиваем повторные записи там, где она возникает, иначе нужные сообщения потеряются в потоке.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="fail-state"></a>

## Останавливаем плагин, если без файла он не заработает

Если без обязательного файла или зависимости плагин работать не сможет, лучше остановить его с понятной причиной через `set_fail_state()`. Функция записывает причину и останавливает текущий плагин. Здесь без `required_rules.ini` продолжать обработку правил бессмысленно, поэтому работу завершаем.

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

Если не загрузился только необязательный звук или другая косметическая возможность, можно отключить одну эту часть. Отклонённая команда игрока тоже не повод останавливать весь плагин: остановку через `set_fail_state()` оставляем для ситуации, когда его основная работа невозможна.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="hot-paths"></a>

## В частом хуке сначала проверяем, нужна ли работа

`PreThink` вызывается часто, поэтому сначала делаем короткие проверки и выходим, если работа сейчас не нужна. Чтение файлов, новое SQL-подключение или перебор всех игроков в таком месте быстро станут лишней нагрузкой. В примере до основной проверки доходим не чаще раза в секунду для каждого живого игрока.

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
	// Проверяем раз в секунду, а не на каждом вызове хука.
	return FMRES_IGNORED;
}
```

Хук при этом продолжает вызываться как раньше, реже выполняется только наше тело проверки. Если покадровая точность не нужна, понятнее может быть отложенная задача, `Think` сущности или подходящее событие. Но переносить вычисление можно лишь тогда, когда это не меняет нужный момент действия.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime)

<a id="distance"></a>

## Квадрат расстояния для проверки радиуса

Если нужно только узнать, попала ли точка в радиус, само расстояние считать необязательно. Складываем квадраты разностей координат и сравниваем с квадратом радиуса. Для неотрицательного радиуса ответ тот же, а извлекать корень не приходится. Здесь расстояние равно пяти, поэтому точка входит в радиус `5.0`.

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

Такая проверка ничего не говорит о стенах, видимости или доступном пути. Если намеренно проверяем круг без учёта высоты, используем две координаты; третью не стоит терять случайно, когда нужен объёмный радиус.

[Vector functions](https://www.amxmodx.org/api/vector)

<a id="static-locals"></a>

## Что сохраняет локальный `static` между вызовами

Локальный `static` остаётся между вызовами функции. В примере `count` не создаётся заново с нуля, поэтому первый вызов даёт `1`, следующий `2`. У обычного локального `new` каждый вызов имеет свои данные и своё время жизни.

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

При рекурсии или повторном входе в функцию тот же `static` будет общим для всех вызовов. Временные данные отдельного вызова поэтому оставляем обычными локальными переменными. Увеличение `#pragma dynamic` не исправит бесконечную рекурсию или выход за границы массива.

[Pawn language](https://wiki.alliedmods.net/Pawn_Tutorial)

<a id="ownership"></a>

## Кто должен закрывать handle

Ненулевое число ещё не говорит, что handle можно использовать или закрывать. Смотрим, какой API его выдал, чем он сообщает об ошибке и кто должен освободить ресурс. Чужой handle закрывать так же опасно, как забыть закрыть собственный.

- Собственные контейнеры `Array` и `Trie` закрываем через `ArrayDestroy()` и `TrieDestroy()`.
- nVault закрываем через `nvault_close()`. Ошибка открытия здесь `INVALID_HANDLE`, то есть `-1`, а не ноль.
- У SQLX свои tuples, соединения и подготовленные запросы закрываем через `SQL_FreeHandle()`. Запрос, полученный в обработчике ответа асинхронного запроса, сами не освобождаем.
- У нового меню учитываем срок использования. Если одно меню общее для нескольких игроков, после ответа первого его ещё нельзя уничтожать.

[Контейнеры](03-arrays-and-containers.md) | [Меню](07-tasks-and-menus.md) | [Хранилища](09-files-vault-and-sql.md)

[`ArrayDestroy`](https://www.amxmodx.org/api/cellarray/ArrayDestroy) | [`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery)

<a id="clocks"></a>

## Игровое время, календарные метки и измерение нагрузки

Для задержки действия в пределах карты берём `get_gametime()`. Для срока, который нужно сохранить как календарную метку, есть `get_systime()` с целыми секундами. Здесь отдельно считаем следующее действие через две игровые секунды и срок истечения через час реального времени.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:next_action = get_gametime() + 2.0;
	new expires_at = get_systime() + 3600;
	server_print( "Map deadline: %.2f, wall-clock expiry: %d", next_action, expires_at );
}
```

Короткий участок кода не стоит измерять двумя чтениями игрового времени в одном кадре: для такой точности эти часы не подходят. При сравнении нагрузки сохраняем одинаковое число игроков и сущностей. И отдельно смотрим, сколько стоит один вызов и как часто он вообще происходит.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime) | [`get_systime`](https://www.amxmodx.org/api/amxmodx/get_systime)
