# Заметки по миграции

[English](../en/MIGRATION.md) | [Українська](../uk/MIGRATION.md) | [Русский](../ru/MIGRATION.md)

[Содержание](../../README.ru.md) | [PHP 7.0-8.5](VERSIONS.md)

<a id="approach"></a>
## Порядок изменений

Сначала проверьте изменения, которые могут повлиять на поведение или сломать существующий вызов. Современный синтаксис можно внедрять отдельно, когда старые сценарии уже работают на целевой версии.

<a id="removed-php8"></a>
## Удалено в PHP 8.0

- create_function() -> замыкания; each() -> foreach. Обе функции устарели в 7.2.
- Фигурные скобки индексов -> квадратные. Устарели в 7.4.
- __autoload() -> spl_autoload_register(); конструкторы с именем класса -> __construct().
- Вложенные полные тернарные выражения требуют явных скобок.
- implode($separator, $parts); обратный порядок аргументов удалён.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="comparisons"></a>
## Сравнения

PHP 8: результат 0 == 'foo' сменился с true на false. Числовые строки имеют отдельные правила сравнения.

switch -> [match](01-syntax-and-functions.md#match) меняет нестрогое сравнение на строгое; нет перехода в следующую ветку, нет совпадения/default -> UnhandledMatchError.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php#string-number-comparison)

<a id="arguments-errors"></a>
## Аргументы и исключения

- PHP 8: строковые ключи call_user_func_array() становятся именами параметров.
- Некорректные аргументы внутренних функций могут вызвать TypeError/ValueError. catch(Exception) не перехватывает Error; Throwable охватывает оба.
- PDO по умолчанию использует исключения с 8.0; mysqli использует MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT с 8.1. [Заметки по БД](10-databases.md).

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="nullable"></a>
## Nullable-параметры

- 8.1: передача null в скалярные параметры встроенных функций, не допускающие null, устарела в режиме неявного преобразования.
- 8.4: string $name = null устарело; используйте ?string $name = null или string|null (8.0+). [Типы](02-types.md#nullable).

[PHP](https://www.php.net/manual/en/migration84.deprecated.php)

<a id="object-state"></a>
## Состояние объектов

- 8.2: [динамические свойства](03-classes-and-objects.md#dynamic-properties) устарели, кроме stdClass / __set / AllowDynamicProperties.
- 8.1: Serializable без __serialize()/__unserialize() устарел. Новые методы существуют с 7.4; __sleep()/__wakeup() в 8.5 являются soft-deprecated (без нового сообщения при выполнении).
- PHP 8: дескрипторы cURL/GD являются объектами; старый is_resource() может отклонить правильный дескриптор.
- 8.2: старая интерполяция с долларом перед фигурными скобками устарела; используйте $name или {$name}.

[PHP](https://www.php.net/manual/en/migration82.deprecated.php)

<a id="php85"></a>
## PHP 8.5

- (boolean)/(integer)/(double)/(binary) устарели -> (bool)/(int)/(float)/(string).
- Shell-оператор в обратных кавычках устарел -> явный API процессов.
- Использование `null` как ключа массива или в `array_key_exists()` устарело; если нужна пустая строка, передавайте её явно. Увеличение нечисловых строк устарело; замена: `str_increment()`.
- curl_close(), curl_share_close(), finfo_close(), imagedestroy() устарели: очистка связана со временем жизни объекта.
- Деструктуризация скаляра через `[]` или `list()` теперь вызывает предупреждение вместо молчаливого заполнения переменных значениями `null`. Источник `null` остаётся допустимым; поддерживаемые объекты `ArrayAccess` также работают.

[PHP: deprecated](https://www.php.net/manual/en/migration85.deprecated.php) | [PHP: compatibility](https://www.php.net/manual/en/migration85.incompatible.php)

<a id="rewrites"></a>
## Полезные замены

- [?? / ??=](01-syntax-and-functions.md#null-coalescing): отсутствует/null; [fn](01-syntax-and-functions.md#arrow-functions): одно выражение, захват по значению.
- [str_contains()](05-strings-and-unicode.md#string-search): булевый поиск; [array_find()](04-arrays-and-iteration.md#predicate-search): первое совпадение.
- [json_validate()](06-json-xml-and-serialization.md#json-validation): только корректность; если нужны значения, декодируйте один раз.
- [readonly](03-classes-and-objects.md#readonly-properties): поверхностная защита; [hooks](03-classes-and-objects.md#property-hooks): доступ к свойству; [clone()](03-classes-and-objects.md#clone-with): изменённая копия.

<a id="verification"></a>
## Перед выпуском

Проверьте изменённые пути ошибок и значимые случаи 0/null/false на целевой версии PHP.
