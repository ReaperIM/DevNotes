# PHP versions

[EN](../en/VERSIONS.md) | [UK](../uk/VERSIONS.md) | [RU](../ru/VERSIONS.md)

[Contents](../../README.md)

Remember roughly which PHP version added a feature? Pick it here and follow the link to the explanation and example. When a feature changed again later, it can appear under more than one version.

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

- [A fallback value with `??`](01-syntax-and-functions.md#null-coalescing)
- [Sort with `<=>`](01-syntax-and-functions.md#spaceship)
- [Group related `use` imports](01-syntax-and-functions.md#group-use)
- [Call a closure with a chosen `$this`](01-syntax-and-functions.md#closure-call)
- [Parameter and return types](02-types.md#scalar-types)
- [Keep types strict with `strict_types`](02-types.md#strict-types)
- [Create a class right where it is needed](03-classes-and-objects.md#anonymous-classes)
- [Yield values from several sources with `yield from`](04-arrays-and-iteration.md#generator-delegation)
- [Get a generator’s final result](04-arrays-and-iteration.md#generator-return)
- [Divide integers with `intdiv()`](07-dates-and-numbers.md#integer-division)
- [`Throwable` covers `Exception` and `Error`](08-errors-and-debugging.md#throwable)
- [Map regex patterns to replacement callbacks](08-errors-and-debugging.md#regex-callback-map)
- [Read a session and release the lock immediately](09-files-http-and-processes.md#read-only-session)
- [Secure bytes and integers](11-security-and-random.md#secure-random)

[Official migration guide](https://www.php.net/manual/en/migration70.php)

<a id="php-71"></a>
## PHP 7.1

- [Make a closure with `Closure::fromCallable()`](01-syntax-and-functions.md#closure-from-callable)
- [Allow `null` with `?T`](02-types.md#nullable)
- [Do something without returning a value: `void`](02-types.md#void)
- [The `iterable` type](02-types.md#iterable-object)
- [Class constant visibility](03-classes-and-objects.md#constant-visibility-final)
- [Unpack an array into variables](04-arrays-and-iteration.md#destructuring)
- [Take selected fields by key](04-arrays-and-iteration.md#keyed-destructuring)
- [Iterate over arrays and iterators the same way](04-arrays-and-iteration.md#iterable)
- [Read a string from the end](05-strings-and-unicode.md#negative-offsets)
- [One `catch` for several exception types](08-errors-and-debugging.md#multi-catch)

[Official migration guide](https://www.php.net/manual/en/migration71.php)

<a id="php-72"></a>
## PHP 7.2

- [The `object` type](02-types.md#iterable-object)
- [Handle damaged UTF-8 in JSON](06-json-xml-and-serialization.md#invalid-utf8-json)
- [Distinguish an unmatched group from an empty match](08-errors-and-debugging.md#unmatched-groups)
- [Argon2i password hashing](11-security-and-random.md#password-algorithms)
- [Encrypt a message and check that it is unchanged](11-security-and-random.md#sodium)

[Official migration guide](https://www.php.net/manual/en/migration72.php)

<a id="php-73"></a>
## PHP 7.3

- [Trailing commas in calls and declarations](01-syntax-and-functions.md#trailing-commas)
- [Change the original element through a reference](04-arrays-and-iteration.md#reference-destructuring)
- [Get the first and last keys](04-arrays-and-iteration.md#boundary-keys)
- [Check whether `count()` can be used](04-arrays-and-iteration.md#countable)
- [Keep multiline text neatly indented](05-strings-and-unicode.md#heredoc)
- [Catch a JSON error as an exception](06-json-xml-and-serialization.md#json-exceptions)
- [Cookie options array](09-files-http-and-processes.md#cookie-options)
- [Argon2id password hashing](11-security-and-random.md#password-algorithms)
- [Measure elapsed time with `hrtime()`](12-runtime-and-performance.md#monotonic-time)

[Official migration guide](https://www.php.net/manual/en/migration73.php)

<a id="php-74"></a>
## PHP 7.4

- [Fill in settings with `??=`](01-syntax-and-functions.md#null-coalescing-assignment)
- [Short functions with `fn`](01-syntax-and-functions.md#arrow-functions)
- [Readable numeric literals](01-syntax-and-functions.md#numeric-literals)
- [Give a property a type](02-types.md#typed-properties)
- [Refine types in a child class](02-types.md#variance)
- [Build an array with `...` and check duplicate keys](04-arrays-and-iteration.md#unpacking)
- [Merge batches even when there are none](04-arrays-and-iteration.md#merge-empty)
- [Split text without cutting through a letter’s bytes](05-strings-and-unicode.md#mb-split)
- [Pass allowed tags as an array](05-strings-and-unicode.md#allowed-tags)
- [Choose what to save and restore on an object](06-json-xml-and-serialization.md#object-serialization)
- [Exceptions thrown by `__toString()`](08-errors-and-debugging.md#to-string-exceptions)
- [Process arguments as an array](09-files-http-and-processes.md#process-arguments)
- [Copy an open SQLite database](10-databases.md#sqlite-backup)
- [Available password algorithms with `password_algos()`](11-security-and-random.md#password-algorithms)
- [Validate a `float` and its range](11-security-and-random.md#float-validation)
- [Observe an object without keeping it alive](12-runtime-and-performance.md#weak-reference)
- [Preload stable application code](12-runtime-and-performance.md#preloading)
- [Work with C through `FFI`](12-runtime-and-performance.md#ffi)

[Official migration guide](https://www.php.net/manual/en/migration74.php)

<a id="php-80"></a>
## PHP 8.0

- [Pass arguments by name](01-syntax-and-functions.md#named-arguments)
- [Argument names from array keys](01-syntax-and-functions.md#unpacking-named-arguments)
- [Choose a value with `match`](01-syntax-and-functions.md#match)
- [Read an object chain with `?->`](01-syntax-and-functions.md#nullsafe)
- [Throw directly from an expression](01-syntax-and-functions.md#throw-expression)
- [Allow one of several types: `A|B`](02-types.md#union)
- [Accept any value with `mixed`](02-types.md#mixed)
- [Return type `static`](02-types.md#static-return)
- [Declare properties in the constructor](03-classes-and-objects.md#constructor-promotion)
- [Read attributes attached to code](03-classes-and-objects.md#attributes)
- [Accept a string or an object with `__toString()`](03-classes-and-objects.md#stringable)
- [Keep the original order when values compare equal](04-arrays-and-iteration.md#stable-sorting)
- [Check the contents, start or end of a string](05-strings-and-unicode.md#string-search)
- [Get `INF` or `NAN` from floating-point operations](07-dates-and-numbers.md#ieee-math)
- [Copy a date into `DateTime` or `DateTimeImmutable`](07-dates-and-numbers.md#date-conversion)
- [`catch` without an unused variable](08-errors-and-debugging.md#catch-without-variable)
- [`ValueError` versus `TypeError`](08-errors-and-debugging.md#value-error)
- [Identify a resource with `get_resource_id()`](08-errors-and-debugging.md#resource-id)
- [Read a value type with `get_debug_type()`](08-errors-and-debugging.md#debug-types)
- [Understand a failed regular expression](08-errors-and-debugging.md#regex-errors)
- [Read PHP source with `PhpToken`](08-errors-and-debugging.md#php-token)
- [cURL handles are objects](09-files-http-and-processes.md#curl-objects)
- [`PDO` errors now throw exceptions](10-databases.md#pdo-errors)
- [A cache that does not keep its object keys alive](12-runtime-and-performance.md#weak-map)
- [What JIT and OPcache each do](12-runtime-and-performance.md#jit)
- [Trailing commas in parameter declarations](01-syntax-and-functions.md#trailing-commas)

[Official migration guide](https://www.php.net/manual/en/migration80.php)

<a id="php-81"></a>
## PHP 8.1

- [Add a named argument after unpacking](01-syntax-and-functions.md#named-after-unpacking)
- [Get a function with `function_name(...)`](01-syntax-and-functions.md#first-class-callables)
- [Require both types with `A&B`](02-types.md#intersection)
- [A function that does not return: `never`](02-types.md#never)
- [Use an object as a default argument](03-classes-and-objects.md#new-initializers)
- [Define allowed states with an enum](03-classes-and-objects.md#enums)
- [What a `readonly` property protects](03-classes-and-objects.md#readonly-properties)
- [Check for a list before encoding JSON](04-arrays-and-iteration.md#list-arrays)
- [Encode an enum using its value](06-json-xml-and-serialization.md#json-enums)
- [Flush file data with `fsync()`](09-files-http-and-processes.md#flush-to-disk)
- [Upload a string as a file](09-files-http-and-processes.md#curl-string-file)
- [Directory uploads and `full_path`](09-files-http-and-processes.md#upload-paths)
- [How default `mysqli` error handling changed](10-databases.md#mysqli-errors)
- [`mysqli_stmt::execute()` parameter arrays](10-databases.md#mysqli-execute)
- [Read one column directly](10-databases.md#fetch-column)
- [Pause and resume a function with `Fiber`](12-runtime-and-performance.md#fibers)
- [Check build capabilities: AVIF in GD](12-runtime-and-performance.md#extension-capabilities)
- [Explicit octal literals with `0o`](01-syntax-and-functions.md#numeric-literals)
- [Unpack arrays with string keys](04-arrays-and-iteration.md#unpacking)
- [Final class constants](03-classes-and-objects.md#constant-visibility-final)

[Official migration guide](https://www.php.net/manual/en/migration81.php)

<a id="php-82"></a>
## PHP 8.2

- [Combine requirements and alternatives: `(A&B)|C`](02-types.md#dnf)
- [Return exactly `true`, `false` or `null`](02-types.md#literal-types)
- [Use an enum name or value in a constant](03-classes-and-objects.md#enum-constants)
- [Make every property `readonly`](03-classes-and-objects.md#readonly-classes)
- [Constants declared in traits](03-classes-and-objects.md#trait-constants)
- [Avoid creating properties by accident](03-classes-and-objects.md#dynamic-properties)
- [Keep secret arguments out of stack traces](08-errors-and-debugging.md#sensitive-parameter)
- [`mysqli::execute_query()` shortcut](10-databases.md#mysqli-execute)
- [One `Randomizer` API](11-security-and-random.md#randomizer)
- [Repeat a random sequence for a test](11-security-and-random.md#seeded-random)
- [Ask OpenSSL for the key length](11-security-and-random.md#cipher-key-length)
- [Parse PHP ini quantities](12-runtime-and-performance.md#ini-quantities)
- [Measure the memory peak of one stage](12-runtime-and-performance.md#measure-memory)
- [File-log creation mode](12-runtime-and-performance.md#ini-and-cli)

[Official migration guide](https://www.php.net/manual/en/migration82.php)

<a id="php-83"></a>
## PHP 8.3

- [Initialize a local `static` value on first use](01-syntax-and-functions.md#static-initializers)
- [Give a class constant a type](02-types.md#typed-constants)
- [Separate a nested object when cloning](03-classes-and-objects.md#readonly-cloning)
- [Create an anonymous `readonly` class](03-classes-and-objects.md#readonly-anonymous)
- [Read a constant using a name from a variable](03-classes-and-objects.md#dynamic-constants)
- [Prevent a child from overriding a trait method](03-classes-and-objects.md#trait-final)
- [Check that a method really overrides something](03-classes-and-objects.md#override)
- [Pad a string to the required length](05-strings-and-unicode.md#mb-pad)
- [Get the next alphanumeric label](05-strings-and-unicode.md#string-increment)
- [Validate JSON without building its data](06-json-xml-and-serialization.md#json-validation)
- [Change attributes and insert text with DOM](06-json-xml-and-serialization.md#dom-convenience)
- [Catch an invalid date string](07-dates-and-numbers.md#date-exceptions)
- [Create a repeating period from ISO 8601](07-dates-and-numbers.md#iso-period)
- [Several stream options in one call](09-files-http-and-processes.md#stream-options)
- [Random bytes from a chosen alphabet](11-security-and-random.md#random-alphabet)
- [Random floats with explicit bounds](11-security-and-random.md#random-floats)
- [Environment fallbacks and multiple-file lint](12-runtime-and-performance.md#ini-and-cli)
- [Separate `static` properties when a child reuses a trait](03-classes-and-objects.md#trait-constants)

[Official migration guide](https://www.php.net/manual/en/migration83.php)

<a id="php-84"></a>
## PHP 8.4

- [Pass `exit()` around as a function](01-syntax-and-functions.md#exit-callable)
- [Handle property reads and writes](03-classes-and-objects.md#property-hooks)
- [Check a value before storing it](03-classes-and-objects.md#setter-validation)
- [Let everyone read, but only the class write](03-classes-and-objects.md#asymmetric-visibility)
- [Require a property through an interface](03-classes-and-objects.md#interface-properties)
- [Initialize an object only when it is needed](03-classes-and-objects.md#lazy-objects)
- [Call a method directly after `new`](03-classes-and-objects.md#new-chaining)
- [Find the first value that matches a condition](04-arrays-and-iteration.md#predicate-search)
- [Check whether any item or every item matches](04-arrays-and-iteration.md#predicate-checks)
- [Change only the first character’s case](05-strings-and-unicode.md#mb-case)
- [Trim Unicode whitespace at the edges](05-strings-and-unicode.md#unicode-trim)
- [Keep the accent with its letter](05-strings-and-unicode.md#grapheme-split)
- [Read HTML with `Dom\HTMLDocument`](06-json-xml-and-serialization.md#html5-dom)
- [Put a string into XPath without breaking its quotes](06-json-xml-and-serialization.md#xpath-quote)
- [Choose where to read and write XML at creation](06-json-xml-and-serialization.md#xml-factories)
- [A date from a Unix timestamp with microseconds](07-dates-and-numbers.md#timestamp-microseconds)
- [State the rounding rule with `RoundingMode`](07-dates-and-numbers.md#rounding-modes)
- [Use decimal arithmetic with `BcMath\Number`](07-dates-and-numbers.md#bcmath-number)
- [Round decimals and get quotient plus remainder](07-dates-and-numbers.md#bcmath-rounding)
- [Mark an old API with `#[Deprecated]`](08-errors-and-debugging.md#deprecated-attribute)
- [Parse form bodies outside `POST`](09-files-http-and-processes.md#request-body)
- [Read the last stream response headers](09-files-http-and-processes.md#http-response-headers)
- [Driver methods through `PDO` subclasses](10-databases.md#pdo-subclasses)
- [Readonly properties use `protected(set)` by default](03-classes-and-objects.md#readonly-properties)
- [`fpow()` for IEEE power semantics](07-dates-and-numbers.md#ieee-math)

[Official migration guide](https://www.php.net/manual/en/migration84.php)

<a id="php-85"></a>
## PHP 8.5

- [Process a value step by step with `|>`](01-syntax-and-functions.md#pipe)
- [Use a function as a default value](01-syntax-and-functions.md#closures-in-constant-expressions)
- [Let a closure call itself](01-syntax-and-functions.md#recursive-closures)
- [Cast a value when declaring a constant](01-syntax-and-functions.md#casts-in-constant-expressions)
- [Add an attribute to a global constant](01-syntax-and-functions.md#global-constant-attributes)
- [Check that a property overrides something](03-classes-and-objects.md#override-properties)
- [Restrict writes to a static property](03-classes-and-objects.md#static-visibility)
- [Add `final` to a promoted property](03-classes-and-objects.md#final-promotion)
- [Clone an object and change selected properties](03-classes-and-objects.md#clone-with)
- [Get the first or last value](04-arrays-and-iteration.md#boundary-values)
- [Format a list for the reader’s language](05-strings-and-unicode.md#intl-lists)
- [Find a locale’s writing direction](05-strings-and-unicode.md#locale-direction)
- [Compare text using whole visible characters](05-strings-and-unicode.md#grapheme-distance)
- [Insert a ready-made HTML fragment](06-json-xml-and-serialization.md#html-fragments)
- [Warn when a function result is discarded](08-errors-and-debugging.md#nodiscard)
- [Which error handlers are currently installed](08-errors-and-debugging.md#current-handlers)
- [Backtraces for fatal errors](08-errors-and-debugging.md#fatal-backtraces)
- [Change address parts through a URI object](09-files-http-and-processes.md#uri-objects)
- [Reuse cURL data between requests](09-files-http-and-processes.md#persistent-curl-share)
- [Which handles have been added to cURL multi](09-files-http-and-processes.md#multi-handles)
- [Partitioned cookies](09-files-http-and-processes.md#cookie-options)
- [Throw when validation fails](11-security-and-random.md#filter-exceptions)
- [Narrow `static` returns in `final` subclasses](02-types.md#static-return)
- [Delayed validation of built-in attribute targets](03-classes-and-objects.md#attributes)

[Official migration guide](https://www.php.net/manual/en/migration85.php)

[Contents](../../README.md)
