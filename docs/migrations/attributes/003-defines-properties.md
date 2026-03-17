# Attribute Migration: #[DefinesProperties]

**ADR-007 leg:** Attributes define properties

## Problem

`#[ViewModel]` is a marker attribute that triggers `AddViewKeyConstantRector` to auto-generate a `view_key` constant. But the contract (what trait is required, what gets generated, which Rector rule enforces it) is documented only in a docblock on `ViewModel.php`. If someone creates a new marker attribute (e.g., `#[ApiModel]`, `#[EventPayload]`), they must:

1. Write the attribute class
2. Write a new Rector rule to enforce the contract
3. Configure the rule in rector.php
4. Document the contract in a docblock

Steps 2–4 are boilerplate. The contract should be machine-readable so a generic Rector rule can enforce any attribute-driven pattern.

## Attribute definition

```php
// src/Helpers/DefinesProperties.php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Meta-attribute: declares what structural contract a marker attribute enforces.
 *
 * Applied to attribute classes (not to user classes). A generic Rector rule reads this
 * to validate that classes using the marker attribute satisfy the contract.
 *
 * @example Applied to #[ViewModel] to declare: requires DataModel trait, generates view_key constant.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class DefinesProperties
{
    /**
     * @param string[] $requires    FQNs of traits/interfaces the target class must use/implement.
     * @param string[] $generates   Constant or property signatures that Rector will auto-generate.
     *                              Format: 'public const string <name>' — Rector creates it from the short class name.
     * @param string   $enforcedBy  FQN of the Rector rule that enforces/generates this contract.
     *                              Empty string means no dedicated rule — the generic validator handles it.
     */
    public function __construct(
        public array $requires = [],
        public array $generates = [],
        public string $enforcedBy = '',
    ) {}
}
```

## Files to apply the attribute

### `src/Helpers/ViewModel.php`

```php
// Before
use Attribute;

/**
 * Marker attribute: signals that this class is a Blade view model.
 *
 * Effects enforced by Rector ({@see \Utils\Rector\Rector\AddViewKeyConstantRector}):
 *   - Adds `public const string view_key = 'ShortClassName';`
 *
 * The view_key constant is used as the Blade template variable key:
 *   $Blade->make(view: View::error->value, data: [ErrorViewModel::view_key => $vm])
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ViewModel {}

// After
use Attribute;
use ZeroToProd\Thryds\Helpers\DefinesProperties;

/**
 * Marker attribute: signals that this class is a Blade view model.
 *
 * The view_key constant is used as the Blade template variable key:
 *   $Blade->make(view: View::error->value, data: [ErrorViewModel::view_key => $vm])
 */
#[DefinesProperties(
    requires: [DataModel::class],
    generates: ['public const string view_key'],
    enforcedBy: \Utils\Rector\Rector\AddViewKeyConstantRector::class,
)]
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ViewModel {}
```

The docblock is simplified — the `#[DefinesProperties]` metadata now carries the machine-readable contract, so the docblock only needs to explain the human-facing purpose.

## Rector rule changes

### `AddViewKeyConstantRector` — read contract from attribute

```php
// Before (rector.php)
$rectorConfig->ruleWithConfiguration(AddViewKeyConstantRector::class, [
    'dataModelTraits' => [\ZeroToProd\Thryds\Helpers\DataModel::class],
    'viewModelAttribute' => \ZeroToProd\Thryds\Helpers\ViewModel::class,
    'mode' => 'auto',
]);

// After — discovers contract from #[DefinesProperties] on ViewModel
$rectorConfig->ruleWithConfiguration(AddViewKeyConstantRector::class, [
    'mode' => 'auto',
]);
```

**Rule implementation change:** Instead of receiving `dataModelTraits` and `viewModelAttribute` via config, the rule:

1. Scans attribute classes in `$rectorConfig->paths()` for `#[DefinesProperties]`.
2. Reads `$instance->requires` to get the trait list.
3. Reads `$instance->generates` to know what to auto-generate.
4. Reads `$instance->enforcedBy` — if it matches `self::class`, this rule handles it.

```php
// In AddViewKeyConstantRector::discoverFromAttribute()
foreach ($this->attributeClasses as $attrClass) {
    $reflection = new \ReflectionClass($attrClass);
    $definesProps = $reflection->getAttributes(DefinesProperties::class);
    if ($definesProps === []) continue;
    $contract = $definesProps[0]->newInstance();
    if ($contract->enforcedBy === self::class || $contract->enforcedBy === '') {
        $this->requires = $contract->requires;
        $this->generates = $contract->generates;
        $this->markerAttribute = $attrClass;
        break;
    }
}
```

### `SuggestAttributeForRepeatedPropertyPatternRector` — read patterns from attribute

