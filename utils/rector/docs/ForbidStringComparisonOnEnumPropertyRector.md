# ForbidStringComparisonOnEnumPropertyRector

Rewrites `$object->enumProp->value === 'string'` comparisons to compare the enum property directly against its case, eliminating the `->value` bypass.

**Category:** Enum Value Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

Comparing an enum property's backing value against a string (`$Config->AppEnv->value === 'production'`) works but bypasses PHP's type system. If `AppEnv` is later refactored, the string literal becomes stale silently. The idiomatic comparison is `$Config->AppEnv === AppEnv::production`, which the type checker understands and which survives enum case renames (when paired with `RenameEnumCaseToMatchValueRector`).

## What It Detects

`===` or `!==` binary expressions where:
- One side is a `PropertyFetch` with name `value` on an expression whose type is one of the configured enum classes.
- The other side is a `String_` literal whose value matches a known backing value of that enum.

Type resolution uses PHPStan scope first, then falls back to an AST-level property type cache built from `Class_` declarations in the same run.

## Transformation

### In `auto` mode

The comparison is rewritten in-place:
- `$Config->AppEnv->value === 'production'` becomes `$Config->AppEnv === AppEnv::production`
- The `->value` accessor is removed and the string is replaced with the fully-qualified enum case fetch.
- Reversed operand order is also handled: `'production' === $Config->AppEnv->value` becomes `AppEnv::production === $Config->AppEnv`.

```php
// Before
$result = $Config->AppEnv->value === 'production';

// After
$result = $Config->AppEnv === \TestAppEnv::production;
```

### In `warn` mode

A TODO comment is prepended to the comparison expression:

```
// TODO: [ForbidStringComparisonOnEnumPropertyRector] Compare against AppEnv::production instead of string 'production'.
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClasses` | `string[]` | `[]` | Fully-qualified names of the backed enums whose properties to watch |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | see source | `sprintf` template; receives `(shortClassName, caseName, stringValue)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(ForbidStringComparisonOnEnumPropertyRector::class, [
    'enumClasses' => [
        AppEnv::class,
        Route::class,
        HTTP_METHOD::class,
        LogLevel::class,
        View::class,
    ],
    'mode' => 'warn',
    'message' => "TODO: [ForbidStringComparisonOnEnumPropertyRector] Compare against %s::%s instead of string '%s'.",
]);
```

## Example

### Before

```php
class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$result = $Config->AppEnv->value === 'production';
```

### After

```php
class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$result = $Config->AppEnv === \TestAppEnv::production;
```

## Resolution

When you see the TODO comment from this rule:
1. Replace `$obj->enumProp->value === 'some_value'` with `$obj->enumProp === EnumClass::caseName`.
2. Verify the enum case name in the TODO comment matches the intended case.
3. For `!==` comparisons, the same substitution applies.

## Related Rules

- [`RequireEnumValueAccessRector`](RequireEnumValueAccessRector.md) — appends `->value` in string argument contexts (complementary direction)
- [`ForbidStringArgForEnumParamRector`](ForbidStringArgForEnumParamRector.md) — flags string literals matching an enum case value
