# API плагинов и модули

[EN](../en/11-plugin-api-and-modules.md) | [UK](../uk/11-plugin-api-and-modules.md) | [RU](../ru/11-plugin-api-and-modules.md) | [Содержание](../../README.ru.md)

Разбираемся, как один плагин вызывает другой, передаёт ему данные и получает ответ. Natives используем для вызовов, forwards для уведомлений и проверок перед действием. Поставщик и вызывающий плагин в примерах собираются отдельно: каждый блок это самостоятельный плагин.

- [Почему одного include недостаточно](#include-and-runtime)
- [Даём другому плагину свою native](#register-native)
- [Читаем и записываем строки другого плагина](#native-strings)
- [Меняем переменную другого плагина по ссылке](#native-byref)
- [Передаём `Float` через native без потери значения](#native-floats)
- [Уведомляем другие плагины через forward](#publish-forward)
- [Даём другому плагину запретить действие](#forward-return-policy)
- [Передаём массив во forward и забираем изменения](#forward-arrays)
- [Работаем и без необязательной native](#optional-natives)
- [Что объяснить в общем include](#api-contract)

<a id="include-and-runtime"></a>
## Почему одного include недостаточно

Подключённый `.inc` сообщает компилятору, какие имена, параметры и константы доступны. Код `stock` при использовании попадёт в наш плагин, а реализацию native всё равно должен дать модуль или другой плагин на сервере. Поэтому успешная компиляция ещё не означает, что native будет кому выполнить.

| Вид | Компилируется в твой плагин? | Поставщик во время работы |
| --- | --- | --- |
| `stock` с Pawn-кодом | Использованный код добавляется | Твой плагин |
| Native из `<cstrike>` | Только объявление | Модуль CStrike |
| Native другого плагина | Только объявление | Плагин-поставщик `.amxx` |
| Native ReAPI | Только объявление | Отдельный модуль ReAPI и нужный API движка или игры |

В includes AMXX обычно уже записаны требования к модулям и данные для их автоматической загрузки. Старые `plugin_modules` и `require_module` возвращать не нужно, они устарели. Если native даёт другой плагин, ставим его перед использующим плагином в `plugins.ini` и описываем эту зависимость.

Источники: [AMXX include declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [CStrike include](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/cstrike.inc).

<a id="register-native"></a>
## Даём другому плагину свою native

Один плагин может дать другому свою функцию через native. Регистрируем её в `plugin_natives()`, ещё до `plugin_init()`. У обычного стиля `0` обработчик получает ID вызывающего плагина и количество аргументов, а их значения забирает через `get_param()` и похожие функции. В примере ограничиваем переданный счёт диапазоном `0..100`.

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

Второй блок это отдельный плагин: он передаст `135` и выведет `100`. Объявление native и `#pragma reqlib notes_api` для общего использования выносим в include. Без поставщика требование библиотеки даст понятную ошибку загрузки. Стиль `1` устарел и для нового API не нужен. Из обработчика не вызываем ту же native рекурсивно.

Источники: [`plugin_natives`](https://www.amxmodx.org/api/amxmodx/plugin_natives), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native), [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library).

<a id="native-strings"></a>
## Читаем и записываем строки другого плагина

У разных плагинов своя память, поэтому переданная строка не становится обычной локальной строкой обработчика. Читаем её через `get_string()`, записываем ответ через `set_string()`. Здесь объявление для вызывающего плагина такое: `native notes_make_label( const prefix[], output[], maxlen )`.


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

Для буфера `new label[64]` передаём `charsmax( label )`, а не `sizeof label`, оставляя место под конец строки. Из префикса `VIP` получится `[VIP] ready`; в меньшем буфере строка обрежется. Настоящий размер знает вызывающий плагин, обработчик не может проверить его по произвольному числу. Для массивов аналогично есть `get_array()` и `set_array()`, им явно передаём число ячеек.

Источники: [`get_string`](https://www.amxmodx.org/api/amxmodx/get_string), [`set_string`](https://www.amxmodx.org/api/amxmodx/set_string), [`get_array`](https://www.amxmodx.org/api/amxmodx/get_array).

<a id="native-byref"></a>
## Меняем переменную другого плагина по ссылке

Если native должна изменить переменную вызывающего плагина, объявляем её по ссылке: `native notes_add_bonus( &score, bonus )`. Первый аргумент указывает на ячейку с чужим `score`. Обычный `get_param( 1 )` прочитал бы адрес, а нам нужно содержимое по этому адресу, поэтому используем `get_param_byref()`.


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

	// Нужен сам score вызывающего плагина, не адрес его cell.
	new score = get_param_byref( 1 );
	score += get_param( 2 );
	set_param_byref( 1, score );
	return score;
}
```

`set_param_byref()` записывает новое значение обратно вызывающему плагину. Было `score = 20`, вызвали `notes_add_bonus( score, 5 )`, стало `25`. Для ссылки на `Float` есть `get_float_byref()` и `set_float_byref()`. Отдельно описываем результат самой native: новое значение, успешность операции или код ошибки.

Источники: [`get_param_byref`](https://www.amxmodx.org/api/amxmodx/get_param_byref), [`set_param_byref`](https://www.amxmodx.org/api/amxmodx/set_param_byref), [`get_float_byref`](https://www.amxmodx.org/api/amxmodx/get_float_byref).

<a id="native-floats"></a>
## Передаём `Float` через native без потери значения

Для `native Float:notes_scale( Float:value, Float:factor )` параметры читаем через `get_param_f()`. Обработчик стиля `0` возвращает обычную ячейку без тега. Поэтому с результата снимаем тег через `_:`: сами биты дробного числа нужно сохранить, а не округлить число до целого.


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
	// Биты Float сохраняем, снимаем только тег результата.
	return _:(value * factor);
}
```

У вызывающего плагина объявление снова пометит эти биты как `Float`, и `notes_scale( 2.5, 2.0 )` даст `5.0`. Если вместо снятия тега вызвать `floatround()`, представление результата изменится и вызывающий код прочитает его неверно. `_:` меняет только тег, числовое преобразование делает другую работу.

Источники: [`get_param_f`](https://www.amxmodx.org/api/amxmodx/get_param_f), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native).

<a id="publish-forward"></a>
## Уведомляем другие плагины через forward

Через native другой плагин вызывает нас, а через forward мы вызываем обработчики других плагинов. Например, сообщаем, что ранг игрока изменился. `CreateMultiForward()` найдёт подходящие `public`-функции; его handle сохраняем и в конце работы освобождаем через `DestroyForward()`.


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

Другой плагин должен объявить `public notes_rank_changed( player, rank )`. С `ET_IGNORE` его ответ нам не нужен, это только уведомление. Сам `ExecuteForward()` сообщает об успешности выполнения, а переменная `result` содержит объединённые ответы обработчиков. Это разные результаты. Если нужен один конкретный плагин, есть `CreateOneForward()`.

Источники: [forward API declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward), [`DestroyForward`](https://www.amxmodx.org/api/amxmodx/DestroyForward).

<a id="forward-return-policy"></a>
## Даём другому плагину запретить действие

Если другой плагин может запретить действие, вызываем forward до самого действия и проверяем ответ. Для награды это означает сначала спросить разрешение и только потом её выдать. Режим `ET_*` определяет, какие обработчики будут вызваны и как собрать их результаты.

| Режим | Поведение |
| --- | --- |
| `ET_IGNORE` | Игнорировать результаты обработчиков |
| `ET_STOP` | Остановиться на первом результате больше `0` |
| `ET_STOP2` | Остановиться именно на `PLUGIN_HANDLED` (`1`); иначе сохранять наибольший результат |
| `ET_CONTINUE` | Вызвать всех обработчиков и сохранить наибольший результат |


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

Здесь `PLUGIN_CONTINUE` разрешает награду, `PLUGIN_HANDLED` запрещает. Без обработчиков получим ноль, и проверка разрешит действие. Если создать или выполнить forward не удалось, пример запрещает награду. Уведомление после выдачи уже ничего не отменит, поэтому порядок вызовов важен.

Источники: [ET constants](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxconst.inc), [forward execution](https://github.com/alliedmodders/amxmodx/blob/1.9-dev/amxmodx/CForward.cpp).

<a id="forward-arrays"></a>
## Передаём массив во forward и забираем изменения

Для массива во forward нужен `PrepareArray()`, одного локального адреса недостаточно. Полученное значение передаём на место параметра `FP_ARRAY` в `ExecuteForward()`. Последний аргумент `1` у `PrepareArray()` разрешает скопировать изменения обработчиков обратно в исходный массив; без такой задачи копирование обратно не нужно.


```pawn
#include <amxmodx>

public plugin_cfg()
{
	new handle = CreateMultiForward( "notes_adjust_values", ET_IGNORE, FP_ARRAY, FP_CELL );

	if( handle == -1 )
		return;

	new values[] = { 10, 20, 30 };
	// Последний 1 нужен, чтобы изменения вернулись в values.
	new prepared = PrepareArray( values, sizeof values, 1 );
	new result;
	ExecuteForward( handle, result, prepared, sizeof values );
	DestroyForward( handle );
	server_print( "First value after callbacks: %d", values[0] );
}
```

Другой плагин реализует `public notes_adjust_values( values[], count )` и работает только до `count`. Размер передаём отдельно: исходного объявления массива у обработчика нет. Подготовленное значение нужно для вызова forward, это не контейнер `Array:`, и в `ArrayDestroy()` его передавать нельзя.

Источники: [`PrepareArray`](https://www.amxmodx.org/api/amxmodx/PrepareArray), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward).

<a id="optional-natives"></a>
## Работаем и без необязательной native

Одного `if` перед вызовом недостаточно, чтобы native стала необязательной: без неё плагин может не загрузиться ещё до выполнения кода. В `plugin_natives()` ставим фильтр ровно для нужной native, а перед вызовом проверяем поставщика. В примере без `notes_api` используем обычный `clamp()`.


```pawn
#include <amxmodx>

native notes_clamp_score( score );

public plugin_natives()
{
	set_native_filter( "filter_optional_native" );
}

public filter_optional_native( const native_name[], index, trap )
{
	// Разрешаем отсутствие только этой native при загрузке.
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

Обязательного `#pragma reqlib notes_api` здесь специально нет. `trap = 0` означает, что native не нашлась при загрузке, `trap = 1` что отсутствующую native уже попытались вызвать. Второй случай и чужие ошибки общим фильтром не скрываем. Если include отдельно требует модуль, может понадобиться точечный фильтр модулей или отдельная сборка: фильтр native требование библиотеки не отменяет.

Источники: [описания фильтров native и модулей](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`LibraryExists`](https://www.amxmodx.org/api/amxmodx/LibraryExists).

<a id="api-contract"></a>
## Что объяснить в общем include

В общем include описываем не только параметры. Другой плагин должен понимать, как правильно вызвать нашу функцию и что делать с результатом:

- какой плагин или модуль нужен и какая версия API подходит; одного наличия библиотеки недостаточно;
- что именно означает ID: слот игрока, userid, индекс сущности или отдельный handle;
- можно ли передать отключённого игрока, бота или ноль;
- сколько символов поместится в выходной буфер и кто закроет возвращённый handle;
- что функция возвращает при успехе и ошибке и что означают ответы обработчиков;
- когда приходит forward: до изменения или после, и разрешено ли менять его аргументы.

Порядок и смысл параметров существующей native незаметно не меняем. Если совместимость меняется, добавляем проверку версии или новую функцию API. Для неверного аргумента используем `log_error( AMX_ERR_NATIVE, ... )`: ошибка будет показана у вызывающего плагина, ближе к её причине. Это понятнее, чем принять плохой ID или handle и сломаться позже в другом месте.

Источники: [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library), [`log_error`](https://www.amxmodx.org/api/amxmodx/log_error).
