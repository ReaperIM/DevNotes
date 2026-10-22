# Features by PHP version

[English](../en/VERSIONS.md) | [Українська](../uk/VERSIONS.md) | [Русский](../ru/VERSIONS.md)

[Contents](../../README.md)

Use this index to find additions and behavior changes by version, then follow the link for examples and limitations. Related improvements may lead to the same section. Older background topics remain in their thematic chapters. These are feature-availability labels, not support-status recommendations.

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

- [A default value with ??](01-syntax-and-functions.md#null-coalescing)
- [Sorting with the spaceship operator <=>](01-syntax-and-functions.md#spaceship)
- [Grouped namespace imports](01-syntax-and-functions.md#group-use)
- [Temporarily binding a closure with call()](01-syntax-and-functions.md#closure-call)
- [Scalar parameters and return types](02-types.md#scalar-types)
- [strict_types: control scalar coercion](02-types.md#strict-types)
- [Anonymous classes](03-classes-and-objects.md#anonymous-classes)
- [Join streams with yield from](04-arrays-and-iteration.md#generator-delegation)
- [Get a generator’s final result](04-arrays-and-iteration.md#generator-return)
- [Divide integers with `intdiv()`](07-dates-and-numbers.md#integer-division)
- [Throwable covers Exception and Error](08-errors-and-debugging.md#throwable)
- [Map regex patterns to replacement callbacks](08-errors-and-debugging.md#regex-callback-map)
- [Read a session and release its lock](09-files-http-and-processes.md#read-only-session)
- [Secure bytes and integers](11-security-and-random.md#secure-random)

[Official migration guide](https://www.php.net/manual/en/migration70.php)

<a id="php-71"></a>
## PHP 7.1

- [Convert a callable with Closure::fromCallable()](01-syntax-and-functions.md#closure-from-callable)
- [Nullable types](02-types.md#nullable)
- [void: finish without a result](02-types.md#void)
- [The iterable type](02-types.md#iterable-object)
- [Class constant visibility](03-classes-and-objects.md#constant-visibility-final)
- [Unpack a row into variables](04-arrays-and-iteration.md#destructuring)
- [Read selected named fields](04-arrays-and-iteration.md#keyed-destructuring)
- [Accept arrays and iterators with `iterable`](04-arrays-and-iteration.md#iterable)
- [Read from the end with negative offsets](05-strings-and-unicode.md#negative-offsets)
- [One catch for several exception types](08-errors-and-debugging.md#multi-catch)

[Official migration guide](https://www.php.net/manual/en/migration71.php)

<a id="php-72"></a>
## PHP 7.2

- [The object type](02-types.md#iterable-object)
- [Choose a policy for invalid UTF-8 in JSON](06-json-xml-and-serialization.md#invalid-utf8-json)
- [Distinguish an unmatched group from an empty match](08-errors-and-debugging.md#unmatched-groups)
- [Argon2i password hashing](11-security-and-random.md#password-algorithms)
- [Authenticated encryption with Sodium](11-security-and-random.md#sodium)

[Official migration guide](https://www.php.net/manual/en/migration72.php)

<a id="php-73"></a>
## PHP 7.3

- [Trailing commas in calls and declarations](01-syntax-and-functions.md#trailing-commas)
- [Destructure by reference to modify the original](04-arrays-and-iteration.md#reference-destructuring)
- [Read first and last keys without moving a pointer](04-arrays-and-iteration.md#boundary-keys)
- [Check whether `count()` is valid](04-arrays-and-iteration.md#countable)
- [Indent heredoc and nowdoc naturally](05-strings-and-unicode.md#heredoc)
- [Handle JSON failures with exceptions](06-json-xml-and-serialization.md#json-exceptions)
- [Cookie options array](09-files-http-and-processes.md#cookie-options)
- [Argon2id password hashing](11-security-and-random.md#password-algorithms)
- [Measure elapsed time with a monotonic clock](12-runtime-and-performance.md#monotonic-time)

[Official migration guide](https://www.php.net/manual/en/migration73.php)

<a id="php-74"></a>
## PHP 7.4

- [Fill missing configuration with ??=](01-syntax-and-functions.md#null-coalescing-assignment)
- [Arrow functions and captured values](01-syntax-and-functions.md#arrow-functions)
- [Readable numeric literals](01-syntax-and-functions.md#numeric-literals)
- [Typed properties and uninitialized state](02-types.md#typed-properties)
- [Covariance and contravariance](02-types.md#variance)
- [Build arrays with ... and understand collisions](04-arrays-and-iteration.md#unpacking)
- [Merge zero or more arrays](04-arrays-and-iteration.md#merge-empty)
- [Split a multibyte string by code points](05-strings-and-unicode.md#mb-split)
- [Pass allowed tag names to `strip_tags()` as an array](05-strings-and-unicode.md#allowed-tags)
- [Control object state with `__serialize()` and `__unserialize()`](06-json-xml-and-serialization.md#object-serialization)
- [Exceptions thrown by __toString()](08-errors-and-debugging.md#to-string-exceptions)
- [Process arguments as an array](09-files-http-and-processes.md#process-arguments)
- [Copy SQLite through its backup API](10-databases.md#sqlite-backup)
- [Available password algorithms with password_algos()](11-security-and-random.md#password-algorithms)
- [Validate a float and its range](11-security-and-random.md#float-validation)
- [Observe an object without keeping it alive](12-runtime-and-performance.md#weak-reference)
- [Preload stable application code](12-runtime-and-performance.md#preloading)
- [FFI for explicit C interoperability](12-runtime-and-performance.md#ffi)

[Official migration guide](https://www.php.net/manual/en/migration74.php)

<a id="php-80"></a>
## PHP 8.0

- [Named arguments](01-syntax-and-functions.md#named-arguments)
- [String keys become named arguments](01-syntax-and-functions.md#unpacking-named-arguments)
- [match returns a value and compares strictly](01-syntax-and-functions.md#match)
- [A nullable object chain with ?->](01-syntax-and-functions.md#nullsafe)
- [throw inside an expression](01-syntax-and-functions.md#throw-expression)
- [Union types: one of several alternatives](02-types.md#union)
- [mixed: an explicitly unrestricted value](02-types.md#mixed)
- [static return type](02-types.md#static-return)
- [Declare properties in the constructor](03-classes-and-objects.md#constructor-promotion)
- [Attributes and reading their metadata](03-classes-and-objects.md#attributes)
- [The Stringable interface](03-classes-and-objects.md#stringable)
- [Stable sorting keeps equal items in order](04-arrays-and-iteration.md#stable-sorting)
- [Search for a substring, prefix or suffix](05-strings-and-unicode.md#string-search)
- [Use explicit IEEE floating-point operations](07-dates-and-numbers.md#ieee-math)
- [Copy dates across mutable and immutable APIs](07-dates-and-numbers.md#date-conversion)
- [catch without an unused variable](08-errors-and-debugging.md#catch-without-variable)
- [ValueError versus TypeError](08-errors-and-debugging.md#value-error)
- [Identify a resource with get_resource_id()](08-errors-and-debugging.md#resource-id)
- [Readable runtime types with get_debug_type()](08-errors-and-debugging.md#debug-types)
- [Understand a failed regular expression](08-errors-and-debugging.md#regex-errors)
- [Read PHP source with PhpToken](08-errors-and-debugging.md#php-token)
- [cURL handles are objects](09-files-http-and-processes.md#curl-objects)
- [PDO exceptions became the default](10-databases.md#pdo-errors)
- [Cache data with weak object keys](12-runtime-and-performance.md#weak-map)
- [JIT and OPcache are different tools](12-runtime-and-performance.md#jit)
- [Trailing commas in parameter declarations](01-syntax-and-functions.md#trailing-commas)

[Official migration guide](https://www.php.net/manual/en/migration80.php)

<a id="php-81"></a>
## PHP 8.1

- [Named arguments after unpacking](01-syntax-and-functions.md#named-after-unpacking)
- [First-class callable syntax: function_name(...)](01-syntax-and-functions.md#first-class-callables)
- [Intersection types: all contracts at once](02-types.md#intersection)
- [never: no normal return](02-types.md#never)
- [Objects as default parameter values](03-classes-and-objects.md#new-initializers)
- [Enums for a closed set of states](03-classes-and-objects.md#enums)
- [Readonly properties and shallow immutability](03-classes-and-objects.md#readonly-properties)
- [Recognize lists and preserve JSON array shape](04-arrays-and-iteration.md#list-arrays)
- [Encode backed enums as their values](06-json-xml-and-serialization.md#json-enums)
- [Flush file data with fsync()](09-files-http-and-processes.md#flush-to-disk)
- [Upload a string as a file](09-files-http-and-processes.md#curl-string-file)
- [Directory uploads and full_path](09-files-http-and-processes.md#upload-paths)
- [mysqli strict reporting became the default](10-databases.md#mysqli-errors)
- [mysqli_stmt::execute() parameter arrays](10-databases.md#mysqli-execute)
- [Read one column directly](10-databases.md#fetch-column)
- [Suspend and resume a call stack](12-runtime-and-performance.md#fibers)
- [Check build capabilities: AVIF in GD](12-runtime-and-performance.md#extension-capabilities)
- [Explicit octal literals with 0o](01-syntax-and-functions.md#numeric-literals)
- [Unpack arrays with string keys](04-arrays-and-iteration.md#unpacking)
- [Final class constants](03-classes-and-objects.md#constant-visibility-final)

[Official migration guide](https://www.php.net/manual/en/migration81.php)

<a id="php-82"></a>
## PHP 8.2

- [DNF: intersections plus alternatives](02-types.md#dnf)
- [Standalone true, false and null](02-types.md#literal-types)
- [Enum properties in constant expressions](03-classes-and-objects.md#enum-constants)
- [Readonly classes](03-classes-and-objects.md#readonly-classes)
- [Constants declared in traits](03-classes-and-objects.md#trait-constants)
- [Dynamic properties are deprecated](03-classes-and-objects.md#dynamic-properties)
- [Keep secret arguments out of stack traces](08-errors-and-debugging.md#sensitive-parameter)
- [mysqli::execute_query() shortcut](10-databases.md#mysqli-execute)
- [One Randomizer API](11-security-and-random.md#randomizer)
- [Reproducible randomness for tests](11-security-and-random.md#seeded-random)
- [Ask OpenSSL for the key length](11-security-and-random.md#cipher-key-length)
- [Parse PHP ini quantities](12-runtime-and-performance.md#ini-quantities)
- [Reset memory peaks for a measured stage](12-runtime-and-performance.md#measure-memory)
- [File-log creation mode](12-runtime-and-performance.md#ini-and-cli)

[Official migration guide](https://www.php.net/manual/en/migration82.php)

<a id="php-83"></a>
## PHP 8.3

- [Expressions in static local initializers](01-syntax-and-functions.md#static-initializers)
- [Typed class constants](02-types.md#typed-constants)
- [Reinitialize readonly properties during __clone()](03-classes-and-objects.md#readonly-cloning)
- [Readonly anonymous classes](03-classes-and-objects.md#readonly-anonymous)
- [A class constant selected by name](03-classes-and-objects.md#dynamic-constants)
- [Import a trait method as final](03-classes-and-objects.md#trait-final)
- [Check overridden methods with #[Override]](03-classes-and-objects.md#override)
- [Pad text by code-point length](05-strings-and-unicode.md#mb-pad)
- [Increment an alphanumeric ASCII label explicitly](05-strings-and-unicode.md#string-increment)
- [Validate JSON only when you do not need its contents](06-json-xml-and-serialization.md#json-validation)
- [Use DOM operations for attributes and text](06-json-xml-and-serialization.md#dom-convenience)
- [Catch specific malformed-date exceptions](07-dates-and-numbers.md#date-exceptions)
- [Create a repeating period from ISO 8601](07-dates-and-numbers.md#iso-period)
- [Several stream options in one call](09-files-http-and-processes.md#stream-options)
- [Random bytes from a chosen alphabet](11-security-and-random.md#random-alphabet)
- [Random floats with explicit bounds](11-security-and-random.md#random-floats)
- [Environment fallbacks and multiple-file lint](12-runtime-and-performance.md#ini-and-cli)
- [Separate static properties when a child reuses a trait](03-classes-and-objects.md#trait-constants)

[Official migration guide](https://www.php.net/manual/en/migration83.php)

<a id="php-84"></a>
## PHP 8.4

- [exit() and die() behave more like functions](01-syntax-and-functions.md#exit-callable)
- [Property hooks: backed and virtual values](03-classes-and-objects.md#property-hooks)
- [A full setter must assign the backing value](03-classes-and-objects.md#setter-validation)
- [Different visibility for reading and writing](03-classes-and-objects.md#asymmetric-visibility)
- [Property contracts in interfaces](03-classes-and-objects.md#interface-properties)
- [Lazy ghosts and lazy proxies](03-classes-and-objects.md#lazy-objects)
- [Call a method directly after new](03-classes-and-objects.md#new-chaining)
- [Find the first matching value or key](04-arrays-and-iteration.md#predicate-search)
- [Ask whether any or all items match](04-arrays-and-iteration.md#predicate-checks)
- [Change the first Unicode character’s case](05-strings-and-unicode.md#mb-case)
- [Remove Unicode whitespace at the edges](05-strings-and-unicode.md#unicode-trim)
- [Split by visible character boundaries](05-strings-and-unicode.md#grapheme-split)
- [Parse modern HTML with `Dom\HTMLDocument`](06-json-xml-and-serialization.md#html5-dom)
- [Quote XPath string values correctly](06-json-xml-and-serialization.md#xpath-quote)
- [Create XML readers and writers with named factories](06-json-xml-and-serialization.md#xml-factories)
- [Create dates from fractional timestamps](07-dates-and-numbers.md#timestamp-microseconds)
- [State the rounding rule with `RoundingMode`](07-dates-and-numbers.md#rounding-modes)
- [Use decimal arithmetic with `BcMath\Number`](07-dates-and-numbers.md#bcmath-number)
- [Round decimals and get quotient plus remainder](07-dates-and-numbers.md#bcmath-rounding)
- [Mark an old API with #[Deprecated]](08-errors-and-debugging.md#deprecated-attribute)
- [Parse form bodies outside POST](09-files-http-and-processes.md#request-body)
- [Read the last stream response headers](09-files-http-and-processes.md#http-response-headers)
- [Driver-specific PDO classes](10-databases.md#pdo-subclasses)
- [Readonly properties use protected(set) by default](03-classes-and-objects.md#readonly-properties)
- [fpow() for IEEE power semantics](07-dates-and-numbers.md#ieee-math)

[Official migration guide](https://www.php.net/manual/en/migration84.php)

<a id="php-85"></a>
## PHP 8.5

- [The pipe operator |>](01-syntax-and-functions.md#pipe)
- [Callbacks in constant expressions](01-syntax-and-functions.md#closures-in-constant-expressions)
- [Recursion with Closure::getCurrent()](01-syntax-and-functions.md#recursive-closures)
- [Type casts in constant expressions](01-syntax-and-functions.md#casts-in-constant-expressions)
- [Attributes on global constants](01-syntax-and-functions.md#global-constant-attributes)
- [Check property overrides with #[Override]](03-classes-and-objects.md#override-properties)
- [Asymmetric visibility for static properties](03-classes-and-objects.md#static-visibility)
- [Final promoted properties](03-classes-and-objects.md#final-promotion)
- [Clone an object with selected property changes](03-classes-and-objects.md#clone-with)
- [Read the first or last value directly](04-arrays-and-iteration.md#boundary-values)
- [Format a list using the reader’s language](05-strings-and-unicode.md#intl-lists)
- [Determine a locale’s default writing direction](05-strings-and-unicode.md#locale-direction)
- [Compare Unicode text by grapheme edit distance](05-strings-and-unicode.md#grapheme-distance)
- [Insert and read HTML fragments](06-json-xml-and-serialization.md#html-fragments)
- [Notice ignored results with #[NoDiscard]](08-errors-and-debugging.md#nodiscard)
- [Inspect the current error and exception handlers](08-errors-and-debugging.md#current-handlers)
- [Backtraces for fatal errors](08-errors-and-debugging.md#fatal-backtraces)
- [Read and modify URI objects](09-files-http-and-processes.md#uri-objects)
- [Reuse cURL data between requests](09-files-http-and-processes.md#persistent-curl-share)
- [List handles in a multi handle](09-files-http-and-processes.md#multi-handles)
- [Partitioned cookies](09-files-http-and-processes.md#cookie-options)
- [Throw when validation fails](11-security-and-random.md#filter-exceptions)
- [Narrow static returns in final subclasses](02-types.md#static-return)
- [Delayed validation of built-in attribute targets](03-classes-and-objects.md#attributes)

[Official migration guide](https://www.php.net/manual/en/migration85.php)

[Contents](../../README.md)
