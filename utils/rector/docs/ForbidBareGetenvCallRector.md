# ForbidBareGetenvCallRector

## Purpose

Detects `getenv('SOME_KEY')` calls where the argument is a bare string literal and replaces it with a `ClassConstFetch` from the configured `Env` class (`Env::SOME_KEY`). In `auto` mode it also writes the constant into the Env class file if it does not already exist.

## Config options

| Key | Type | Default | Description |
|---|---|---|---|
| `envClass` | `string` | `''` | Fully qualified class name of the Env constants class |
| `functions` | `string[]` | `['getenv']` | Function names to target |
| `mode` | `'auto'\|'warn'` | `'warn'` | `auto` replaces the string; `warn` adds a TODO comment |
| `message` | `string` | built-in | sprintf template — receives `(shortClassName, constName, constName)` |

## Before / after

### auto mode

```php
// before
return new self(
    host: (string) getenv('DB_HOST'),
    port: (int) (getenv('DB_PORT') ?: 3306),
);

// after
return new self(
    host: (string) getenv(Env::DB_HOST),
    port: (int) (getenv(Env::DB_PORT) ?: 3306),
);
```

### warn mode

```php
// before
$host = (string) getenv('DB_HOST');

// after (comment prepended to statement)
// TODO: [ForbidBareGetenvCallRector] Use Env::DB_HOST instead of bare string 'DB_HOST' in getenv(). See: utils/rector/docs/ForbidBareGetenvCallRector.md
$host = (string) getenv('DB_HOST');
```

## Skipped cases

- First argument is already a `ClassConstFetch` (not a `String_` literal)
- First argument is a variable, not a literal string

## Node types

Processes both `Expression` and `Return_` statement nodes to handle `getenv()` calls that appear inside `return` statements (e.g. `DatabaseConfig::fromEnv()`).

## Caveats

- `addConstantToClassFile` writes directly to the Env class file on disk. If the class is not loaded (no reflection available), the constant is not written.
- In `auto` mode the replacement uses `FullyQualified` names. `importNames()` in `rector.php` will collapse them to short names with a `use` statement automatically.
- The message sprintf template receives three positional arguments: `(shortClassName, constName, constName)`. The third argument mirrors the second so the default message can show both the constant name and the raw string value.

## Registration in rector.php

```php
use Utils\Rector\Rector\ForbidBareGetenvCallRector;

$rectorConfig->ruleWithConfiguration(ForbidBareGetenvCallRector::class, [
    'envClass' => Env::class,
    'functions' => ['getenv'],
    'mode' => 'auto',
    'message' => "TODO: [ForbidBareGetenvCallRector] Use %s::%s instead of bare string '%s' in getenv(). See: utils/rector/docs/ForbidBareGetenvCallRector.md",
]);
```
