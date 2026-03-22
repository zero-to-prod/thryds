# RequireViewKeyConstantOnViewModelRector

**Category:** Graph Completeness
**Mode:** `warn` (default) / `auto`

## Purpose

Any class carrying `#[ViewModel]` must declare `public const string view_key`. The inventory graph (`scripts/list-inventory.php`) calls `$ref->getConstant('view_key')` on every discovered ViewModel — a missing constant produces a null entry in the graph output.

This rule is a safety net that fires on **any** `#[ViewModel]` class regardless of whether it uses the `DataModel` trait. `AddViewKeyConstantRector` handles the `DataModel` + `#[ViewModel]` pattern; this rule covers everything else.

## Configuration

| Key | Type | Default | Description |
|---|---|---|---|
| `viewModelAttribute` | `string` | `''` | FQCN of the ViewModel attribute (e.g. `ZeroToProd\Thryds\Attributes\ViewModel`) |
| `mode` | `'warn'` \| `'auto'` | `'warn'` | `warn` prepends a TODO comment; `auto` inserts the constant |
| `message` | `string` | (see below) | TODO comment text; `%s` is replaced with the short class name |

Default message:
```
TODO: [RequireViewKeyConstantOnViewModelRector] %s is missing `public const string view_key`. Required for graph inventory.
```

## Before / After — warn mode (default)

**Before:**
```php
use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    public string $name;
}
```

**After:**
```php
use ZeroToProd\Thryds\Attributes\ViewModel;

// TODO: [RequireViewKeyConstantOnViewModelRector] UserViewModel is missing `public const string view_key`. Required for graph inventory.
#[ViewModel]
readonly class UserViewModel
{
    public string $name;
}
```

## Before / After — auto mode

**Before:**
```php
use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    public string $name;
}
```

**After:**
```php
use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    public const string view_key = 'UserViewModel';

    public string $name;
}
```

The constant is inserted after the last `TraitUse` statement in the class body, or at index 0 if there are no trait uses.

## Caveats

- The TODO comment is idempotent: the marker is the static prefix of `message` before the first `%` character. Re-running in warn mode will not add a second comment.
- In auto mode the value of `view_key` is set to the short class name (unqualified). Change it manually if the Blade template key differs.
- This rule matches the `viewModelAttribute` by both FQCN and short name, so it works whether or not the file has a `use` import for the attribute.

## Registration in rector.php

```php
$rectorConfig->ruleWithConfiguration(RequireViewKeyConstantOnViewModelRector::class, [
    'viewModelAttribute' => ViewModel::class,
    'mode' => 'warn',
    'message' => 'TODO: [RequireViewKeyConstantOnViewModelRector] %s is missing `public const string view_key`. Required for graph inventory. See: utils/rector/docs/RequireViewKeyConstantOnViewModelRector.md',
]);
```
