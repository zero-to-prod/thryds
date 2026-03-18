# RequireViewModelAttributeOnDataModelRector

Adds the `#[ViewModel]` attribute to classes that use the `DataModel` trait and declare a `view_key` constant but are missing the attribute.

**Category:** DataModel & ViewModel
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The DataModel/ViewModel pattern in this project uses three elements together:
1. The `DataModel` trait — provides the `::from(array $data)` factory method.
2. A `view_key` constant — a stable string key used to nest the model inside view data arrays.
3. The `#[ViewModel]` attribute — a marker that declares the class as a view model, enabling other rules and tooling to identify it.

When a class has the trait and the constant but lacks the attribute, it is partially following the convention. This rule completes the setup by adding `#[ViewModel]` automatically.

## What It Detects

A class that:
- Uses one of the configured `traitClasses` (e.g. `ZeroToProd\Thryds\Attributes\DataModel`)
- Declares a constant named `view_key` (or the configured `constantName`)
- Does **not** already have the configured `attributeClass` attribute

## Transformation

### In `auto` mode

The `#[ViewModel]` attribute group is prepended to the class declaration.

```php
// Before
readonly class ProfileViewModel
{
    use \App\Helpers\DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}

// After
#[\App\Helpers\ViewModel]
readonly class ProfileViewModel
{
    use \App\Helpers\DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
```

### In `warn` mode

A TODO comment is prepended to the class:

```
// TODO: [RequireViewModelAttributeOnDataModelRector] ProfileViewModel uses DataModel + view_key but is missing #[ViewModel].
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `traitClasses` | `string[]` | `[]` | Fully-qualified trait names that mark a class as a DataModel |
| `constantName` | `string` | `'view_key'` | The constant name that must be present |
| `attributeClass` | `string` | `''` | Fully-qualified attribute class to add |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | (see source) | TODO comment template; `%s` receives the class name |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(RequireViewModelAttributeOnDataModelRector::class, [
    'traitClasses' => [
        \ZeroToProd\Thryds\Attributes\DataModel::class,
        DataModel::class,
    ],
    'constantName' => 'view_key',
    'attributeClass' => ViewModel::class,
    'mode' => 'auto',
]);
```

## Example

### Before
```php
readonly class ProfileViewModel
{
    use TestDataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
```

### After
```php
#[\Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestViewModel]
readonly class ProfileViewModel
{
    use TestDataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
```

## Resolution

When you see the TODO comment from this rule:
1. Add `#[ViewModel]` (or the fully-qualified attribute) above the class declaration.
2. Ensure the `ViewModel` class is imported via `use`.
3. Remove the TODO comment.

## Related Rules

- [`AddViewKeyConstantRector`](AddViewKeyConstantRector.md) — adds the `view_key` constant to classes that have `#[ViewModel]` but are missing it
- [`SuggestAttributeForRepeatedPropertyPatternRector`](SuggestAttributeForRepeatedPropertyPatternRector.md) — generic rule that detects the same trait+constant pattern
- [`UseClassConstArrayKeyForDataModelRector`](UseClassConstArrayKeyForDataModelRector.md) — replaces string keys in `::from()` calls with class constants
