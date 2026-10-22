# PHP 7.0-8.5: a practical guide

[English](README.md) | [Українська](README.uk.md) | [Русский](README.ru.md)

A practical guide to the language and bundled extensions: what each feature solves, how to use it, and where its limits matter. English is the primary edition; Ukrainian and Russian cover the same topics and examples.

- [Read by topic](#topics)
- [Read by version](#versions)
- [Updating existing code](#migration)
- [How to read the examples](#examples)
- [Scope and sources](#scope)

<a id="topics"></a>
## Read by topic

| Chapter | What you will find |
| --- | --- |
| [01. Syntax and functions](docs/en/01-syntax-and-functions.md) | Defaults, closures, named arguments, match, nullsafe chains and pipes. |
| [02. Types and contracts](docs/en/02-types.md) | Strict typing, nullable values, unions, intersections, DNF and typed constants. |
| [03. Classes and objects](docs/en/03-classes-and-objects.md) | Enums, attributes, readonly, property hooks, visibility, lazy objects and cloning. |
| [04. Arrays and iteration](docs/en/04-arrays-and-iteration.md) | Destructuring, unpacking, generators, list checks, searching and array boundaries. |
| [05. Strings and Unicode](docs/en/05-strings-and-unicode.md) | Byte, code point and grapheme boundaries, string search and locale-aware text. |
| [06. JSON, XML, HTML and serialization](docs/en/06-json-xml-and-serialization.md) | JSON errors, validation, object state, HTML5 DOM and XML streams. |
| [07. Dates and numbers](docs/en/07-dates-and-numbers.md) | Immutable dates, timestamps, precise exceptions, rounding and decimal arithmetic. |
| [08. Errors and debugging](docs/en/08-errors-and-debugging.md) | Throwable, useful diagnostics, sensitive arguments and diagnostic attributes. |
| [09. Files, HTTP and processes](docs/en/09-files-http-and-processes.md) | Process arguments, streams, request bodies, URIs, cURL and cookies. |
| [10. Databases](docs/en/10-databases.md) | PDO and mysqli changes, prepared queries, driver classes and SQLite backup. |
| [11. Security and random values](docs/en/11-security-and-random.md) | Secure randomness, seeded tests, passwords, Sodium and validation APIs. |
| [12. Runtime and performance](docs/en/12-runtime-and-performance.md) | Weak references, Fibers, OPcache, JIT, FFI, memory and CLI tools. |

<a id="versions"></a>
## Read by version

The version index links features back to their full explanations. Start there to discover what became available between your current PHP version and a target version.

[Version index: PHP 7.0-8.5](docs/en/VERSIONS.md)

<a id="migration"></a>
## Updating existing code

The migration guide separates useful rewrites from compatibility changes. A newer spelling is only a good replacement when it preserves the behavior you need.

[Migration guide](docs/en/MIGRATION.md)

<a id="start"></a>
## Suggested starting points

New to modern PHP? Read types, syntax, then classes. Updating an application? Start with migration and your target versions. Looking for one function? Open its topic and use the page contents or GitHub file search.

<a id="examples"></a>
## How to read the examples

Every code block is independent unless the text explicitly says otherwise. Copy a PHP example into a file after an opening `<?php` tag; do not combine all snippets into one script. Repeated class and function names are intentional.

A version label names the feature's minimum minor version. If a later addition is used in the same section, the text identifies it. Runtime-sensitive examples describe their HTTP, database, file or extension prerequisites. An expected result may appear as a code comment.

<a id="extensions"></a>
## Core features and extensions

PHP version alone does not guarantee every extension is loaded. For example, mbstring, intl, curl and database drivers depend on your installation. A function can also require a particular library build option. The relevant chapter identifies these dependencies.

<a id="run"></a>
## Run locally

```console
php -v
php -m
php --ri mbstring
php -l example.php
php example.php
```

Check the interpreter, extensions and syntax before executing an example. Lint checks syntax; it does not prove database connectivity, HTTP behavior or the availability of every called function.

<a id="scope"></a>
## Scope and sources

This edition covers useful additions introduced in PHP 7.0 through 8.5. It includes a little older background where needed to explain a new feature. Composer packages and framework APIs are outside its scope. Each feature links to the PHP manual or its official RFC.

Guide edition: 1.0.0. Content reviewed: 22 October 2026. This is a first expanded edition, not a catalogue of every PHP function or patch release. Feature availability is distinct from whether an old PHP branch is still supported.

- [Official migration guides](https://www.php.net/manual/en/appendices.php)
- [Supported PHP versions](https://www.php.net/supported-versions.php)

<a id="earlier"></a>
## Earlier notes

The earlier reference and personal snippets remain available separately:

- [PHP 7.4-8.5](full_useful_notes_7.4-8.5.md)
- [notes.md](notes.md)
