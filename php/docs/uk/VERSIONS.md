# Версії PHP

[EN](../en/VERSIONS.md) | [UK](../uk/VERSIONS.md) | [RU](../ru/VERSIONS.md)

[Зміст](../../README.uk.md)

Пам’ятаєш приблизно, в якій версії PHP з’явилася потрібна річ? Вибираємо її тут і переходимо до пояснення з прикладом. Якщо можливість пізніше змінювалася, вона може трапитися одразу в кількох версіях.

- [PHP 7.0](#php-70)
- [PHP 7.1](#php-71)
- [PHP 7.2](#php-72)
- [PHP 7.3](#php-73)
- [PHP 7.4](#php-74)
- [PHP 8.0](#php-80)
- [PHP 8.1](#php-81)
- [PHP 8.2](#php-82)
- [PHP 8.3](#php-83)
- [PHP 8.4](#php-84)
- [PHP 8.5](#php-85)

<a id="php-70"></a>
## PHP 7.0

- [Запасне значення через `??`](01-syntax-and-functions.md#null-coalescing)
- [Сортуємо через `<=>`](01-syntax-and-functions.md#spaceship)
- [Збираємо схожі `use` в один рядок](01-syntax-and-functions.md#group-use)
- [Викликаємо замикання з потрібним `$this`](01-syntax-and-functions.md#closure-call)
- [Типи параметрів і результату](02-types.md#scalar-types)
- [Не підміняємо типи через `strict_types`](02-types.md#strict-types)
- [Створюємо клас просто на місці](03-classes-and-objects.md#anonymous-classes)
- [Віддаємо значення з кількох джерел через `yield from`](04-arrays-and-iteration.md#generator-delegation)
- [Отримуємо підсумок роботи генератора](04-arrays-and-iteration.md#generator-return)
- [Цілочислове ділення через `intdiv()`](07-dates-and-numbers.md#integer-division)
- [`Throwable` об’єднує `Exception` та `Error`](08-errors-and-debugging.md#throwable)
- [Власний callback заміни для кожного шаблону](08-errors-and-debugging.md#regex-callback-map)
- [Читаємо сесію й відразу відпускаємо блокування](09-files-http-and-processes.md#read-only-session)
- [Безпечні випадкові байти й числа](11-security-and-random.md#secure-random)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration70.php)

<a id="php-71"></a>
## PHP 7.1

- [Отримуємо замикання через `Closure::fromCallable()`](01-syntax-and-functions.md#closure-from-callable)
- [Дозволяємо `null` через `?T`](02-types.md#nullable)
- [Виконуємо дію без результату: `void`](02-types.md#void)
- [Тип `iterable`](02-types.md#iterable-object)
- [Доступ до констант класу](03-classes-and-objects.md#constant-visibility-final)
- [Розкладаємо масив у змінні](04-arrays-and-iteration.md#destructuring)
- [Беремо потрібні поля за ключами](04-arrays-and-iteration.md#keyed-destructuring)
- [Перебираємо масиви й ітератори однаково](04-arrays-and-iteration.md#iterable)
- [Читаємо рядок із кінця](05-strings-and-unicode.md#negative-offsets)
- [Один `catch` для кількох типів винятків](08-errors-and-debugging.md#multi-catch)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration71.php)

<a id="php-72"></a>
## PHP 7.2

- [Тип `object`](02-types.md#iterable-object)
- [Що робити з пошкодженим UTF-8 у JSON](06-json-xml-and-serialization.md#invalid-utf8-json)
- [Як відрізнити відсутню групу від порожнього збігу](08-errors-and-debugging.md#unmatched-groups)
- [Хешування паролів Argon2i](11-security-and-random.md#password-algorithms)
- [Шифруємо повідомлення й перевіряємо його цілісність](11-security-and-random.md#sodium)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration72.php)

<a id="php-73"></a>
## PHP 7.3

- [Кінцева кома у викликах та оголошеннях](01-syntax-and-functions.md#trailing-commas)
- [Змінюємо початковий елемент через посилання](04-arrays-and-iteration.md#reference-destructuring)
- [Беремо перший і останній ключ](04-arrays-and-iteration.md#boundary-keys)
- [Перевіряємо, чи можна викликати `count()`](04-arrays-and-iteration.md#countable)
- [Залишаємо нормальні відступи в багаторядковому тексті](05-strings-and-unicode.md#heredoc)
- [Ловимо помилку JSON як виняток](06-json-xml-and-serialization.md#json-exceptions)
- [Масив параметрів cookie](09-files-http-and-processes.md#cookie-options)
- [Хешування паролів Argon2id](11-security-and-random.md#password-algorithms)
- [Вимірюємо час роботи через `hrtime()`](12-runtime-and-performance.md#monotonic-time)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration73.php)

<a id="php-74"></a>
## PHP 7.4

- [Доповнюємо налаштування через `??=`](01-syntax-and-functions.md#null-coalescing-assignment)
- [Короткі функції через `fn`](01-syntax-and-functions.md#arrow-functions)
- [Читабельні числові літерали](01-syntax-and-functions.md#numeric-literals)
- [Указуємо тип властивості](02-types.md#typed-properties)
- [Уточнюємо типи в нащадку](02-types.md#variance)
- [Збираємо масив через `...` і стежимо за повторними ключами](04-arrays-and-iteration.md#unpacking)
- [З’єднуємо набори, навіть якщо наборів немає](04-arrays-and-iteration.md#merge-empty)
- [Ділимо текст без розрізання байтів однієї літери](05-strings-and-unicode.md#mb-split)
- [Передаємо дозволені теги масивом](05-strings-and-unicode.md#allowed-tags)
- [Вибираємо, що зберігати й відновлювати в об’єкта](06-json-xml-and-serialization.md#object-serialization)
- [Винятки з `__toString()`](08-errors-and-debugging.md#to-string-exceptions)
- [Аргументи процесу масивом](09-files-http-and-processes.md#process-arguments)
- [Копіюємо відкриту базу SQLite](10-databases.md#sqlite-backup)
- [Доступні алгоритми паролів через `password_algos()`](11-security-and-random.md#password-algorithms)
- [Перевірка дробового числа й діапазону](11-security-and-random.md#float-validation)
- [Стежимо за об’єктом, не утримуючи його в пам’яті](12-runtime-and-performance.md#weak-reference)
- [Попереднє завантаження сталого коду](12-runtime-and-performance.md#preloading)
- [Робота з C через `FFI`](12-runtime-and-performance.md#ffi)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration74.php)

<a id="php-80"></a>
## PHP 8.0

- [Передаємо аргументи за ім’ям](01-syntax-and-functions.md#named-arguments)
- [Імена аргументів із ключів масиву](01-syntax-and-functions.md#unpacking-named-arguments)
- [Вибираємо значення через `match`](01-syntax-and-functions.md#match)
- [Читаємо ланцюжок об’єктів через `?->`](01-syntax-and-functions.md#nullsafe)
- [Викидаємо виняток просто у виразі](01-syntax-and-functions.md#throw-expression)
- [Дозволяємо один із типів: `A|B`](02-types.md#union)
- [Приймаємо будь-яке значення через `mixed`](02-types.md#mixed)
- [Тип результату `static`](02-types.md#static-return)
- [Оголошуємо властивості просто в конструкторі](03-classes-and-objects.md#constructor-promotion)
- [Читаємо атрибути, додані до коду](03-classes-and-objects.md#attributes)
- [Приймаємо рядок або об’єкт із `__toString()`](03-classes-and-objects.md#stringable)
- [Зберігаємо порядок за рівних значень](04-arrays-and-iteration.md#stable-sorting)
- [Перевіряємо вміст, початок або кінець рядка](05-strings-and-unicode.md#string-search)
- [Отримуємо `INF` або `NAN` в операціях із дробовими числами](07-dates-and-numbers.md#ieee-math)
- [Копіюємо дату в `DateTime` або `DateTimeImmutable`](07-dates-and-numbers.md#date-conversion)
- [`catch` без невикористаної змінної](08-errors-and-debugging.md#catch-without-variable)
- [`ValueError` і `TypeError`](08-errors-and-debugging.md#value-error)
- [Ідентифікатор ресурсу через `get_resource_id()`](08-errors-and-debugging.md#resource-id)
- [Як дізнатися тип значення через `get_debug_type()`](08-errors-and-debugging.md#debug-types)
- [Як зрозуміти помилку регулярного виразу](08-errors-and-debugging.md#regex-errors)
- [Читання PHP-коду через `PhpToken`](08-errors-and-debugging.md#php-token)
- [Дескриптори cURL стали об’єктами](09-files-http-and-processes.md#curl-objects)
- [Помилки `PDO` тепер викликають винятки](10-databases.md#pdo-errors)
- [Кеш, який не утримує об’єкт заради ключа](12-runtime-and-performance.md#weak-map)
- [Чим відрізняються JIT і OPcache](12-runtime-and-performance.md#jit)
- [Кінцева кома в оголошенні параметрів](01-syntax-and-functions.md#trailing-commas)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration80.php)

<a id="php-81"></a>
## PHP 8.1

- [Додаємо аргумент після розпакування](01-syntax-and-functions.md#named-after-unpacking)
- [Посилання на функцію через `function_name(...)`](01-syntax-and-functions.md#first-class-callables)
- [Вимагаємо одразу обидва типи: `A&B`](02-types.md#intersection)
- [Після цієї функції не продовжуємо: `never`](02-types.md#never)
- [Об’єкт як запасний аргумент](03-classes-and-objects.md#new-initializers)
- [Задаємо дозволені стани через enum](03-classes-and-objects.md#enums)
- [Що саме захищає властивість `readonly`](03-classes-and-objects.md#readonly-properties)
- [Перевіряємо список перед виведенням у JSON](04-arrays-and-iteration.md#list-arrays)
- [Записуємо enum у JSON його значенням](06-json-xml-and-serialization.md#json-enums)
- [Запис даних на диск через `fsync()`](09-files-http-and-processes.md#flush-to-disk)
- [Завантаження рядка як файлу](09-files-http-and-processes.md#curl-string-file)
- [Завантаження каталогів і `full_path`](09-files-http-and-processes.md#upload-paths)
- [Як змінилася обробка помилок `mysqli` за замовчуванням](10-databases.md#mysqli-errors)
- [Масив параметрів `mysqli_stmt::execute()`](10-databases.md#mysqli-execute)
- [Пряме читання одного стовпця](10-databases.md#fetch-column)
- [Пауза й продовження функції через `Fiber`](12-runtime-and-performance.md#fibers)
- [Перевірка можливостей збірки: AVIF у GD](12-runtime-and-performance.md#extension-capabilities)
- [Явні вісімкові літерали з `0o`](01-syntax-and-functions.md#numeric-literals)
- [Розпакування масивів із рядковими ключами](04-arrays-and-iteration.md#unpacking)
- [Final-константи класу](03-classes-and-objects.md#constant-visibility-final)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration81.php)

<a id="php-82"></a>
## PHP 8.2

- [Поєднуємо вимоги й варіанти: `(A&B)|C`](02-types.md#dnf)
- [Повертаємо саме `true`, `false` або `null`](02-types.md#literal-types)
- [Беремо ім’я або значення enum для константи](03-classes-and-objects.md#enum-constants)
- [Робимо всі властивості класу `readonly`](03-classes-and-objects.md#readonly-classes)
- [Константи, оголошені у traits](03-classes-and-objects.md#trait-constants)
- [Не створюємо властивості випадково](03-classes-and-objects.md#dynamic-properties)
- [Приховування секретних аргументів у стеку](08-errors-and-debugging.md#sensitive-parameter)
- [Скорочений запит `mysqli::execute_query()`](10-databases.md#mysqli-execute)
- [Єдиний API `Randomizer`](11-security-and-random.md#randomizer)
- [Повторюємо випадкову послідовність для тесту](11-security-and-random.md#seeded-random)
- [Отримання довжини ключа від OpenSSL](11-security-and-random.md#cipher-key-length)
- [Розбір розмірів із `php.ini`](12-runtime-and-performance.md#ini-quantities)
- [Вимірюємо пік пам’яті окремого етапу](12-runtime-and-performance.md#measure-memory)
- [Режим створення файлу журналу](12-runtime-and-performance.md#ini-and-cli)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration82.php)

<a id="php-83"></a>
## PHP 8.3

- [Готуємо значення `static` під час першого виклику](01-syntax-and-functions.md#static-initializers)
- [Указуємо тип константи класу](02-types.md#typed-constants)
- [Відокремлюємо вкладений об’єкт під час клонування](03-classes-and-objects.md#readonly-cloning)
- [Створюємо анонімний `readonly`-клас](03-classes-and-objects.md#readonly-anonymous)
- [Читаємо константу за ім’ям зі змінної](03-classes-and-objects.md#dynamic-constants)
- [Забороняємо нащадку змінювати метод трейту](03-classes-and-objects.md#trait-final)
- [Перевіряємо, що метод справді перевизначено](03-classes-and-objects.md#override)
- [Доповнюємо рядок до потрібної довжини](05-strings-and-unicode.md#mb-pad)
- [Отримуємо наступну буквено-числову мітку](05-strings-and-unicode.md#string-increment)
- [Перевіряємо JSON без отримання його даних](06-json-xml-and-serialization.md#json-validation)
- [Змінюємо атрибути й додаємо текст через DOM](06-json-xml-and-serialization.md#dom-convenience)
- [Ловимо помилку в рядку дати](07-dates-and-numbers.md#date-exceptions)
- [Повторюваний період із рядка ISO 8601](07-dates-and-numbers.md#iso-period)
- [Кілька параметрів потоку одним викликом](09-files-http-and-processes.md#stream-options)
- [Випадкові байти із заданого алфавіту](11-security-and-random.md#random-alphabet)
- [Випадкові дробові числа з явними межами](11-security-and-random.md#random-floats)
- [Резервні значення оточення та lint кількох файлів](12-runtime-and-performance.md#ini-and-cli)
- [Окремі статичні властивості при повторному підключенні трейта нащадком](03-classes-and-objects.md#trait-constants)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration83.php)

<a id="php-84"></a>
## PHP 8.4

- [Передаємо `exit()` як функцію](01-syntax-and-functions.md#exit-callable)
- [Обробляємо читання й запис властивості](03-classes-and-objects.md#property-hooks)
- [Перевіряємо значення перед записом у властивість](03-classes-and-objects.md#setter-validation)
- [Читати можна всім, змінювати лише класу](03-classes-and-objects.md#asymmetric-visibility)
- [Вимагаємо властивість через інтерфейс](03-classes-and-objects.md#interface-properties)
- [Готуємо об’єкт лише під час першого звернення](03-classes-and-objects.md#lazy-objects)
- [Викликаємо метод одразу після `new`](03-classes-and-objects.md#new-chaining)
- [Шукаємо перше значення за умовою](04-arrays-and-iteration.md#predicate-search)
- [Перевіряємо хоча б один елемент або всі одразу](04-arrays-and-iteration.md#predicate-checks)
- [Змінюємо регістр лише першої літери](05-strings-and-unicode.md#mb-case)
- [Прибираємо пробіли Unicode з країв](05-strings-and-unicode.md#unicode-trim)
- [Не відриваємо знак наголосу від літери](05-strings-and-unicode.md#grapheme-split)
- [Читаємо HTML через `Dom\HTMLDocument`](06-json-xml-and-serialization.md#html5-dom)
- [Підставляємо рядок у XPath без зламаних лапок](06-json-xml-and-serialization.md#xpath-quote)
- [Одразу вибираємо, звідки читати XML і куди писати](06-json-xml-and-serialization.md#xml-factories)
- [Дата з Unix-мітки з мікросекундами](07-dates-and-numbers.md#timestamp-microseconds)
- [Явне правило округлення через `RoundingMode`](07-dates-and-numbers.md#rounding-modes)
- [Десяткова арифметика через `BcMath\Number`](07-dates-and-numbers.md#bcmath-number)
- [Округлення десяткових чисел і частка з остачею](07-dates-and-numbers.md#bcmath-rounding)
- [Позначення старого API через `#[Deprecated]`](08-errors-and-debugging.md#deprecated-attribute)
- [Розбір тіла форми для інших HTTP-методів](09-files-http-and-processes.md#request-body)
- [Читання останніх заголовків HTTP-потоку](09-files-http-and-processes.md#http-response-headers)
- [Методи конкретного драйвера через підкласи `PDO`](10-databases.md#pdo-subclasses)
- [Readonly-властивості мають `protected(set)` за замовчуванням](03-classes-and-objects.md#readonly-properties)
- [`fpow()` для IEEE-семантики степеня](07-dates-and-numbers.md#ieee-math)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration84.php)

<a id="php-85"></a>
## PHP 8.5

- [Обробляємо значення крок за кроком через `|>`](01-syntax-and-functions.md#pipe)
- [Функція як значення за замовчуванням](01-syntax-and-functions.md#closures-in-constant-expressions)
- [Замикання викликає саме себе](01-syntax-and-functions.md#recursive-closures)
- [Перетворюємо тип під час оголошення константи](01-syntax-and-functions.md#casts-in-constant-expressions)
- [Додаємо атрибут до глобальної константи](01-syntax-and-functions.md#global-constant-attributes)
- [Перевіряємо перевизначення властивості](03-classes-and-objects.md#override-properties)
- [Обмежуємо запис у статичну властивість](03-classes-and-objects.md#static-visibility)
- [Додаємо `final` до властивості в конструкторі](03-classes-and-objects.md#final-promotion)
- [Копіюємо об’єкт і змінюємо вибрані властивості](03-classes-and-objects.md#clone-with)
- [Беремо перше або останнє значення](04-arrays-and-iteration.md#boundary-values)
- [Складаємо список за правилами мови](05-strings-and-unicode.md#intl-lists)
- [Дізнаємося напрямок письма для мови](05-strings-and-unicode.md#locale-direction)
- [Порівнюємо текст за цілими видимими символами](05-strings-and-unicode.md#grapheme-distance)
- [Вставляємо готовий фрагмент HTML](06-json-xml-and-serialization.md#html-fragments)
- [Попередження, якщо результат функції загубили](08-errors-and-debugging.md#nodiscard)
- [Які обробники помилок зараз установлені](08-errors-and-debugging.md#current-handlers)
- [Стек викликів для фатальних помилок](08-errors-and-debugging.md#fatal-backtraces)
- [Змінюємо частини адреси через об’єкт URI](09-files-http-and-processes.md#uri-objects)
- [Повторне використання даних cURL між запитами](09-files-http-and-processes.md#persistent-curl-share)
- [Які дескриптори додані в cURL multi](09-files-http-and-processes.md#multi-handles)
- [Partitioned-cookie](09-files-http-and-processes.md#cookie-options)
- [Виняток при невдалій перевірці](11-security-and-random.md#filter-exceptions)
- [Уточнення результату `static` у `final`-нащадках](02-types.md#static-return)
- [Відкладена перевірка цілей вбудованих атрибутів](03-classes-and-objects.md#attributes)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration85.php)

[Зміст](../../README.uk.md)
