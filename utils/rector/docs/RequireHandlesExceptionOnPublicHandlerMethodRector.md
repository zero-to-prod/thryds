# RequireHandlesExceptionOnPublicHandlerMethodRector

TODO: One-sentence description of what the rule enforces.

**Category:** TODO
**Mode:** `warn`
**Auto-fix:** No

## Rationale

TODO: Why this rule exists. The principle or project convention it enforces.

## What It Detects

TODO: The code pattern(s) that trigger this rule.

## Transformation

### In `auto` mode

TODO: Describe exactly what change is made to the code. (Remove this section if auto is a no-op.)

### In `warn` mode

```
// TODO: [RequireHandlesExceptionOnPublicHandlerMethodRector] Public method %s::%s accepts a Throwable subtype but is missing #[HandlesException] — it will never be dispatched. See: utils/rector/docs/RequireHandlesExceptionOnPublicHandlerMethodRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |

## Example

### Before

```php
// TODO: add example from test fixture
```

### After

```php
// TODO: add example from test fixture
```

## Resolution

When you see the TODO comment from this rule:

1. TODO: step one
2. TODO: step two

## Related Rules

None yet.