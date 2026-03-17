# Rector Rule: RequireViewModelAttributeOnDataModelRector

**Enforces:** Every class with `DataModel` trait + `view_key` constant must have `#[ViewModel]`.

## Problem

A developer copies the `ErrorViewModel` pattern — adds `use DataModel`, adds `public const string view_key` — but forgets `#[ViewModel]`. The `AddViewKeyConstantRector` won't maintain the `view_key` constant on future runs because it only targets `#[ViewModel]`-annotated classes. The class looks like a ViewModel but isn't recognized as one.

## Detection heuristic

A class needs `#[ViewModel]` when ALL of these are true:
1. It uses the `DataModel` trait (project alias or vendor original)
2. It has a `public const string view_key` constant
3. It does NOT already have `#[ViewModel]`

## Before / After

```php
// Before — pattern present, attribute missing
readonly class ProfileViewModel
{
    use DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}

// After (auto)
use ZeroToProd\Thryds\Helpers\ViewModel;

#[ViewModel]
readonly class ProfileViewModel
{
    use DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
```

## Configuration

```php
// rector.php
$rectorConfig->ruleWithConfiguration(RequireViewModelAttributeOnDataModelRector::class, [
    'traitClasses' => [
        \ZeroToProd\Thryds\Helpers\DataModel::class,
        \Zerotoprod\DataModel\DataModel::class,
    ],
    'constantName' => 'view_key',
    'attributeClass' => \ZeroToProd\Thryds\Helpers\ViewModel::class,
    'mode' => 'auto',
    'message' => "TODO: [RequireViewModelAttributeOnDataModelRector] %s uses DataModel + view_key but is missing #[ViewModel].",
]);
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `traitClasses` | `string[]` | (required) | FQNs of the DataModel trait (project alias + vendor) |
| `constantName` | `string` | `'view_key'` | Name of the constant that signals the ViewModel pattern |
| `attributeClass` | `string` | (required) | FQN of the `ViewModel` attribute |
| `mode` | `'auto'\|'warn'` | `'auto'` | `auto` adds attribute; `warn` adds TODO |
| `message` | `string` | See above | `sprintf`: `%1$s` = class short name |

## Implementation

```php
<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireViewModelAttributeOnDataModelRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $traitClasses = [];

    private string $constantName = 'view_key';

    private string $attributeClass = '';

    private string $mode = 'auto';

    private string $message = "TODO: [RequireViewModelAttributeOnDataModelRector] %s uses DataModel + view_key but is missing #[ViewModel].";

    public function configure(array $configuration): void
    {
        $this->traitClasses = $configuration['traitClasses'] ?? [];
        $this->constantName = $configuration['constantName'] ?? 'view_key';
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[ViewModel] attribute on classes that use DataModel trait and have a view_key constant',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class ProfileViewModel
{
    use \App\Helpers\DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
#[\App\Helpers\ViewModel]
readonly class ProfileViewModel
{
    use \App\Helpers\DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
CODE_SAMPLE,
                    [
                        'traitClasses' => ['App\\Helpers\\DataModel'],
                        'constantName' => 'view_key',
                        'attributeClass' => 'App\\Helpers\\ViewModel',
                        'mode' => 'auto',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // Must already have the attribute → skip
        if ($this->hasAttribute($node, $this->attributeClass)) {
            return null;
        }

        // Must use DataModel trait
        if (!$this->usesDataModelTrait($node)) {
            return null;
        }

        // Must have view_key constant
        if (!$this->hasConstant($node, $this->constantName)) {
            return null;
        }

        $className = (string) $node->name;

        if ($this->mode === 'auto') {
            return $this->addAttribute($node);
        }

        return $this->addTodoComment($node, $className);
    }

    private function usesDataModelTrait(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof TraitUse) {
                continue;
            }

            foreach ($stmt->traits as $trait) {
                $traitName = $this->getName($trait);
                if ($traitName !== null && in_array($traitName, $this->traitClasses, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasConstant(Class_ $node, string $name): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if ((string) $const->name === $name) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasAttribute(Class_ $node, string $attributeClass): bool
    {
        $parts = explode('\\', $attributeClass);
        $shortName = end($parts);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $attributeClass || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addAttribute(Class_ $node): Class_
    {
        $attr = new Attribute(new FullyQualified($this->attributeClass));
        array_unshift($node->attrGroups, new AttributeGroup([$attr]));

        return $node;
    }

    private function addTodoComment(Class_ $node, string $className): Class_
    {
        $todoText = sprintf($this->message, $className);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return $node;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
```

## Test structure

```
utils/rector/tests/RequireViewModelAttributeOnDataModelRector/
├── RequireViewModelAttributeOnDataModelRectorTest.php
├── config/
│   └── configured_rule.php
├── Support/
│   ├── TestDataModel.php
│   └── TestViewModel.php
└── Fixture/
    ├── adds_attribute_when_trait_and_const_present.php.inc
    ├── skips_class_already_has_attribute.php.inc
    ├── skips_class_without_view_key.php.inc
    └── skips_class_without_trait.php.inc
```

### Config: `configured_rule.php`

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireViewModelAttributeOnDataModelRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireViewModelAttributeOnDataModelRector::class, [
        'traitClasses' => [
            'Utils\\Rector\\Tests\\RequireViewModelAttributeOnDataModelRector\\TestDataModel',
        ],
        'constantName' => 'view_key',
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireViewModelAttributeOnDataModelRector\\TestViewModel',
        'mode' => 'auto',
    ]);
};
```

### Support: `TestDataModel.php`

```php
<?php

namespace Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector;

trait TestDataModel {}
```

### Support: `TestViewModel.php`

```php
<?php

namespace Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestViewModel {}
```

### Fixture: `adds_attribute_when_trait_and_const_present.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;

readonly class ProfileViewModel
{
    use TestDataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}

?>
-----
<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;
use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestViewModel;

#[TestViewModel]
readonly class ProfileViewModel
{
    use TestDataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}

?>
```

### Fixture: `skips_class_already_has_attribute.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;
use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestViewModel;

#[TestViewModel]
readonly class ErrorViewModel
{
    use TestDataModel;
    public const string view_key = 'ErrorViewModel';
    public string $message;
}

?>
-----
<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;
use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestViewModel;

#[TestViewModel]
readonly class ErrorViewModel
{
    use TestDataModel;
    public const string view_key = 'ErrorViewModel';
    public string $message;
}

?>
```

### Fixture: `skips_class_without_view_key.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;

readonly class Config
{
    use TestDataModel;
    public const string AppEnv = 'AppEnv';
    public string $blade_cache_dir;
}

?>
-----
<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;

readonly class Config
{
    use TestDataModel;
    public const string AppEnv = 'AppEnv';
    public string $blade_cache_dir;
}

?>
```

### Fixture: `skips_class_without_trait.php.inc`

```php
<?php

readonly class PlainClass
{
    public const string view_key = 'PlainClass';
    public string $name;
}

?>
-----
<?php

readonly class PlainClass
{
    public const string view_key = 'PlainClass';
    public string $name;
}

?>
```

## Registration in rector.php

```php
// Add import
use Utils\Rector\Rector\RequireViewModelAttributeOnDataModelRector;

// Add in DataModel & ViewModel section
$rectorConfig->ruleWithConfiguration(RequireViewModelAttributeOnDataModelRector::class, [
    'traitClasses' => [
        \ZeroToProd\Thryds\Helpers\DataModel::class,
        \Zerotoprod\DataModel\DataModel::class,
    ],
    'constantName' => 'view_key',
    'attributeClass' => \ZeroToProd\Thryds\Helpers\ViewModel::class,
    'mode' => 'auto',
]);
```
