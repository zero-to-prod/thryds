# RequireExhaustiveMatchOnEnumRector

Require `match()` on a backed enum to explicitly cover all cases, preventing new enum cases from silently falling through a `default` arm.

**Category:** Enum Design
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Adding a new case to a backed enum should force the developer to handle it at every `match()` site. A `default` arm silently swallows unhandled cases, breaking the "1 place to change behavior" principle. This rule flags any `match()` on a backed enum where the coverage is not exhaustive — either because cases are missing, or because a `default` covers what should be explicit branches.

## What It Detects

- `return match($var) { ... }` or `$result = match($var) { ... }` where `$var` has a backed enum type (inferred by PHPStan)
- Flags when one or more enum cases are not explicitly listed as match arms
- Does NOT flag when every enum case is listed explicitly (exhaustive without relying on `default`)

### Flagged patterns

```php
// Only default — all cases silently swallowed
return match($status) {
    default => 'unknown',
};

// Missing cases (pending not covered)
return match($status) {
    Status::active => 'Active',
    Status::inactive => 'Inactive',
};

// Some cases explicit, default catches the rest
return match($status) {
    Status::active => 'Active',
    default => 'Other',
};
```

### Not flagged

```php
// All cases explicitly listed — exhaustive
return match($status) {
    Status::active => 'Active',
    Status::inactive => 'Inactive',
    Status::pending => 'Pending',
};
```

## Transformation

### In `auto` mode

No-op — returns `null`. The correct handling of a new enum case is domain-specific and cannot be automated.

### In `warn` mode

Adds a TODO comment above the statement containing the `match()` expression:

```
// TODO: [RequireExhaustiveMatchOnEnumRector] match() on Status must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` is a no-op; `'warn'` adds a TODO comment |
| `message` | `string` | See below | TODO comment template. Use `%s` for the enum short class name. |

Default message:
```
TODO: [RequireExhaustiveMatchOnEnumRector] match() on %s must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md
```

## Example

### Before

```php
function processStatus(Status $status): string
{
    return match($status) {
        Status::active => 'Active',
        default => 'Other',
    };
}
```

### After

```php
function processStatus(Status $status): string
{
    // TODO: [RequireExhaustiveMatchOnEnumRector] match() on Status must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md
    return match($status) {
        Status::active => 'Active',
        default => 'Other',
    };
}
```

## Resolution

When you see the TODO comment from this rule:

1. Identify which enum cases are missing from the `match()` arms.
2. Add an explicit arm for each missing case with the correct return value for your domain.
3. Remove the `default` arm once all cases are covered (or keep it only as a safety net with a clear intention, e.g. throwing an exception for truly unexpected cases).
4. Verify the new arm is tested.

## Caveats

- The rule uses PHPStan type inference (`$this->getType()` on the match condition). It only fires when PHPStan can statically determine the type is a backed enum. If the variable type is unknown or inferred as a union, no flag is added.
- Only `return match(...)` and `$var = match(...)` at the statement level are detected. Match expressions nested deeper (e.g. as function arguments) are not currently flagged.
- Pure enums (no backing type) are not flagged.

## Related Rules

- `RequireClosedSetOnBackedEnumRector` — requires backed enums to carry a `#[ClosedSet]` attribute
- `ForbidStringArgForEnumParamRector` — flags string literals that match backed enum values
- `RequireEnumValueAccessRector` — requires `->value` when using enum cases in string contexts
