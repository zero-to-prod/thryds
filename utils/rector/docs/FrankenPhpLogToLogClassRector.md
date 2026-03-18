# FrankenPhpLogToLogClassRector

Replaces raw `frankenphp_log()` and `error_log()` calls with typed static calls on the project's `Log` class.

**Category:** Logging
**Mode:** `auto`
**Auto-fix:** Yes

## Rationale

FrankenPHP exposes a low-level `frankenphp_log($message, $level, $context)` function. Using it directly scatters raw function calls with magic integer log levels throughout the codebase and loses the type-safe, structured interface the project's `Log` class provides. This rule migrates those calls to `Log::debug()`, `Log::info()`, `Log::warn()`, or `Log::error()`, dropping the now-redundant level argument and forwarding the context array as the second parameter.

## What It Detects

- Any call to a function listed in `functions` (default: `['frankenphp_log']`)
- `error_log()` calls when included in `functions`

The rule maps the integer level argument to a method name:

| Level integer | Method |
|---------------|--------|
| `-4`          | `debug` |
| `0`           | `info` |
| `4`           | `warn` |
| `8`           | `error` |

When no level argument is present, `info` is used. When the level is a non-integer expression, the node is left unchanged.

## Transformation

### In `auto` mode

The function call is replaced with a static call on the configured `logClass`. The level argument is removed; the message and context array are preserved.

```php
// Before
frankenphp_log('Hello World!', 4, ['key' => 'value']);
frankenphp_log('debug', -4);
frankenphp_log('info', 0);
frankenphp_log('error', 8, ['error' => 'details']);
frankenphp_log('default level');
error_log('something went wrong');

// After
\App\Log::warn('Hello World!', ['key' => 'value']);
\App\Log::debug('debug');
\App\Log::info('info');
\App\Log::error('error', ['error' => 'details']);
\App\Log::info('default level');
\App\Log::error('something went wrong');
```

### In `warn` mode

A TODO comment is prepended to the call. No structural change is made.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `functions` | `string[]` | `['frankenphp_log']` | Function names to replace |
| `logClass` | `string` | `''` | Fully-qualified class name of the target Log class |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(FrankenPhpLogToLogClassRector::class, [
    'functions' => ['frankenphp_log'],
    'logClass' => Log::class,
    'mode' => 'auto',
]);
```

## Example

### Before
```php
frankenphp_log('Hello World!', 4, ['key' => 'value']);
frankenphp_log('debug', -4);
frankenphp_log('info', 0);
frankenphp_log('error', 8, ['error' => 'details']);
frankenphp_log('default level');
```

### After
```php
\Fixture\Log::warn('Hello World!', ['key' => 'value']);
\Fixture\Log::debug('debug');
\Fixture\Log::info('info');
\Fixture\Log::error('error', ['error' => 'details']);
\Fixture\Log::info('default level');
```

## Resolution

This rule is auto-fix only. After Rector runs, verify:
1. Confirm the correct severity method was chosen for each call site.
2. Add a durable event ID to the context array — see [`RequireLogEventRector`](RequireLogEventRector.md).
3. Replace any remaining string context keys with class constants — see [`UseLogContextConstRector`](UseLogContextConstRector.md).

## Related Rules

- [`RequireLogEventRector`](RequireLogEventRector.md) — enforces a durable event identifier in every Log call
- [`UseLogContextConstRector`](UseLogContextConstRector.md) — replaces magic string context keys with class constants
