# API плагінів і модулі

[EN](../en/11-plugin-api-and-modules.md) | [UK](../uk/11-plugin-api-and-modules.md) | [RU](../ru/11-plugin-api-and-modules.md) | [Зміст](../../README.uk.md)

Розбираємося, як один плагін викликає інший, передає йому дані та отримує відповідь. Natives використовуємо для викликів, forwards для сповіщень і перевірок перед дією. Постачальник і плагін виклику в прикладах збираються окремо: кожен блок це самостійний плагін.

- [Чому одного include недостатньо](#include-and-runtime)
- [Даємо іншому плагіну свою native](#register-native)
- [Читаємо й записуємо рядки іншого плагіна](#native-strings)
- [Змінюємо змінну іншого плагіна за посиланням](#native-byref)
- [Передаємо `Float` через native без втрати значення](#native-floats)
- [Сповіщаємо інші плагіни через forward](#publish-forward)
- [Даємо іншому плагіну заборонити дію](#forward-return-policy)
- [Передаємо масив у forward і забираємо зміни](#forward-arrays)
- [Працюємо й без необов’язкової native](#optional-natives)
- [Що пояснити у спільному include](#api-contract)

<a id="include-and-runtime"></a>
## Чому одного include недостатньо

Підключений `.inc` повідомляє компілятору, які імена, параметри й константи доступні. Код `stock` під час використання потрапить у наш плагін, а реалізацію native все одно має дати модуль або інший плагін на сервері. Тому успішна компіляція ще не означає, що native буде кому виконати.

| Вид | Компілюється у твій плагін? | Постачальник під час роботи |
| --- | --- | --- |
| `stock` з Pawn-кодом | Використаний код додається | Твій плагін |
| Native з `<cstrike>` | Лише оголошення | Модуль CStrike |
| Native іншого плагіна | Лише оголошення | Плагін-постачальник `.amxx` |
| Native ReAPI | Лише оголошення | Окремий модуль ReAPI та потрібний API рушія або гри |

В includes AMXX зазвичай уже записані вимоги до модулів і дані для їх автоматичного завантаження. Старі `plugin_modules` і `require_module` повертати не потрібно, вони застаріли. Якщо native дає інший плагін, ставимо його перед плагіном, який її використовує, у `plugins.ini` й описуємо цю залежність.

Джерела: [AMXX include declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [CStrike include](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/cstrike.inc).

<a id="register-native"></a>
## Даємо іншому плагіну свою native

Один плагін може дати іншому свою функцію через native. Реєструємо її в `plugin_natives()`, ще до `plugin_init()`. У звичайного стилю `0` обробник отримує ID плагіна виклику й кількість аргументів, а їхні значення забирає через `get_param()` і схожі функції. У прикладі обмежуємо переданий рахунок діапазоном `0..100`.

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

Другий блок це окремий плагін: він передасть `135` і виведе `100`. Оголошення native й `#pragma reqlib notes_api` для спільного використання виносимо в include. Без постачальника вимога бібліотеки дасть зрозумілу помилку завантаження. Стиль `1` застарів і для нового API не потрібен. З обробника не викликаємо ту саму native рекурсивно.

Джерела: [`plugin_natives`](https://www.amxmodx.org/api/amxmodx/plugin_natives), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native), [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library).

<a id="native-strings"></a>
## Читаємо й записуємо рядки іншого плагіна

У різних плагінів своя пам'ять, тому переданий рядок не стає звичайним локальним рядком обробника. Читаємо його через `get_string()`, записуємо відповідь через `set_string()`. Тут оголошення для плагіна виклику таке: `native notes_make_label( const prefix[], output[], maxlen )`.


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

Для буфера `new label[64]` передаємо `charsmax( label )`, а не `sizeof label`, залишаючи місце під кінець рядка. Із префікса `VIP` вийде `[VIP] ready`; у меншому буфері рядок обріжеться. Справжній розмір знає плагін виклику, обробник не може перевірити його за довільним числом. Для масивів аналогічно є `get_array()` і `set_array()`, їм явно передаємо кількість комірок.

Джерела: [`get_string`](https://www.amxmodx.org/api/amxmodx/get_string), [`set_string`](https://www.amxmodx.org/api/amxmodx/set_string), [`get_array`](https://www.amxmodx.org/api/amxmodx/get_array).

<a id="native-byref"></a>
## Змінюємо змінну іншого плагіна за посиланням

Якщо native має змінити змінну плагіна виклику, оголошуємо її за посиланням: `native notes_add_bonus( &score, bonus )`. Перший аргумент указує на комірку з чужим `score`. Звичайний `get_param( 1 )` прочитав би адресу, а нам потрібен вміст за цією адресою, тому використовуємо `get_param_byref()`.


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

	// Потрібен сам score плагіна виклику, не адреса його cell.
	new score = get_param_byref( 1 );
	score += get_param( 2 );
	set_param_byref( 1, score );
	return score;
}
```

`set_param_byref()` записує нове значення назад плагіну виклику. Було `score = 20`, викликали `notes_add_bonus( score, 5 )`, стало `25`. Для посилання на `Float` є `get_float_byref()` і `set_float_byref()`. Окремо описуємо результат самої native: нове значення, успішність операції чи код помилки.

Джерела: [`get_param_byref`](https://www.amxmodx.org/api/amxmodx/get_param_byref), [`set_param_byref`](https://www.amxmodx.org/api/amxmodx/set_param_byref), [`get_float_byref`](https://www.amxmodx.org/api/amxmodx/get_float_byref).

<a id="native-floats"></a>
## Передаємо `Float` через native без втрати значення

Для `native Float:notes_scale( Float:value, Float:factor )` параметри читаємо через `get_param_f()`. Обробник стилю `0` повертає звичайну комірку без тегу. Тому з результату знімаємо тег через `_:`: самі біти дробового числа потрібно зберегти, а не округлити число до цілого.


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
	// Біти Float зберігаємо, знімаємо лише тег результату.
	return _:(value * factor);
}
```

У плагіна виклику оголошення знову позначить ці біти як `Float`, і `notes_scale( 2.5, 2.0 )` дасть `5.0`. Якщо замість зняття тегу викликати `floatround()`, представлення результату зміниться й код виклику прочитає його неправильно. `_:` змінює лише тег, числове перетворення робить іншу роботу.

Джерела: [`get_param_f`](https://www.amxmodx.org/api/amxmodx/get_param_f), [`register_native`](https://www.amxmodx.org/api/amxmodx/register_native).

<a id="publish-forward"></a>
## Сповіщаємо інші плагіни через forward

Через native інший плагін викликає нас, а через forward ми викликаємо обробники інших плагінів. Наприклад, повідомляємо, що ранг гравця змінився. `CreateMultiForward()` знайде відповідні `public`-функції; його handle зберігаємо й наприкінці роботи звільняємо через `DestroyForward()`.


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

Інший плагін має оголосити `public notes_rank_changed( player, rank )`. З `ET_IGNORE` його відповідь нам не потрібна, це лише сповіщення. Сам `ExecuteForward()` повідомляє про успішність виконання, а змінна `result` містить об'єднані відповіді обробників. Це різні результати. Якщо потрібен один конкретний плагін, є `CreateOneForward()`.

Джерела: [forward API declarations](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward), [`DestroyForward`](https://www.amxmodx.org/api/amxmodx/DestroyForward).

<a id="forward-return-policy"></a>
## Даємо іншому плагіну заборонити дію

Якщо інший плагін може заборонити дію, викликаємо forward до самої дії й перевіряємо відповідь. Для нагороди це означає спочатку запитати дозвіл і лише потім її видати. Режим `ET_*` визначає, які обробники будуть викликані та як зібрати їхні результати.

| Режим | Поведінка |
| --- | --- |
| `ET_IGNORE` | Ігнорувати результати обробників |
| `ET_STOP` | Зупинитися на першому результаті понад `0` |
| `ET_STOP2` | Зупинитися саме на `PLUGIN_HANDLED` (`1`); інакше зберігати найбільший результат |
| `ET_CONTINUE` | Викликати всіх обробників і зберегти найбільший результат |


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

Тут `PLUGIN_CONTINUE` дозволяє нагороду, `PLUGIN_HANDLED` забороняє. Без обробників отримаємо нуль, і перевірка дозволить дію. Якщо створити чи виконати forward не вдалося, приклад забороняє нагороду. Сповіщення після видачі вже нічого не скасує, тому порядок викликів важливий.

Джерела: [ET constants](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxconst.inc), [forward execution](https://github.com/alliedmodders/amxmodx/blob/1.9-dev/amxmodx/CForward.cpp).

<a id="forward-arrays"></a>
## Передаємо масив у forward і забираємо зміни

Для масиву у forward потрібен `PrepareArray()`, однієї локальної адреси недостатньо. Отримане значення передаємо на місце параметра `FP_ARRAY` в `ExecuteForward()`. Останній аргумент `1` у `PrepareArray()` дозволяє скопіювати зміни обробників назад у вихідний масив; без такої задачі копіювання назад не потрібне.


```pawn
#include <amxmodx>

public plugin_cfg()
{
	new handle = CreateMultiForward( "notes_adjust_values", ET_IGNORE, FP_ARRAY, FP_CELL );

	if( handle == -1 )
		return;

	new values[] = { 10, 20, 30 };
	// Остання 1 потрібна, щоб зміни повернулися у values.
	new prepared = PrepareArray( values, sizeof values, 1 );
	new result;
	ExecuteForward( handle, result, prepared, sizeof values );
	DestroyForward( handle );
	server_print( "First value after callbacks: %d", values[0] );
}
```

Інший плагін реалізує `public notes_adjust_values( values[], count )` і працює лише до `count`. Розмір передаємо окремо: вихідного оголошення масиву в обробника немає. Підготовлене значення потрібне для виклику forward, це не контейнер `Array:`, і в `ArrayDestroy()` його передавати не можна.

Джерела: [`PrepareArray`](https://www.amxmodx.org/api/amxmodx/PrepareArray), [`ExecuteForward`](https://www.amxmodx.org/api/amxmodx/ExecuteForward).

<a id="optional-natives"></a>
## Працюємо й без необов’язкової native

Одного `if` перед викликом недостатньо, щоб native стала необов'язковою: без неї плагін може не завантажитися ще до виконання коду. У `plugin_natives()` ставимо фільтр саме для потрібної native, а перед викликом перевіряємо постачальника. У прикладі без `notes_api` використовуємо звичайний `clamp()`.


```pawn
#include <amxmodx>

native notes_clamp_score( score );

public plugin_natives()
{
	set_native_filter( "filter_optional_native" );
}

public filter_optional_native( const native_name[], index, trap )
{
	// Дозволяємо відсутність лише цієї native під час завантаження.
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

Обов'язкового `#pragma reqlib notes_api` тут навмисно немає. `trap = 0` означає, що native не знайшлася під час завантаження, `trap = 1` що відсутню native вже спробували викликати. Другий випадок і чужі помилки загальним фільтром не приховуємо. Якщо include окремо вимагає модуль, може знадобитися точковий фільтр модулів або окрема збірка: фільтр native вимогу бібліотеки не скасовує.

Джерела: [описи фільтрів native і модулів](https://github.com/alliedmodders/amxmodx/blob/master/plugins/include/amxmodx.inc), [`LibraryExists`](https://www.amxmodx.org/api/amxmodx/LibraryExists).

<a id="api-contract"></a>
## Що пояснити у спільному include

У спільному include описуємо не лише параметри. Інший плагін має розуміти, як правильно викликати нашу функцію й що робити з результатом:

- який плагін чи модуль потрібен і яка версія API підходить; самої наявності бібліотеки недостатньо;
- що саме означає ID: слот гравця, userid, індекс сутності чи окремий handle;
- чи можна передати відключеного гравця, бота або нуль;
- скільки символів уміститься у вихідний буфер і хто закриє повернений handle;
- що функція повертає за успіху й помилки та що означають відповіді обробників;
- коли приходить forward: до зміни чи після, і чи дозволено змінювати його аргументи.

Порядок і зміст параметрів наявної native непомітно не змінюємо. Якщо сумісність змінюється, додаємо перевірку версії або нову функцію API. Для неправильного аргументу використовуємо `log_error( AMX_ERR_NATIVE, ... )`: помилка буде показана в плагіна виклику, ближче до її причини. Це зрозуміліше, ніж прийняти поганий ID чи handle і зламатися пізніше в іншому місці.

Джерела: [`register_library`](https://www.amxmodx.org/api/amxmodx/register_library), [`log_error`](https://www.amxmodx.org/api/amxmodx/log_error).
