# UseLogContextConstRector

Replaces magic string keys in `Log` context arrays with class constant fetches, and adds any missing constants to the `Log` class.

**Category:** Logging
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

String keys like `'exception'`, `'file'`, and `'line'` in log context arrays are magic values. They can be mistyped, differ across call sites, and resist IDE navigation. The project convention is to define these keys as string constants directly on the `Log` class (`public const string exception = 'exception'`) and always reference them as `Log::exception`. This makes the full set of known context keys discoverable from a single class, enables IDE go-to-definition, and ensures consistency across every log call.

## What It Detects

A call to the configured `logClass` on any of the `methods` (`debug`, `info`, `warn`, `error`) where a context array contains a string-literal key whose name appears in the configured `keys` list (default: `['exception', 'file', 'line']`).

## Transformation

### In `auto` mode

Each matching string key is replaced with a class constant fetch on the `logClass`. If the constant does not yet exist on the class, it is added automatically (`public const string <name> = '<name>';`).

```php
// Before
Log::error('Something failed', [
    'exception' => 'RuntimeException',
    'file' => '/app/index.php',
    'line' => 42,
]);

// After
Log::error('Something failed', [
    \Fixture\Log::exception => 'RuntimeException',
    \Fixture\Log::file => '/app/index.php',
    \Fixture\Log::line => 42,
]);
```

### In `warn` mode

A TODO comment is prepended to the `Log::*()` call. No key replacement occurs.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `logClass` | `string` | `''` | Fully-qualified class name of the Log class |
| `keys` | `string[]` | `[]` | Context key names to enforce as constants |
| `methods` | `string[]` | `['debug','info','warn','error']` | Log methods to inspect |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | TODO comment text (used in `warn` mode) |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
    'logClass' => Log::class,
    'keys' => ['exception', 'file', 'line'],
    'mode' => 'auto',
]);
```

## Example

### Before
```php
Log::error('Something failed', [
    'exception' => 'RuntimeException',
    'file' => '/app/index.php',
    'line' => 42,
]);
```

### After
```php
Log::error('Something failed', [
    \Fixture\Log::exception => 'RuntimeException',
    \Fixture\Log::file => '/app/index.php',
    \Fixture\Log::line => 42,
]);
```

## Resolution

When you see the TODO comment from this rule (in `warn` mode):
1. Add the missing constant to your `Log` class: `public const string exception = 'exception';`
2. Replace the string key with the constant: `Log::exception =>`.
3. Remove the TODO comment.

## Related Rules

- [`FrankenPhpLogToLogClassRector`](FrankenPhpLogToLogClassRector.md) — migrates raw `frankenphp_log()` calls to the Log class
- [`RequireLogEventRector`](RequireLogEventRector.md) — enforces a durable event identifier in every Log call
