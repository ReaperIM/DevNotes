# Pawn и значения

[EN](../en/01-pawn-and-values.md) | [UK](../uk/01-pawn-and-values.md) | [RU](../ru/01-pawn-and-values.md)

[Содержание](../../README.ru.md)

- [Ячейки и теги](#cells-and-tags)
- [Преобразовать значение или только сменить тег?](#float-conversion)
- [Как не потерять дробную часть при делении](#division-and-rounding)
- [Сравнения и порядок проверок](#conditions-and-comparisons)
- [Константы и значения `enum`](#constants-and-enums)
- [Как вернуть несколько значений через параметры](#reference-parameters)
- [Аргументы по умолчанию и по имени](#default-and-named-arguments)
- [Битовые флаги: установить, проверить, сбросить](#bit-flags)
- [Локальные `new` и `static`](#static-storage)
- [Несколько значений и диапазоны в `switch`](#switch-and-ranges)
- [Когда нужны `public` и `stock`](#function-visibility)

<a id="cells-and-tags"></a>
## Ячейки и теги

В Pawn целое число, `bool` и `Float` занимают одинаковое место: одну 32-битную ячейку. Чтобы компилятор понимал, какое значение перед ним и как с ним работать, указываем тег. Например, `Float:` подсказывает, что `0.25` нужно обрабатывать как дробное число. Отдельный объект при этом не создаётся.

```pawn
#include <amxmodx>

public plugin_init()
{
	new count = 7;
	new bool:enabled = true;
	new Float:delay = 0.25;

	server_print( "count=%d enabled=%d delay=%.2f", count, enabled, delay );
	// count=7 enabled=1 delay=0.25
}
```

Если появился `tag mismatch`, сначала разбираемся, какие значения смешались. Запись `_:` уберёт тег, но неправильные биты останутся. Вызванная native может получить совсем не то число, которое мы ожидали.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Types)

<a id="float-conversion"></a>
## Преобразовать значение или только сменить тег?

Нужно получить дробное число из целого? Используем `float()`. Запись `Float:` делает другое: оставляет биты как есть и меняет их трактовку. В примере `7` превращается в `7.0`, а после деления получаем `3.5`. Для обратного перехода есть `floatround()`, где можно выбрать округление вниз или вверх.

```pawn
#include <amxmodx>

public plugin_init()
{
	new count = 7;
	new Float:scaled = float( count ) / 2.0;

	server_print( "%.1f / %d / %d", scaled,
		floatround( scaled, floatround_floor ),
		floatround( scaled, floatround_ceil ) );
	// 3.5 / 3 / 4
}
```

Менять только тег имеет смысл, когда API специально передаёт биты `Float` в ячейке без тега. Для обычного перевода числа из одного вида в другой нужна функция преобразования.

[AMXX / Pawn](https://www.amxmodx.org/api/float/floatround)

<a id="division-and-rounding"></a>
## Как не потерять дробную часть при делении

Если разделить `5` на `2`, Pawn получит `2`: оба числа целые. Запись результата во `Float` уже не вернёт потерянные `0.5`. Поэтому преобразуем число до деления. Ниже рядом показаны целый результат, остаток и среднее с дробной частью.

```pawn
#include <amxmodx>

public plugin_init()
{
	new total = 5, players = 2;
	new whole = total / players;
	new remainder = total % players;
	// Преобразуем до деления, чтобы сохранить дробную часть.
	new Float:average = float( total ) / float( players );

	server_print( "%d remainder %d; %.1f", whole, remainder, average );
	// 2 remainder 1; 2.5
}
```

Если делитель пришёл из команды или счётчика, проверяем его до вычисления. На ноль нельзя ни делить через `/`, ни искать остаток через `%`.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="conditions-and-comparisons"></a>
## Сравнения и порядок проверок

Значения сравниваем через `==` и `!=`; PHP-варианта `===` в Pawn нет. Условия связываем через `&&` и `||`. Проверки идут слева направо, и правая часть пропускается, когда ответ уже понятен. Это позволяет сначала проверить индекс и только потом прочитать элемент массива.

```pawn
#include <amxmodx>

public plugin_init()
{
	new values[] = { 10, 20, 30 };
	new index = 2;

	if( index >= 0 && index < sizeof values && values[index] == 30 )
		server_print( "found" );
	// found
}
```

В примере обращение к `values[index]` произойдёт только после проверки границ. Для строк нужны `equal()` или `equali()`: `==` не сравнивает их содержимое.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="constants-and-enums"></a>
## Константы и значения `enum`

Когда у числа есть смысл, даём ему имя: `MAX_ROUNDS` читается понятнее случайного `30` в условии. Для нескольких состояний удобно завести `enum`. В примере тег `round_state` помогает заметить, если вместо состояния случайно передали другое значение.

```pawn
#include <amxmodx>

const MAX_ROUNDS = 30;

enum round_state
{
	round_waiting,
	round_live,
	round_finished
};

public plugin_init()
{
	new round_state:current_state = round_live;
	server_print( "limit=%d live=%d", MAX_ROUNDS, current_state == round_live );
	// limit=30 live=1
}
```

`#define` подставляет текст ещё до компиляции, а `const` объявляет константу для компилятора. Изменяемую глобальную переменную оба варианта не создают.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Compiler_Pre-processor_Directives)

<a id="reference-parameters"></a>
## Как вернуть несколько значений через параметры

Обычный числовой параметр получает копию значения. Если функции нужно записать результат прямо в переменную вызывающего кода, добавляем `&`. Ниже одна функция заполняет сразу `quotient` и `remainder`. С массивами иначе: параметр-массив уже обращается к исходному массиву.

```pawn
#include <amxmodx>

public plugin_init()
{
	new quotient, remainder;
	divide_with_remainder( 17, 5, quotient, remainder );
	server_print( "%d remainder %d", quotient, remainder );
	// 3 remainder 2
}

divide_with_remainder( value, divisor, &quotient, &remainder )
{
	quotient = value / divisor;
	remainder = value % divisor;
}
```

Если массив нужен только для чтения, ставим `const`. Для выходной строки передаём ещё и вместимость: по записи `output[]` функция не узнает, сколько места ей выделили снаружи.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="default-and-named-arguments"></a>
## Аргументы по умолчанию и по имени

Часто функцию вызывают с одними и теми же настройками. Их можно задать по умолчанию, а при вызове передавать только то, что меняется. Запись `.bonus = 5` позволяет изменить последний параметр и оставить `multiplier` равным `10`. Поэтому два вызова ниже дают `30` и `35`.

```pawn
#include <amxmodx>

public plugin_init()
{
	server_print( "%d / %d", calculate_reward( 3 ),
		calculate_reward( 3, .bonus = 5 ) );
	// 30 / 35
}

calculate_reward( count, multiplier = 10, bonus = 0 )
{
	return count * multiplier + bonus;
}
```

Если аргумент передаётся по имени, переименование параметра затронет и такие вызовы. Проверяем их вместе с объявлением функции.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="bit-flags"></a>
## Битовые флаги: установить, проверить, сбросить

Несколько независимых настроек можно хранить в одном числе. Для этого каждому флагу выделяем свой бит: `1`, `2`, `4` и дальше. В примере включаем HUD, проверяем сразу два флага, затем выключаем звук. Остальные настройки при этом сохраняются.

```pawn
#include <amxmodx>

enum (<<= 1)
{
	flag_sound = 1,
	flag_hud,
	flag_trail
};

public plugin_init()
{
	new flags = flag_sound;
	flags |= flag_hud;

	// Нужны оба флага, а не любой из них.
	if( (flags & (flag_sound | flag_hud)) == (flag_sound | flag_hud) )
		server_print( "both enabled" );

	flags &= ~flag_sound;
	server_print( "sound=%d hud=%d", !!(flags & flag_sound), !!(flags & flag_hud) );
	// both enabled
	// sound=0 hud=1
}
```

`flags & mask` достаточно, когда нужен хотя бы один флаг из маски. Для всех сразу используем `(flags & mask) == mask`. Если бит выбирается по индексу игрока, сначала проверяем индекс: в ячейке всего 32 бита.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="static-storage"></a>
## Локальные `new` и `static`

У локальной `new` при каждом вызове снова исходное значение. Локальная `static` помнит результат прошлого вызова, но обращаться к ней по имени можно только внутри этой функции. Поэтому `next_sequence()` ниже возвращает сначала `1`, затем `2`.

```pawn
#include <amxmodx>

public plugin_init()
{
	server_print( "%d", next_sequence() ); // 1
	server_print( "%d", next_sequence() ); // 2
}

next_sequence()
{
	static sequence;
	return ++sequence;
}
```

У `static`-буфера одна общая память на все вызовы функции. Оставшиеся данные нужно сбрасывать там, где они больше не нужны. Для каждого игрока отдельного буфера не появится; при рекурсии вызовы тоже будут работать с той же памятью.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Variables)

<a id="switch-and-ranges"></a>
## Несколько значений и диапазоны в `switch`

В одном `case` можно перечислить несколько значений или задать диапазон. Запись `3..5` включает обе границы, поэтому `level = 4` попадёт в этот вариант. После выполнения подходящего `case` Pawn сам выходит из `switch`; `break` для этого не нужен.

```pawn
#include <amxmodx>

public plugin_init()
{
	new level = 4;

	switch( level )
	{
		case 0: server_print( "none" );
		case 1, 2: server_print( "basic" );
		case 3..5: server_print( "advanced" );
		default: server_print( "custom" );
	}
	// advanced
}
```

Если в одном варианте несколько действий, объединяем их фигурными скобками. В метках `case` нужны целые константы: произвольную проверку, вычисляемую во время работы, туда не поставить.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Switch_Statements)

<a id="function-visibility"></a>
## Когда нужны `public` и `stock`

Команда регистрируется по имени обработчика: AMXX позже найдёт и вызовет `command_double`. Такую функцию отмечаем `public`; её часто называют callback. Для обычного прямого вызова `double_value()` это не требуется. Пометка `stock` позволяет не включать вспомогательную функцию в плагин, если она нигде не используется.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_srvcmd( "notes_double", "command_double" );
}

public command_double()
{
	server_print( "%d", double_value( 7 ) ); // 14
	return PLUGIN_HANDLED;
}

stock double_value( value )
{
	return value * 2;
}
```

Когда имя обработчика записано строкой, оно должно точно совпадать с именем `public`-функции. Параметры тоже должны подходить API. Одной пометки `stock` недостаточно, чтобы AMXX смог найти обработчик.

[AMXX / Pawn](https://www.amxmodx.org/api/amxmodx/register_srvcmd)
