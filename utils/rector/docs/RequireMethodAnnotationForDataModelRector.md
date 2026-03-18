# RequireMethodAnnotationForDataModelRector

Adds or updates a `@method static self from(array{...} $data)` PHPDoc annotation on classes using the `DataModel` trait, reflecting the class's actual typed properties.

**Category:** DataModel & ViewModel
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The `DataModel::from(array $data)` method accepts a plain array and returns a populated instance. Without a `@method` docblock, IDEs and static analysers see `from()` as accepting `array` with no shape information, losing all type safety at call sites. This rule generates and maintains the precise array-shape signature from the class's actual property declarations, including:

- Required vs optional keys (properties with a `#[Describe([Describe::default => ...])]` attribute become optional, marked with `?`)
- Backed enum properties are documented as `EnumType|backingType` to reflect that `from()` accepts either the enum case or its raw backing value

The annotation is updated whenever properties change, keeping documentation in sync without manual effort.

## What It Detects

A class that:
- Uses one of the configured `dataModelTraits`
- Has at least one typed property
- Either lacks a `@method` docblock entirely, or has one whose `from()` signature does not match the current properties

## Transformation

### In `auto` mode

A `/** @method static self from(array{...} $data) */` docblock is added or updated. Existing docblocks are extended (the annotation is appended); existing `@method from()` entries are replaced in-place.

Properties with `#[Describe([Describe::default => ...])]` are marked optional (`key?:`).

```php
// Before (basic case)
class MethodAnnotationProfile
{
    use DataModel;
    public string $username;
    public int $age;
}

// After
/**
 * @method static self from(array{username: string, age: int} $data)
 */
class MethodAnnotationProfile
{
    use DataModel;
    public string $username;
    public int $age;
}
```

```php
// Before (with Describe default)
class MethodAnnotationConfig
{
    use DataModel;
    #[Describe([Describe::default => 'production'])]
    public string $app_env;
    public string $template_dir;
}

// After
/**
 * @method static self from(array{app_env?: string, template_dir: string} $data)
 */
class MethodAnnotationConfig
{
    use DataModel;
    #[Describe([Describe::default => 'production'])]
    public string $app_env;
    public string $template_dir;
}
```

### In `warn` mode

A TODO comment is prepended to the class. No docblock is added or modified.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `dataModelTraits` | `string[]` | `[]` | Fully-qualified trait names that identify DataModel classes |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | TODO comment text (used in `warn` mode) |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(RequireMethodAnnotationForDataModelRector::class, [
    'dataModelTraits' => [
        DataModel::class,
        \ZeroToProd\Thryds\Attributes\DataModel::class,
    ],
    'mode' => 'auto',
]);
```

## Example

### Before
```php
use Zerotoprod\DataModel\DataModel;

class MethodAnnotationProfile
{
    use DataModel;

    public string $username;
    public int $age;
}
```

### After
```php
use Zerotoprod\DataModel\DataModel;

/**
 * @method static self from(array{username: string, age: int} $data)
 */
class MethodAnnotationProfile
{
    use DataModel;

    public string $username;
    public int $age;
}
```

## Resolution

When you see the TODO comment from this rule:
1. Inspect all typed properties of the DataModel class.
2. Add a `/** @method static self from(array{prop: type, ...} $data) */` docblock above the class declaration.
3. Mark properties that have a `#[Describe]` default as optional with `?` (e.g. `app_env?: string`).
4. Remove the TODO comment.

## Related Rules

- [`UseClassConstArrayKeyForDataModelRector`](UseClassConstArrayKeyForDataModelRector.md) — adds property constants and replaces string keys in `::from()` calls
- [`ReplaceFullyQualifiedNameRector`](ReplaceFullyQualifiedNameRector.md) — redirects `use Zerotoprod\DataModel\DataModel` to the project-local alias
