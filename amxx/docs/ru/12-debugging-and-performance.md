# Отладка и производительность

[EN](../en/12-debugging-and-performance.md) | [UK](../uk/12-debugging-and-performance.md) | [RU](../ru/12-debugging-and-performance.md)

[Содержание](../../README.ru.md)

Загрузка, диагностика выполнения, владение ресурсами и частые callbacks. Оптимизируйте подтверждённый частый участок с сохранением контракта события и данных.

- [Ошибки и предупреждения компилятора](#compile)
- [Ошибка загрузки и ошибка выполнения](#load-errors)
- [Запись ошибки с контекстом](#logging)
- [Остановка плагина без обязательной зависимости](#fail-state)
- [Дешёвые проверки перед тяжёлой работой](#hot-paths)
- [Квадрат расстояния для проверки радиуса](#distance)
- [Static сохраняет состояние между вызовами](#static-locals)
- [Владение handle важнее ненулевого значения](#ownership)
- [Игровое время не является точным профайлером](#clocks)

<a id="compile"></a>

## Ошибки и предупреждения компилятора

Неизвестный символ часто означает отсутствующий или неправильный include; tag mismatch может означать передачу `Float`, `enum` или handle как другого вида cell. Исправляйте контракт, а не скрывайте предупреждение приведением тега.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:duration = 1.5;
	new seconds = floatround( duration, floatround_ceil );
	server_print( "duration=%.1f, whole seconds=%d", duration, seconds ); // 1.5, 2
}
```

Компилируйте с нужным набором includes. Успешная сборка не проверяет валидность сущностей, наличие модулей, пути или время вызова callbacks на сервере.

[Compiler options](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))

<a id="load-errors"></a>

## Ошибка загрузки и ошибка выполнения

Если плагин не запускается, проверьте `amxx plugins`, `amxx modules` и журнал ошибок AMXX. Неизвестная native часто означает отсутствующий поставщик или более старый runtime/модуль, чем include при компиляции.

Для ошибок выполнения добавьте плагину флаг debug в `plugins.ini`, воспроизведите событие и проверьте функцию и строку в журнале. Убедитесь, что загруженный `.amxx` соответствует исходнику. [Совместимость](COMPATIBILITY.md).

[Debugging](https://wiki.alliedmods.net/Debugging_Plugins_(AMX_Mod_X))

<a id="logging"></a>

## Запись ошибки с контекстом

Используйте постоянную строку формата, а внешний текст передавайте аргументом. Добавьте операцию и контекст подключения, чтобы сообщение оставалось понятным после выхода игрока.

```pawn
#include <amxmodx>

public client_authorized( id, const authid[] )
{
	log_amx( "Authorized: slot=%d userid=%d auth=%s", id, get_user_userid(id), authid );
}
```

Это диагностический пример, а не требование записывать каждую авторизацию. Не выводите пароли, доступ к БД или каждый кадр. Повторные ошибки стоит ограничивать в месте их возникновения.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="fail-state"></a>

## Остановка плагина без обязательной зависимости

`set_fail_state()` останавливает текущий плагин и записывает причину. Это подходит для отсутствующей обязательной зависимости или файла, без которых корректная работа невозможна.

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

Необязательную косметическую возможность можно отключить отдельно. Fail state нужен, когда плагин действительно не может выполнить свою задачу, а не для каждой отклонённой команды.

[`amxmodx.inc`](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc)

<a id="hot-paths"></a>

## Дешёвые проверки перед тяжёлой работой

`PreThink` может вызываться очень часто. Сначала отсеивайте выключенное или ненужное состояние; не читайте файлы, не открывайте SQL и не сканируйте всех игроков ради локального действия.

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

Это ограничивает частоту тела, а не самого хука. Если покадровый доступ не нужен, task, think сущности или конкретное событие могут быть понятнее. Не переносите вычисление, если это меняет нужный момент выполнения.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime)

<a id="distance"></a>

## Квадрат расстояния для проверки радиуса

Для проверки радиуса сравните сумму квадратов разностей координат с квадратом радиуса. Для неотрицательного радиуса решение то же, без вычисления самого расстояния.

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

Это геометрическая проверка, а не видимость или доступность пути. Двумерный радиус намеренно не учитывает высоту; не теряйте её случайно.

[Vector functions](https://www.amxmodx.org/api/vector)

<a id="static-locals"></a>

## Static сохраняет состояние между вызовами

Локальный `static` повторно использует буфер между вызовами; обычный локальный `new` имеет отдельное время жизни для каждого вызова. Рекурсия или повторный вход в функцию совместно используют static.

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

Static сохраняет счётчик между вызовами. В рекурсивном коде или при повторном входе временные данные отдельного вызова храните в обычных локальных переменных. Увеличение `#pragma dynamic` не исправляет бесконечную рекурсию или неправильные индексы.

[Pawn language](https://wiki.alliedmods.net/Pawn_Tutorial)

<a id="ownership"></a>

## Владение handle важнее ненулевого значения

Handle имеет смысл только для своего API. Важно знать, кто его создаёт, кто закрывает и какое значение означает ошибку.

- `Array` / `Trie`: `ArrayDestroy()` / `TrieDestroy()` для собственных контейнеров.
- nVault: `nvault_close()`; ошибка открытия `INVALID_HANDLE` (`-1`).
- SQLX: собственные tuples, соединения и подготовленные запросы закрывают через `SQL_FreeHandle()`; запрос потокового callback не освобождают.
- New menu: уничтожение зависит от времени жизни; общее меню нельзя удалять после первого игрока.

[Контейнеры](03-arrays-and-containers.md) | [Меню](07-tasks-and-menus.md) | [Хранилища](09-files-vault-and-sql.md)

[`ArrayDestroy`](https://www.amxmodx.org/api/cellarray/ArrayDestroy) | [`SQL_ThreadQuery`](https://www.amxmodx.org/api/sqlx/SQL_ThreadQuery)

<a id="clocks"></a>

## Игровое время не является точным профайлером

`get_gametime()` удобен для cooldown и планирования в пределах карты. `get_systime()` возвращает целые секунды календарного времени для постоянных меток. Это разные задачи.

```pawn
#include <amxmodx>

public plugin_init()
{
	new Float:next_action = get_gametime() + 2.0;
	new expires_at = get_systime() + 3600;
	server_print( "Map deadline: %.2f, wall-clock expiry: %d", next_action, expires_at );
}
```

Не измеряйте короткий блок вычитанием двух чтений игрового времени в одном кадре. Сравнивайте одинаковую нагрузку с контролируемым количеством игроков/сущностей; частота callback и стоимость одного вызова являются разными величинами.

[`get_gametime`](https://www.amxmodx.org/api/amxmodx/get_gametime) | [`get_systime`](https://www.amxmodx.org/api/amxmodx/get_systime)
