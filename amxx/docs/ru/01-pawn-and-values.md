# Pawn и значения

[English](../en/01-pawn-and-values.md) | [Українська](../uk/01-pawn-and-values.md) | [Русский](../ru/01-pawn-and-values.md)

[Содержание](../../README.ru.md)

- [Ячейки и теги](#cells-and-tags)
- [Преобразовать значение или только сменить тег?](#float-conversion)
- [Целочисленное деление до преобразования в Float](#division-and-rounding)
- [Сравнения, bool и короткое вычисление](#conditions-and-comparisons)
- [Константы и значения enum](#constants-and-enums)
- [Выходные значения через параметры-ссылки](#reference-parameters)
- [Аргументы по умолчанию и по имени](#default-and-named-arguments)
- [Битовые флаги: установить, проверить, сбросить](#bit-flags)
- [Локальные new и static](#static-storage)
- [switch без проваливания между case](#switch-and-ranges)
- [public callbacks и вспомогательные stock](#function-visibility)

<a id="cells-and-tags"></a>
## Ячейки и теги

В AMXX обычное целое число, bool и Float занимают по одной 32-битной ячейке. Тег подсказывает компилятору, как трактовать значение и какие операторы использовать; отдельного объекта он не создаёт.

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

Предупреждение tag mismatch стоит исправлять. `_:` может убрать предупреждение, но оставить неверное битовое представление для native.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Types)

<a id="float-conversion"></a>
## Преобразовать значение или только сменить тег?

`float()` преобразует целое число в Float. `Float:` лишь переинтерпретирует существующие биты. Обратное преобразование тоже явное: правило округления задаётся в `floatround()`.

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

Переинтерпретация нужна, когда API явно передаёт биты Float в ячейке без тега. Это не замена числового преобразования.

[AMXX / Pawn](https://www.amxmodx.org/api/float/floatround)

<a id="division-and-rounding"></a>
## Целочисленное деление до преобразования в Float

Способ деления определяется операндами. Присваивание результата во Float не вернёт дробную часть, уже потерянную при целочисленном делении.

```pawn
#include <amxmodx>

public plugin_init()
{
	new total = 5, players = 2;
	new whole = total / players;
	new remainder = total % players;
	new Float:average = float( total ) / float( players );

	server_print( "%d remainder %d; %.1f", whole, remainder, average );
	// 2 remainder 1; 2.5
}
```

Преобразуйте до деления. Если делитель получен из ввода или счётчика, проверяйте его; для `%` он тоже не может быть нулём.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="conditions-and-comparisons"></a>
## Сравнения, bool и короткое вычисление

В Pawn используются `==` и `!=`; аналога PHP `===` нет. Условия соединяются через `&&` и `||`. Правый операнд не вычисляется, когда результат уже известен по левому.

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

Проверка границ должна стоять перед обращением к массиву. Строки сравнивайте через `equal()` или `equali()`, а не через `==`.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="constants-and-enums"></a>
## Константы и значения enum

Для границ используйте именованные константы, для набора состояний enum. Enum может иметь тег, который помогает заметить смешивание разных значений.

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

`#define` выполняет текстовую подстановку, `const` объявляет константу компилятора. Оба варианта не создают изменяемую глобальную переменную.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Compiler_Pre-processor_Directives)

<a id="reference-parameters"></a>
## Выходные значения через параметры-ссылки

Скалярный параметр обычно получает копию. Добавьте `&`, когда функция должна менять переменную вызывающего кода. Параметр-массив уже ссылается на массив вызывающего кода.

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

Массивы только для чтения помечайте `const`. Для выходной строки также передавайте вместимость: из `output[]` функция не узнает размер буфера вызывающего кода.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="default-and-named-arguments"></a>
## Аргументы по умолчанию и по имени

Значения по умолчанию убирают необязательные настройки из простого вызова. Именованный аргумент позволяет изменить более поздний параметр, не повторяя предыдущие значения по умолчанию.

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

Имя параметра становится частью таких вызовов. После переименования может потребоваться изменить не только объявление функции.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="bit-flags"></a>
## Битовые флаги: установить, проверить, сбросить

Битовая маска хранит несколько независимых переключателей в одной ячейке. Степени двойки дают каждому флагу отдельный бит.

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

	if( (flags & (flag_sound | flag_hud)) == (flag_sound | flag_hud) )
		server_print( "both enabled" );

	flags &= ~flag_sound;
	server_print( "sound=%d hud=%d", !!(flags & flag_sound), !!(flags & flag_hud) );
	// both enabled
	// sound=0 hud=1
}
```

`flags & mask` проверяет хотя бы один бит, `(flags & mask) == mask` проверяет все. Не выполняйте сдвиг по непроверенному индексу игрока: в ячейке только 32 бита.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="static-storage"></a>
## Локальные new и static

Локальная переменная `new` начинает с исходного состояния при каждом вызове. Локальная `static` инициализируется один раз и сохраняет значение между вызовами, но её имя доступно только в функции.

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

Буфер static общий для всех вызовов этой функции. Сбрасывайте части, относящиеся к одной операции; это не отдельное состояние игрока и не безопасный временный буфер для рекурсии.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Variables)

<a id="switch-and-ranges"></a>
## switch без проваливания между case

Pawn switch поддерживает несколько значений в одном case и диапазоны включительно. После совпадения case завершается без `break` и не переходит в следующий.

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

Для нескольких действий в case нужны фигурные скобки. Метки case являются целыми константами, а не произвольными условиями времени выполнения.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Switch_Statements)

<a id="function-visibility"></a>
## public callbacks и вспомогательные stock

AMXX находит callbacks по публичному имени. Внутренней функции, вызываемой напрямую, `public` не нужен. Вспомогательная `stock` не попадает в плагин, если её не используют.

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

Callback, зарегистрированный строкой, должен сохранять точное имя и нужную сигнатуру. Пометка stock не делает его видимым для AMXX.

[AMXX / Pawn](https://www.amxmodx.org/api/amxmodx/register_srvcmd)
