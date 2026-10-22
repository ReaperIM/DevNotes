# Версії та сумісність

[EN](../en/COMPATIBILITY.md) | [UK](../uk/COMPATIBILITY.md) | [RU](../ru/COMPATIBILITY.md)

[Зміст](../../README.uk.md)

Перед використанням фрагмента визначте його API та серверні залежності.

- [Що означає базова версія](#baseline)
- [1.8.2, 1.9 та 1.10](#versions)
- [AMXX, game DLL і ReAPI є різними шарами](#layers)
- [Include є контрактом для компіляції](#includes)
- [Корисні оновлення старих фрагментів](#legacy)
- [Поля сутностей залежать від гри та збірки](#game-data)
- [Що перевіряє компіляція](#verification)

<a id="baseline"></a>

## Що означає базова версія

Приклади розраховані на AMXX 1.9 або новіший, якщо розділ не вказує додаткову залежність. Це базовий API, а не твердження, що кожна можливість уперше з’явилася у 1.9. Мова тут є діалектом Pawn, який постачається з AMXX.

SourceMod використовує SourcePawn та інший runtime/API. Його приклади з `methodmap`, `enum struct` або оголошенням `int` не можна просто переносити до плагіна AMXX.

[AMXX scripting](https://wiki.alliedmods.net/Category:Scripting_(AMX_Mod_X)) | [SourcePawn](https://wiki.alliedmods.net/Introduction_to_SourcePawn_1.7)

<a id="versions"></a>

## 1.8.2, 1.9 та 1.10

Старі фрагменти часто розраховані на 1.8.2, тоді як сучасні includes містять пізніші функції та сигнатури callbacks. Компілятор, includes і серверний runtime перевіряють окремо.

| Гілка | Практичне значення тут |
| --- | --- |
| 1.8.2 | Стара ціль. Перед перенесенням перевіряють пізніші natives, прапорці та forwards. |
| 1.9 | Основа нотаток: доступні прив’язки cvar, config forwards, відбір гравців і сучасна обробка відключень. |
| 1.10 | Для пізніших API та виправлень важливі точна збірка й changelog. Номер гілки не визначає збірку. |

AMXX публікує rolling builds. Поточні номери дивіться на офіційних сторінках завантажень і збірок.

[1.9 notes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes) | [AMXX builds](https://www.amxmodx.org/downloads-new.php)

<a id="layers"></a>

## AMXX, game DLL і ReAPI є різними шарами

Версія AMXX не описує весь сервер. Native може вимагати модуль, певну game DLL або API іншого плагіна.

| Шар | Роль |
| --- | --- |
| HLDS / ReHLDS | Серверний рушій GoldSrc. |
| Game DLL / ReGameDLL | Правила гри та поведінка ігрових сутностей. |
| Metamod | Завантажує серверні плагіни, зокрема AMXX. |
| AMXX | Виконує Pawn-плагіни та надає natives через ядро й модулі. |
| ReAPI | Додатковий модуль AMXX з API підтримуваних інтерфейсів ReHLDS/ReGameDLL. |

Перевіряйте, який інтерфейс потрібен конкретній функції ReAPI. Установлення ReAPI не переносить усі ігрові приклади до будь-якого мода.

[ReAPI](https://github.com/rehlds/ReAPI) | [AMXX modules](https://www.amxmodx.org/api/)

<a id="includes"></a>

## Include є контрактом для компіляції

Файл `.inc` може оголошувати natives, константи та stocks. Оголошення native дозволяє компіляцію; постачальник функції все одно потрібен під час завантаження.

Використовуйте includes, що відповідають установленому модулю/API. Копіювання оголошення або примусова зміна `AMXX_VERSION_NUM` не додає реалізацію native. `#if` потрібен для справжньої альтернативної гілки. [API плагінів і модулі](11-plugin-api-and-modules.md).

[Official includes](https://github.com/alliedmodders/amxmodx/tree/master/plugins/include)

<a id="legacy"></a>

## Корисні оновлення старих фрагментів

Новий запис корисний, коли його поведінка відповідає старому коду. Ці місця варто перевірити, а не замінювати автоматично.

- `client_disconnect()` -> `client_disconnected()`: більше випадків відключення; перевірте момент очищення.
- Часті `get_cvar_*()` за назвою -> збережений pcvar або `bind_pcvar_*()`; визначте потребу в поточних змінах.
- Рядкові прапорці `register_event()` -> `register_event_ex()`; зміст даних події залишається важливим.
- Старі обгортки кольорового чату -> `client_print_color()`, якщо підходить підтримка гри та семантика кольорів.
- Часті `read_file()` за номером рядка -> послідовний файловий API для повного обходу.
- Сирі pdata offsets -> перевірена gamedata або іменовані члени ReAPI, якщо залежність доречна.

[Гравці](04-plugin-lifecycle-and-players.md) | [Команди та cvar](05-commands-cvars-and-configs.md) | [Хуки](06-events-hooks-and-messages.md)

[1.9 API changes](https://wiki.alliedmods.net/AMX_Mod_X_1.9_Release_Notes)

<a id="game-data"></a>

## Поля сутностей залежать від гри та збірки

`cstrike`, ID зброї CS, класи Ham і приклади private data можуть залежати від гри. Одиниці offsets і поправки платформи залежать від точної функції доступу та структури класу.

Числовий offset зі старої таблиці не є перевіреним полем для іншої game DLL. Див. [сутності](08-entities-and-gameplay.md) та перевіряйте залежність сервера перед записом private state.

[Fakemeta](https://www.amxmodx.org/api/fakemeta) | [ReAPI members](https://github.com/rehlds/ReAPI/tree/master/reapi/extra/amxmodx/scripting/include)

<a id="verification"></a>

## Що перевіряє компіляція

Компілюйте кожен незалежний Pawn-блок потрібним компілятором та includes. Потім перевіряйте відповідну поведінку на цільовому сервері: завантаження, момент подій, відключення, скасування меню та помилки сховища.

Результат компіляції перевіряє синтаксис і оголошені сигнатури. Він не доводить наявність звуку, завантаження постачальника native чи правильний момент повернення хука в грі. [Налагодження](12-debugging-and-performance.md).

[Compiling](https://wiki.alliedmods.net/Compiling_Plugins_(AMX_Mod_X))
