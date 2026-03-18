# ReplaceFullyQualifiedNameRector

Replaces `use` import statements with configured aliases, redirecting upstream package names to project-local wrappers.

**Category:** DataModel & ViewModel
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The project wraps third-party packages — in particular `Zerotoprod\DataModel\DataModel` and `Zerotoprod\DataModel\Describe` — in project-local classes (`ZeroToProd\Thryds\Helpers\DataModel`, `ZeroToProd\Thryds\Helpers\Describe`). Importing the upstream package names directly bypasses any project-level extensions or future substitutions. This rule enforces the convention by rewriting `use` statements to point to the project-local aliases, keeping the rest of the file (class body, method calls) unchanged since the short name remains the same.

## What It Detects

A `use` statement whose fully-qualified name appears as a key in the configured `replacements` map.

## Transformation

### In `auto` mode

The `use` statement's target is replaced with the configured value. The class body is unaffected because the short name (e.g. `DataModel`) stays identical after the rewrite.

```php
// Before
use Zerotoprod\DataModel\DataModel;

class Config
{
    use DataModel;
}

// After
use App\Helpers\DataModel;

class Config
{
    use DataModel;
}
```

### In `warn` mode

A TODO comment is prepended to the `use` statement. No replacement occurs.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `replacements` | `array<string, string>` | `[]` | Map of `'OldFQN' => 'NewFQN'` |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | TODO comment text (used in `warn` mode) |

The `replacements` map can also be passed directly as the configuration array (without the `replacements` key) for backward compatibility.

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(ReplaceFullyQualifiedNameRector::class, [
    'replacements' => [
        DataModel::class => \ZeroToProd\Thryds\Helpers\DataModel::class,
        Describe::class  => \ZeroToProd\Thryds\Helpers\Describe::class,
    ],
    'mode' => 'auto',
]);
```

## Example

### Before
```php
namespace Test;

use Zerotoprod\DataModel\DataModel;

class Config
{
    use DataModel;
}
```

### After
```php
namespace Test;

use Fixture\Helpers\DataModel;

class Config
{
    use DataModel;
}
```

## Resolution

When you see the TODO comment from this rule:
1. Change the `use` statement from the upstream package name to the project-local alias listed in the TODO.
2. Verify the class body is unaffected (the short name is the same).
3. Remove the TODO comment.

## Related Rules

- [`UseClassConstArrayKeyForDataModelRector`](UseClassConstArrayKeyForDataModelRector.md) — enforces property constants on DataModel classes; depends on the correct trait being detected
- [`RequireMethodAnnotationForDataModelRector`](RequireMethodAnnotationForDataModelRector.md) — generates `@method` annotations; uses the same trait-detection logic
- [`RequireViewModelAttributeOnDataModelRector`](RequireViewModelAttributeOnDataModelRector.md) — enforces `#[ViewModel]` on DataModel classes
