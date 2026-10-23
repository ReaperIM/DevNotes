# Версии PHP

[EN](../en/VERSIONS.md) | [UK](../uk/VERSIONS.md) | [RU](../ru/VERSIONS.md)

[Содержание](../../README.ru.md)

Помнишь примерно, в какой версии PHP появилась нужная вещь? Выбираем её здесь и переходим к пояснению с примером. Если возможность позже менялась, она может встретиться сразу в нескольких версиях.

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

- [Запасное значение через `??`](01-syntax-and-functions.md#null-coalescing)
- [Сортируем через `<=>`](01-syntax-and-functions.md#spaceship)
- [Собираем похожие `use` в одну строку](01-syntax-and-functions.md#group-use)
- [Вызываем замыкание с нужным `$this`](01-syntax-and-functions.md#closure-call)
- [Типы параметров и результата](02-types.md#scalar-types)
- [Не подменяем типы через `strict_types`](02-types.md#strict-types)
- [Создаём класс прямо на месте](03-classes-and-objects.md#anonymous-classes)
- [Отдаём значения из нескольких источников через `yield from`](04-arrays-and-iteration.md#generator-delegation)
- [Получаем итог работы генератора](04-arrays-and-iteration.md#generator-return)
- [Целочисленное деление через `intdiv()`](07-dates-and-numbers.md#integer-division)
- [`Throwable` объединяет `Exception` и `Error`](08-errors-and-debugging.md#throwable)
- [Свой callback замены для каждого шаблона](08-errors-and-debugging.md#regex-callback-map)
- [Читаем сессию и сразу отпускаем блокировку](09-files-http-and-processes.md#read-only-session)
- [Безопасные случайные байты и числа](11-security-and-random.md#secure-random)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration70.php)

<a id="php-71"></a>
## PHP 7.1

- [Получаем замыкание через `Closure::fromCallable()`](01-syntax-and-functions.md#closure-from-callable)
- [Разрешаем `null` через `?T`](02-types.md#nullable)
- [Делаем действие без результата: `void`](02-types.md#void)
- [Тип `iterable`](02-types.md#iterable-object)
- [Доступ к константам класса](03-classes-and-objects.md#constant-visibility-final)
- [Раскладываем массив по переменным](04-arrays-and-iteration.md#destructuring)
- [Забираем нужные поля по ключам](04-arrays-and-iteration.md#keyed-destructuring)
- [Перебираем массивы и итераторы одинаково](04-arrays-and-iteration.md#iterable)
- [Читаем строку с конца](05-strings-and-unicode.md#negative-offsets)
- [Один `catch` для нескольких типов исключений](08-errors-and-debugging.md#multi-catch)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration71.php)

<a id="php-72"></a>
## PHP 7.2

- [Тип `object`](02-types.md#iterable-object)
- [Что делать с повреждённым UTF-8 в JSON](06-json-xml-and-serialization.md#invalid-utf8-json)
- [Как отличить отсутствующую группу от пустого совпадения](08-errors-and-debugging.md#unmatched-groups)
- [Хеширование паролей Argon2i](11-security-and-random.md#password-algorithms)
- [Шифруем сообщение и проверяем его целостность](11-security-and-random.md#sodium)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration72.php)

<a id="php-73"></a>
## PHP 7.3

- [Завершающая запятая в вызовах и объявлениях](01-syntax-and-functions.md#trailing-commas)
- [Меняем исходный элемент через ссылку](04-arrays-and-iteration.md#reference-destructuring)
- [Берём первый и последний ключ](04-arrays-and-iteration.md#boundary-keys)
- [Проверяем, можно ли вызвать `count()`](04-arrays-and-iteration.md#countable)
- [Оставляем нормальные отступы у многострочного текста](05-strings-and-unicode.md#heredoc)
- [Ловим ошибку JSON как исключение](06-json-xml-and-serialization.md#json-exceptions)
- [Массив параметров cookie](09-files-http-and-processes.md#cookie-options)
- [Хеширование паролей Argon2id](11-security-and-random.md#password-algorithms)
- [Измеряем время работы через `hrtime()`](12-runtime-and-performance.md#monotonic-time)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration73.php)

<a id="php-74"></a>
## PHP 7.4

- [Дополняем настройки через `??=`](01-syntax-and-functions.md#null-coalescing-assignment)
- [Короткие функции через `fn`](01-syntax-and-functions.md#arrow-functions)
- [Читаемые числовые литералы](01-syntax-and-functions.md#numeric-literals)
- [Указываем тип у свойства](02-types.md#typed-properties)
- [Уточняем типы в наследнике](02-types.md#variance)
- [Собираем массив через `...` и следим за повторными ключами](04-arrays-and-iteration.md#unpacking)
- [Склеиваем наборы, даже если наборов нет](04-arrays-and-iteration.md#merge-empty)
- [Делим текст без разрезания байтов одной буквы](05-strings-and-unicode.md#mb-split)
- [Передаём разрешённые теги массивом](05-strings-and-unicode.md#allowed-tags)
- [Выбираем, что сохранять и восстанавливать у объекта](06-json-xml-and-serialization.md#object-serialization)
- [Исключения из `__toString()`](08-errors-and-debugging.md#to-string-exceptions)
- [Аргументы процесса массивом](09-files-http-and-processes.md#process-arguments)
- [Копируем открытую базу SQLite](10-databases.md#sqlite-backup)
- [Доступные алгоритмы паролей через `password_algos()`](11-security-and-random.md#password-algorithms)
- [Проверка дробного числа и диапазона](11-security-and-random.md#float-validation)
- [Следим за объектом, не удерживая его в памяти](12-runtime-and-performance.md#weak-reference)
- [Предварительная загрузка постоянного кода](12-runtime-and-performance.md#preloading)
- [Работа с C через `FFI`](12-runtime-and-performance.md#ffi)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration74.php)

<a id="php-80"></a>
## PHP 8.0

- [Передаём аргументы по имени](01-syntax-and-functions.md#named-arguments)
- [Имена аргументов из ключей массива](01-syntax-and-functions.md#unpacking-named-arguments)
- [Выбираем значение через `match`](01-syntax-and-functions.md#match)
- [Читаем цепочку объектов через `?->`](01-syntax-and-functions.md#nullsafe)
- [Выбрасываем исключение прямо в выражении](01-syntax-and-functions.md#throw-expression)
- [Разрешаем один из типов: `A|B`](02-types.md#union)
- [Принимаем любое значение через `mixed`](02-types.md#mixed)
- [Тип результата `static`](02-types.md#static-return)
- [Объявляем свойства прямо в конструкторе](03-classes-and-objects.md#constructor-promotion)
- [Читаем атрибуты, добавленные к коду](03-classes-and-objects.md#attributes)
- [Принимаем строку или объект с `__toString()`](03-classes-and-objects.md#stringable)
- [Сохраняем порядок при равных значениях](04-arrays-and-iteration.md#stable-sorting)
- [Проверяем подстроку, начало и конец строки](05-strings-and-unicode.md#string-search)
- [Получаем `INF` или `NAN` в операциях с дробными числами](07-dates-and-numbers.md#ieee-math)
- [Копируем дату в `DateTime` или `DateTimeImmutable`](07-dates-and-numbers.md#date-conversion)
- [`catch` без неиспользуемой переменной](08-errors-and-debugging.md#catch-without-variable)
- [`ValueError` и `TypeError`](08-errors-and-debugging.md#value-error)
- [Идентификатор ресурса через `get_resource_id()`](08-errors-and-debugging.md#resource-id)
- [Как узнать тип значения через `get_debug_type()`](08-errors-and-debugging.md#debug-types)
- [Как понять ошибку регулярного выражения](08-errors-and-debugging.md#regex-errors)
- [Чтение PHP-кода через `PhpToken`](08-errors-and-debugging.md#php-token)
- [Дескрипторы cURL стали объектами](09-files-http-and-processes.md#curl-objects)
- [Ошибки `PDO` теперь вызывают исключения](10-databases.md#pdo-errors)
- [Кеш, который не удерживает объект ради ключа](12-runtime-and-performance.md#weak-map)
- [Чем отличаются JIT и OPcache](12-runtime-and-performance.md#jit)
- [Завершающая запятая в объявлении параметров](01-syntax-and-functions.md#trailing-commas)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration80.php)

<a id="php-81"></a>
## PHP 8.1

- [Добавляем аргумент после распаковки](01-syntax-and-functions.md#named-after-unpacking)
- [Ссылка на функцию через `function_name(...)`](01-syntax-and-functions.md#first-class-callables)
- [Требуем сразу оба типа: `A&B`](02-types.md#intersection)
- [После этой функции не продолжаем: `never`](02-types.md#never)
- [Объект как запасной аргумент](03-classes-and-objects.md#new-initializers)
- [Задаём допустимые состояния через enum](03-classes-and-objects.md#enums)
- [Что именно защищает свойство `readonly`](03-classes-and-objects.md#readonly-properties)
- [Проверяем список перед выводом в JSON](04-arrays-and-iteration.md#list-arrays)
- [Записываем enum в JSON его значением](06-json-xml-and-serialization.md#json-enums)
- [Запись данных на диск через `fsync()`](09-files-http-and-processes.md#flush-to-disk)
- [Отправка строки как файла](09-files-http-and-processes.md#curl-string-file)
- [Загрузка каталогов и `full_path`](09-files-http-and-processes.md#upload-paths)
- [Как изменились ошибки `mysqli` по умолчанию](10-databases.md#mysqli-errors)
- [Массив параметров `mysqli_stmt::execute()`](10-databases.md#mysqli-execute)
- [Прямое чтение одного столбца](10-databases.md#fetch-column)
- [Пауза и продолжение функции через `Fiber`](12-runtime-and-performance.md#fibers)
- [Проверка возможностей сборки: AVIF в GD](12-runtime-and-performance.md#extension-capabilities)
- [Явные восьмеричные литералы с `0o`](01-syntax-and-functions.md#numeric-literals)
- [Распаковка массивов со строковыми ключами](04-arrays-and-iteration.md#unpacking)
- [Final-константы класса](03-classes-and-objects.md#constant-visibility-final)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration81.php)

<a id="php-82"></a>
## PHP 8.2

- [Сочетаем требования и варианты: `(A&B)|C`](02-types.md#dnf)
- [Возвращаем именно `true`, `false` или `null`](02-types.md#literal-types)
- [Берём имя или значение enum для константы](03-classes-and-objects.md#enum-constants)
- [Делаем все свойства класса `readonly`](03-classes-and-objects.md#readonly-classes)
- [Константы, объявленные в traits](03-classes-and-objects.md#trait-constants)
- [Не создаём свойства случайно](03-classes-and-objects.md#dynamic-properties)
- [Скрытие секретных аргументов в стеке](08-errors-and-debugging.md#sensitive-parameter)
- [Сокращённый запрос `mysqli::execute_query()`](10-databases.md#mysqli-execute)
- [Единый API `Randomizer`](11-security-and-random.md#randomizer)
- [Повторяем случайную последовательность для теста](11-security-and-random.md#seeded-random)
- [Получение длины ключа от OpenSSL](11-security-and-random.md#cipher-key-length)
- [Разбор размеров из `php.ini`](12-runtime-and-performance.md#ini-quantities)
- [Измеряем пик памяти отдельного этапа](12-runtime-and-performance.md#measure-memory)
- [Режим создания файла журнала](12-runtime-and-performance.md#ini-and-cli)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration82.php)

<a id="php-83"></a>
## PHP 8.3

- [Готовим значение `static` при первом вызове](01-syntax-and-functions.md#static-initializers)
- [Указываем тип константы класса](02-types.md#typed-constants)
- [Отделяем вложенный объект при клонировании](03-classes-and-objects.md#readonly-cloning)
- [Создаём анонимный `readonly`-класс](03-classes-and-objects.md#readonly-anonymous)
- [Читаем константу по имени из переменной](03-classes-and-objects.md#dynamic-constants)
- [Запрещаем наследнику менять метод трейта](03-classes-and-objects.md#trait-final)
- [Проверяем, что метод действительно переопределён](03-classes-and-objects.md#override)
- [Дополняем строку до нужной длины](05-strings-and-unicode.md#mb-pad)
- [Получаем следующую буквенно-числовую метку](05-strings-and-unicode.md#string-increment)
- [Проверяем JSON без получения его данных](06-json-xml-and-serialization.md#json-validation)
- [Меняем атрибуты и добавляем текст через DOM](06-json-xml-and-serialization.md#dom-convenience)
- [Ловим ошибку в строке даты](07-dates-and-numbers.md#date-exceptions)
- [Повторяющийся период из строки ISO 8601](07-dates-and-numbers.md#iso-period)
- [Несколько параметров потока одним вызовом](09-files-http-and-processes.md#stream-options)
- [Случайные байты из заданного алфавита](11-security-and-random.md#random-alphabet)
- [Случайные дробные числа с явными границами](11-security-and-random.md#random-floats)
- [Запасные значения окружения и lint нескольких файлов](12-runtime-and-performance.md#ini-and-cli)
- [Отдельные статические свойства при повторном подключении трейта наследником](03-classes-and-objects.md#trait-constants)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration83.php)

<a id="php-84"></a>
## PHP 8.4

- [Передаём `exit()` как функцию](01-syntax-and-functions.md#exit-callable)
- [Обрабатываем чтение и запись свойства](03-classes-and-objects.md#property-hooks)
- [Проверяем значение перед записью в свойство](03-classes-and-objects.md#setter-validation)
- [Читать можно всем, менять только классу](03-classes-and-objects.md#asymmetric-visibility)
- [Требуем свойство через интерфейс](03-classes-and-objects.md#interface-properties)
- [Готовим объект только при первом обращении](03-classes-and-objects.md#lazy-objects)
- [Вызываем метод сразу после `new`](03-classes-and-objects.md#new-chaining)
- [Ищем первое значение по условию](04-arrays-and-iteration.md#predicate-search)
- [Проверяем хотя бы один элемент или все сразу](04-arrays-and-iteration.md#predicate-checks)
- [Меняем регистр только первой буквы](05-strings-and-unicode.md#mb-case)
- [Убираем пробелы Unicode по краям](05-strings-and-unicode.md#unicode-trim)
- [Не отрываем знак ударения от буквы](05-strings-and-unicode.md#grapheme-split)
- [Читаем HTML через `Dom\HTMLDocument`](06-json-xml-and-serialization.md#html5-dom)
- [Подставляем строку в XPath без сломанных кавычек](06-json-xml-and-serialization.md#xpath-quote)
- [Сразу выбираем, откуда читать XML и куда писать](06-json-xml-and-serialization.md#xml-factories)
- [Дата из Unix-метки с микросекундами](07-dates-and-numbers.md#timestamp-microseconds)
- [Явное правило округления через `RoundingMode`](07-dates-and-numbers.md#rounding-modes)
- [Десятичная арифметика через `BcMath\Number`](07-dates-and-numbers.md#bcmath-number)
- [Округление десятичных чисел и частное с остатком](07-dates-and-numbers.md#bcmath-rounding)
- [Пометка старого API через `#[Deprecated]`](08-errors-and-debugging.md#deprecated-attribute)
- [Разбор тела формы для других HTTP-методов](09-files-http-and-processes.md#request-body)
- [Чтение последних заголовков HTTP-потока](09-files-http-and-processes.md#http-response-headers)
- [Методы конкретного драйвера через подклассы `PDO`](10-databases.md#pdo-subclasses)
- [Readonly-свойства имеют `protected(set)` по умолчанию](03-classes-and-objects.md#readonly-properties)
- [`fpow()` для IEEE-семантики степени](07-dates-and-numbers.md#ieee-math)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration84.php)

<a id="php-85"></a>
## PHP 8.5

- [Обрабатываем значение по шагам через `|>`](01-syntax-and-functions.md#pipe)
- [Функция как значение по умолчанию](01-syntax-and-functions.md#closures-in-constant-expressions)
- [Замыкание вызывает само себя](01-syntax-and-functions.md#recursive-closures)
- [Преобразуем тип при объявлении константы](01-syntax-and-functions.md#casts-in-constant-expressions)
- [Добавляем атрибут к глобальной константе](01-syntax-and-functions.md#global-constant-attributes)
- [Проверяем переопределение свойства](03-classes-and-objects.md#override-properties)
- [Ограничиваем запись в статическое свойство](03-classes-and-objects.md#static-visibility)
- [Добавляем `final` к свойству в конструкторе](03-classes-and-objects.md#final-promotion)
- [Копируем объект и меняем выбранные свойства](03-classes-and-objects.md#clone-with)
- [Берём первое или последнее значение](04-arrays-and-iteration.md#boundary-values)
- [Собираем список по правилам языка](05-strings-and-unicode.md#intl-lists)
- [Узнаём направление письма для языка](05-strings-and-unicode.md#locale-direction)
- [Сравниваем текст по целым видимым символам](05-strings-and-unicode.md#grapheme-distance)
- [Вставляем готовый фрагмент HTML](06-json-xml-and-serialization.md#html-fragments)
- [Предупреждение, если результат функции потеряли](08-errors-and-debugging.md#nodiscard)
- [Какие обработчики ошибок сейчас установлены](08-errors-and-debugging.md#current-handlers)
- [Стек вызовов для фатальных ошибок](08-errors-and-debugging.md#fatal-backtraces)
- [Меняем части адреса через объект URI](09-files-http-and-processes.md#uri-objects)
- [Повторное использование данных cURL между запросами](09-files-http-and-processes.md#persistent-curl-share)
- [Какие дескрипторы добавлены в cURL multi](09-files-http-and-processes.md#multi-handles)
- [Partitioned-cookie](09-files-http-and-processes.md#cookie-options)
- [Исключение при неудачной проверке](11-security-and-random.md#filter-exceptions)
- [Уточнение результата `static` в `final`-наследниках](02-types.md#static-return)
- [Отложенная проверка целей встроенных атрибутов](03-classes-and-objects.md#attributes)

[Официальное руководство по миграции](https://www.php.net/manual/en/migration85.php)

[Содержание](../../README.ru.md)
