# Версии PHP

[English](../en/VERSIONS.md) | [Українська](../uk/VERSIONS.md) | [Русский](../ru/VERSIONS.md)

[Содержание](../../README.ru.md)

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

- [Значение по умолчанию через ??](01-syntax-and-functions.md#null-coalescing)
- [Сортировка оператором <=>](01-syntax-and-functions.md#spaceship)
- [Группировка импортов пространства имён](01-syntax-and-functions.md#group-use)
- [Временная привязка замыкания через call()](01-syntax-and-functions.md#closure-call)
- [Скалярные параметры и результат](02-types.md#scalar-types)
- [strict_types](02-types.md#strict-types)
- [Анонимные классы](03-classes-and-objects.md#anonymous-classes)
- [Объединение потоков через yield from](04-arrays-and-iteration.md#generator-delegation)
- [Итоговый результат генератора](04-arrays-and-iteration.md#generator-return)
- [Целочисленное деление через `intdiv()`](07-dates-and-numbers.md#integer-division)
- [Throwable объединяет Exception и Error](08-errors-and-debugging.md#throwable)
- [Свой callback замены для каждого шаблона](08-errors-and-debugging.md#regex-callback-map)
- [Чтение сеанса с освобождением блокировки](09-files-http-and-processes.md#read-only-session)
- [Безопасные случайные байты и числа](11-security-and-random.md#secure-random)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration70.php)

<a id="php-71"></a>
## PHP 7.1

- [Преобразование callable через Closure::fromCallable()](01-syntax-and-functions.md#closure-from-callable)
- [Nullable-типы](02-types.md#nullable)
- [void](02-types.md#void)
- [Тип iterable](02-types.md#iterable-object)
- [Доступ к константам класса](03-classes-and-objects.md#constant-visibility-final)
- [Разбор массива по переменным](04-arrays-and-iteration.md#destructuring)
- [Чтение выбранных именованных полей](04-arrays-and-iteration.md#keyed-destructuring)
- [Массивы и итераторы через `iterable`](04-arrays-and-iteration.md#iterable)
- [Чтение с конца через отрицательные индексы](05-strings-and-unicode.md#negative-offsets)
- [Один catch для нескольких типов исключений](08-errors-and-debugging.md#multi-catch)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration71.php)

<a id="php-72"></a>
## PHP 7.2

- [Тип object](02-types.md#iterable-object)
- [Политика обработки некорректного UTF-8 в JSON](06-json-xml-and-serialization.md#invalid-utf8-json)
- [Как отличить отсутствующую группу от пустого совпадения](08-errors-and-debugging.md#unmatched-groups)
- [Хеширование паролей Argon2i](11-security-and-random.md#password-algorithms)
- [Аутентифицированное шифрование через Sodium](11-security-and-random.md#sodium)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration72.php)

<a id="php-73"></a>
## PHP 7.3

- [Завершающая запятая в вызовах и объявлениях](01-syntax-and-functions.md#trailing-commas)
- [Разбор по ссылке для изменения оригинала](04-arrays-and-iteration.md#reference-destructuring)
- [Первый и последний ключ без изменения указателя](04-arrays-and-iteration.md#boundary-keys)
- [Проверка возможности `count()`](04-arrays-and-iteration.md#countable)
- [Естественные отступы в heredoc и nowdoc](05-strings-and-unicode.md#heredoc)
- [Обработка ошибок JSON через исключения](06-json-xml-and-serialization.md#json-exceptions)
- [Массив параметров cookie](09-files-http-and-processes.md#cookie-options)
- [Хеширование паролей Argon2id](11-security-and-random.md#password-algorithms)
- [Измерение длительности монотонными часами](12-runtime-and-performance.md#monotonic-time)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration73.php)

<a id="php-74"></a>
## PHP 7.4

- [Дополнение настроек через ??=](01-syntax-and-functions.md#null-coalescing-assignment)
- [Стрелочные функции и захваченные значения](01-syntax-and-functions.md#arrow-functions)
- [Читаемые числовые литералы](01-syntax-and-functions.md#numeric-literals)
- [Типизированные свойства](02-types.md#typed-properties)
- [Ковариантность / контравариантность](02-types.md#variance)
- [Создание массивов через ... и повторяющиеся ключи](04-arrays-and-iteration.md#unpacking)
- [Слияние нуля или более массивов](04-arrays-and-iteration.md#merge-empty)
- [Разбиение многобайтовой строки по кодовым точкам](05-strings-and-unicode.md#mb-split)
- [Разрешённые теги массивом для `strip_tags()`](05-strings-and-unicode.md#allowed-tags)
- [Управление состоянием через `__serialize()` и `__unserialize()`](06-json-xml-and-serialization.md#object-serialization)
- [Исключения из __toString()](08-errors-and-debugging.md#to-string-exceptions)
- [Аргументы процесса массивом](09-files-http-and-processes.md#process-arguments)
- [Копирование SQLite через API резервирования](10-databases.md#sqlite-backup)
- [Доступные алгоритмы паролей через password_algos()](11-security-and-random.md#password-algorithms)
- [Проверка дробного числа и диапазона](11-security-and-random.md#float-validation)
- [Наблюдение за объектом без удержания в памяти](12-runtime-and-performance.md#weak-reference)
- [Предварительная загрузка постоянного кода](12-runtime-and-performance.md#preloading)
- [FFI для явного взаимодействия с C](12-runtime-and-performance.md#ffi)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration74.php)

<a id="php-80"></a>
## PHP 8.0

- [Именованные аргументы](01-syntax-and-functions.md#named-arguments)
- [Строковые ключи становятся именованными аргументами](01-syntax-and-functions.md#unpacking-named-arguments)
- [match возвращает значение и сравнивает строго](01-syntax-and-functions.md#match)
- [Цепочка необязательных объектов через ?->](01-syntax-and-functions.md#nullsafe)
- [throw внутри выражения](01-syntax-and-functions.md#throw-expression)
- [Union: A|B](02-types.md#union)
- [mixed](02-types.md#mixed)
- [Тип результата static](02-types.md#static-return)
- [Объявление свойств в конструкторе](03-classes-and-objects.md#constructor-promotion)
- [Атрибуты и чтение их метаданных](03-classes-and-objects.md#attributes)
- [Интерфейс Stringable](03-classes-and-objects.md#stringable)
- [Стабильная сортировка сохраняет порядок равных элементов](04-arrays-and-iteration.md#stable-sorting)
- [Поиск подстроки, префикса или суффикса](05-strings-and-unicode.md#string-search)
- [Явные операции IEEE с дробными числами](07-dates-and-numbers.md#ieee-math)
- [Копирование дат между изменяемым и неизменяемым API](07-dates-and-numbers.md#date-conversion)
- [catch без неиспользуемой переменной](08-errors-and-debugging.md#catch-without-variable)
- [ValueError и TypeError](08-errors-and-debugging.md#value-error)
- [Идентификатор ресурса через get_resource_id()](08-errors-and-debugging.md#resource-id)
- [Понятные типы во время выполнения через get_debug_type()](08-errors-and-debugging.md#debug-types)
- [Как понять ошибку регулярного выражения](08-errors-and-debugging.md#regex-errors)
- [Чтение PHP-кода через PhpToken](08-errors-and-debugging.md#php-token)
- [Дескрипторы cURL стали объектами](09-files-http-and-processes.md#curl-objects)
- [Исключения PDO стали поведением по умолчанию](10-databases.md#pdo-errors)
- [Кеш данных со слабыми ключами-объектами](12-runtime-and-performance.md#weak-map)
- [JIT и OPcache являются разными инструментами](12-runtime-and-performance.md#jit)
- [Завершающая запятая в объявлении параметров](01-syntax-and-functions.md#trailing-commas)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration80.php)

<a id="php-81"></a>
## PHP 8.1

- [Именованные аргументы после распаковки](01-syntax-and-functions.md#named-after-unpacking)
- [Синтаксис ссылки на функцию: function_name(...)](01-syntax-and-functions.md#first-class-callables)
- [Intersection: A&B](02-types.md#intersection)
- [never](02-types.md#never)
- [Объекты как значения параметров по умолчанию](03-classes-and-objects.md#new-initializers)
- [Enum для закрытого набора состояний](03-classes-and-objects.md#enums)
- [Readonly-свойства и поверхностная неизменяемость](03-classes-and-objects.md#readonly-properties)
- [Распознавание списков и форма JSON-массива](04-arrays-and-iteration.md#list-arrays)
- [Кодирование backed enum как его значения](06-json-xml-and-serialization.md#json-enums)
- [Запись данных на диск через fsync()](09-files-http-and-processes.md#flush-to-disk)
- [Отправка строки как файла](09-files-http-and-processes.md#curl-string-file)
- [Загрузка каталогов и full_path](09-files-http-and-processes.md#upload-paths)
- [Строгий режим ошибок mysqli стал стандартным](10-databases.md#mysqli-errors)
- [Массив параметров mysqli_stmt::execute()](10-databases.md#mysqli-execute)
- [Прямое чтение одного столбца](10-databases.md#fetch-column)
- [Приостановка и возобновление стека вызовов](12-runtime-and-performance.md#fibers)
- [Проверка возможностей сборки: AVIF в GD](12-runtime-and-performance.md#extension-capabilities)
- [Явные восьмеричные литералы с 0o](01-syntax-and-functions.md#numeric-literals)
- [Распаковка массивов со строковыми ключами](04-arrays-and-iteration.md#unpacking)
- [Final-константы класса](03-classes-and-objects.md#constant-visibility-final)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration81.php)

<a id="php-82"></a>
## PHP 8.2

- [DNF: (A&B)|C](02-types.md#dnf)
- [Отдельные true / false / null](02-types.md#literal-types)
- [Свойства enum в константных выражениях](03-classes-and-objects.md#enum-constants)
- [Readonly-классы](03-classes-and-objects.md#readonly-classes)
- [Константы, объявленные в traits](03-classes-and-objects.md#trait-constants)
- [Динамические свойства устарели](03-classes-and-objects.md#dynamic-properties)
- [Скрытие секретных аргументов в стеке](08-errors-and-debugging.md#sensitive-parameter)
- [Сокращённый запрос mysqli::execute_query()](10-databases.md#mysqli-execute)
- [Единый API Randomizer](11-security-and-random.md#randomizer)
- [Воспроизводимая случайность для тестов](11-security-and-random.md#seeded-random)
- [Получение длины ключа от OpenSSL](11-security-and-random.md#cipher-key-length)
- [Разбор размеров из php.ini](12-runtime-and-performance.md#ini-quantities)
- [Сброс пика памяти для отдельного этапа](12-runtime-and-performance.md#measure-memory)
- [Режим создания файла журнала](12-runtime-and-performance.md#ini-and-cli)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration82.php)

<a id="php-83"></a>
## PHP 8.3

- [Выражения в инициализаторах статических локальных переменных](01-syntax-and-functions.md#static-initializers)
- [Типизированные константы](02-types.md#typed-constants)
- [Повторная инициализация readonly в __clone()](03-classes-and-objects.md#readonly-cloning)
- [Анонимные readonly-классы](03-classes-and-objects.md#readonly-anonymous)
- [Выбор константы класса по имени](03-classes-and-objects.md#dynamic-constants)
- [Импорт метода trait как final](03-classes-and-objects.md#trait-final)
- [Проверка переопределённых методов через #[Override]](03-classes-and-objects.md#override)
- [Дополнение текста по количеству кодовых точек](05-strings-and-unicode.md#mb-pad)
- [Явное увеличение буквенно-числовой ASCII-метки](05-strings-and-unicode.md#string-increment)
- [Проверка JSON, когда его содержимое не нужно](06-json-xml-and-serialization.md#json-validation)
- [Операции DOM с атрибутами и текстом](06-json-xml-and-serialization.md#dom-convenience)
- [Точный перехват ошибок разбора дат](07-dates-and-numbers.md#date-exceptions)
- [Повторяющийся период из строки ISO 8601](07-dates-and-numbers.md#iso-period)
- [Несколько параметров потока одним вызовом](09-files-http-and-processes.md#stream-options)
- [Случайные байты из заданного алфавита](11-security-and-random.md#random-alphabet)
- [Случайные дробные числа с явными границами](11-security-and-random.md#random-floats)
- [Запасные значения окружения и lint нескольких файлов](12-runtime-and-performance.md#ini-and-cli)
- [Отдельные статические свойства при повторном подключении трейта наследником](03-classes-and-objects.md#trait-constants)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration83.php)

<a id="php-84"></a>
## PHP 8.4

- [exit() и die() ведут себя ближе к обычным функциям](01-syntax-and-functions.md#exit-callable)
- [Обработчики свойств: хранимые и виртуальные значения](03-classes-and-objects.md#property-hooks)
- [Полный setter должен записывать значение свойства](03-classes-and-objects.md#setter-validation)
- [Разные права для чтения и записи](03-classes-and-objects.md#asymmetric-visibility)
- [Контракты свойств в интерфейсах](03-classes-and-objects.md#interface-properties)
- [Отложенные объекты: ghost и proxy](03-classes-and-objects.md#lazy-objects)
- [Вызов метода сразу после new](03-classes-and-objects.md#new-chaining)
- [Поиск первого подходящего значения или ключа](04-arrays-and-iteration.md#predicate-search)
- [Проверка любого или всех элементов](04-arrays-and-iteration.md#predicate-checks)
- [Изменение регистра первого символа Unicode](05-strings-and-unicode.md#mb-case)
- [Удаление пробелов Unicode по краям](05-strings-and-unicode.md#unicode-trim)
- [Разбиение по границам видимых символов](05-strings-and-unicode.md#grapheme-split)
- [Разбор современного HTML через `Dom\HTMLDocument`](06-json-xml-and-serialization.md#html5-dom)
- [Правильное экранирование строковых значений XPath](06-json-xml-and-serialization.md#xpath-quote)
- [Создание `XMLReader` и `XMLWriter` именованными фабриками](06-json-xml-and-serialization.md#xml-factories)
- [Создание дат из дробных временных меток](07-dates-and-numbers.md#timestamp-microseconds)
- [Явное правило округления через `RoundingMode`](07-dates-and-numbers.md#rounding-modes)
- [Десятичная арифметика через `BcMath\Number`](07-dates-and-numbers.md#bcmath-number)
- [Округление десятичных чисел и частное с остатком](07-dates-and-numbers.md#bcmath-rounding)
- [Пометка старого API через #[Deprecated]](08-errors-and-debugging.md#deprecated-attribute)
- [Разбор тела формы для других HTTP-методов](09-files-http-and-processes.md#request-body)
- [Чтение последних заголовков HTTP-потока](09-files-http-and-processes.md#http-response-headers)
- [Классы PDO для отдельных драйверов](10-databases.md#pdo-subclasses)
- [Readonly-свойства имеют protected(set) по умолчанию](03-classes-and-objects.md#readonly-properties)
- [fpow() для IEEE-семантики степени](07-dates-and-numbers.md#ieee-math)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration84.php)

<a id="php-85"></a>
## PHP 8.5

- [Оператор последовательной обработки |>](01-syntax-and-functions.md#pipe)
- [Callback в константных выражениях](01-syntax-and-functions.md#closures-in-constant-expressions)
- [Рекурсия через Closure::getCurrent()](01-syntax-and-functions.md#recursive-closures)
- [Приведение типа в константных выражениях](01-syntax-and-functions.md#casts-in-constant-expressions)
- [Атрибуты глобальных констант](01-syntax-and-functions.md#global-constant-attributes)
- [Проверка переопределения свойств через #[Override]](03-classes-and-objects.md#override-properties)
- [Асимметричная видимость статических свойств](03-classes-and-objects.md#static-visibility)
- [Final для свойств в параметрах конструктора](03-classes-and-objects.md#final-promotion)
- [Клонирование объекта с изменением выбранных свойств](03-classes-and-objects.md#clone-with)
- [Прямое чтение первого или последнего значения](04-arrays-and-iteration.md#boundary-values)
- [Оформление списка по правилам языка читателя](05-strings-and-unicode.md#intl-lists)
- [Стандартное направление письма локали](05-strings-and-unicode.md#locale-direction)
- [Сравнение Unicode-текста по графемному расстоянию](05-strings-and-unicode.md#grapheme-distance)
- [Вставка и чтение фрагментов HTML](06-json-xml-and-serialization.md#html-fragments)
- [Выявление проигнорированного результата через #[NoDiscard]](08-errors-and-debugging.md#nodiscard)
- [Проверка текущих обработчиков ошибок и исключений](08-errors-and-debugging.md#current-handlers)
- [Стек вызовов для фатальных ошибок](08-errors-and-debugging.md#fatal-backtraces)
- [Чтение и изменение URI-объектов](09-files-http-and-processes.md#uri-objects)
- [Повторное использование данных cURL между запросами](09-files-http-and-processes.md#persistent-curl-share)
- [Список дескрипторов в multi handle](09-files-http-and-processes.md#multi-handles)
- [Partitioned-cookie](09-files-http-and-processes.md#cookie-options)
- [Исключение при неудачной проверке](11-security-and-random.md#filter-exceptions)
- [Уточнение результата static в final-наследниках](02-types.md#static-return)
- [Отложенная проверка целей встроенных атрибутов](03-classes-and-objects.md#attributes)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration85.php)

[Содержание](../../README.ru.md)
