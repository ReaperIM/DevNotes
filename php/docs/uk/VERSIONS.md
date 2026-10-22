# Версії PHP

[English](../en/VERSIONS.md) | [Українська](../uk/VERSIONS.md) | [Русский](../ru/VERSIONS.md)

[Зміст](../../README.uk.md)

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

- [Значення за замовчуванням через ??](01-syntax-and-functions.md#null-coalescing)
- [Сортування оператором <=>](01-syntax-and-functions.md#spaceship)
- [Групування імпортів простору імен](01-syntax-and-functions.md#group-use)
- [Тимчасова прив’язка замикання через call()](01-syntax-and-functions.md#closure-call)
- [Скалярні параметри й результат](02-types.md#scalar-types)
- [strict_types](02-types.md#strict-types)
- [Анонімні класи](03-classes-and-objects.md#anonymous-classes)
- [Об’єднання потоків через yield from](04-arrays-and-iteration.md#generator-delegation)
- [Підсумковий результат генератора](04-arrays-and-iteration.md#generator-return)
- [Цілочислове ділення через `intdiv()`](07-dates-and-numbers.md#integer-division)
- [Throwable об’єднує Exception та Error](08-errors-and-debugging.md#throwable)
- [Власний callback заміни для кожного шаблону](08-errors-and-debugging.md#regex-callback-map)
- [Читання сеансу зі звільненням блокування](09-files-http-and-processes.md#read-only-session)
- [Безпечні випадкові байти й числа](11-security-and-random.md#secure-random)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration70.php)

<a id="php-71"></a>
## PHP 7.1

- [Перетворення callable через Closure::fromCallable()](01-syntax-and-functions.md#closure-from-callable)
- [Nullable-типи](02-types.md#nullable)
- [void](02-types.md#void)
- [Тип iterable](02-types.md#iterable-object)
- [Доступ до констант класу](03-classes-and-objects.md#constant-visibility-final)
- [Розкладання масиву за змінними](04-arrays-and-iteration.md#destructuring)
- [Читання вибраних іменованих полів](04-arrays-and-iteration.md#keyed-destructuring)
- [Масиви та ітератори через `iterable`](04-arrays-and-iteration.md#iterable)
- [Читання з кінця через від’ємні індекси](05-strings-and-unicode.md#negative-offsets)
- [Один catch для кількох типів винятків](08-errors-and-debugging.md#multi-catch)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration71.php)

<a id="php-72"></a>
## PHP 7.2

- [Тип object](02-types.md#iterable-object)
- [Політика обробки некоректного UTF-8 у JSON](06-json-xml-and-serialization.md#invalid-utf8-json)
- [Як відрізнити відсутню групу від порожнього збігу](08-errors-and-debugging.md#unmatched-groups)
- [Хешування паролів Argon2i](11-security-and-random.md#password-algorithms)
- [Автентифіковане шифрування через Sodium](11-security-and-random.md#sodium)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration72.php)

<a id="php-73"></a>
## PHP 7.3

- [Кінцева кома у викликах та оголошеннях](01-syntax-and-functions.md#trailing-commas)
- [Розкладання за посиланням для зміни оригіналу](04-arrays-and-iteration.md#reference-destructuring)
- [Перший і останній ключ без зміни вказівника](04-arrays-and-iteration.md#boundary-keys)
- [Перевірка можливості `count()`](04-arrays-and-iteration.md#countable)
- [Природні відступи в heredoc та nowdoc](05-strings-and-unicode.md#heredoc)
- [Обробка помилок JSON через винятки](06-json-xml-and-serialization.md#json-exceptions)
- [Масив параметрів cookie](09-files-http-and-processes.md#cookie-options)
- [Хешування паролів Argon2id](11-security-and-random.md#password-algorithms)
- [Вимірювання тривалості монотонним годинником](12-runtime-and-performance.md#monotonic-time)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration73.php)

<a id="php-74"></a>
## PHP 7.4

- [Доповнення налаштувань через ??=](01-syntax-and-functions.md#null-coalescing-assignment)
- [Стрілкові функції та захоплені значення](01-syntax-and-functions.md#arrow-functions)
- [Читабельні числові літерали](01-syntax-and-functions.md#numeric-literals)
- [Типізовані властивості](02-types.md#typed-properties)
- [Коваріантність / контраваріантність](02-types.md#variance)
- [Побудова масивів через ... і повторні ключі](04-arrays-and-iteration.md#unpacking)
- [Злиття нуля або більше масивів](04-arrays-and-iteration.md#merge-empty)
- [Поділ багатобайтового рядка за кодовими точками](05-strings-and-unicode.md#mb-split)
- [Дозволені теги масивом для `strip_tags()`](05-strings-and-unicode.md#allowed-tags)
- [Керування станом через `__serialize()` та `__unserialize()`](06-json-xml-and-serialization.md#object-serialization)
- [Винятки з __toString()](08-errors-and-debugging.md#to-string-exceptions)
- [Аргументи процесу масивом](09-files-http-and-processes.md#process-arguments)
- [Копіювання SQLite через API резервування](10-databases.md#sqlite-backup)
- [Доступні алгоритми паролів через password_algos()](11-security-and-random.md#password-algorithms)
- [Перевірка дробового числа й діапазону](11-security-and-random.md#float-validation)
- [Спостереження за об’єктом без утримання в пам’яті](12-runtime-and-performance.md#weak-reference)
- [Попереднє завантаження сталого коду](12-runtime-and-performance.md#preloading)
- [FFI для явної взаємодії з C](12-runtime-and-performance.md#ffi)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration74.php)

<a id="php-80"></a>
## PHP 8.0

- [Іменовані аргументи](01-syntax-and-functions.md#named-arguments)
- [Рядкові ключі стають іменованими аргументами](01-syntax-and-functions.md#unpacking-named-arguments)
- [match повертає значення та порівнює строго](01-syntax-and-functions.md#match)
- [Ланцюжок необов’язкових об’єктів через ?->](01-syntax-and-functions.md#nullsafe)
- [throw усередині виразу](01-syntax-and-functions.md#throw-expression)
- [Union: A|B](02-types.md#union)
- [mixed](02-types.md#mixed)
- [Тип результату static](02-types.md#static-return)
- [Оголошення властивостей у конструкторі](03-classes-and-objects.md#constructor-promotion)
- [Атрибути та читання їхніх метаданих](03-classes-and-objects.md#attributes)
- [Інтерфейс Stringable](03-classes-and-objects.md#stringable)
- [Стабільне сортування зберігає порядок рівних елементів](04-arrays-and-iteration.md#stable-sorting)
- [Пошук підрядка, префікса чи суфікса](05-strings-and-unicode.md#string-search)
- [Явні операції IEEE з дробовими числами](07-dates-and-numbers.md#ieee-math)
- [Копіювання дат між змінним і незмінним API](07-dates-and-numbers.md#date-conversion)
- [catch без невикористаної змінної](08-errors-and-debugging.md#catch-without-variable)
- [ValueError і TypeError](08-errors-and-debugging.md#value-error)
- [Ідентифікатор ресурсу через get_resource_id()](08-errors-and-debugging.md#resource-id)
- [Зрозумілі типи під час виконання через get_debug_type()](08-errors-and-debugging.md#debug-types)
- [Як зрозуміти помилку регулярного виразу](08-errors-and-debugging.md#regex-errors)
- [Читання PHP-коду через PhpToken](08-errors-and-debugging.md#php-token)
- [Дескриптори cURL стали об’єктами](09-files-http-and-processes.md#curl-objects)
- [Винятки PDO стали типовою поведінкою](10-databases.md#pdo-errors)
- [Кеш даних зі слабкими ключами-об’єктами](12-runtime-and-performance.md#weak-map)
- [JIT та OPcache є різними інструментами](12-runtime-and-performance.md#jit)
- [Кінцева кома в оголошенні параметрів](01-syntax-and-functions.md#trailing-commas)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration80.php)

<a id="php-81"></a>
## PHP 8.1

- [Іменовані аргументи після розпакування](01-syntax-and-functions.md#named-after-unpacking)
- [Синтаксис посилання на функцію: function_name(...)](01-syntax-and-functions.md#first-class-callables)
- [Intersection: A&B](02-types.md#intersection)
- [never](02-types.md#never)
- [Об’єкти як значення параметрів за замовчуванням](03-classes-and-objects.md#new-initializers)
- [Enum для закритого набору станів](03-classes-and-objects.md#enums)
- [Readonly-властивості та поверхнева незмінюваність](03-classes-and-objects.md#readonly-properties)
- [Розпізнавання списків і форма JSON-масиву](04-arrays-and-iteration.md#list-arrays)
- [Кодування backed enum як його значення](06-json-xml-and-serialization.md#json-enums)
- [Запис даних на диск через fsync()](09-files-http-and-processes.md#flush-to-disk)
- [Завантаження рядка як файлу](09-files-http-and-processes.md#curl-string-file)
- [Завантаження каталогів і full_path](09-files-http-and-processes.md#upload-paths)
- [Суворий режим помилок mysqli став типовим](10-databases.md#mysqli-errors)
- [Масив параметрів mysqli_stmt::execute()](10-databases.md#mysqli-execute)
- [Пряме читання одного стовпця](10-databases.md#fetch-column)
- [Призупинення й відновлення стека викликів](12-runtime-and-performance.md#fibers)
- [Перевірка можливостей збірки: AVIF у GD](12-runtime-and-performance.md#extension-capabilities)
- [Явні вісімкові літерали з 0o](01-syntax-and-functions.md#numeric-literals)
- [Розпакування масивів із рядковими ключами](04-arrays-and-iteration.md#unpacking)
- [Final-константи класу](03-classes-and-objects.md#constant-visibility-final)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration81.php)

<a id="php-82"></a>
## PHP 8.2

- [DNF: (A&B)|C](02-types.md#dnf)
- [Окремі true / false / null](02-types.md#literal-types)
- [Властивості enum у константних виразах](03-classes-and-objects.md#enum-constants)
- [Readonly-класи](03-classes-and-objects.md#readonly-classes)
- [Константи, оголошені у traits](03-classes-and-objects.md#trait-constants)
- [Динамічні властивості застаріли](03-classes-and-objects.md#dynamic-properties)
- [Приховування секретних аргументів у стеку](08-errors-and-debugging.md#sensitive-parameter)
- [Скорочений запит mysqli::execute_query()](10-databases.md#mysqli-execute)
- [Єдиний API Randomizer](11-security-and-random.md#randomizer)
- [Відтворювана випадковість для тестів](11-security-and-random.md#seeded-random)
- [Отримання довжини ключа від OpenSSL](11-security-and-random.md#cipher-key-length)
- [Розбір розмірів із php.ini](12-runtime-and-performance.md#ini-quantities)
- [Скидання піку пам’яті для окремого етапу](12-runtime-and-performance.md#measure-memory)
- [Режим створення файлу журналу](12-runtime-and-performance.md#ini-and-cli)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration82.php)

<a id="php-83"></a>
## PHP 8.3

- [Вирази в ініціалізаторах статичних локальних змінних](01-syntax-and-functions.md#static-initializers)
- [Типізовані константи](02-types.md#typed-constants)
- [Повторна ініціалізація readonly у __clone()](03-classes-and-objects.md#readonly-cloning)
- [Анонімні readonly-класи](03-classes-and-objects.md#readonly-anonymous)
- [Вибір константи класу за назвою](03-classes-and-objects.md#dynamic-constants)
- [Імпорт методу trait як final](03-classes-and-objects.md#trait-final)
- [Перевірка перевизначених методів через #[Override]](03-classes-and-objects.md#override)
- [Доповнення тексту за кількістю кодових точок](05-strings-and-unicode.md#mb-pad)
- [Явне збільшення буквено-числової ASCII-мітки](05-strings-and-unicode.md#string-increment)
- [Перевірка JSON, коли його вміст не потрібен](06-json-xml-and-serialization.md#json-validation)
- [Операції DOM з атрибутами та текстом](06-json-xml-and-serialization.md#dom-convenience)
- [Точне перехоплення помилок розбору дат](07-dates-and-numbers.md#date-exceptions)
- [Повторюваний період із рядка ISO 8601](07-dates-and-numbers.md#iso-period)
- [Кілька параметрів потоку одним викликом](09-files-http-and-processes.md#stream-options)
- [Випадкові байти із заданого алфавіту](11-security-and-random.md#random-alphabet)
- [Випадкові дробові числа з явними межами](11-security-and-random.md#random-floats)
- [Резервні значення оточення та lint кількох файлів](12-runtime-and-performance.md#ini-and-cli)
- [Окремі статичні властивості при повторному підключенні трейта нащадком](03-classes-and-objects.md#trait-constants)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration83.php)

<a id="php-84"></a>
## PHP 8.4

- [exit() і die() поводяться ближче до звичайних функцій](01-syntax-and-functions.md#exit-callable)
- [Обробники властивостей: збережені та віртуальні значення](03-classes-and-objects.md#property-hooks)
- [Повний setter має записувати значення властивості](03-classes-and-objects.md#setter-validation)
- [Різні права для читання та запису](03-classes-and-objects.md#asymmetric-visibility)
- [Контракти властивостей в інтерфейсах](03-classes-and-objects.md#interface-properties)
- [Відкладені об’єкти: ghost і proxy](03-classes-and-objects.md#lazy-objects)
- [Виклик методу одразу після new](03-classes-and-objects.md#new-chaining)
- [Пошук першого відповідного значення чи ключа](04-arrays-and-iteration.md#predicate-search)
- [Перевірка будь-якого або всіх елементів](04-arrays-and-iteration.md#predicate-checks)
- [Зміна регістру першого символу Unicode](05-strings-and-unicode.md#mb-case)
- [Видалення пробілів Unicode з країв](05-strings-and-unicode.md#unicode-trim)
- [Поділ за межами видимих символів](05-strings-and-unicode.md#grapheme-split)
- [Розбір сучасного HTML через `Dom\HTMLDocument`](06-json-xml-and-serialization.md#html5-dom)
- [Правильне екранування рядкових значень XPath](06-json-xml-and-serialization.md#xpath-quote)
- [Створення `XMLReader` та `XMLWriter` іменованими фабриками](06-json-xml-and-serialization.md#xml-factories)
- [Створення дат із дробових часових міток](07-dates-and-numbers.md#timestamp-microseconds)
- [Явне правило округлення через `RoundingMode`](07-dates-and-numbers.md#rounding-modes)
- [Десяткова арифметика через `BcMath\Number`](07-dates-and-numbers.md#bcmath-number)
- [Округлення десяткових чисел і частка з остачею](07-dates-and-numbers.md#bcmath-rounding)
- [Позначення старого API через #[Deprecated]](08-errors-and-debugging.md#deprecated-attribute)
- [Розбір тіла форми для інших HTTP-методів](09-files-http-and-processes.md#request-body)
- [Читання останніх заголовків HTTP-потоку](09-files-http-and-processes.md#http-response-headers)
- [Класи PDO для окремих драйверів](10-databases.md#pdo-subclasses)
- [Readonly-властивості мають protected(set) за замовчуванням](03-classes-and-objects.md#readonly-properties)
- [fpow() для IEEE-семантики степеня](07-dates-and-numbers.md#ieee-math)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration84.php)

<a id="php-85"></a>
## PHP 8.5

- [Оператор послідовної обробки |>](01-syntax-and-functions.md#pipe)
- [Callback у константних виразах](01-syntax-and-functions.md#closures-in-constant-expressions)
- [Рекурсія через Closure::getCurrent()](01-syntax-and-functions.md#recursive-closures)
- [Приведення типу в константних виразах](01-syntax-and-functions.md#casts-in-constant-expressions)
- [Атрибути глобальних констант](01-syntax-and-functions.md#global-constant-attributes)
- [Перевірка перевизначення властивостей через #[Override]](03-classes-and-objects.md#override-properties)
- [Асиметрична видимість статичних властивостей](03-classes-and-objects.md#static-visibility)
- [Final для властивостей у параметрах конструктора](03-classes-and-objects.md#final-promotion)
- [Клонування об’єкта зі зміною вибраних властивостей](03-classes-and-objects.md#clone-with)
- [Безпосереднє читання першого чи останнього значення](04-arrays-and-iteration.md#boundary-values)
- [Оформлення списку за правилами мови читача](05-strings-and-unicode.md#intl-lists)
- [Типовий напрямок письма локалі](05-strings-and-unicode.md#locale-direction)
- [Порівняння Unicode-тексту за графемною відстанню](05-strings-and-unicode.md#grapheme-distance)
- [Вставка та читання фрагментів HTML](06-json-xml-and-serialization.md#html-fragments)
- [Виявлення проігнорованого результату через #[NoDiscard]](08-errors-and-debugging.md#nodiscard)
- [Перевірка поточних обробників помилок і винятків](08-errors-and-debugging.md#current-handlers)
- [Стек викликів для фатальних помилок](08-errors-and-debugging.md#fatal-backtraces)
- [Читання та зміна URI-об’єктів](09-files-http-and-processes.md#uri-objects)
- [Повторне використання даних cURL між запитами](09-files-http-and-processes.md#persistent-curl-share)
- [Список дескрипторів у multi handle](09-files-http-and-processes.md#multi-handles)
- [Partitioned-cookie](09-files-http-and-processes.md#cookie-options)
- [Виняток при невдалій перевірці](11-security-and-random.md#filter-exceptions)
- [Уточнення результату static у final-нащадках](02-types.md#static-return)
- [Відкладена перевірка цілей вбудованих атрибутів](03-classes-and-objects.md#attributes)

[Офіційний посібник з міграції](https://www.php.net/manual/en/migration85.php)

[Зміст](../../README.uk.md)
