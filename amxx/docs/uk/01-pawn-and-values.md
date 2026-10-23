# Pawn і значення

[EN](../en/01-pawn-and-values.md) | [UK](../uk/01-pawn-and-values.md) | [RU](../ru/01-pawn-and-values.md)

[Зміст](../../README.uk.md)

- [Комірки та теги](#cells-and-tags)
- [Перетворити значення чи лише змінити тег?](#float-conversion)
- [Як не втратити дробову частину під час ділення](#division-and-rounding)
- [Порівняння та порядок перевірок](#conditions-and-comparisons)
- [Константи та значення `enum`](#constants-and-enums)
- [Як повернути кілька значень через параметри](#reference-parameters)
- [Типові та іменовані аргументи](#default-and-named-arguments)
- [Бітові прапорці: встановити, перевірити, скинути](#bit-flags)
- [Локальні `new` і `static`](#static-storage)
- [Кілька значень і діапазони у `switch`](#switch-and-ranges)
- [Коли потрібні `public` і `stock`](#function-visibility)

<a id="cells-and-tags"></a>
## Комірки та теги

У Pawn ціле число, `bool` і `Float` займають однакове місце: одну 32-бітну комірку. Щоб компілятор розумів, яке значення перед ним і як із ним працювати, вказуємо тег. Наприклад, `Float:` підказує, що `0.25` треба обробляти як дробове число. Окремий об’єкт при цьому не створюється.

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

Якщо з’явився `tag mismatch`, спочатку розбираємося, які значення змішалися. Запис `_:` прибере тег, але неправильні біти залишаться. Викликана native може отримати зовсім не те число, на яке ми розраховували.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Types)

<a id="float-conversion"></a>
## Перетворити значення чи лише змінити тег?

Потрібне дробове число з цілого? Використовуємо `float()`. Запис `Float:` робить інше: залишає біти як є та змінює їх трактування. У прикладі `7` перетворюється на `7.0`, а після ділення отримуємо `3.5`. Для зворотного переходу є `floatround()`, де можна вибрати округлення вниз або вгору.

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

Змінювати лише тег має сенс, коли API спеціально передає біти `Float` у комірці без тегу. Для звичайного переведення числа з одного виду в інший потрібна функція перетворення.

[AMXX / Pawn](https://www.amxmodx.org/api/float/floatround)

<a id="division-and-rounding"></a>
## Як не втратити дробову частину під час ділення

Якщо поділити `5` на `2`, Pawn отримає `2`: обидва числа цілі. Запис результату у `Float` уже не поверне втрачені `0.5`. Тому перетворюємо число до ділення. Нижче поруч показані цілий результат, остача й середнє з дробовою частиною.

```pawn
#include <amxmodx>

public plugin_init()
{
	new total = 5, players = 2;
	new whole = total / players;
	new remainder = total % players;
	// Перетворюємо до ділення, щоб зберегти дробову частину.
	new Float:average = float( total ) / float( players );

	server_print( "%d remainder %d; %.1f", whole, remainder, average );
	// 2 remainder 1; 2.5
}
```

Якщо дільник надійшов із команди або лічильника, перевіряємо його до обчислення. На нуль не можна ні ділити через `/`, ні шукати остачу через `%`.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="conditions-and-comparisons"></a>
## Порівняння та порядок перевірок

Значення порівнюємо через `==` і `!=`; PHP-варіанта `===` у Pawn немає. Умови поєднуємо через `&&` та `||`. Перевірки йдуть зліва направо, і права частина пропускається, коли відповідь уже зрозуміла. Це дозволяє спочатку перевірити індекс і лише потім прочитати елемент масиву.

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

У прикладі звернення до `values[index]` відбудеться лише після перевірки меж. Для рядків потрібні `equal()` або `equali()`: `==` не порівнює їхній вміст.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="constants-and-enums"></a>
## Константи та значення `enum`

Коли число має певний сенс, даємо йому ім’я: `MAX_ROUNDS` читається зрозуміліше за випадкове `30` в умові. Для кількох станів зручно завести `enum`. У прикладі тег `round_state` допомагає помітити, якщо замість стану випадково передали інше значення.

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

`#define` підставляє текст ще до компіляції, а `const` оголошує константу для компілятора. Змінюваної глобальної змінної обидва варіанти не створюють.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Compiler_Pre-processor_Directives)

<a id="reference-parameters"></a>
## Як повернути кілька значень через параметри

Звичайний числовий параметр отримує копію значення. Якщо функції треба записати результат просто у змінну коду, який її викликав, додаємо `&`. Нижче одна функція заповнює одразу `quotient` і `remainder`. З масивами інакше: параметр-масив уже звертається до початкового масиву.

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

Якщо масив потрібен лише для читання, ставимо `const`. Для вихідного рядка передаємо ще й місткість: із запису `output[]` функція не дізнається, скільки місця для нього виділили зовні.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="default-and-named-arguments"></a>
## Типові та іменовані аргументи

Часто функцію викликають з одними й тими самими налаштуваннями. Їх можна задати за замовчуванням, а під час виклику передавати лише те, що змінюється. Запис `.bonus = 5` дозволяє змінити останній параметр і залишити `multiplier` рівним `10`. Тому два виклики нижче дають `30` і `35`.

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

Якщо аргумент передається за ім’ям, перейменування параметра зачепить і такі виклики. Перевіряємо їх разом з оголошенням функції.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Functions)

<a id="bit-flags"></a>
## Бітові прапорці: встановити, перевірити, скинути

Кілька незалежних налаштувань можна зберігати в одному числі. Для цього кожному прапорцю виділяємо свій біт: `1`, `2`, `4` і далі. У прикладі вмикаємо HUD, перевіряємо одразу два прапорці, а потім вимикаємо звук. Решта налаштувань при цьому зберігається.

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

	// Потрібні обидва прапорці, а не будь-який один.
	if( (flags & (flag_sound | flag_hud)) == (flag_sound | flag_hud) )
		server_print( "both enabled" );

	flags &= ~flag_sound;
	server_print( "sound=%d hud=%d", !!(flags & flag_sound), !!(flags & flag_hud) );
	// both enabled
	// sound=0 hud=1
}
```

`flags & mask` достатньо, коли потрібен хоча б один прапорець із маски. Для всіх одразу використовуємо `(flags & mask) == mask`. Якщо біт вибирається за індексом гравця, спочатку перевіряємо індекс: у комірці лише 32 біти.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Expressions)

<a id="static-storage"></a>
## Локальні `new` і `static`

У локальної `new` під час кожного виклику знову початкове значення. Локальна `static` пам’ятає результат попереднього виклику, але звертатися до неї за ім’ям можна лише всередині цієї функції. Тому `next_sequence()` нижче повертає спочатку `1`, а потім `2`.

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

У `static`-буфера одна спільна пам’ять на всі виклики функції. Дані, що залишилися, треба скидати там, де вони більше не потрібні. Для кожного гравця окремого буфера не з’явиться; під час рекурсії виклики теж працюватимуть із тією самою пам’яттю.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Variables)

<a id="switch-and-ranges"></a>
## Кілька значень і діапазони у `switch`

В одному `case` можна перелічити кілька значень або задати діапазон. Запис `3..5` включає обидві межі, тому `level = 4` потрапить у цей варіант. Після виконання потрібного `case` Pawn сам виходить зі `switch`; `break` для цього не потрібен.

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

Якщо в одному варіанті кілька дій, об’єднуємо їх фігурними дужками. У мітках `case` потрібні цілі константи: довільну перевірку, що обчислюється під час роботи, туди не поставити.

[AMXX / Pawn](https://wiki.alliedmods.net/Pawn_Tutorial#Switch_Statements)

<a id="function-visibility"></a>
## Коли потрібні `public` і `stock`

Команда реєструється за ім’ям обробника: AMXX згодом знайде та викличе `command_double`. Таку функцію позначаємо `public`; її часто називають callback. Для звичайного прямого виклику `double_value()` це не потрібно. Позначка `stock` дозволяє не включати допоміжну функцію у плагін, якщо вона ніде не використовується.

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

Коли ім’я обробника записане рядком, воно має точно збігатися з ім’ям `public`-функції. Параметри теж мають підходити API. Самої позначки `stock` недостатньо, щоб AMXX знайшов обробник.

[AMXX / Pawn](https://www.amxmodx.org/api/amxmodx/register_srvcmd)
