# Pawn і значення

[English](../en/01-pawn-and-values.md) | [Українська](../uk/01-pawn-and-values.md) | [Русский](../ru/01-pawn-and-values.md)

[Зміст](../../README.uk.md)

- [Комірки та теги](#cells-and-tags)
- [Перетворити значення чи лише змінити тег?](#float-conversion)
- [Цілочисельне ділення до перетворення на Float](#division-and-rounding)
- [Порівняння, bool і коротке обчислення](#conditions-and-comparisons)
- [Константи та значення enum](#constants-and-enums)
- [Вихідні значення через параметри-посилання](#reference-parameters)
- [Типові та іменовані аргументи](#default-and-named-arguments)
- [Бітові прапорці: встановити, перевірити, скинути](#bit-flags)
- [Локальні new і static](#static-storage)
- [switch без провалювання між case](#switch-and-ranges)
- [public callbacks і допоміжні stock](#function-visibility)

<a id="cells-and-tags"></a>
## Комірки та теги

В AMXX звичайне ціле число, bool і Float займають по одній 32-бітній комірці. Тег підказує компілятору, як трактувати значення та які оператори застосовувати; окремий об’єкт він не створює.

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

Попередження tag mismatch варто виправляти. `_:` може прибрати попередження, але залишити неправильне бітове подання для native.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Types)

<a id="float-conversion"></a>
## Перетворити значення чи лише змінити тег?

`float()` перетворює ціле число на Float. `Float:` лише переінтерпретує наявні біти. Зворотне перетворення теж явне: правило округлення задається в `floatround()`.

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

Переінтерпретація потрібна, коли API явно передає біти Float у комірці без тегу. Це не заміна числового перетворення.

[AMXX / Pawn](https://www.amxmodx.org/api/float/floatround)

<a id="division-and-rounding"></a>
## Цілочисельне ділення до перетворення на Float

Спосіб ділення визначається операндами. Присвоєння результату у Float не поверне дробову частину, яку вже втрачено під час цілочисельного ділення.

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

Перетворюйте до ділення. Якщо дільник отримано з вводу або лічильника, перевіряйте його; для `%` він також не може бути нулем.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="conditions-and-comparisons"></a>
## Порівняння, bool і коротке обчислення

У Pawn використовуються `==` та `!=`; аналога PHP `===` немає. Умови поєднуються через `&&` і `||`. Правий операнд не обчислюється, коли результат уже відомий з лівого.

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

Перевірка меж має стояти перед доступом до масиву. Рядки порівнюйте через `equal()` або `equali()`, а не через `==`.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="constants-and-enums"></a>
## Константи та значення enum

Для меж використовуйте іменовані константи, для набору станів enum. Enum може мати тег, який допомагає помітити змішування різних значень.

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

`#define` виконує текстову підстановку, `const` оголошує константу компілятора. Обидва варіанти не створюють змінну глобального стану.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Compiler_Pre-processor_Directives)

<a id="reference-parameters"></a>
## Вихідні значення через параметри-посилання

Скалярний параметр зазвичай отримує копію. Додайте `&`, коли функція має змінювати змінну виклику. Параметр-масив уже посилається на масив виклику.

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

Масиви лише для читання позначайте `const`. Для вихідного рядка також передавайте місткість: з `output[]` функція не дізнається розмір буфера виклику.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="default-and-named-arguments"></a>
## Типові та іменовані аргументи

Типові значення прибирають необов’язкові налаштування з простого виклику. Іменований аргумент дозволяє змінити пізніший параметр, не повторюючи попередні типові значення.

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

Ім’я параметра стає частиною таких викликів. Після перейменування може знадобитися змінити не лише оголошення функції.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="bit-flags"></a>
## Бітові прапорці: встановити, перевірити, скинути

Бітова маска зберігає кілька незалежних перемикачів в одній комірці. Степені двійки дають кожному прапорцю окремий біт.

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

`flags & mask` перевіряє хоча б один біт, `(flags & mask) == mask` перевіряє всі. Не виконуйте зсув за неперевіреним індексом гравця: у комірці лише 32 біти.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="static-storage"></a>
## Локальні new і static

Локальна змінна `new` створює початковий стан при кожному виклику. Локальна `static` ініціалізується один раз і зберігає значення між викликами, але її ім’я доступне лише у функції.

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

Буфер static спільний для всіх викликів цієї функції. Скидайте частини, що належать одній операції; це не окремий стан гравця і не безпечний тимчасовий буфер для рекурсії.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Variables)

<a id="switch-and-ranges"></a>
## switch без провалювання між case

Pawn switch підтримує кілька значень в одному case та діапазони включно з межами. Після збігу case завершується без `break` і не переходить у наступний.

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

Для кількох дій у case потрібні фігурні дужки. Мітки case є цілими константами, а не довільними умовами часу виконання.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Switch_Statements)

<a id="function-visibility"></a>
## public callbacks і допоміжні stock

AMXX знаходить callbacks за публічним ім’ям. Внутрішня функція, яку викликають напряму, не потребує `public`. Допоміжна `stock` не потрапляє до плагіна, якщо її не використовують.

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

Callback, зареєстрований рядком, має зберігати точне ім’я та потрібну сигнатуру. Позначка stock не робить його видимим для AMXX.

[AMXX / Pawn](https://www.amxmodx.org/api/amxmodx/register_srvcmd)
