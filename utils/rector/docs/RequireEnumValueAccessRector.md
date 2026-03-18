# RequireEnumValueAccessRector

Appends `->value` to backed enum case fetches that appear in string contexts (string-typed function arguments and concatenation expressions).

**Category:** Enum Value Safety
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

A backed enum case such as `View::home` is an enum object, not a string. Passing it to a parameter typed `string` is a type error; PHP will throw a `TypeError` at runtime. The backing value is accessed via `->value`. This rule catches every site where an enum case is used in a string context without the required `->value` accessor.

## What It Detects

- A `ClassConstFetch` on a configured enum class passed as an argument to a parameter whose declared type is `string`.
- A `ClassConstFetch` on a configured enum class used as the left or right operand of a string concatenation (`.`).

The rule skips expressions that already have `->value` appended.

## Transformation

### In `auto` mode

`->value` is appended directly to the enum case fetch in the AST.

```php
// Before — string argument
make(\TestView::home);

// After
make(\TestView::home->value);
```

```php
// Before — concatenation
$result = 'prefix-' . \TestView::home;

// After
$result = 'prefix-' . \TestView::home->value;
```

### In `warn` mode

A TODO comment is prepended to the offending statement:

```
// TODO: [RequireEnumValueAccessRector] TestView::home is a backed enum case — use ->value to get the string.
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClasses` | `string[]` | `[]` | Fully-qualified names of the backed enums to watch |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | see source | `sprintf` template; receives `(className, caseName)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireEnumValueAccessRector::class, [
    'enumClasses' => [
        View::class,
        Route::class,
        HTTP_METHOD::class,
        AppEnv::class,
        LogLevel::class,
    ],
    'mode' => 'auto',
    'message' => 'TODO: [RequireEnumValueAccessRector] %s::%s is a backed enum case — use ->value to get the string.',
]);
```

## Example

### Before

```php
function make(string $view): void {}

make(\TestView::home);
```

### After

```php
function make(string $view): void {}

make(\TestView::home->value);
```

## Resolution

When you see the TODO comment from this rule:
1. Locate the enum case reference (e.g., `View::home`).
2. Append `->value` to obtain the backing string: `View::home->value`.
3. If the call site accepts an enum type, remove `->value` and update the parameter type instead.

## Related Rules

- [`ForbidStringComparisonOnEnumPropertyRector`](ForbidStringComparisonOnEnumPropertyRector.md) — rewrites `$obj->enumProp->value === 'string'` to a direct enum comparison
- [`ForbidStringArgForEnumParamRector`](ForbidStringArgForEnumParamRector.md) — flags string literals that match a known enum case value
