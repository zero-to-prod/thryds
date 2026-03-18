# ForbidEnvCheckOutsideConfigRector

Flags direct environment reads (`$_ENV`, `$_SERVER`, `getenv`, `putenv`) that appear outside the designated Config/AppEnv boundary.

**Category:** Environment Key Safety
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Environment-dependent behavior should be resolved once at the config boundary, not scattered across controllers, middleware, and services. Reading `$_ENV`, `$_SERVER`, or calling `getenv`/`putenv` in arbitrary classes:

- Makes it hard to audit which parts of the codebase depend on environment variables.
- Couples business logic to deployment concerns.
- Prevents centralized validation and documentation of required env keys.

The project uses `src/AppEnv.php` and `src/Config.php` as the single source of truth for environment access. All other code should receive config values through constructor injection or method parameters.

## What It Detects

Any `Expression` statement (assignment, standalone call, etc.) that contains one of:

1. `$_ENV['KEY']` — superglobal array access
2. `$_SERVER['KEY']` — superglobal array access
3. `getenv(...)` — function call
4. `putenv(...)` — function call

## Allowlist

The rule does NOT flag env reads in:

- Files named `AppEnv.php` or `Config.php` (the boundary classes)
- Any directory segment named `AppEnv` or `Config` (e.g. `src/Config/SomeClass.php`)
- Files under `scripts/`
- Files under `tests/` (application tests, not `utils/rector/tests/`)
- `public/index.php` (bootstrap)

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to any statement containing a direct env read outside the allowed boundary.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | Built-in default | Comment text to prepend |
| `allowedClasses` | `string[]` | `['AppEnv', 'Config']` | Filenames (without extension) that are allowed to read env |
| `allowedNamespaceParts` | `string[]` | `['AppEnv', 'Config']` | Directory segments that are allowed to read env |
| `envFunctions` | `string[]` | `['getenv', 'putenv']` | Function names to flag |
| `envSuperglobals` | `string[]` | `['_ENV', '_SERVER']` | Superglobal variable names to flag |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidEnvCheckOutsideConfigRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md',
]);
```

## Example

### Before (in a controller or service)
```php
$value = $_ENV['APP_KEY'];
$env = $_SERVER['APP_ENV'];
$db = getenv('DATABASE_URL');
putenv('FOO=bar');
```

### After
```php
// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
$value = $_ENV['APP_KEY'];
// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
$env = $_SERVER['APP_ENV'];
// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
$db = getenv('DATABASE_URL');
// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
putenv('FOO=bar');
```

## Resolution

When you see the TODO comment from this rule:

1. Move the environment read into `src/AppEnv.php` or `src/Config.php`.
2. Expose the resolved value as a typed constant or method return value.
3. Inject the config value into the consuming class via constructor or method parameter.

## Related Rules

- [`ForbidBareServerEnvKeyRector`](ForbidBareServerEnvKeyRector.md) — replaces bare string keys in `$_SERVER`/`$_ENV` with class constants from the `Env` class
