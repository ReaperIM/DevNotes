# HUD, чат и ресурсы

[English](../en/10-hud-chat-and-resources.md) | [Українська](../uk/10-hud-chat-and-resources.md) | [Русский](../ru/10-hud-chat-and-resources.md)

[Содержание](../../README.ru.md)

Вывод для нужного клиента, повторные обновления без лишнего наложения и правильные пути ресурсов. Примеры для AMXX 1.9. Цветной чат и пример StatusIcon относятся к CS 1.6 / Condition Zero.

**В этой главе**

- [Цветной чат: получатель и источник цвета разные](#colored-chat)
- [Текст игрока является данными, а не строкой формата](#format-player-text)
- [Обычный HUD имеет четыре канала на клиента](#classic-hud)
- [Обновляй и очищай область HUD через sync-объект](#hud-sync)
- [DHUD является стеком, а не каналом для замены](#director-hud)
- [User message должен соответствовать своему протоколу](#user-messages)
- [Переводи для фактического получателя](#localized-output)
- [Пути WAV-звуков относительны к sound/](#precache-sound)
- [Precache моделей и индексы в пределах карты](#precache-models)
- [Регистрация загрузки не является precache звука](#generic-downloads)

<a id="colored-chat"></a>

## Цветной чат: получатель и источник цвета разные

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print_color)

В CS 1.6 и Condition Zero `client_print_color()` встроен в AMXX 1.9. Первый аргумент определяет получателя, второй источник командного цвета. `print_team_red` в sender не ограничивает доставку террористами.

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

Сообщение получает только вызвавший команду. `^4` зелёный, `^3` выбранный командный цвет, `^1` возвращает стандартный. Получатель `0` означает всех; фильтрация получателей является отдельным действием. Не подключай старую собственную реализацию ColorChat с тем же именем native.

<a id="format-player-text"></a>

## Текст игрока является данными, а не строкой формата

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/client_print)

Имя игрока может содержать `%` и другие символы форматирования. Строку формата оставляй фиксированной, а имя передавай через `%s`. То же относится к консоли, логам, HUD и тексту из файла.

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

Имя вроде `100% ready` останется текстом. Не заменяй вызов на `client_print( id, print_chat, name )`. Безопасное форматирование не убирает управляющие коды или цвета чата; при необходимости обрабатывай их отдельно.

<a id="classic-hud"></a>

## Обычный HUD имеет четыре канала на клиента

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/set_hudmessage)

`set_hudmessage()` задаёт параметры следующего вывода, а не постоянный объект HUD. Каналы `1..4` заменяют предыдущее сообщение в своём канале. `-1` выбирает автоматически; фиксированный канал может сделать периодический текст стабильнее, но другие плагины используют те же каналы клиента.

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

`-1.0` центрирует по горизонтали, а `0.2` размещает текст ближе к верху. Задавай параметры непосредственно перед показом: эти настройки общие между плагинами. Больший hold time не обновляет здоровье автоматически.

<a id="hud-sync"></a>

## Обновляй и очищай область HUD через sync-объект

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/ShowSyncHudMsg)

Sync-объект отслеживает предыдущее сообщение логической области HUD. Повторное использование аккуратно заменяет свой текст даже с автоматическими каналами. Создай объект один раз; он может обслуживать одинаковый тип отображения для нескольких клиентов.

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

`ShowSyncHudMsg()` сам управляет каналом и игнорирует канал из `set_hudmessage()`. Объект не резервирует место экрана и не создаёт дополнительные каналы. `ClearSyncHud()` убирает отслеживаемый текст без выделения нового канала сообщения.

<a id="director-hud"></a>

## DHUD является стеком, а не каналом для замены

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/show_dhudmessage)

Director HUD удобен для короткого объявления. Одновременно поддерживается до восьми сообщений; новые заменяют старые в порядке отправки. Отдельное сообщение DHUD нельзя убрать через sync-объект.

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

API ограничивает текст 128 символами. Покадровые вызовы будут накапливать или заменять объявления, а не обновлять одну постоянную подпись. Для часто меняющегося статуса удобнее обычный HUD с синхронизацией.

<a id="user-messages"></a>

## User message должен соответствовать своему протоколу

[AMX Mod X API](https://www.amxmodx.org/api/messages/message_begin)

`message_begin()` и `message_end()` ограничивают одно сообщение протокола. Порядок и типы `write_*` должны точно соответствовать сообщению игры. Пример для CS отправляет `StatusIcon`: байт включения, название спрайта, затем три байта цвета.

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

Меняется иконка, а не серверное разрешение покупать. Все проверки делай до `message_begin()` и не выходи посреди сообщения. Unreliable-доставка может потерять косметическое обновление; reliable не повод переполнять канал. Отправляй при значимом изменении или с ограниченной частотой. [Формат StatusIcon](https://wiki.alliedmods.net/Half-Life_1_Game_Events#StatusIcon).

<a id="localized-output"></a>

## Переводи для фактического получателя

[AMX Mod X API](https://www.amxmodx.org/api/lang/register_dictionary)

Зарегистрируй словарь из `addons/amxmodx/data/lang/`. `%L` принимает выбор языка, затем ключ и его аргументы форматирования. ID получателя выбирает его язык; `LANG_SERVER` явно выбирает язык сервера.

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

Создай `notes.txt` с одинаковыми подстановками во всех языках. В своём цикле переводи для каждого получателя, а не один раз для всех. Многобайтовый текст также должен помещаться в буфер и ограничения сообщений клиента.

```ini
[en]
NOTE_WELCOME = Welcome, %s!

[uk]
NOTE_WELCOME = Вітаємо, %s!

[ru]
NOTE_WELCOME = Добро пожаловать, %s!
```

<a id="precache-sound"></a>

## Пути WAV-звуков относительны к sound/

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_sound)

`precache_sound()` регистрирует WAV в таблице звуков движка во время `plugin_precache()`. Путь не содержит префикс `sound/`. `emit_sound()` использует тот же относительный путь и воспроизводит позиционный звук от entity.

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

Индекс игрока является источником звука, а не единственным получателем: его могут слышать клиенты рядом. Пример использует стандартный звук Half-Life. Свои файлы должны быть на сервере до загрузки карты, с тем же написанием и регистром в дереве загрузок.

<a id="precache-models"></a>

## Precache моделей и индексы в пределах карты

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_model)

Модели и спрайты имеют пути `models/...mdl` и `sprites/...spr` относительно каталога мода. `precache_model()` возвращает индекс ресурса движка. Храни его для эффектов, которым нужен индекс, но не записывай как постоянный ID между картами.

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

Фаза precache проходит до `plugin_init()` и выполнения окончательных конфигов. Модель, выбранную поздним изменением cvar, нельзя просто precache по запросу посреди карты. Определяй ресурсы заранее или регистрируй небольшой известный набор; таблицы ресурсов движка ограничены.

<a id="generic-downloads"></a>

## Регистрация загрузки не является precache звука

[AMX Mod X API](https://www.amxmodx.org/api/amxmodx/precache_generic)

`precache_generic()` регистрирует файл для загрузки, но не добавляет WAV в таблицу звуков движка. Здесь нужен полный путь от каталога мода, включая `sound/` для MP3. Файл всё равно должен существовать и поддерживаться клиентом, который его использует.

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

Это только регистрация необязательного файла: она не воспроизводит музыку и не гарантирует загрузку каждым клиентом. Используй фиксированные или разрешённые пути, поддерживай соответствующие файлы FastDL, если он есть, и добавляй только нужные ресурсы. Generic-регистрация звука не делает его пригодным для `emit_sound()`.
