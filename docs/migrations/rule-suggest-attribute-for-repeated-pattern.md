# Rector Rule: SuggestAttributeForRepeatedPropertyPatternRector

**ADR-007 leg:** Attributes define properties

## Problem

When multiple classes share identical structural boilerplate — the same trait, the same constant pattern, the same property configuration — that pattern should be declared via an attribute and enforced by Rector, not manually copied. Today `#[ViewModel]` + `AddViewKeyConstantRector` handles one such pattern. But the principle is general: any repeated class-level pattern is a candidate for an attribute.

This rule detects classes that share a structural pattern with N+ other classes but lack the corresponding marker attribute.

## Rule behavior

Scan classes for a configurable structural pattern (trait usage + constant naming convention). If a class uses the trait and has the expected constant but lacks the marker attribute, add a TODO suggesting the attribute. In `auto` mode, add the attribute.

## Before / After

```php
// Before (manual boilerplate, no attribute)
readonly class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}

// After (auto)
#[ViewModel]
readonly class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}

// After (warn)
// TODO: [SuggestAttributeForRepeatedPropertyPatternRector] UserViewModel uses DataModel + view_key — add #[ViewModel] attribute.
readonly class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
```

## Configuration

```php
// rector.php
$rectorConfig->ruleWithConfiguration(SuggestAttributeForRepeatedPropertyPatternRector::class, [
    'patterns' => [
        [
            'trait' => \ZeroToProd\Thryds\Helpers\DataModel::class,
            'constant' => 'view_key',
            'attribute' => \ZeroToProd\Thryds\Helpers\ViewModel::class,
        ],
    ],
    'mode' => 'auto',
    'message' => "TODO: [SuggestAttributeForRepeatedPropertyPatternRector] %s uses %s + %s — add #[%s] attribute.",
]);
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `patterns` | `array[]` | (required) | List of pattern definitions, each with `trait`, `constant`, and `attribute` |
| `patterns[].trait` | `string` | — | FQN of the trait that identifies the pattern |
| `patterns[].constant` | `string` | — | Name of the constant that must be present |
| `patterns[].attribute` | `string` | — | FQN of the attribute that should be applied |
| `mode` | `'auto'\|'warn'` | `'warn'` | `auto` adds the attribute; `warn` adds TODO |
| `message` | `string` | See above | `sprintf`: `%1$s` = class name, `%2$s` = trait short name, `%3$s` = constant name, `%4$s` = attribute short name |

## Implementation

### Node types

`Class_` — for each class node:

1. Check if the class uses any of the configured traits (via `$node->getTraitUses()`).
2. Check if the class has the corresponding constant (via `$node->getConstants()`).
3. Check if the class already has the attribute (via `$node->attrGroups`).
4. If traits + constant match but attribute is missing → trigger.

### Auto mode

Add an `AttributeGroup` with the attribute to the class node:

```php
$node->attrGroups[] = new AttributeGroup([
    new Attribute(new FullyQualified($pattern['attribute']))
]);
```

### Warn mode

Add a TODO comment above the `Class_` node.

### Why this matters

This is the "attributes define properties" enforcement. Without it, a developer creates a new ViewModel, manually adds `use DataModel` and `public const string view_key = '...'`, but forgets `#[ViewModel]`. The `AddViewKeyConstantRector` then has no signal to maintain the constant. This rule catches the gap.

## Test structure

```
utils/rector/tests/SuggestAttributeForRepeatedPropertyPatternRector/
├── SuggestAttributeForRepeatedPropertyPatternRectorTest.php
├── config/
│   └── configured_rule.php
├── Support/
│   ├── TestDataModel.php     # Test-local trait
│   └── TestViewModel.php     # Test-local attribute
└── Fixture/
    ├── adds_attribute_when_trait_and_const_present.php.inc
    ├── skips_class_already_has_attribute.php.inc
    ├── skips_class_without_trait.php.inc
    └── skips_class_without_constant.php.inc
```

### Fixture: `adds_attribute_when_trait_and_const_present.php.inc`

```php
<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;

class UserViewModel
{
    use TestDataModel;
    public const string view_key = 'UserViewModel';
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;
use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestViewModel;

#[TestViewModel]
class UserViewModel
{
    use TestDataModel;
    public const string view_key = 'UserViewModel';
}

?>
```

### Fixture: `skips_class_already_has_attribute.php.inc`

```php
<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;
use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestViewModel;

#[TestViewModel]
class ErrorViewModel
{
    use TestDataModel;
    public const string view_key = 'ErrorViewModel';
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;
use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestViewModel;

#[TestViewModel]
class ErrorViewModel
{
    use TestDataModel;
    public const string view_key = 'ErrorViewModel';
}

?>
```

### Support: `TestDataModel.php`

```php
<?php

namespace Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector;

trait TestDataModel {}
```

### Support: `TestViewModel.php`

```php
<?php

namespace Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
class TestViewModel {}
```

## Files affected at registration

| File | Change |
|------|--------|
| `rector.php` | Add import + `ruleWithConfiguration()` call |
| `utils/rector/src/SuggestAttributeForRepeatedPropertyPatternRector.php` | New rule file |
| `utils/rector/tests/SuggestAttributeForRepeatedPropertyPatternRector/` | New test directory |
