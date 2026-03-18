# RequireLogEventRector

Flags `Log` method calls that are missing a durable event identifier in their context array.

**Category:** Logging
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Structured logging is only as good as its keys. A free-form message string like `"Something failed"` is human-readable but unsearchable at scale. A durable event ID — a class constant such as `Log::user_login_failed` — gives every log entry a stable, grep-able label that survives message rewording, log aggregator queries, and alerting rules.

This rule enforces the convention: every `Log::debug/info/warn/error()` call must include a context array key whose name matches `eventKey` (default: `'event'`) and whose value is a class constant fetch (e.g. `Log::some_event`), not a bare string.

## What It Detects

A call to the configured `logClass` on any of the `methods` (`debug`, `info`, `warn`, `error` by default) where:
- There is no context array argument, **or**
- The context array does not contain a key named `event` (or the configured `eventKey`), **or**
- The event key's value is not a class constant fetch

## Transformation

### In `auto` mode

Not applicable — this rule only operates in `warn` mode.

### In `warn` mode

A TODO comment is prepended to the log statement:

```
// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
```

The exact text is controlled by the `message` option in `rector.php`.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `logClass` | `string` | `''` | Fully-qualified class name of the Log class to inspect |
| `eventKey` | `string` | `'event'` | The required context key name |
| `methods` | `string[]` | `['debug','info','warn','error']` | Log methods to check |
| `mode` | `string` | `'warn'` | Must be `'warn'`; `'auto'` is a no-op for this rule |
| `message` | `string` | (see source) | TODO comment template; supports `%s` for short class name, event key, and class name |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(RequireLogEventRector::class, [
    'logClass' => Log::class,
    'eventKey' => 'event',
    'mode' => 'warn',
    'message' => 'TODO: [RequireLogEventRector] Log calls need a durable event id. Add `%s::%s => %s::<event_label>` to the context array.',
]);
```

## Example

### Before
```php
Log::error('Something failed', [
    Log::exception => 'RuntimeException',
]);
```

### After
```php
// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::error('Something failed', [
    Log::exception => 'RuntimeException',
]);
```

## Resolution

When you see the TODO comment from this rule:
1. Choose a stable, descriptive constant name for the event (e.g. `user_login_failed`, `payment_declined`).
2. Add `public const string <event_label> = '<event_label>';` to your `Log` class.
3. Add `Log::event => Log::<event_label>` as the first key in the context array.
4. Remove the TODO comment.

## Related Rules

- [`FrankenPhpLogToLogClassRector`](FrankenPhpLogToLogClassRector.md) — migrates raw `frankenphp_log()` calls to the Log class
- [`UseLogContextConstRector`](UseLogContextConstRector.md) — replaces magic string context keys with class constants