```php
// Before (from rule-suggest-attribute-for-repeated-pattern.md)
$rectorConfig->ruleWithConfiguration(SuggestAttributeForRepeatedPropertyPatternRector::class, [
    'patterns' => [
        [
            'trait' => \ZeroToProd\Thryds\Helpers\DataModel::class,
            'constant' => 'view_key',
            'attribute' => \ZeroToProd\Thryds\Helpers\ViewModel::class,
        ],
    ],
    'mode' => 'auto',
]);

// After — discovers all patterns from #[DefinesProperties] attributes
$rectorConfig->ruleWithConfiguration(SuggestAttributeForRepeatedPropertyPatternRector::class, [
    'mode' => 'auto',
]);
```

**Rule implementation change:** Scan all `#[Attribute]` classes for `#[DefinesProperties]`. For each, extract:
- `requires[0]` → the trait to look for
- `generates[0]` → parse to get the constant name (e.g., `'public const string view_key'` → `'view_key'`)
- The attribute class itself → what to add if missing

## New Rector rule: `ValidateAttributeContractRector`

A generic rule that checks all classes using a `#[DefinesProperties]`-annotated attribute:

1. **Trait check:** Does the class use all traits listed in `requires`?
2. **Constant/property check:** Does the class have all signatures listed in `generates`?
3. **Type check:** Do the generated constants match the expected types?

```php
// Catches:
#[ViewModel]  // ← #[DefinesProperties(requires: [DataModel::class], generates: ['public const string view_key'])]
readonly class BrokenViewModel
{
    // Missing: use DataModel;
    // Missing: public const string view_key = 'BrokenViewModel';
    public string $name;
}
// → TODO: [ValidateAttributeContractRector] BrokenViewModel uses #[ViewModel] but is missing: DataModel trait, view_key constant.
```

### Configuration

```php
$rectorConfig->ruleWithConfiguration(ValidateAttributeContractRector::class, [
    'mode' => 'warn',
    'message' => "TODO: [ValidateAttributeContractRector] %s uses #[%s] but is missing: %s.",
]);
```

No attribute or trait lists needed — the rule discovers everything from `#[DefinesProperties]` metadata.

### Implementation

```php
// Node type: Class_
public function refactor(Node $node): ?Node
{
    foreach ($node->attrGroups as $attrGroup) {
        foreach ($attrGroup->attrs as $attr) {
            $attrClass = $this->getName($attr->name);
            if ($attrClass === null) continue;

            $contract = $this->getDefinesPropertiesContract($attrClass);
            if ($contract === null) continue;

            $violations = [];

            // Check requires (traits)
            foreach ($contract->requires as $requiredTrait) {
                if (!$this->classUsesTrait($node, $requiredTrait)) {
                    $violations[] = $this->shortName($requiredTrait) . ' trait';
                }
            }

            // Check generates (constants/properties)
            foreach ($contract->generates as $signature) {
                $constName = $this->parseConstantName($signature);
                if ($constName !== null && !$this->classHasConstant($node, $constName)) {
                    $violations[] = $constName . ' constant';
                }
            }

            if ($violations !== []) {
                // Add TODO comment
            }
        }
    }
    return null;
}
```

## Extensibility example

Adding a new attribute-driven pattern (e.g., `#[ApiModel]` for JSON-serializable models) requires only:

```php
// src/Helpers/ApiModel.php
#[DefinesProperties(
    requires: [DataModel::class],
    generates: ['public const string api_key'],
)]
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ApiModel {}
```

No new Rector rule needed. `ValidateAttributeContractRector` and `SuggestAttributeForRepeatedPropertyPatternRector` both pick it up automatically.

## AI agent impact

An agent creating a new ViewModel:

```
1. Knows to add #[ViewModel] (from existing examples)
2. Reads #[DefinesProperties] on ViewModel → sees requires: [DataModel::class]
3. Adds `use DataModel;`
4. Reads generates: ['public const string view_key'] → knows Rector will create it
5. Reads enforcedBy → knows AddViewKeyConstantRector handles generation
6. Runs ./run fix:rector → view_key constant is auto-generated
```

An agent creating a new attribute-driven pattern:

```
1. Creates the attribute class with #[DefinesProperties]
2. No Rector rule to write — ValidateAttributeContractRector handles validation
3. SuggestAttributeForRepeatedPropertyPatternRector catches classes that match the pattern but lack the attribute
```

## Files to create/modify

| File | Action |
|------|--------|
| `src/Helpers/DefinesProperties.php` | Create meta-attribute class |
| `src/Helpers/ViewModel.php` | Add `#[DefinesProperties]` |
| `rector.php` | Simplify `AddViewKeyConstantRector` config |
| `utils/rector/src/AddViewKeyConstantRector.php` | Add attribute discovery fallback |
| `utils/rector/src/ValidateAttributeContractRector.php` | Create new generic rule |
| `utils/rector/tests/ValidateAttributeContractRector/` | Create test directory with fixtures |

## Migration order

1. Create `src/Helpers/DefinesProperties.php`.
2. Add `#[DefinesProperties]` to `ViewModel.php`.
3. Run `./run check:all` to verify no regressions.
4. Create `ValidateAttributeContractRector` with tests.
5. Update `AddViewKeyConstantRector` to support attribute discovery.
6. Simplify `rector.php` config.
7. Run `./run check:all` + `./run test:rector` to verify.
