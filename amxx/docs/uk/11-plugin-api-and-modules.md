# API плагінів і модулі

[English](../en/11-plugin-api-and-modules.md) | [Українська](../uk/11-plugin-api-and-modules.md) | [Русский](../ru/11-plugin-api-and-modules.md) | [Зміст](../../README.uk.md)

Natives для запитів, forwards для сповіщень і контракти пам’яті та залежностей між плагінами. Приклади постачальника та споживача є окремими плагінами; кожен блок компілюється незалежно.

- [Include не є завантаженим модулем](#include-and-runtime)
- [Native постачальника та її виклик](#register-native)
- [Рядки між пам’яттю різних плагінів](#native-strings)
- [Значення за посиланням потребують відповідного доступу](#native-byref)
- [Float-параметри та cell результату native](#native-floats)
- [Публікуємо forward і звільняємо handle](#publish-forward)
- [Визначаємо зміст результатів споживача](#forward-return-policy)
- [Готуємо масив перед forward](#forward-arrays)
- [Необов’язкові natives потребують явного fallback](#optional-natives)
- [Невеликий і явний контракт API](#api-contract)

<a id="include-and-runtime"></a>
## Include не є завантаженим модулем

`.inc` дає компілятору оголошення, константи й stocks. Native все одно потрібен постачальник під час роботи: модуль AMXX або інший плагін. Успішна компіляція підтверджує доступність імен і сигнатур компілятору, але не їх реалізації на сервері.

| Вид | Компілюється у твій плагін? | Постачальник під час роботи |
| --- | --- | --- |
| `stock` з Pawn-кодом | Використаний код додається | Твій плагін |
| Native з `<cstrike>` | Лише оголошення | Модуль CStrike |
| Native іншого плагіна | Лише оголошення | Плагін-постачальник `.amxx` |
| Native ReAPI | Лише оголошення | Окремий модуль ReAPI та потрібний engine / game API |

Includes AMXX зазвичай містять вимоги до модулів і metadata для autoload. Не повертай `plugin_modules` / `require_module`: вони застарілі й не забезпечують сучасне завантаження. Став плагіни-постачальники перед споживачами у `plugins.ini` та описуй залежність.

Джерела: [AMXX include declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [CStrike include](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/cstrike.inc).

<a id="register-native"></a>
## Native постачальника та її виклик

Реєструй natives у `plugin_natives`, що виконується до `plugin_init`. Стиль `0` отримує ID викликаючого плагіна та кількість аргументів; самі параметри читаються через `get_param` та споріднені natives.

**Плагін-постачальник:**
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

**Окремий плагін-споживач:**


```pawn
#include <amxmodx>

#pragma reqlib notes_api
native notes_clamp_score( score );

public plugin_init()
{
	server_print( "Clamped score: %d", notes_clamp_score( 135 ) );
}
```

Споживач друкує `100`. У спільному API перенеси оголошення та `#pragma reqlib notes_api` до include. Вимога бібліотеки дає явну помилку завантаження без постачальника. Natives стилю `1` застарілі; для нового API вони не потрібні. Не викликай ту саму native рекурсивно з її handler.

Джерела: [plugin_natives](https://www.amxmodx.org/api/amxmodx/plugin_natives), [register_native](https://www.amxmodx.org/api/amxmodx/register_native), [register_library](https://www.amxmodx.org/api/amxmodx/register_library).

<a id="native-strings"></a>
## Рядки між пам’яттю різних плагінів

Параметр-рядок не є адресою локального рядка постачальника. Вхід читається через `get_string`, вихід записується через `set_string`. API прикладу: `native notes_make_label( const prefix[], output[], maxlen )`.


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

Для `new label[64]` виклик передає `charsmax( label )`, а не `sizeof label`. Префікс `VIP` дає `[VIP] ready`, з обрізанням для меншого буфера. Реальну місткість знає викликаюча сторона; постачальник не може вивести її з довільного числа. Масиви так само передаються через `get_array` / `set_array` з явною кількістю cells.

Джерела: [get_string](https://www.amxmodx.org/api/amxmodx/get_string), [set_string](https://www.amxmodx.org/api/amxmodx/set_string), [get_array](https://www.amxmodx.org/api/amxmodx/get_array).

<a id="native-byref"></a>
## Значення за посиланням потребують відповідного доступу

У `native notes_add_bonus( &score, bonus )` аргумент `1` посилається на cell споживача. `get_param( 1 )` прочитав би значення адреси аргументу, а не score у цій cell.


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

Якщо `score = 20`, виклик `notes_add_bonus( score, 5 )` залишить у споживача `25`. Для посилань на `Float` є `get_float_byref` / `set_float_byref`. В API визнач, що повертається: оновлене значення, ознака успіху чи код помилки.

Джерела: [get_param_byref](https://www.amxmodx.org/api/amxmodx/get_param_byref), [set_param_byref](https://www.amxmodx.org/api/amxmodx/set_param_byref), [get_float_byref](https://www.amxmodx.org/api/amxmodx/get_float_byref).

<a id="native-floats"></a>
## Float-параметри та cell результату native

Для `native Float:notes_scale( Float:value, Float:factor )` читай параметри через `get_param_f`. Handler native стилю 0 повертає cell без тега; прибери тег, не перетворюючи float на ціле число.


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

Оголошення у споживача надає повернутим бітам тег `Float`. `notes_scale( 2.5, 2.0 )` дає `5.0`. `floatround` тут змінив би подання результату й порушив контракт оголошення. Зняття тега через `_:` та числове перетворення є різними операціями.

Джерела: [get_param_f](https://www.amxmodx.org/api/amxmodx/get_param_f), [register_native](https://www.amxmodx.org/api/amxmodx/register_native).

<a id="publish-forward"></a>
## Публікуємо forward і звільняємо handle

Native дає споживачу можливість викликати постачальника. Forward дозволяє постачальнику сповіщати споживачів. `CreateMultiForward` знаходить відповідні public-функції у плагінах; збережи handle та звільни його після завершення постачальника.


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

Споживач реалізує `public notes_rank_changed( player, rank )`. `ET_IGNORE` означає, що сповіщення не використовує результати споживачів. Сам `ExecuteForward` повертає успішність виконання; вихідний `result` містить об’єднаний результат callbacks. Це різні значення. `CreateOneForward` адресує один плагін замість усіх відповідних споживачів.

Джерела: [forward API declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [ExecuteForward](https://www.amxmodx.org/api/amxmodx/ExecuteForward), [DestroyForward](https://www.amxmodx.org/api/amxmodx/DestroyForward).

<a id="forward-return-policy"></a>
## Визначаємо зміст результатів споживача

Forward перед дією може дозволити відмову, але лише якщо постачальник перевірить результат до виконання дії. Режим виконання є частиною контракту API.

| Режим | Поведінка |
| --- | --- |
| `ET_IGNORE` | Ігнорувати результати callbacks |
| `ET_STOP` | Зупинитися на першому результаті понад `0` |
| `ET_STOP2` | Зупинитися саме на `PLUGIN_HANDLED` (`1`); інакше зберігати найбільший результат |
| `ET_CONTINUE` | Викликати всі callbacks і зберегти найбільший результат |


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

Контракт споживача: `PLUGIN_CONTINUE` дозволяє, `PLUGIN_HANDLED` забороняє. Без споживачів результат нульовий і перевірка дозволяє дію. Приклад забороняє її за помилки створення чи виконання forward. Сповіщення після дії не скасує вже видану нагороду.

Джерела: [ET constants](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxconst.inc), [forward execution](https://github.com/alliedmodders/amxmodx/blob/1.9-dev/amxmodx/CForward.cpp).

<a id="forward-arrays"></a>
## Готуємо масив перед forward

`FP_ARRAY` не передається як звичайна адреса локального масиву. `PrepareArray` створює параметр для `ExecuteForward`. `copyback = 1` потрібен лише тоді, коли зміни споживачів треба повернути до вихідного масиву.


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

Споживач реалізує `public notes_adjust_values( values[], count )` і працює лише в межах `count`. Довжина передається явно, бо callback не отримує початкове оголошення масиву. Підготовлене значення призначене для виконання forward, а не є handle контейнера `Array:`; його не передають до `ArrayDestroy`.

Джерела: [PrepareArray](https://www.amxmodx.org/api/amxmodx/PrepareArray), [ExecuteForward](https://www.amxmodx.org/api/amxmodx/ExecuteForward).

<a id="optional-natives"></a>
## Необов’язкові natives потребують явного fallback

Runtime `if` сам не робить відсутню native необов’язковою: плагін може не завантажитися. Для одного необов’язкового API відфільтруй лише потрібну native у `plugin_natives` і захисти її виклик. Цей споживач використовує постачальника `notes_api` з попереднього прикладу.


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

Тут навмисно немає обов’язкового `#pragma reqlib notes_api`. `trap = 0` означає відсутність native під час пошуку; `trap = 1` означає спробу її виклику. Не приховуй другу помилку чи сторонні missing natives загальним фільтром. Include з вимогою необов’язкового модуля може також потребувати точного module filter або окремої збірки: native filter не виконує вимогу бібліотеки.

Джерела: [native and module filter declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [LibraryExists](https://www.amxmodx.org/api/amxmodx/LibraryExists).

<a id="api-contract"></a>
## Невеликий і явний контракт API

Спільний include має пояснювати те, чого споживач не дізнається лише із сигнатури:

- потрібний постачальник і версія API; наявність бібліотеки ще не доводить сумісність;
- чи ID є слотом гравця, userid, індексом сутності або окремим handle;
- чи дозволені від’єднані гравці, боти й нульові значення;
- максимальна довжина виходу та власник повернутого handle;
- результати успіху, помилки й callbacks;
- чи forward виконується до або після зміни стану й чи можуть споживачі змінювати аргументи.

Не змінюй порядок чи зміст параметрів існуючої native непомітно. За зміни сумісності додай перевірку версії або нову точку входу. `log_error( AMX_ERR_NATIVE, ... )` показує помилку перевірки постачальника як помилку у викликаючому плагіні; це корисніше за прийняття невалідного гравця чи handle з пізнім падінням.

Джерела: [register_library](https://www.amxmodx.org/api/amxmodx/register_library), [log_error](https://www.amxmodx.org/api/amxmodx/log_error).
