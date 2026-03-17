# Rector Rule: RequireNamesKeysOnMixedConstantsClassRector

**Enforces:** Classes with 3+ `public const string` members alongside methods must have `#[NamesKeys]`.

## Problem

Rule #2 (`RequireNamesKeysOnConstantsClassRector`) only catches pure constants classes (no methods, no properties). Classes like `Log` have both constants AND methods. Without this rule, a new service class with multiple string constants (e.g., a `Metrics` class with context keys + `record()` methods) would slip through.

## Detection heuristic

A class needs `#[NamesKeys]` when ALL of these are true:
1. It has `>= minConstants` (default: 3) `public const string` members
2. It does NOT already have `#[NamesKeys]`
3. It does NOT use a `DataModel` trait (those classes have property-key constants, not external keys)
4. It does NOT have other excluded attributes

## Before / After

```php
// Before — constants + methods, no attribute
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(string $message, array $context = []): void { /* ... */ }
}

// After (warn)
// TODO: [RequireNamesKeysOnMixedConstantsClassRector] Metrics has 3 string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(string $message, array $context = []): void { /* ... */ }
}
```

## Configuration

```php
// rector.php
$rectorConfig->ruleWithConfiguration(RequireNamesKeysOnMixedConstantsClassRector::class, [
    'attributeClass' => \ZeroToProd\Thryds\Helpers\NamesKeys::class,
    'minConstants' => 3,
    'excludedTraits' => [
        \ZeroToProd\Thryds\Helpers\DataModel::class,
        \Zerotoprod\DataModel\DataModel::class,
    ],
    'excludedAttributes' => [
        \ZeroToProd\Thryds\Helpers\ViewModel::class,
    ],
    'mode' => 'warn',
    'message' => "TODO: [RequireNamesKeysOnMixedConstantsClassRector] %s has %d string constants — add #[NamesKeys] to declare what they name (ADR-007).",
]);
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `attributeClass` | `string` | (required) | FQN of the `NamesKeys` attribute |
| `minConstants` | `int` | `3` | Minimum `public const string` count to trigger |
| `excludedTraits` | `string[]` | `[]` | Classes using these traits are skipped (DataModel classes have property keys, not naming keys) |
| `excludedAttributes` | `string[]` | `[]` | Classes with these attributes are skipped |
| `mode` | `'auto'\|'warn'` | `'warn'` | `auto` adds attribute with placeholder; `warn` adds TODO |
| `message` | `string` | See above | `sprintf`: `%1$s` = class short name, `%2$d` = constant count |

## Implementation

```php
<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireNamesKeysOnMixedConstantsClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = '';

    private int $minConstants = 3;

    /** @var string[] */
    private array $excludedTraits = [];

    /** @var string[] */
    private array $excludedAttributes = [];

    private string $mode = 'warn';

    private string $message = "TODO: [RequireNamesKeysOnMixedConstantsClassRector] %s has %d string constants — add #[NamesKeys] to declare what they name (ADR-007).";

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->minConstants = $configuration['minConstants'] ?? 3;
        $this->excludedTraits = $configuration['excludedTraits'] ?? [];
        $this->excludedAttributes = $configuration['excludedAttributes'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[NamesKeys] attribute on classes that have 3+ string constants alongside methods',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireNamesKeysOnMixedConstantsClassRector] Metrics has 3 string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\NamesKeys',
                        'minConstants' => 3,
                        'mode' => 'warn',
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
        // Already has #[NamesKeys]
        if ($this->hasAttribute($node, $this->attributeClass)) {
            return null;
        }

        // Has an excluded attribute
        foreach ($this->excludedAttributes as $excludedAttr) {
            if ($this->hasAttribute($node, $excludedAttr)) {
                return null;
            }
        }

        // Uses an excluded trait (DataModel = property keys, not naming keys)
        if ($this->usesExcludedTrait($node)) {
            return null;
        }

        // Count public const string members
        $string_const_count = $this->countStringConstants($node);
        if ($string_const_count < $this->minConstants) {
            return null;
        }

        $className = (string) $node->name;

        if ($this->mode === 'auto') {
            return $this->addAttribute($node);
        }

        return $this->addTodoComment($node, $className, $string_const_count);
    }

    private function countStringConstants(Class_ $node): int
    {
        $count = 0;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            if (!$stmt->isPublic()) {
                continue;
            }

            // Check if typed as string
            if ($stmt->type instanceof Identifier && $stmt->type->name === 'string') {
                $count += count($stmt->consts);
            }
        }

        return $count;
    }

    private function usesExcludedTrait(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof TraitUse) {
                continue;
            }

            foreach ($stmt->traits as $trait) {
                $traitName = $this->getName($trait);
                if ($traitName !== null && in_array($traitName, $this->excludedTraits, true)) {
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
        $attr = new Attribute(
            new FullyQualified($this->attributeClass),
            [
                new Arg(
                    value: new String_('TODO: describe source'),
                    name: new Identifier('source'),
                ),
            ],
        );

        array_unshift($node->attrGroups, new AttributeGroup([$attr]));

        return $node;
    }

    private function addTodoComment(Class_ $node, string $className, int $count): Class_
    {
        $todoText = sprintf($this->message, $className, $count);
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
utils/rector/tests/RequireNamesKeysOnMixedConstantsClassRector/
├── RequireNamesKeysOnMixedConstantsClassRectorTest.php
├── config/
│   └── configured_rule.php
├── Support/
│   ├── TestNamesKeys.php
│   └── TestDataModel.php
└── Fixture/
    ├── adds_todo_to_class_with_3_consts_and_method.php.inc
    ├── skips_class_below_threshold.php.inc
    ├── skips_class_with_attribute.php.inc
    ├── skips_class_with_datamodel_trait.php.inc
    └── skips_class_with_private_consts.php.inc
```

### Test: `RequireNamesKeysOnMixedConstantsClassRectorTest.php`

```php
<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RequireNamesKeysOnMixedConstantsClassRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
```

### Config: `configured_rule.php`

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireNamesKeysOnMixedConstantsClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireNamesKeysOnMixedConstantsClassRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireNamesKeysOnMixedConstantsClassRector\\TestNamesKeys',
        'minConstants' => 3,
        'excludedTraits' => [
            'Utils\\Rector\\Tests\\RequireNamesKeysOnMixedConstantsClassRector\\TestDataModel',
        ],
        'mode' => 'warn',
    ]);
};
```

### Support: `TestNamesKeys.php`

```php
<?php

namespace Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestNamesKeys
{
    public function __construct(
        public string $source = '',
    ) {}
}
```

### Support: `TestDataModel.php`

```php
<?php

namespace Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector;

trait TestDataModel {}
```

### Fixture: `adds_todo_to_class_with_3_consts_and_method.php.inc`

```php
<?php

readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}

?>
-----
<?php

// TODO: [RequireNamesKeysOnMixedConstantsClassRector] Metrics has 3 string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}

?>
```

### Fixture: `skips_class_below_threshold.php.inc`

```php
<?php

readonly class SmallService
{
    public const string key = 'key';
    public const string value = 'value';

    public static function run(): void {}
}

?>
-----
<?php

readonly class SmallService
{
    public const string key = 'key';
    public const string value = 'value';

    public static function run(): void {}
}

?>
```

### Fixture: `skips_class_with_attribute.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class TaggedService
{
    public const string a = 'a';
    public const string b = 'b';
    public const string c = 'c';

    public static function run(): void {}
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class TaggedService
{
    public const string a = 'a';
    public const string b = 'b';
    public const string c = 'c';

    public static function run(): void {}
}

?>
```

### Fixture: `skips_class_with_datamodel_trait.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestDataModel;

readonly class Config
{
    use TestDataModel;
    public const string app_env = 'app_env';
    public const string cache_dir = 'cache_dir';
    public const string template_dir = 'template_dir';
    public string $app_env;
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestDataModel;

readonly class Config
{
    use TestDataModel;
    public const string app_env = 'app_env';
    public const string cache_dir = 'cache_dir';
    public const string template_dir = 'template_dir';
    public string $app_env;
}

?>
```

### Fixture: `skips_class_with_private_consts.php.inc`

```php
<?php

readonly class InternalKeys
{
    private const string a = 'a';
    private const string b = 'b';
    private const string c = 'c';

    public static function run(): void {}
}

?>
-----
<?php

readonly class InternalKeys
{
    private const string a = 'a';
    private const string b = 'b';
    private const string c = 'c';

    public static function run(): void {}
}

?>
```

## Registration in rector.php

```php
// Add import
use Utils\Rector\Rector\RequireNamesKeysOnMixedConstantsClassRector;

// Add after RequireNamesKeysOnConstantsClassRector
$rectorConfig->ruleWithConfiguration(RequireNamesKeysOnMixedConstantsClassRector::class, [
    'attributeClass' => \ZeroToProd\Thryds\Helpers\NamesKeys::class,
    'minConstants' => 3,
    'excludedTraits' => [
        \ZeroToProd\Thryds\Helpers\DataModel::class,
        \Zerotoprod\DataModel\DataModel::class,
    ],
    'excludedAttributes' => [
        \ZeroToProd\Thryds\Helpers\ViewModel::class,
    ],
    'mode' => 'warn',
    'message' => "TODO: [RequireNamesKeysOnMixedConstantsClassRector] %s has %d string constants — add #[NamesKeys] to declare what they name (ADR-007).",
]);
```
