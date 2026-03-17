# Rector Rule: RequireNamesKeysOnConstantsClassRector

**Enforces:** Every pure constants class in `src/` must have `#[NamesKeys]`.

## Problem

A developer adds a new `readonly class CacheKey` with only `public const string` members. Without `#[NamesKeys]`, the class is invisible to attribute-discovery-based Rector rules and AI agents can't determine what the constants name without reading every usage site.

## Detection heuristic

A class is a "pure constants class" when ALL of these are true:
1. It is `readonly`
2. It has 1+ `public const string` or `public const array` members
3. It has NO constructor, NO methods, NO properties
4. It does NOT already have `#[NamesKeys]` or another known attribute (`#[ViewModel]`, etc.)

## Before / After

```php
// Before
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}

// After (warn)
// TODO: [RequireNamesKeysOnConstantsClassRector] CacheKey contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}

// After (auto)
use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(source: 'TODO: describe source')]
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}
```

## Configuration

```php
// rector.php
$rectorConfig->ruleWithConfiguration(RequireNamesKeysOnConstantsClassRector::class, [
    'attributeClass' => \ZeroToProd\Thryds\Helpers\KeyRegistry::class,
    'excludedAttributes' => [
        \ZeroToProd\Thryds\Helpers\ViewModel::class,
    ],
    'mode' => 'warn',
    'message' => "TODO: [RequireNamesKeysOnConstantsClassRector] %s contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).",
]);
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `attributeClass` | `string` | (required) | FQN of the `NamesKeys` attribute |
| `excludedAttributes` | `string[]` | `[]` | Classes with these attributes are skipped (e.g., ViewModel has its own attribute) |
| `mode` | `'auto'\|'warn'` | `'warn'` | `auto` adds attribute with placeholder source; `warn` adds TODO |
| `message` | `string` | See above | `sprintf`: `%1$s` = class short name |

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
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireNamesKeysOnConstantsClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = '';

    /** @var string[] */
    private array $excludedAttributes = [];

    private string $mode = 'warn';

    private string $message = "TODO: [RequireNamesKeysOnConstantsClassRector] %s contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).";

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->excludedAttributes = $configuration['excludedAttributes'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[NamesKeys] attribute on readonly classes that contain only string constants',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireNamesKeysOnConstantsClassRector] CacheKey contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\NamesKeys',
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
        // Must be readonly
        if (!$node->isReadonly()) {
            return null;
        }

        // Must not already have #[NamesKeys]
        if ($this->hasAttribute($node, $this->attributeClass)) {
            return null;
        }

        // Must not have an excluded attribute (e.g., #[ViewModel])
        foreach ($this->excludedAttributes as $excludedAttr) {
            if ($this->hasAttribute($node, $excludedAttr)) {
                return null;
            }
        }

        // Check if it's a pure constants class
        if (!$this->isPureConstantsClass($node)) {
            return null;
        }

        $className = (string) $node->name;

        if ($this->mode === 'auto') {
            return $this->addAttribute($node);
        }

        return $this->addTodoComment($node, $className);
    }

    private function isPureConstantsClass(Class_ $node): bool
    {
        $has_constants = false;

        foreach ($node->stmts as $stmt) {
            // Allow ClassConst (the whole point)
            if ($stmt instanceof ClassConst) {
                $has_constants = true;
                continue;
            }

            // Disallow methods and properties — not a pure constants class
            if ($stmt instanceof ClassMethod || $stmt instanceof Property) {
                return false;
            }

            // Allow trait uses (some constants classes use traits)
            // Allow comments, nops, etc.
        }

        return $has_constants;
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
utils/rector/tests/RequireNamesKeysOnConstantsClassRector/
├── RequireNamesKeysOnConstantsClassRectorTest.php
├── config/
│   └── configured_rule.php
├── Support/
│   ├── TestNamesKeys.php
│   └── TestViewModel.php
└── Fixture/
    ├── adds_todo_to_pure_constants_class.php.inc
    ├── skips_class_with_methods.php.inc
    ├── skips_class_with_properties.php.inc
    ├── skips_class_with_attribute.php.inc
    ├── skips_non_readonly_class.php.inc
    └── skips_class_with_excluded_attribute.php.inc
```

### Test: `RequireNamesKeysOnConstantsClassRectorTest.php`

```php
<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RequireNamesKeysOnConstantsClassRectorTest extends AbstractRectorTestCase
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
use Utils\Rector\Rector\RequireNamesKeysOnConstantsClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireNamesKeysOnConstantsClassRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireNamesKeysOnConstantsClassRector\\TestNamesKeys',
        'excludedAttributes' => [
            'Utils\\Rector\\Tests\\RequireNamesKeysOnConstantsClassRector\\TestViewModel',
        ],
        'mode' => 'warn',
    ]);
};
```

### Support: `TestNamesKeys.php`

```php
<?php

namespace Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestNamesKeys
{
    public function __construct(
        public string $source = '',
    ) {}
}
```

### Support: `TestViewModel.php`

```php
<?php

namespace Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestViewModel {}
```

### Fixture: `adds_todo_to_pure_constants_class.php.inc`

```php
<?php

readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}

?>
-----
<?php

// TODO: [RequireNamesKeysOnConstantsClassRector] CacheKey contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}

?>
```

### Fixture: `skips_class_with_methods.php.inc`

```php
<?php

readonly class Util
{
    public const string key = 'key';

    public static function doSomething(): void {}
}

?>
-----
<?php

readonly class Util
{
    public const string key = 'key';

    public static function doSomething(): void {}
}

?>
```

### Fixture: `skips_class_with_attribute.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class Tagged
{
    public const string key = 'key';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class Tagged
{
    public const string key = 'key';
}

?>
```

### Fixture: `skips_non_readonly_class.php.inc`

```php
<?php

class MutableConstants
{
    public const string key = 'key';
}

?>
-----
<?php

class MutableConstants
{
    public const string key = 'key';
}

?>
```

### Fixture: `skips_class_with_properties.php.inc`

```php
<?php

readonly class HasProps
{
    public const string key = 'key';
    public string $name;
}

?>
-----
<?php

readonly class HasProps
{
    public const string key = 'key';
    public string $name;
}

?>
```

### Fixture: `skips_class_with_excluded_attribute.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestViewModel;

#[TestViewModel]
readonly class ErrorViewModel
{
    public const string view_key = 'ErrorViewModel';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestViewModel;

#[TestViewModel]
readonly class ErrorViewModel
{
    public const string view_key = 'ErrorViewModel';
}

?>
```

## Registration in rector.php

```php
// Add import
use Utils\Rector\Rector\RequireNamesKeysOnConstantsClassRector;

// Add after NamesKeys-related rules
$rectorConfig->ruleWithConfiguration(RequireNamesKeysOnConstantsClassRector::class, [
    'attributeClass' => \ZeroToProd\Thryds\Helpers\KeyRegistry::class,
    'excludedAttributes' => [
        \ZeroToProd\Thryds\Helpers\ViewModel::class,
    ],
    'mode' => 'warn',
    'message' => "TODO: [RequireNamesKeysOnConstantsClassRector] %s contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).",
]);
```
