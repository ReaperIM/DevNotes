# Версии и совместимость

[EN](../en/COMPATIBILITY.md) | [UK](../uk/COMPATIBILITY.md) | [RU](../ru/COMPATIBILITY.md)

[Содержание](../../README.ru.md)

Перед использованием фрагмента определите его API и серверные зависимости.

- [Что означает базовая версия](#baseline)
- [1.8.2, 1.9 и 1.10](#versions)
- [AMXX, game DLL и ReAPI являются разными слоями](#layers)
- [Include является контрактом для компиляции](#includes)
- [Полезные обновления старых фрагментов](#legacy)
- [Поля сущностей зависят от игры и сборки](#game-data)
- [Что проверяет компиляция](#verification)

<a id="baseline"></a>

## Что означает базовая версия

Примеры рассчитаны на AMXX 1.9 или новее, если раздел не указывает дополнительную зависимость. Это базовый API, а не утверждение, что каждая возможность впервые появилась в 1.9. Здесь используется диалект Pawn, поставляемый с AMXX.

SourceMod использует SourcePawn и другой runtime/API. Его примеры с `methodmap`, `enum struct` или объявлением `int` нельзя просто переносить в плагин AMXX.

[AMXX scripting](https://wiki.alliedmods.net/Category:Scripting_(AMX_Mod_X)) | [SourcePawn](https://wiki.alliedmods.net/Introduction_to_SourcePawn_1.7)

<a id="versions"></a>

## 1.8.2, 1.9 и 1.10

Старые фрагменты часто рассчитаны на 1.8.2, тогда как современные includes содержат более поздние функции и сигнатуры callbacks. Компилятор, includes и серверный runtime проверяют отдельно.

| Ветка | Практическое значение здесь |
| --- | --- |
| 1.8.2 | Старая цель. Перед переносом проверяют более поздние natives, флаги и forwards. |
| 1.9 | Основа заметок: доступны привязки cvar, config forwards, выборка игроков и современная обработка отключений. |
| 1.10 | Для поздних API и исправлений важны точная сборка и changelog. Номер ветки не определяет сборку. |

AMXX публикует rolling builds. Текущие номера смотрите на официальных страницах загрузок и сборок.

[1.9 notes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes) | [AMXX builds](https://www.amxmodx.org/downloads-new.php)

<a id="layers"></a>

## AMXX, game DLL и ReAPI являются разными слоями

Версия AMXX не описывает весь сервер. Native может требовать модуль, определённую game DLL или API другого плагина.

| Слой | Роль |
| --- | --- |
| HLDS / ReHLDS | Серверный движок GoldSrc. |
| Game DLL / ReGameDLL | Правила игры и поведение игровых сущностей. |
| Metamod | Загружает серверные плагины, включая AMXX. |
| AMXX | Выполняет Pawn-плагины и предоставляет natives через ядро и модули. |
| ReAPI | Дополнительный модуль AMXX с API поддерживаемых интерфейсов ReHLDS/ReGameDLL. |

Проверяйте, какой интерфейс нужен конкретной функции ReAPI. Установка ReAPI не переносит все игровые примеры в любой мод.

[ReAPI](https://github.com/rehlds/ReAPI) | [AMXX modules](https://www.amxmodx.org/api/)

<a id="includes"></a>

## Include является контрактом для компиляции

Файл `.inc` может объявлять natives, константы и stocks. Объявление native позволяет компиляцию; поставщик функции всё равно нужен при загрузке.

Используйте includes, соответствующие установленному модулю/API. Копирование объявления или принудительная смена `AMXX_VERSION_NUM` не добавляет реализацию native. `#if` нужен для настоящей альтернативной ветки. [API плагинов и модули](11-plugin-api-and-modules.md).

[Official includes](https://github.com/alliedmodders/amxmodx/tree/master/plugins/include)

<a id="legacy"></a>

## Полезные обновления старых фрагментов

Новая запись полезна, когда её поведение соответствует старому коду. Эти места стоит проверить, а не заменять автоматически.

- `client_disconnect()` -> `client_disconnected()`: больше случаев отключения; проверьте момент очистки.
- Частые `get_cvar_*()` по имени -> сохранённый pcvar или `bind_pcvar_*()`; определите потребность в текущих изменениях.
- Строковые флаги `register_event()` -> `register_event_ex()`; смысл данных события остаётся важным.
- Старые обёртки цветного чата -> `client_print_color()`, если подходит поддержка игры и семантика цветов.
- Частые `read_file()` по номеру строки -> последовательный файловый API для полного обхода.
- Сырые pdata offsets -> проверенная gamedata или именованные члены ReAPI, если зависимость уместна.

[Игроки](04-plugin-lifecycle-and-players.md) | [Команды и cvar](05-commands-cvars-and-configs.md) | [Хуки](06-events-hooks-and-messages.md)

[1.9 API changes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes)

<a id="game-data"></a>

## Поля сущностей зависят от игры и сборки

`cstrike`, ID оружия CS, классы Ham и примеры private data могут зависеть от игры. Единицы offsets и поправки платформы зависят от точной функции доступа и структуры класса.

Числовой offset из старой таблицы не является проверенным полем для другой game DLL. См. [сущности](08-entities-and-gameplay.md) и проверяйте зависимость сервера перед записью private state.

[Fakemeta](https://www.amxmodx.org/api/fakemeta) | [ReAPI members](https://github.com/rehlds/ReAPI/tree/master/reapi/extra/amxmodx/scripting/include)

<a id="verification"></a>

## Что проверяет компиляция

Компилируйте каждый независимый Pawn-блок нужным компилятором и includes. Затем проверяйте соответствующее поведение на целевом сервере: загрузку, момент событий, отключения, отмену меню и ошибки хранилища.

Результат компиляции проверяет синтаксис и объявленные сигнатуры. Он не доказывает наличие звука, загрузку поставщика native или правильный момент возврата хука в игре. [Отладка](12-debugging-and-performance.md).

[Compiling](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))
