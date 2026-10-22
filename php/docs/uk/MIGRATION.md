# Нотатки щодо міграції

[English](../en/MIGRATION.md) | [Українська](../uk/MIGRATION.md) | [Русский](../ru/MIGRATION.md)

[Зміст](../../README.uk.md) | [PHP 7.0-8.5](VERSIONS.md)

<a id="approach"></a>
## Порядок змін

Спочатку перевірте зміни, які можуть вплинути на поведінку або зламати наявний виклик. Сучасний синтаксис можна впроваджувати окремо, коли старі сценарії вже працюють на цільовій версії.

<a id="removed-php8"></a>
## Видалено у PHP 8.0

- create_function() -> замикання; each() -> foreach. Обидві функції застаріли у 7.2.
- Фігурні дужки індексів -> квадратні. Застаріли у 7.4.
- __autoload() -> spl_autoload_register(); конструктори з назвою класу -> __construct().
- Вкладені повні тернарні вирази потребують явних дужок.
- implode($separator, $parts); зворотний порядок аргументів видалено.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="comparisons"></a>
## Порівняння

PHP 8: результат 0 == 'foo' змінився з true на false. Числові рядки мають окремі правила порівняння.

switch -> [match](01-syntax-and-functions.md#match) змінює нестроге порівняння на суворе; немає переходу до наступної гілки, немає збігу/default -> UnhandledMatchError.

[PHP](https://www.php.net/manual/en/migration80.incompatible.php#string-number-comparison)

<a id="arguments-errors"></a>
## Аргументи та винятки

- PHP 8: рядкові ключі call_user_func_array() стають назвами параметрів.
- Некоректні аргументи внутрішніх функцій можуть спричинити TypeError/ValueError. catch(Exception) не перехоплює Error; Throwable охоплює обидва.
- PDO типово використовує винятки від 8.0; mysqli використовує MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT від 8.1. [Нотатки про БД](10-databases.md).

[PHP](https://www.php.net/manual/en/migration80.incompatible.php)

<a id="nullable"></a>
## Nullable-параметри

- 8.1: передавання null у скалярні параметри вбудованих функцій, які не допускають null, застаріло в режимі неявного перетворення.
- 8.4: string $name = null застаріло; використовуйте ?string $name = null або string|null (8.0+). [Типи](02-types.md#nullable).

[PHP](https://www.php.net/manual/en/migration84.deprecated.php)

<a id="object-state"></a>
## Стан об'єктів

- 8.2: [динамічні властивості](03-classes-and-objects.md#dynamic-properties) застаріли, крім stdClass / __set / AllowDynamicProperties.
- 8.1: Serializable без __serialize()/__unserialize() застарів. Нові методи існують від 7.4; __sleep()/__wakeup() у 8.5 є soft-deprecated (без нового повідомлення під час виконання).
- PHP 8: дескриптори cURL/GD є об'єктами; старий is_resource() може відхилити правильний дескриптор.
- 8.2: стара інтерполяція з доларом перед фігурними дужками застаріла; використовуйте $name або {$name}.

[PHP](https://www.php.net/manual/en/migration82.deprecated.php)

<a id="php85"></a>
## PHP 8.5

- (boolean)/(integer)/(double)/(binary) застаріли -> (bool)/(int)/(float)/(string).
- Shell-оператор у зворотних лапках застарів -> явний API процесів.
- Використання `null` як ключа масиву або в `array_key_exists()` застаріло; якщо потрібен порожній рядок, передавайте його явно. Збільшення нечислових рядків застаріло; заміна: `str_increment()`.
- curl_close(), curl_share_close(), finfo_close(), imagedestroy() застаріли: очищення пов'язане з життям об'єкта.
- Деструктуризація скаляра через `[]` або `list()` тепер спричиняє попередження замість тихого заповнення змінних значеннями `null`. Джерело `null` залишається допустимим; підтримувані об’єкти `ArrayAccess` також працюють.

[PHP: deprecated](https://www.php.net/manual/en/migration85.deprecated.php) | [PHP: compatibility](https://www.php.net/manual/en/migration85.incompatible.php)

<a id="rewrites"></a>
## Корисні заміни

- [?? / ??=](01-syntax-and-functions.md#null-coalescing): відсутнє/null; [fn](01-syntax-and-functions.md#arrow-functions): один вираз, захоплення за значенням.
- [str_contains()](05-strings-and-unicode.md#string-search): булевий пошук; [array_find()](04-arrays-and-iteration.md#predicate-search): перший збіг.
- [json_validate()](06-json-xml-and-serialization.md#json-validation): лише коректність; якщо потрібні значення, декодуйте один раз.
- [readonly](03-classes-and-objects.md#readonly-properties): поверхневий захист; [hooks](03-classes-and-objects.md#property-hooks): доступ до властивості; [clone()](03-classes-and-objects.md#clone-with): змінена копія.

<a id="verification"></a>
## Перед випуском

Перевірте змінені шляхи помилок і важливі випадки 0/null/false на цільовій версії PHP.
