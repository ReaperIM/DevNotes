# API плагинов и модули

[EN](../en/11-plugin-api-and-modules.md) | [UK](../uk/11-plugin-api-and-modules.md) | [RU](../ru/11-plugin-api-and-modules.md) | [Содержание](../../README.ru.md)

Natives для запросов, forwards для уведомлений и контракты памяти и зависимостей между плагинами. Примеры поставщика и потребителя являются отдельными плагинами; каждый блок компилируется независимо.

- [Include не является загруженным модулем](#include-and-runtime)
- [Native поставщика и её вызов](#register-native)
- [Строки между памятью разных плагинов](#native-strings)
- [Значения по ссылке требуют соответствующего доступа](#native-byref)
- [`Float`-параметры и cell результата native](#native-floats)
- [Публикуем forward и освобождаем handle](#publish-forward)
- [Определяем смысл результатов потребителя](#forward-return-policy)
- [Готовим массив перед forward](#forward-arrays)
- [Необязательные natives требуют явного fallback](#optional-natives)
- [Небольшой и явный контракт API](#api-contract)

<a id="include-and-runtime"></a>
## Include не является загруженным модулем

`.inc` даёт компилятору объявления, константы и stocks. Native всё равно нужен поставщик во время работы: модуль AMXX или другой плагин. Успешная компиляция подтверждает доступность имён и сигнатур компилятору, но не их реализации на сервере.

| Вид | Компилируется в твой плагин? | Поставщик во время работы |
| --- | --- | --- |
| `stock` с Pawn-кодом | Использованный код добавляется | Твой плагин |
| Native из `<cstrike>` | Только объявление | Модуль CStrike |
| Native другого плагина | Только объявление | Плагин-поставщик `.amxx` |
| Native ReAPI | Только объявление | Отдельный модуль ReAPI и нужный engine / game API |

Includes AMXX обычно содержат требования к модулям и metadata для autoload. Не возвращай `plugin_modules` / `require_module`: они устарели и не обеспечивают современную загрузку. Ставь плагины-поставщики перед потребителями в `plugins.ini` и описывай зависимость.

Источники: [AMXX include declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [CStrike include](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/cstrike.inc).

<a id="register-native"></a>
## Native поставщика и её вызов

Регистрируй natives в `plugin_natives`, который выполняется до `plugin_init`. Стиль `0` получает ID вызывающего плагина и число аргументов; сами параметры читаются через `get_param` и связанные natives.

**Плагин-поставщик:**
```pawn
#include <amxmodx>

public plugin_natives()
{
	register_library( "notes_api" );
	register_native( "notes_clamp_score", "native_clamp_score" );
}

public native_clamp_score( plugin_id, params )
{
	if( params != 1 )
	{
		log_error( AMX_ERR_NATIVE, "Expected one score argument" );
		return 0;
	}

	return clamp( get_param( 1 ), 0, 100 );
}
```

**Отдельный плагин-потребитель:**


```pawn
#include <amxmodx>

#pragma reqlib notes_api
native notes_clamp_score( score );

public plugin_init()
{
	server_print( "Clamped score: %d", notes_clamp_score( 135 ) );
}
```

Потребитель печатает `100`. В общем API перенеси объявление и `#pragma reqlib notes_api` в include. Требование библиотеки даёт явную ошибку загрузки без поставщика. Natives стиля `1` устарели; для нового API они не нужны. Не вызывай ту же native рекурсивно из её handler.

Источники: [`plugin_natives`](https://www.amxmodx.org/api/amxmodx/plugin_natives), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native), [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library).

<a id="native-strings"></a>
## Строки между памятью разных плагинов

Параметр-строка не является адресом локальной строки поставщика. Вход читается через `get_string`, выход записывается через `set_string`. API примера: `native notes_make_label( const prefix[], output[], maxlen )`.


```pawn
#include <amxmodx>

public plugin_natives()
{
	register_native( "notes_make_label", "native_make_label" );
}

public native_make_label( plugin_id, params )
{
	if( params != 3 )
	{
		log_error( AMX_ERR_NATIVE, "Expected prefix, output and maxlen" );
		return 0;
	}

	new maxlen = get_param( 3 );

	if( maxlen < 0 )
	{
		log_error( AMX_ERR_NATIVE, "maxlen must not be negative" );
		return 0;
	}

	new prefix[32], label[64];
	get_string( 1, prefix, charsmax( prefix ) );
	formatex( label, charsmax( label ), "[%s] ready", prefix );
	return set_string( 2, label, maxlen );
}
```

Для `new label[64]` вызов передаёт `charsmax( label )`, а не `sizeof label`. Префикс `VIP` даёт `[VIP] ready`, с обрезкой для меньшего буфера. Реальную вместимость знает вызывающая сторона; поставщик не может вывести её из произвольного числа. Массивы аналогично передаются через `get_array` / `set_array` с явным числом cells.

Источники: [`get_string`](https://www.amxmodx.org/api/amxmodx/get_string), [`set_string`](https://www.amxmodx.org/api/amxmodx/set_string), [`get_array`](https://www.amxmodx.org/api/amxmodx/get_array).

<a id="native-byref"></a>
## Значения по ссылке требуют соответствующего доступа

В `native notes_add_bonus( &score, bonus )` аргумент `1` ссылается на cell потребителя. `get_param( 1 )` прочитал бы значение адреса аргумента, а не score в этой cell.


```pawn
#include <amxmodx>

public plugin_natives()
{
	register_native( "notes_add_bonus", "native_add_bonus" );
}

public native_add_bonus( plugin_id, params )
{
	if( params != 2 )
	{
		log_error( AMX_ERR_NATIVE, "Expected score reference and bonus" );
		return 0;
	}

	new score = get_param_byref( 1 );
	score += get_param( 2 );
	set_param_byref( 1, score );
	return score;
}
```

Если `score = 20`, вызов `notes_add_bonus( score, 5 )` оставит у потребителя `25`. Для ссылок на `Float` есть `get_float_byref` / `set_float_byref`. В API определи, что возвращается: обновлённое значение, признак успеха или код ошибки.

Источники: [`get_param_byref`](https://www.amxmodx.org/api/amxmodx/get_param_byref), [`set_param_byref`](https://www.amxmodx.org/api/amxmodx/set_param_byref), [`get_float_byref`](https://www.amxmodx.org/api/amxmodx/get_float_byref).

<a id="native-floats"></a>
## `Float`-параметры и cell результата native

Для `native Float:notes_scale( Float:value, Float:factor )` читай параметры через `get_param_f`. Handler native стиля 0 возвращает cell без тега; убери тег, не преобразуя float в целое число.


```pawn
#include <amxmodx>

public plugin_natives()
{
	register_native( "notes_scale", "native_scale" );
}

public native_scale( plugin_id, params )
{
	if( params != 2 )
	{
		log_error( AMX_ERR_NATIVE, "Expected value and factor" );
		return _:0.0;
	}

	new Float:value = get_param_f( 1 );
	new Float:factor = get_param_f( 2 );
	return _:(value * factor);
}
```

Объявление у потребителя придаёт возвращённым битам тег `Float`. `notes_scale( 2.5, 2.0 )` даёт `5.0`. `floatround` здесь изменил бы представление результата и нарушил контракт объявления. Снятие тега через `_:` и числовое преобразование являются разными операциями.

Источники: [`get_param_f`](https://www.amxmodx.org/api/amxmodx/get_param_f), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native).

<a id="publish-forward"></a>
## Публикуем forward и освобождаем handle

Native даёт потребителю возможность вызвать поставщика. Forward позволяет поставщику уведомлять потребителей. `CreateMultiForward` находит соответствующие `public`-функции в плагинах; сохрани handle и освободи его при завершении поставщика.


```pawn
#include <amxmodx>

new g_rank_forward = -1;

public plugin_init()
{
	g_rank_forward = CreateMultiForward( "notes_rank_changed", ET_IGNORE, FP_CELL, FP_CELL );
}

public publish_rank( player, rank )
{
	if( g_rank_forward == -1 )
		return 0;

	new result;
	return ExecuteForward( g_rank_forward, result, player, rank );
}

public plugin_end()
{
	if( g_rank_forward != -1 )
		DestroyForward( g_rank_forward );
}
```

Потребитель реализует `public notes_rank_changed( player, rank )`. `ET_IGNORE` означает, что уведомление не использует результаты потребителей. Сам `ExecuteForward` возвращает успешность выполнения; выходной `result` содержит объединённый результат callbacks. Это разные значения. `CreateOneForward` адресует один плагин вместо всех подходящих потребителей.

Источники: [forward API declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward), [`DestroyForward`](https://www.amxmodx.org/api/amxmodx/DestroyForward).

<a id="forward-return-policy"></a>
## Определяем смысл результатов потребителя

Forward перед действием может дать право отказа, но только если поставщик проверит результат до выполнения действия. Режим выполнения является частью контракта API.

| Режим | Поведение |
| --- | --- |
| `ET_IGNORE` | Игнорировать результаты callbacks |
| `ET_STOP` | Остановиться на первом результате больше `0` |
| `ET_STOP2` | Остановиться именно на `PLUGIN_HANDLED` (`1`); иначе сохранять наибольший результат |
| `ET_CONTINUE` | Вызвать все callbacks и сохранить наибольший результат |


```pawn
#include <amxmodx>

new g_reward_forward = -1;

public plugin_init()
{
	g_reward_forward = CreateMultiForward( "notes_can_reward", ET_STOP, FP_CELL );
}

public bool:can_reward( player )
{
	if( g_reward_forward == -1 )
		return false;

	new result;

	if( !ExecuteForward( g_reward_forward, result, player ) )
		return false;

	return result < PLUGIN_HANDLED;
}

public plugin_end()
{
	if( g_reward_forward != -1 )
		DestroyForward( g_reward_forward );
}
```

Контракт потребителя: `PLUGIN_CONTINUE` разрешает, `PLUGIN_HANDLED` запрещает. Без потребителей результат нулевой и проверка разрешает действие. Пример запрещает её при ошибке создания или выполнения forward. Уведомление после действия не отменит уже выданную награду.

Источники: [ET constants](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxconst.inc), [forward execution](https://github.com/alliedmodders/amxmodx/blob/1.9-dev/amxmodx/CForward.cpp).

<a id="forward-arrays"></a>
## Готовим массив перед forward

`FP_ARRAY` не передаётся как обычный адрес локального массива. `PrepareArray` создаёт параметр для `ExecuteForward`. `copyback = 1` нужен только тогда, когда изменения потребителей нужно вернуть в исходный массив.


```pawn
#include <amxmodx>

public plugin_cfg()
{
	new handle = CreateMultiForward( "notes_adjust_values", ET_IGNORE, FP_ARRAY, FP_CELL );

	if( handle == -1 )
		return;

	new values[] = { 10, 20, 30 };
	new prepared = PrepareArray( values, sizeof values, 1 );
	new result;
	ExecuteForward( handle, result, prepared, sizeof values );
	DestroyForward( handle );
	server_print( "First value after callbacks: %d", values[0] );
}
```

Потребитель реализует `public notes_adjust_values( values[], count )` и работает только в пределах `count`. Длина передаётся явно, поскольку callback не получает исходное объявление массива. Подготовленное значение предназначено для выполнения forward, а не является handle контейнера `Array:`; его не передают в `ArrayDestroy`.

Источники: [`PrepareArray`](https://www.amxmodx.org/api/amxmodx/PrepareArray), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward).

<a id="optional-natives"></a>
## Необязательные natives требуют явного fallback

Runtime `if` сам не делает отсутствующую native необязательной: плагин может не загрузиться. Для одного необязательного API отфильтруй только нужную native в `plugin_natives` и защити её вызов. Этот потребитель использует поставщика `notes_api` из предыдущего примера.


```pawn
#include <amxmodx>

native notes_clamp_score( score );

public plugin_natives()
{
	set_native_filter( "filter_optional_native" );
}

public filter_optional_native( const native_name[], index, trap )
{
	if( !trap && equal( native_name, "notes_clamp_score" ) )
		return PLUGIN_HANDLED;

	return PLUGIN_CONTINUE;
}

public plugin_cfg()
{
	new score = 135;

	if( LibraryExists( "notes_api", LibType_Library ) )
		score = notes_clamp_score( score );
	else
		score = clamp( score, 0, 100 );

	server_print( "Score: %d", score );
}
```

Здесь намеренно нет обязательного `#pragma reqlib notes_api`. `trap = 0` означает отсутствие native при поиске; `trap = 1` означает попытку её вызова. Не скрывай вторую ошибку или посторонние missing natives общим фильтром. Include с требованием необязательного модуля может также требовать точного module filter или отдельной сборки: native filter не выполняет требование библиотеки.

Источники: [native and module filter declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`LibraryExists`](https://www.amxmodx.org/api/amxmodx/LibraryExists).

<a id="api-contract"></a>
## Небольшой и явный контракт API

Общий include должен объяснять то, чего потребитель не узнает только из сигнатуры:

- нужный поставщик и версия API; наличие библиотеки ещё не доказывает совместимость;
- является ли ID слотом игрока, userid, индексом сущности или отдельным handle;
- допустимы ли отключённые игроки, боты и нулевые значения;
- максимальная длина выхода и владелец возвращённого handle;
- результаты успеха, ошибки и callbacks;
- выполняется ли forward до или после изменения состояния и могут ли потребители менять аргументы.

Не меняй порядок или смысл параметров существующей native незаметно. При изменении совместимости добавь проверку версии или новую точку входа. `log_error( AMX_ERR_NATIVE, ... )` показывает ошибку проверки поставщика как ошибку в вызывающем плагине; это полезнее принятия невалидного игрока или handle с поздним сбоем.

Источники: [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library), [`log_error`](https://www.amxmodx.org/api/amxmodx/log_error).
