# SuggestAttributeForRepeatedPropertyPatternRector

Adds a configured marker attribute to classes that exhibit a repeating trait+constant pattern, without being tied to a specific attribute or constant name.

**Category:** DataModel & ViewModel
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The `#[ViewModel]` attribute on DataModel classes is one instance of a broader convention: when a class always carries a specific trait and a specific constant together, that combination deserves a dedicated marker attribute. This rule is the generic engine behind that enforcement — it takes a list of `(trait, constant, attribute)` triples and applies the attribute wherever the trait and constant are present but the attribute is not.

In `rector.php` it is configured for the DataModel/ViewModel pattern:
- trait: `ZeroToProd\Thryds\Helpers\DataModel`
- constant: `view_key`
- attribute: `ZeroToProd\Thryds\Helpers\ViewModel`

This is a companion to `RequireViewModelAttributeOnDataModelRector`, which is more narrowly focused on the same ViewModel pattern. This rule can be extended to enforce other attribute conventions by adding more patterns.

## What It Detects

For each configured pattern, a class that:
- Uses the configured `trait`
- Declares a constant with the configured `constant` name
- Does **not** already have the configured `attribute`

## Transformation

### In `auto` mode

The configured attribute is prepended to the class declaration.

```php
// Before
class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
}

// After
#[ViewModel]
class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
}
```

### In `warn` mode

A TODO comment is prepended to the class:

```
// TODO: [SuggestAttributeForRepeatedPropertyPatternRector] UserViewModel uses DataModel + view_key — add #[ViewModel] attribute.
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `patterns` | `array[]` | `[]` | List of `{trait, constant, attribute}` triples to enforce |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | (see source) | TODO comment template; receives class name, trait short name, constant name, attribute short name |

Each pattern entry:

| Key | Type | Description |
|-----|------|-------------|
| `trait` | `string` | Fully-qualified trait class name |
| `constant` | `string` | Constant name that must exist on the class |
| `attribute` | `string` | Fully-qualified attribute class to add |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(SuggestAttributeForRepeatedPropertyPatternRector::class, [
    'patterns' => [
        [
            'trait' => \ZeroToProd\Thryds\Helpers\DataModel::class,
            'constant' => 'view_key',
            'attribute' => ViewModel::class,
        ],
    ],
    'mode' => 'auto',
    'message' => 'TODO: [SuggestAttributeForRepeatedPropertyPatternRector] %s uses %s + %s — add #[%s] attribute.',
]);
```

## Example

### Before
```php
class UserViewModel
{
    use TestDataModel;
    public const string view_key = 'UserViewModel';
}
```

### After
```php
#[TestViewModel]
class UserViewModel
{
    use TestDataModel;
    public const string view_key = 'UserViewModel';
}
```

## Resolution

When you see the TODO comment from this rule:
1. Add the suggested attribute (e.g. `#[ViewModel]`) above the class declaration.
2. Add the necessary `use` import for the attribute class.
3. Remove the TODO comment.

## Related Rules

- [`RequireViewModelAttributeOnDataModelRector`](RequireViewModelAttributeOnDataModelRector.md) — focused variant for the DataModel/ViewModel pattern specifically
- [`AddViewKeyConstantRector`](AddViewKeyConstantRector.md) — adds the `view_key` constant when the `#[ViewModel]` attribute is present but the constant is missing
