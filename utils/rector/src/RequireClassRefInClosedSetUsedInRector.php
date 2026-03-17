<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireClassRefInClosedSetUsedInRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var list<array{attributeClass: string, paramName: string}> */
    private array $attributes = [];

    private string $mode = 'warn';

    private string $message = "TODO: [RequireClassRefInClosedSetUsedInRector] Each %s entry must be [Class::class, 'method'], not a plain string. Found '%s'.";

    public function configure(array $configuration): void
    {
        $this->attributes = $configuration['attributes'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getNodeTypes(): array
    {
        return [Enum_::class, Class_::class];
    }

    /**
     * @param Enum_|Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Enum_ && $node->scalarType === null) {
            return null;
        }

        $allInvalid = [];

        foreach ($this->attributes as $attrConfig) {
            $attributeClass = $attrConfig['attributeClass'];
            $paramName = $attrConfig['paramName'];

            $arg = $this->findParamArg($node, $attributeClass, $paramName);
            if ($arg === null) {
                continue;
            }

            $value = $arg->value;
            if (!$value instanceof Array_) {
                continue;
            }

            foreach ($this->findInvalidEntries($value) as $entry) {
                $allInvalid[] = [$paramName, $entry];
            }
        }

        if ($allInvalid === []) {
            return null;
        }

        return $this->addTodoComments($node, $allInvalid);
    }

    private function findParamArg(Enum_|Class_ $node, string $attributeClass, string $paramName): ?Node\Arg
    {
        $shortName = $this->shortName($attributeClass);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name !== $attributeClass && $name !== $shortName) {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    if ($arg->name !== null && $this->getName($arg->name) === $paramName) {
                        return $arg;
                    }
                }
            }
        }

        return null;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * @return string[]
     */
    private function findInvalidEntries(Array_ $array): array
    {
        $invalid = [];

        foreach ($array->items as $item) {
            if ($item === null) {
                continue;
            }

            if (!$this->isValidEntry($item->value)) {
                $invalid[] = $this->describeNode($item->value);
            }
        }

        return $invalid;
    }

    private function isValidEntry(Node\Expr $expr): bool
    {
        if (!$expr instanceof Array_) {
            return false;
        }

        if (count($expr->items) !== 2) {
            return false;
        }

        $first = $expr->items[0]?->value;
        $second = $expr->items[1]?->value;

        return $first instanceof ClassConstFetch && $second instanceof String_;
    }

    private function describeNode(Node\Expr $expr): string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        return '<non-callable entry>';
    }

    /**
     * @param Enum_|Class_                    $node
     * @param list<array{string, string}>     $invalidEntries Each is [paramName, entryDescription].
     */
    private function addTodoComments(Enum_|Class_ $node, array $invalidEntries): Enum_|Class_
    {
        $comments = $node->getComments();
        $changed = false;

        foreach ($invalidEntries as [$paramName, $entry]) {
            $todoText = sprintf($this->message, $paramName, $entry);

            $alreadyExists = false;
            foreach ($comments as $comment) {
                if (str_contains($comment->getText(), $todoText)) {
                    $alreadyExists = true;
                    break;
                }
            }

            if (!$alreadyExists) {
                array_unshift($comments, new Comment('// ' . $todoText));
                $changed = true;
            }
        }

        if (!$changed) {
            return $node;
        }

        $node->setAttribute('comments', $comments);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            "Enforce that attribute array params (usedIn, used_in) use [Class::class, 'method'] format, not plain strings",
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[ClosedSet(usedIn: ['Router::map'])]
enum Route: string
{
    case home = '/';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireClassRefInClosedSetUsedInRector] Each usedIn entry must be [Class::class, 'method'], not a plain string. Found 'Router::map'.
#[ClosedSet(usedIn: ['Router::map'])]
enum Route: string
{
    case home = '/';
}
CODE_SAMPLE,
                    [
                        'attributes' => [
                            ['attributeClass' => 'App\\Helpers\\ClosedSet', 'paramName' => 'usedIn'],
                        ],
                        'mode' => 'warn',
                    ],
                ),
            ]
        );
    }
}
