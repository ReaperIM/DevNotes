# HUD, чат и ресурсы

[EN](../en/10-hud-chat-and-resources.md) | [UK](../uk/10-hud-chat-and-resources.md) | [RU](../ru/10-hud-chat-and-resources.md)

[Содержание](../../README.ru.md)

Показываем текст нужному игроку, обновляем HUD без наложения старых сообщений и заранее готовим звуки с моделями. Примеры рассчитаны на AMXX 1.9. Цветной чат и `StatusIcon` здесь относятся к CS 1.6 / Condition Zero.

**В этой главе**

- [Цветной чат: кому отправляем и откуда берём цвет](#colored-chat)
- [Имя игрока передаём через `%s`](#format-player-text)
- [Четыре канала обычного HUD](#classic-hud)
- [Обновляем и убираем свой HUD через sync-объект](#hud-sync)
- [Короткие объявления через DHUD](#director-hud)
- [Собираем игровое сообщение в нужном порядке](#user-messages)
- [Показываем перевод на языке получателя](#localized-output)
- [Готовим WAV-звук и указываем путь без `sound/`](#precache-sound)
- [Готовим модели и спрайты до старта карты](#precache-models)
- [Добавляем файл в загрузку через `precache_generic()`](#generic-downloads)

<a id="colored-chat"></a>

## Цветной чат: кому отправляем и откуда берём цвет

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print_color)

В CS 1.6 и Condition Zero можно использовать встроенный `client_print_color()` из AMXX 1.9. Первым аргументом выбираем, кому показать сообщение, вторым откуда взять командный цвет. Это разные вещи: `print_team_red` делает цвет красным, но не выбирает террористов получателями.

```pawn
#include <amxmodx>

public plugin_init()
{
	register_clcmd( "say /colors", "command_colors" );
}

public command_colors( id )
{
	// Красный цвет не выбирает получателей, сообщение идёт только id.
	client_print_color( id, print_team_red, "^4[Notes] ^3Red ^1default" );
	return PLUGIN_HANDLED;
}
```

В примере сообщение увидит только игрок, написавший команду. `^4` включает зелёный, `^3` выбранный командный цвет, `^1` возвращает обычный. Если первым аргументом передать `0`, сообщение уйдёт всем; нужных игроков отбираем отдельно. Старую собственную реализацию `ColorChat` с тем же именем native подключать уже не нужно.

<a id="format-player-text"></a>

## Имя игрока передаём через `%s`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print)

В имени игрока может встретиться `%`, поэтому имя нельзя использовать как саму строку формата. Оставляем формат фиксированным, а имя передаём отдельным аргументом через `%s`. То же правило работает для консоли, журналов, HUD и текста, прочитанного из файла.

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
	// Символы % в имени должны остаться текстом.
	client_print( id, print_chat, "Your name: %s", name );
	return PLUGIN_HANDLED;
}
```

Имя `100% ready` в примере останется обычным текстом. Если заменить вызов на `client_print( id, print_chat, name )`, символы имени уже будут разбираться как формат. При этом `%s` не убирает цветовые и управляющие коды чата: если их тоже нужно ограничить, делаем это отдельно.

<a id="classic-hud"></a>

## Четыре канала обычного HUD

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_hudmessage)

У обычного HUD четыре канала на клиента, `1..4`. Новое сообщение в том же канале заменяет старое, а `-1` позволяет выбрать канал автоматически. `set_hudmessage()` задаёт параметры следующего показа, но не создаёт постоянный элемент интерфейса. Здесь показываем здоровье в канале `2`.

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

Координата `-1.0` центрирует текст по горизонтали, `0.2` поднимает его ближе к верху. Настройки общие между плагинами, поэтому задаём их непосредственно перед показом. Фиксированный канал может помочь при обновлениях, но остальные плагины тоже его используют. И долгое время показа лишь держит текст на экране, здоровье само от этого не обновляется.

<a id="hud-sync"></a>

## Обновляем и убираем свой HUD через sync-объект

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/ShowSyncHudMsg)

Если один и тот же текст нужно обновлять, удобно создать sync-объект через `CreateHudSyncObj()`. Он запоминает предыдущее сообщение, чтобы следующий `ShowSyncHudMsg()` заменил наш старый вывод. Объект создаём один раз; для одинакового вида статуса его можно использовать у нескольких игроков.

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

В примере `/status` показывает статус, а `/clearstatus` убирает его через тот же объект. `ShowSyncHudMsg()` сам выбирает канал, игнорируя номер из `set_hudmessage()`. Дополнительных каналов и закреплённого места на экране объект не создаёт. `ClearSyncHud()` очищает отслеживаемое сообщение, не занимая для очистки новый канал.

<a id="director-hud"></a>

## Короткие объявления через DHUD

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/show_dhudmessage)

Director HUD подходит для короткого объявления вроде начала тренировки. Одновременно может быть до восьми сообщений; новые вытесняют старые в порядке отправки. Это важно при повторном показе: у DHUD нет отдельного канала, в котором мы просто заменяем одну подпись.

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

Длина текста ограничена 128 символами. Если вызывать показ каждый кадр, объявления будут накапливаться или вытеснять друг друга. Отдельное DHUD-сообщение через sync-объект не убрать, поэтому для постоянно меняющегося статуса удобнее обычный синхронизированный HUD.

<a id="user-messages"></a>

## Собираем игровое сообщение в нужном порядке

[AMX Mod X API](https://www.amxmodx.org/api/messages/message_begin)

Игровое сообщение собирается между `message_begin()` и `message_end()`. Клиент ждёт определённые типы и порядок полей, поэтому произвольно менять вызовы `write_*` нельзя. В примере для CS отправляем `StatusIcon`: сначала байт включения, потом имя спрайта и три байта цвета.

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

	// Проверки уже пройдены, сообщение теперь собираем до message_end().
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

Изменится только показанная иконка, разрешение покупать на сервере останется прежним. Все проверки делаем до `message_begin()`, чтобы не выйти посреди незаконченного сообщения. `MSG_ONE_UNRELIABLE` допускает потерю этого косметического обновления. Надёжная доставка тоже не повод отправлять каждый кадр: показываем при изменении или ограничиваем частоту. [Формат `StatusIcon`](https://wiki.alliedmods.net/Half-Life_1_Game_Events#StatusIcon).

<a id="localized-output"></a>

## Показываем перевод на языке получателя

[AMX Mod X API](https://www.amxmodx.org/api/lang/register_dictionary)

Чтобы игрок получил сообщение на своём языке, регистрируем словарь из `addons/amxmodx/data/lang/`. После `%L` передаём выбор языка, ключ перевода и его параметры. В примере язык выбирает `id` получателя, а имя игрока подставляется уже в найденную строку. `LANG_SERVER` вместо ID выбрал бы язык сервера.

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

В `notes.txt` у переводов должны совпадать подстановки, здесь это `%s`. Если отправляем сообщение своим циклом, переводим отдельно для каждого игрока: один готовый перевод на всех не учтёт их языки. Многобайтовый текст тоже должен помещаться в буфер и ограничения сообщения клиента.

```ini
[en]
NOTE_WELCOME = Welcome, %s!

[uk]
NOTE_WELCOME = Вітаємо, %s!

[ru]
NOTE_WELCOME = Добро пожаловать, %s!
```

<a id="precache-sound"></a>

## Готовим WAV-звук и указываем путь без `sound/`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_sound)

Перед использованием WAV регистрируем его через `precache_sound()` в `plugin_precache()`. Путь задаём от папки `sound/`, без самого префикса: здесь это `buttons/button9.wav`. Тот же путь передаём в `emit_sound()`, который воспроизводит звук от указанной сущности.

```pawn
#include <amxmodx>

new const NOTE_SOUND[] = "buttons/button9.wav";

public plugin_precache()
{
	// Регистрируем до старта карты, путь уже идёт от sound/.
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

Игрок `id` здесь источник звука, а не единственный слушатель. Звук могут услышать и клиенты рядом с ним. Пример использует стандартный файл Half-Life. Свои звуки заранее кладём на сервер до загрузки карты, а в дереве загрузок сохраняем то же написание пути и регистр букв.

<a id="precache-models"></a>

## Готовим модели и спрайты до старта карты

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_model)

Для модели или спрайта вызываем `precache_model()` и получаем индекс ресурса на текущей карте. Путь идёт от папки мода: `models/...mdl` для модели или `sprites/...spr` для спрайта. Индекс сохраняем, если позже он нужен эффекту, но постоянным ID между картами он не является.

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

Precache выполняется ещё до `plugin_init()` и окончательных конфигов. Если модель выбрана поздним изменением cvar, загрузить её таким вызовом посреди карты уже нельзя. Ресурсы определяем заранее или регистрируем небольшой известный набор, поскольку размер таблиц движка ограничен.

<a id="generic-downloads"></a>

## Добавляем файл в загрузку через `precache_generic()`

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_generic)

Если файл нужен клиенту для загрузки, есть `precache_generic()`. В отличие от звука для `emit_sound()`, здесь нужен полный путь от папки мода, включая `sound/` у MP3. Файл должен существовать, а клиент должен уметь с ним работать. В примере сначала проверяем наличие необязательной музыки.

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

Регистрация сама музыку не включает и не гарантирует, что каждый клиент её скачал. Используем фиксированные или заранее разрешённые пути и добавляем только нужные файлы; при наличии FastDL держим их и там. WAV через generic тоже не попадёт в таблицу звуков, поэтому для `emit_sound()` такой регистрации недостаточно.
