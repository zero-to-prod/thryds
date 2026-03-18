# AddViewKeyConstantRector

Adds `public const string view_key = 'ClassName';` to classes that carry the `#[ViewModel]` attribute and use the `DataModel` trait but are missing the constant.

**Category:** DataModel & ViewModel
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The DataModel/ViewModel pattern requires three elements to work correctly: the `DataModel` trait (for `::from()`), the `#[ViewModel]` attribute (for tooling and rule detection), and the `view_key` constant (a stable string key used to nest the model in view data arrays). When a class has the attribute and the trait but no `view_key`, the constant must be added before other rules — like `ReplaceShortClassNameWithViewKeyRector` — can operate. This rule fills that gap automatically.

The `view_key` value is always set to the class's short (unqualified) name, matching the project convention.

## What It Detects

A class that:
- Has the configured `viewModelAttribute` attribute (e.g. `#[ViewModel]`)
- Uses one of the configured `dataModelTraits`
- Does **not** already declare a `view_key` constant

## Transformation

### In `auto` mode

`public const string view_key = 'ShortClassName';` is inserted immediately after the last `use` (trait use) statement in the class body.

```php
// Before
#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;

    public string $message;
}

// After
#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';

    public string $message;
}
```

### In `warn` mode

A TODO comment is prepended to the class. No constant is added.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `dataModelTraits` | `string[]` | `[]` | Fully-qualified trait names to look for |
| `viewModelAttribute` | `string` | `''` | Fully-qualified attribute class that marks a ViewModel |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | TODO comment text (used in `warn` mode) |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(AddViewKeyConstantRector::class, [
    'dataModelTraits' => [
        \ZeroToProd\Thryds\Helpers\DataModel::class,
    ],
    'viewModelAttribute' => ViewModel::class,
    'mode' => 'auto',
]);
```

## Example

### Before
```php
use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;

    public string $message;
}
```

### After
```php
use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';

    public string $message;
}
```

## Resolution

When you see the TODO comment from this rule:
1. Add `public const string view_key = 'YourClassName';` immediately after the `use DataModel;` line.
2. Ensure the value matches the class's short (unqualified) name.
3. Remove the TODO comment.

## Related Rules

- [`RequireViewModelAttributeOnDataModelRector`](RequireViewModelAttributeOnDataModelRector.md) — adds `#[ViewModel]` to classes that have the trait and constant but lack the attribute (the inverse problem)
- [`ReplaceShortClassNameWithViewKeyRector`](ReplaceShortClassNameWithViewKeyRector.md) — replaces `short_class_name(Class::class)` array keys with `Class::view_key` (requires `view_key` to exist)
- [`UseClassConstArrayKeyForDataModelRector`](UseClassConstArrayKeyForDataModelRector.md) — adds property constants and replaces string keys in `::from()` calls
