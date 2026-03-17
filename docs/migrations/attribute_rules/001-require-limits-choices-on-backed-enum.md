# Rector Rule: RequireLimitsChoicesOnBackedEnumRector

**Enforces:** Every backed enum in `src/` must have `#[LimitsChoices]`.

## Problem

A developer adds `enum Permission: string` without `#[LimitsChoices]`. All enum-aware Rector rules (`RequireEnumValueAccessRector`, `ForbidStringComparisonOnEnumPropertyRector`, `ForbidHardcodedRouteStringRector`) that discover targets via `#[LimitsChoices]` will silently ignore this enum. It becomes an enforcement gap.

## Before / After

```php
// Before — missing attribute
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

// After (warn)
// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum Permission must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

// After (auto)
use ZeroToProd\Thryds\Helpers\LimitsChoices;

#[LimitsChoices(domain: 'TODO: describe domain')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}
```

## Configuration

```php
// rector.php
$rectorConfig->ruleWithConfiguration(RequireLimitsChoicesOnBackedEnumRector::class, [
    'attributeClass' => \ZeroToProd\Thryds\Helpers\LimitsChoices::class,
    'mode' => 'warn',
    'message' => "TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum %s must declare #[LimitsChoices] — enums limit choices (ADR-007).",
]);
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `attributeClass` | `string` | (required) | FQN of the `LimitsChoices` attribute |
| `mode` | `'auto'\|'warn'` | `'warn'` | `auto` adds attribute with placeholder domain; `warn` adds TODO |
| `message` | `string` | See above | `sprintf`: `%1$s` = enum short name |

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
use PhpParser\Node\Stmt\Enum_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireLimitsChoicesOnBackedEnumRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = '';

    private string $mode = 'warn';

    private string $message = "TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum %s must declare #[LimitsChoices] — enums limit choices (ADR-007).";

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[LimitsChoices] attribute on all backed enums',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum Permission must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\LimitsChoices',
                        'mode' => 'warn',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Enum_::class];
    }

    /**
     * @param Enum_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // Skip non-backed enums (no scalarType means pure enum)
        if ($node->scalarType === null) {
            return null;
        }

        // Skip enums that already have the attribute
        if ($this->hasAttribute($node)) {
            return null;
        }

        $enumName = (string) $node->name;

        if ($this->mode === 'auto') {
            return $this->addAttribute($node, $enumName);
        }

        return $this->addTodoComment($node, $enumName);
    }

    private function hasAttribute(Enum_ $node): bool
    {
        $shortName = $this->shortAttributeName();

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $this->attributeClass || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function shortAttributeName(): string
    {
        $parts = explode('\\', $this->attributeClass);

        return end($parts);
    }

    private function addAttribute(Enum_ $node, string $enumName): Enum_
    {
        $attr = new Attribute(
            new FullyQualified($this->attributeClass),
            [
                new Arg(
                    value: new String_('TODO: describe domain'),
                    name: new Identifier('domain'),
                ),
            ],
        );

        array_unshift($node->attrGroups, new AttributeGroup([$attr]));

        return $node;
    }

    private function addTodoComment(Enum_ $node, string $enumName): Enum_
    {
        $todoText = sprintf($this->message, $enumName);
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
utils/rector/tests/RequireLimitsChoicesOnBackedEnumRector/
├── RequireLimitsChoicesOnBackedEnumRectorTest.php
├── config/
│   └── configured_rule.php
├── Support/
│   └── TestLimitsChoices.php
└── Fixture/
    ├── adds_todo_to_backed_string_enum.php.inc
    ├── adds_todo_to_backed_int_enum.php.inc
    ├── skips_pure_enum.php.inc
    └── skips_enum_with_attribute.php.inc
```

### Test: `RequireLimitsChoicesOnBackedEnumRectorTest.php`

```php
<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireLimitsChoicesOnBackedEnumRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RequireLimitsChoicesOnBackedEnumRectorTest extends AbstractRectorTestCase
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
use Utils\Rector\Rector\RequireLimitsChoicesOnBackedEnumRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireLimitsChoicesOnBackedEnumRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireLimitsChoicesOnBackedEnumRector\\TestLimitsChoices',
        'mode' => 'warn',
    ]);
};
```

### Support: `TestLimitsChoices.php`

```php
<?php

namespace Utils\Rector\Tests\RequireLimitsChoicesOnBackedEnumRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestLimitsChoices
{
    public function __construct(
        public string $domain = '',
    ) {}
}
```

### Fixture: `adds_todo_to_backed_string_enum.php.inc`

```php
<?php

enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
-----
<?php

// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum Permission must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
```

### Fixture: `adds_todo_to_backed_int_enum.php.inc`

```php
<?php

enum Priority: int
{
    case Low = 1;
    case High = 2;
}

?>
-----
<?php

// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum Priority must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum Priority: int
{
    case Low = 1;
    case High = 2;
}

?>
```

### Fixture: `skips_pure_enum.php.inc`

```php
<?php

enum Color
{
    case Red;
    case Blue;
}

?>
-----
<?php

enum Color
{
    case Red;
    case Blue;
}

?>
```

### Fixture: `skips_enum_with_attribute.php.inc`

```php
<?php

use Utils\Rector\Tests\RequireLimitsChoicesOnBackedEnumRector\TestLimitsChoices;

#[TestLimitsChoices(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireLimitsChoicesOnBackedEnumRector\TestLimitsChoices;

#[TestLimitsChoices(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
```

## Registration in rector.php

```php
// Add import
use Utils\Rector\Rector\RequireLimitsChoicesOnBackedEnumRector;

// Add after existing enum rules
$rectorConfig->ruleWithConfiguration(RequireLimitsChoicesOnBackedEnumRector::class, [
    'attributeClass' => \ZeroToProd\Thryds\Helpers\LimitsChoices::class,
    'mode' => 'warn',
    'message' => "TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum %s must declare #[LimitsChoices] — enums limit choices (ADR-007).",
]);
```
