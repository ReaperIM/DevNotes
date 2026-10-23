# HUD, чат і ресурси

[EN](../en/10-hud-chat-and-resources.md) | [UK](../uk/10-hud-chat-and-resources.md) | [RU](../ru/10-hud-chat-and-resources.md)

[Зміст](../../README.uk.md)

Показуємо текст потрібному гравцю, оновлюємо HUD без накладання старих повідомлень і заздалегідь готуємо звуки з моделями. Приклади розраховані на AMXX 1.9. Кольоровий чат і `StatusIcon` тут належать до CS 1.6 / Condition Zero.

**У цьому розділі**

- [Кольоровий чат: кому надсилаємо й звідки беремо колір](#colored-chat)
- [Ім’я гравця передаємо через `%s`](#format-player-text)
- [Чотири канали звичайного HUD](#classic-hud)
- [Оновлюємо й прибираємо свій HUD через sync-об’єкт](#hud-sync)
- [Короткі оголошення через DHUD](#director-hud)
- [Збираємо ігрове повідомлення в потрібному порядку](#user-messages)
- [Показуємо переклад мовою отримувача](#localized-output)
- [Готуємо WAV-звук і вказуємо шлях без `sound/`](#precache-sound)
- [Готуємо моделі й спрайти до старту карти](#precache-models)
- [Додаємо файл у завантаження через `precache_generic()`](#generic-downloads)

<a id="colored-chat"></a>

## Кольоровий чат: кому надсилаємо й звідки беремо колір

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print_color)

У CS 1.6 і Condition Zero можна використовувати вбудований `client_print_color()` з AMXX 1.9. Першим аргументом обираємо, кому показати повідомлення, другим звідки взяти командний колір. Це різні речі: `print_team_red` робить колір червоним, але не обирає терористів отримувачами.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /colors", "command_colors" );
}

public command_colors( id )
{
	// Червоний колір не обирає отримувачів, повідомлення йде лише id.
	client_print_color( id, print_team_red, "^4[Notes] ^3Red ^1default" );
	return PLUGIN_HANDLED;
}
```

У прикладі повідомлення побачить лише гравець, який написав команду. `^4` вмикає зелений, `^3` вибраний командний колір, `^1` повертає звичайний. Якщо першим аргументом передати `0`, повідомлення піде всім; потрібних гравців відбираємо окремо. Стару власну реалізацію `ColorChat` із тим самим ім'ям native підключати вже не потрібно.

<a id="format-player-text"></a>

## Ім’я гравця передаємо через `%s`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print)

В імені гравця може зустрітися `%`, тому ім'я не можна використовувати як сам рядок формату. Залишаємо формат фіксованим, а ім'я передаємо окремим аргументом через `%s`. Те саме правило працює для консолі, журналів, HUD і тексту, прочитаного з файлу.

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
	// Символи % в імені мають залишитися текстом.
	client_print( id, print_chat, "Your name: %s", name );
	return PLUGIN_HANDLED;
}
```

Ім'я `100% ready` у прикладі залишиться звичайним текстом. Якщо замінити виклик на `client_print( id, print_chat, name )`, символи імені вже розбиратимуться як формат. При цьому `%s` не прибирає кольорові та керівні коди чату: якщо їх теж потрібно обмежити, робимо це окремо.

<a id="classic-hud"></a>

## Чотири канали звичайного HUD

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_hudmessage)

У звичайного HUD чотири канали на клієнта, `1..4`. Нове повідомлення в тому самому каналі замінює старе, а `-1` дозволяє обрати канал автоматично. `set_hudmessage()` задає параметри наступного показу, але не створює постійний елемент інтерфейсу. Тут показуємо здоров'я в каналі `2`.

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

Координата `-1.0` центрує текст по горизонталі, `0.2` підіймає його ближче до верху. Налаштування спільні між плагінами, тому задаємо їх безпосередньо перед показом. Фіксований канал може допомогти під час оновлень, але інші плагіни теж його використовують. І тривалий час показу лише тримає текст на екрані, здоров'я саме від цього не оновлюється.

<a id="hud-sync"></a>

## Оновлюємо й прибираємо свій HUD через sync-об’єкт

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/ShowSyncHudMsg)

Якщо той самий текст потрібно оновлювати, зручно створити sync-об'єкт через `CreateHudSyncObj()`. Він запам'ятовує попереднє повідомлення, щоб наступний `ShowSyncHudMsg()` замінив наше старе виведення. Об'єкт створюємо один раз; для однакового виду статусу його можна використовувати в кількох гравців.

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

У прикладі `/status` показує статус, а `/clearstatus` прибирає його через той самий об'єкт. `ShowSyncHudMsg()` сам обирає канал, ігноруючи номер із `set_hudmessage()`. Додаткових каналів і закріпленого місця на екрані об'єкт не створює. `ClearSyncHud()` очищує відстежуване повідомлення, не займаючи для очищення новий канал.

<a id="director-hud"></a>

## Короткі оголошення через DHUD

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/show_dhudmessage)

Director HUD підходить для короткого оголошення на кшталт початку тренування. Одночасно може бути до восьми повідомлень; нові витісняють старі в порядку надсилання. Це важливо під час повторного показу: у DHUD немає окремого каналу, у якому ми просто замінюємо один напис.

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

Довжина тексту обмежена 128 символами. Якщо викликати показ кожен кадр, оголошення накопичуватимуться або витіснятимуть одне одного. Окреме DHUD-повідомлення через sync-об'єкт не прибрати, тому для постійно змінюваного статусу зручніше звичайний синхронізований HUD.

<a id="user-messages"></a>

## Збираємо ігрове повідомлення в потрібному порядку

[AMX Mod X API](https://www.amxmodx.org/api/messages/message_begin)

Ігрове повідомлення збирається між `message_begin()` і `message_end()`. Клієнт чекає певні типи й порядок полів, тому довільно міняти виклики `write_*` не можна. У прикладі для CS надсилаємо `StatusIcon`: спочатку байт увімкнення, потім ім'я спрайта й три байти кольору.

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

	// Перевірки вже пройдені, повідомлення тепер збираємо до message_end().
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

Зміниться лише показана іконка, дозвіл купувати на сервері залишиться попереднім. Усі перевірки робимо до `message_begin()`, щоб не вийти посеред незавершеного повідомлення. `MSG_ONE_UNRELIABLE` допускає втрату цього косметичного оновлення. Надійна доставка теж не привід надсилати кожен кадр: показуємо під час зміни або обмежуємо частоту. [Формат `StatusIcon`](https://wiki.alliedmods.net/Half-Life_1_Game_Events#StatusIcon).

<a id="localized-output"></a>

## Показуємо переклад мовою отримувача

[AMX Mod X API](https://www.amxmodx.org/api/lang/register_dictionary)

Щоб гравець отримав повідомлення своєю мовою, реєструємо словник з `addons/amxmodx/data/lang/`. Після `%L` передаємо вибір мови, ключ перекладу та його параметри. У прикладі мову обирає `id` отримувача, а ім'я гравця підставляється вже у знайдений рядок. `LANG_SERVER` замість ID обрав би мову сервера.

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

У `notes.txt` у перекладів мають збігатися підстановки, тут це `%s`. Якщо надсилаємо повідомлення своїм циклом, перекладаємо окремо для кожного гравця: один готовий переклад на всіх не врахує їхніх мов. Багатобайтовий текст теж має вміщуватися в буфер і обмеження повідомлення клієнта.

```ini
[en]
NOTE_WELCOME = Welcome, %s!

[uk]
NOTE_WELCOME = Вітаємо, %s!

[ru]
NOTE_WELCOME = Добро пожаловать, %s!
```

<a id="precache-sound"></a>

## Готуємо WAV-звук і вказуємо шлях без `sound/`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_sound)

Перед використанням WAV реєструємо його через `precache_sound()` у `plugin_precache()`. Шлях задаємо від папки `sound/`, без самого префікса: тут це `buttons/button9.wav`. Той самий шлях передаємо в `emit_sound()`, який відтворює звук від указаної сутності.

```pawn
#include <amxmodx>

new const NOTE_SOUND[] = "buttons/button9.wav";

public plugin_precache()
{
	// Реєструємо до старту карти, шлях уже йде від sound/.
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

Гравець `id` тут джерело звуку, а не єдиний слухач. Звук можуть почути й клієнти поруч із ним. Приклад використовує стандартний файл Half-Life. Свої звуки заздалегідь кладемо на сервер до завантаження карти, а в дереві завантажень зберігаємо те саме написання шляху й регістр літер.

<a id="precache-models"></a>

## Готуємо моделі й спрайти до старту карти

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_model)

Для моделі чи спрайта викликаємо `precache_model()` й отримуємо індекс ресурсу на поточній карті. Шлях іде від папки мода: `models/...mdl` для моделі або `sprites/...spr` для спрайта. Індекс зберігаємо, якщо пізніше він потрібен ефекту, але постійним ID між картами він не є.

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

Precache виконується ще до `plugin_init()` й остаточних конфігів. Якщо модель обрана пізньою зміною cvar, завантажити її таким викликом посеред карти вже не можна. Ресурси визначаємо заздалегідь або реєструємо невеликий відомий набір, оскільки розмір таблиць рушія обмежений.

<a id="generic-downloads"></a>

## Додаємо файл у завантаження через `precache_generic()`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_generic)

Якщо файл потрібен клієнту для завантаження, є `precache_generic()`. На відміну від звуку для `emit_sound()`, тут потрібен повний шлях від папки мода, включно із `sound/` для MP3. Файл має існувати, а клієнт має вміти з ним працювати. У прикладі спочатку перевіряємо наявність необов'язкової музики.

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

Реєстрація сама музику не вмикає й не гарантує, що кожен клієнт її завантажив. Використовуємо фіксовані або заздалегідь дозволені шляхи й додаємо лише потрібні файли; за наявності FastDL тримаємо їх і там. WAV через generic теж не потрапить у таблицю звуків, тому для `emit_sound()` такої реєстрації недостатньо.
