# ForbidStringArgForEnumParamRector

Flags string literals whose value matches a known backed enum case, signaling that the enum case reference should be used instead.

**Category:** Enum Value Safety
**Mode:** `warn` only (no auto-fix)
**Auto-fix:** No

## Rationale

When a backed enum exists for a set of values (e.g., `AppEnv::production`, `HTTP_METHOD::GET`), using a raw string like `'production'` bypasses type safety and makes the value opaque. This rule identifies every statement that contains a string literal matching a configured enum's backing value and adds a TODO comment directing the developer to replace it with the enum case.

String arguments inside string-manipulation functions (`str_contains`, `preg_match`, `explode`, `sprintf`, etc.) are skipped, as those operate on the string value itself and are not substitution sites.

## What It Detects

Any statement containing a `String_` node whose value exactly matches a backing value of one of the configured enums, unless:
- The string is already wrapped in an enum case `->value` expression.
- The string appears as a direct argument to a string-manipulation function (see the `skipFunctions` list in the source).
- The string is shorter than `minLength` characters.

## Transformation

### In `warn` mode

A TODO comment is prepended to the statement:

```
// TODO: [ForbidStringArgForEnumParamRector] 'production' matches AppEnv::production — use AppEnv::production->value.
```

This rule has no `auto` mode. Setting `mode` to `'auto'` is a no-op — the rule returns `null` immediately.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClasses` | `string[]` | `[]` | Fully-qualified names of the backed enums whose values to match |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` is a no-op |
| `message` | `string` | see source | `sprintf` template; receives `(stringValue, shortClassName, caseName, shortClassName, caseName)` |
| `minLength` | `int` | `2` | Strings shorter than this are ignored |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidStringArgForEnumParamRector::class, [
    'enumClasses' => [
        AppEnv::class,
        HTTP_METHOD::class,
        Route::class,
        View::class,
        LogLevel::class,
        ButtonVariant::class,
        ButtonSize::class,
        AlertVariant::class,
        InputType::class,
    ],
    'mode' => 'warn',
    'message' => "TODO: [ForbidStringArgForEnumParamRector] '%s' matches %s::%s — use %s::%s->value.",
]);
```

## Example

### Before

```php
$env = 'production';
```

### After

```php
// TODO: [ForbidStringArgForEnumParamRector] 'production' matches TestAppEnv::production — use TestAppEnv::production->value.
$env = 'production';
```

## Resolution

When you see the TODO comment from this rule:
1. Identify which enum and case the string matches (shown in the comment).
2. Replace the bare string with `EnumClass::caseName->value` (e.g., `AppEnv::production->value`).
3. If the receiving parameter or variable accepts the enum type, use `EnumClass::caseName` directly (no `->value`).

## Related Rules

- [`RequireEnumValueAccessRector`](RequireEnumValueAccessRector.md) — appends `->value` to enum cases in string-typed argument positions
- [`ForbidStringComparisonOnEnumPropertyRector`](ForbidStringComparisonOnEnumPropertyRector.md) — rewrites `$obj->enumProp->value === 'string'` comparisons
