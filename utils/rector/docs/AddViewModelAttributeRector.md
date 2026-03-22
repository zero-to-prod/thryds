# AddViewModelAttributeRector

Adds `#[ViewModel]` to every class in the configured ViewModel namespace that is missing the attribute.

**Category:** DataModel & ViewModel
**Mode:** `auto`
**Auto-fix:** Yes

## Rationale

Tooling that discovers ViewModels (e.g. `scripts/list-inventory.php`) reflects on the `#[ViewModel]` attribute. A ViewModel class without it is invisible to the inventory graph and any other attribute-driven tooling.

## What It Detects

A class whose namespace exactly matches the configured `namespace` value and that does not already carry the configured `attributeClass` attribute.

## Transformation

### In `auto` mode

Prepends `#[ViewModel]` (or whichever `attributeClass` is configured) to the class. `importNames()` is enabled in `rector.php`, so a `use` statement is added automatically.

### In `warn` mode

No-op. This rule has no meaningful warn-only behaviour because the fix is trivially safe.

## Configuration

| Option | Type | Default | Description |
|---|---|---|---|
| `namespace` | `string` | `''` | Exact PHP namespace to target (required) |
| `attributeClass` | `string` | `''` | Fully qualified attribute class to add (required) |
| `mode` | `string` | `'auto'` | `'auto'` to add the attribute; `'warn'` is a no-op |

## Example

### Before

```php
namespace ZeroToProd\Thryds\ViewModels;

readonly class UserViewModel
{
    use \ZeroToProd\Thryds\Attributes\DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
```

### After

```php
namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Attributes\ViewModel;
use ZeroToProd\Thryds\Attributes\DataModel;

#[ViewModel]
readonly class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
```

## Caveats

- Detection checks both the fully qualified name and the short name of the attribute, so a class with `use ... ViewModel;` and `#[ViewModel]` is correctly skipped.
- The rule matches on namespace only — no trait or constant requirements. Every class in the target namespace receives the attribute.

## Related Rules

- `RequireViewModelAttributeOnDataModelRector` — warn-based rule that flags classes using the DataModel trait with a `view_key` constant but missing `#[ViewModel]`. Complements this rule for cases outside the ViewModels namespace.
- `AddViewKeyConstantRector` — adds the `view_key` constant to classes that already have `#[ViewModel]`.
