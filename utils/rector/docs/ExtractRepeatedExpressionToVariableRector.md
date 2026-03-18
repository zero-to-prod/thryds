# ExtractRepeatedExpressionToVariableRector

Extracts repeated calls to a configured set of pure functions into a single variable assigned before the first use.

**Category:** Code Quality
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

Calling the same pure function with the same arguments more than once in the same scope is redundant work and a maintenance burden: if the argument changes, every occurrence must be updated. Assigning the result once to a named variable also makes the intent explicit. This rule is particularly relevant for `dirname(__DIR__)` in configuration files, which is often repeated multiple times across path constructions.

## What It Detects

Any call to a function listed in the `functions` configuration that appears two or more times in the same statement-list scope with identical arguments. The comparison is done on the printed representation of the call (excluding comments).

```php
$a = dirname(__DIR__) . '/var/cache';
$b = dirname(__DIR__) . '/templates';
```

## Transformation

### In `auto` mode

1. Identifies all repeated calls within the same statement list.
2. Inserts a variable assignment (`$varName = funcCall(...)`) immediately before the first statement that contains the call. For `dirname(__DIR__)`, the variable is named `$baseDir`; for other functions the variable is named after the function.
3. Replaces every occurrence of the call within the scope with the variable.

### In `warn` mode

Adds a comment above the first statement containing the repeated call.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `functions` | `string[]` | `[]` | List of pure function names to track for repetition |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(ExtractRepeatedExpressionToVariableRector::class, [
    'functions' => ['dirname'],
    'mode' => 'auto',
]);
```

## Example

### Before
```php
require dirname(__DIR__) . '/vendor/autoload.php';
$a = dirname(__DIR__) . '/var/cache';
$b = dirname(__DIR__) . '/templates';
```

### After
```php
$baseDir = dirname(__DIR__);
require $baseDir . '/vendor/autoload.php';
$a = $baseDir . '/var/cache';
$b = $baseDir . '/templates';
```

## Related Rules

- [`InlineSingleUseVariableRector`](InlineSingleUseVariableRector.md) — the inverse operation: removes variables used only once on the next line
- [`SuggestDuplicateStringConstantRector`](SuggestDuplicateStringConstantRector.md) — flags repeated string literals that should also become a single source of truth
