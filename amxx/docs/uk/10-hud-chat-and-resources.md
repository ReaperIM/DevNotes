# HUD, чат і ресурси

[EN](../en/10-hud-chat-and-resources.md) | [UK](../uk/10-hud-chat-and-resources.md) | [RU](../ru/10-hud-chat-and-resources.md)

[Зміст](../../README.uk.md)

Виведення для потрібного клієнта, повторні оновлення без зайвого накладання та правильні шляхи ресурсів. Приклади для AMXX 1.9. Кольоровий чат і приклад `StatusIcon` стосуються CS 1.6 / Condition Zero.

**У цьому розділі**

- [Кольоровий чат: одержувач і джерело кольору різні](#colored-chat)
- [Текст гравця є даними, а не рядком формату](#format-player-text)
- [Звичайний HUD має чотири канали на клієнта](#classic-hud)
- [Оновлюй та очищуй область HUD через sync-об'єкт](#hud-sync)
- [DHUD є стеком, а не каналом для заміни](#director-hud)
- [User message має відповідати своєму протоколу](#user-messages)
- [Перекладай для фактичного одержувача](#localized-output)
- [Шляхи WAV-звуків відносні до `sound/`](#precache-sound)
- [Precache моделей та індекси в межах карти](#precache-models)
- [Реєстрація завантаження не є precache звуку](#generic-downloads)

<a id="colored-chat"></a>

## Кольоровий чат: одержувач і джерело кольору різні

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print_color)

У CS 1.6 і Condition Zero `client_print_color()` вбудований в AMXX 1.9. Перший аргумент визначає одержувача, другий джерело командного кольору. `print_team_red` у sender не обмежує доставку терористами.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /colors", "command_colors" );
}

public command_colors( id )
{
	client_print_color( id, print_team_red, "^4[Notes] ^3Red ^1default" );
	return PLUGIN_HANDLED;
}
```

Повідомлення отримує лише той, хто викликав команду. `^4` зелений, `^3` вибраний командний колір, `^1` повертає стандартний. Одержувач `0` означає всіх; фільтрація одержувачів є окремою дією. Не підключай стару власну реалізацію `ColorChat` з таким самим ім'ям native.

<a id="format-player-text"></a>

## Текст гравця є даними, а не рядком формату

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print)

Ім'я гравця може містити `%` та інші символи форматування. Рядок формату залишай фіксованим, а ім'я передавай через `%s`. Те саме стосується консолі, логів, HUD і тексту з файлу.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /name", "command_name" );
}

public command_name( id )
{
	new name[32];
	get_user_name( id, name, charsmax( name ) );
	client_print( id, print_chat, "Your name: %s", name );
	return PLUGIN_HANDLED;
}
```

Ім'я на кшталт `100% ready` залишиться текстом. Не замінюй виклик на `client_print( id, print_chat, name )`. Безпечне форматування не прибирає керувальні коди чи кольори чату; за потреби обробляй їх окремо.

<a id="classic-hud"></a>

## Звичайний HUD має чотири канали на клієнта

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_hudmessage)

`set_hudmessage()` задає параметри наступного виведення, а не постійний об'єкт HUD. Канали `1..4` замінюють попереднє повідомлення у своєму каналі. `-1` вибирає автоматично; фіксований канал може стабілізувати періодичний текст, але інші плагіни використовують ті самі канали клієнта.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /hud", "command_hud" );
}

public command_hud( id )
{
	set_hudmessage( 0, 180, 255, -1.0, 0.2, 0, 0.0, 3.0, 0.1, 0.2, 2 );
	show_hudmessage( id, "Health: %d", get_user_health( id ) );
	return PLUGIN_HANDLED;
}
```

`-1.0` центрує по горизонталі, а `0.2` розміщує текст ближче до верху. Задавай параметри безпосередньо перед показом: ці налаштування спільні між плагінами. Більший hold time не оновлює здоров'я автоматично.

<a id="hud-sync"></a>

## Оновлюй та очищуй область HUD через sync-об'єкт

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/ShowSyncHudMsg)

Sync-об'єкт відстежує попереднє повідомлення логічної області HUD. Повторне використання акуратно замінює власний текст навіть з автоматичними каналами. Створи об'єкт один раз; він може обслуговувати однаковий тип відображення для кількох клієнтів.

```pawn
#include <amxmodx>

new g_status_hud;

public plugin_init()
{
	g_status_hud = CreateHudSyncObj();
	register_clcmd( "say /status", "command_status" );
	register_clcmd( "say /clearstatus", "command_clear_status" );
}

public command_status( id )
{
	set_hudmessage( 100, 220, 100, 0.02, 0.25, 0, 0.0, 5.0 );
	ShowSyncHudMsg( id, g_status_hud, "Players: %d", get_playersnum() );
	return PLUGIN_HANDLED;
}

public command_clear_status( id )
{
	ClearSyncHud( id, g_status_hud );
	return PLUGIN_HANDLED;
}
```

`ShowSyncHudMsg()` сам керує каналом та ігнорує канал із `set_hudmessage()`. Об'єкт не резервує місце екрана і не створює додаткові канали. `ClearSyncHud()` прибирає відстежуваний текст без виділення нового каналу повідомлення.

<a id="director-hud"></a>

## DHUD є стеком, а не каналом для заміни

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/show_dhudmessage)

Director HUD зручний для короткого оголошення. Одночасно підтримується до восьми повідомлень; нові замінюють старі в порядку надсилання. Окреме повідомлення DHUD не можна прибрати через sync-об'єкт.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /notice", "command_notice" );
}

public command_notice( id )
{
	set_dhudmessage( 255, 180, 20, -1.0, 0.3, 0, 0.0, 3.0, 0.1, 0.2 );
	show_dhudmessage( id, "Practice starts soon" );
	return PLUGIN_HANDLED;
}
```

API обмежує текст 128 символами. Щокадрові виклики накопичуватимуть або замінюватимуть оголошення, а не оновлюватимуть один постійний підпис. Для часто змінюваного статусу зручніший звичайний HUD із синхронізацією.

<a id="user-messages"></a>

## User message має відповідати своєму протоколу

[AMX Mod X API](https://www.amxmodx.org/api/messages/message_begin)

`message_begin()` та `message_end()` обмежують одне повідомлення протоколу. Порядок і типи `write_*` мають точно відповідати повідомленню гри. Приклад для CS надсилає `StatusIcon`: байт увімкнення, назву спрайта, потім три байти кольору.

```pawn
#include <amxmodx>

new g_status_icon;

public plugin_init()
{
	g_status_icon = get_user_msgid( "StatusIcon" );
	register_clcmd( "say /icon", "command_icon" );
}

public command_icon( id )
{
	if( !g_status_icon )
		return PLUGIN_HANDLED;

	message_begin( MSG_ONE_UNRELIABLE, g_status_icon, _, id );
	write_byte( 1 );
	write_string( "buyzone" );
	write_byte( 0 );
	write_byte( 255 );
	write_byte( 0 );
	message_end();
	return PLUGIN_HANDLED;
}
```

Змінюється іконка, а не серверний дозвіл купувати. Усі перевірки роби до `message_begin()` і не виходь посеред повідомлення. Unreliable-доставка може втратити косметичне оновлення; reliable не є приводом переповнювати канал. Надсилай при значущій зміні або з обмеженою частотою. [Формат `StatusIcon`](https://wiki.alliedmods.net/Half-Life_1_Game_Events#StatusIcon).

<a id="localized-output"></a>

## Перекладай для фактичного одержувача

[AMX Mod X API](https://www.amxmodx.org/api/lang/register_dictionary)

Зареєструй словник із `addons/amxmodx/data/lang/`. `%L` приймає вибір мови, потім ключ і його аргументи форматування. ID одержувача вибирає його мову; `LANG_SERVER` явно вибирає мову сервера.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_dictionary( "notes.txt" );
	register_clcmd( "say /welcome", "command_welcome" );
}

public command_welcome( id )
{
	new name[32];
	get_user_name( id, name, charsmax( name ) );
	client_print( id, print_chat, "%L", id, "NOTE_WELCOME", name );
	return PLUGIN_HANDLED;
}
```

Створи `notes.txt` з однаковими підстановками в усіх мовах. У власному циклі перекладай для кожного одержувача, а не один раз для всіх. Багатобайтовий текст також має вміщатися в буфер і ліміти повідомлень клієнта.

```ini
[en]
NOTE_WELCOME = Welcome, %s!

[uk]
NOTE_WELCOME = Вітаємо, %s!

[ru]
NOTE_WELCOME = Добро пожаловать, %s!
```

<a id="precache-sound"></a>

## Шляхи WAV-звуків відносні до `sound/`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_sound)

`precache_sound()` реєструє WAV у таблиці звуків рушія під час `plugin_precache()`. Шлях не містить префікса `sound/`. `emit_sound()` використовує той самий відносний шлях і відтворює позиційний звук від entity.

```pawn
#include <amxmodx>

new const NOTE_SOUND[] = "buttons/button9.wav";

public plugin_precache()
{
	precache_sound( NOTE_SOUND );
}

public plugin_init()
{
	register_clcmd( "say /sound", "command_sound" );
}

public command_sound( id )
{
	if( !is_user_alive( id ) )
		return PLUGIN_HANDLED;

	emit_sound( id, CHAN_ITEM, NOTE_SOUND, VOL_NORM, ATTN_NORM, 0, PITCH_NORM );
	return PLUGIN_HANDLED;
}
```

Індекс гравця є джерелом звуку, а не єдиним одержувачем: його можуть чути клієнти поруч. Приклад використовує стандартний звук Half-Life. Власні файли мають бути на сервері до завантаження карти, з тим самим написанням і регістром у дереві завантажень.

<a id="precache-models"></a>

## Precache моделей та індекси в межах карти

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_model)

Моделі та спрайти мають шляхи `models/...mdl` і `sprites/...spr` відносно каталогу мода. `precache_model()` повертає індекс ресурсу рушія. Зберігай його для ефектів, яким потрібен індекс, але не записуй як постійний ID між картами.

```pawn
#include <amxmodx>

new g_beam_sprite;

public plugin_precache()
{
	g_beam_sprite = precache_model( "sprites/white.spr" );
}

public plugin_init()
{
	server_print( "Beam sprite index on this map: %d", g_beam_sprite );
}
```

Фаза precache відбувається до `plugin_init()` і виконання остаточних конфигів. Модель, вибрану пізнішою зміною cvar, не можна просто precache на вимогу посеред карти. Визначай ресурси завчасно або реєструй невеликий відомий набір; таблиці ресурсів рушія обмежені.

<a id="generic-downloads"></a>

## Реєстрація завантаження не є precache звуку

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_generic)

`precache_generic()` реєструє файл для завантаження, але не додає WAV до таблиці звуків рушія. Тут потрібен повний шлях від каталогу мода, включно із `sound/` для MP3. Файл усе одно має існувати та підтримуватися клієнтом, який його використовує.

```pawn
#include <amxmodx>

public plugin_precache()
{
	new const music_path[] = "sound/notes/intro.mp3";

	if( !file_exists( music_path ) )
	{
		log_amx( "Optional music is missing: %s", music_path );
		return;
	}

	precache_generic( music_path );
}
```

Це лише реєстрація необов'язкового файлу: вона не відтворює музику і не гарантує завантаження кожним клієнтом. Використовуй фіксовані чи дозволені шляхи, підтримуй відповідні файли FastDL, якщо він є, та додавай лише потрібні ресурси. Generic-реєстрація звуку не робить його придатним для `emit_sound()`.
