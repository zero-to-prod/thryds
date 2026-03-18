# ForbidBareServerEnvKeyRector

Replaces bare string keys in `$_SERVER` and `$_ENV` superglobal accesses with typed class constants from the configured `Env` class.

**Category:** Environment Key Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

Environment variable names are stringly-typed by default. A typo in `$_SERVER['MAX_REQEUSTS']` silently returns `null` at runtime. Centralizing all environment key names as typed `public const string` values on a dedicated `Env` class makes every key discoverable, refactorable, and verifiable by static analysis.

## What It Detects

Any array dimension fetch on `$_SERVER` or `$_ENV` (or whichever superglobals are configured) where the key is a string literal:

```php
$_SERVER['MAX_REQUESTS']
$_ENV['APP_ENV']
```

## Transformation

### In `auto` mode

The bare string is replaced with a class constant fetch on the configured `envClass`. If the constant does not yet exist on that class, the rule writes it into the class file automatically (`public const string KEY_NAME = 'KEY_NAME';`).

```php
// Before
$max = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);

// After
$max = (int) ($_SERVER[Env::MAX_REQUESTS] ?? 0);
```

```php
// Before
$env = $_ENV['APP_ENV'] ?? 'production';

// After
$env = $_ENV[Env::APP_ENV] ?? 'production';
```

### In `warn` mode

A TODO comment is prepended to the statement:

```
// TODO: [ForbidBareServerEnvKeyRector] Use Env::MAX_REQUESTS instead of bare string 'MAX_REQUESTS'.
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `envClass` | `string` | `''` | Fully-qualified class name whose constants hold all env key names |
| `superglobals` | `string[]` | `['_SERVER', '_ENV']` | Variable names to inspect |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | see source | `sprintf` template; receives `(shortClassName, constName, constName)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidBareServerEnvKeyRector::class, [
    'envClass' => Env::class,
    'superglobals' => ['_SERVER', '_ENV'],
    'mode' => 'auto',
    'message' => "TODO: [ForbidBareServerEnvKeyRector] Use %s::%s instead of bare string '%s'.",
]);
```

## Example

### Before

```php
$max = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
```

### After

```php
$max = (int) ($_SERVER[Env::MAX_REQUESTS] ?? 0);
```

## Resolution

When you see the TODO comment from this rule:
1. Open the `Env` class and add `public const string KEY_NAME = 'KEY_NAME';` for each missing key.
2. Replace the bare string `$_SERVER['KEY_NAME']` with `$_SERVER[Env::KEY_NAME]`.
3. Run `./run check:all` to verify.

## Related Rules

- [`RequireEnumValueAccessRector`](RequireEnumValueAccessRector.md) — enforces `->value` on backed enum cases used in string contexts
- [`ForbidStringArgForEnumParamRector`](ForbidStringArgForEnumParamRector.md) — flags string literals that match a known backed enum case value
