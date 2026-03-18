# InlineSingleUseVariableRector

Inlines a variable that is assigned exactly once and used exactly once on the immediately following statement.

**Category:** Code Quality
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

Intermediate variables that exist only to hand a value to the very next statement add noise without aiding readability. They force the reader to hold a name in mind for one line. Inlining removes the indirection and makes the data flow explicit. The rule applies only when the variable cannot be misread (single assignment, single use, no control-structure wrapping, not passed by reference).

## What It Detects

Two consecutive statements where the first is a simple assignment (`$var = expr`) and the second uses `$var` exactly once, with no other assignments to or uses of `$var` anywhere in the scope. The rule is conservative: it skips cases where the use is inside a loop, conditional, closure, or where the variable is passed by reference.

```php
$request_id = RequestId::init($server_request_interface);
$response = handle($request_id);
```

## Transformation

### In `auto` mode

Replaces every read of the variable in the next statement with the assigned expression and removes the assignment statement. Chains collapse iteratively.

### In `warn` mode

Adds a comment above the assignment statement.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(InlineSingleUseVariableRector::class, [
    'mode' => 'auto',
]);
```

## Example

### Before
```php
$request_id = RequestId::init($server_request_interface);
$response = handle($request_id);
```

### After
```php
$response = handle(RequestId::init($server_request_interface));
```

### Before (chain of single-use variables)
```php
$a = foo();
$b = bar($a);
$c = baz($b);
```

### After
```php
$c = baz(bar(foo()));
```

## Related Rules

- [`ExtractRepeatedExpressionToVariableRector`](ExtractRepeatedExpressionToVariableRector.md) — the inverse: extracts repeated expressions that should be named
