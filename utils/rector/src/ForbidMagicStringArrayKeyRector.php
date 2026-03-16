<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidMagicStringArrayKeyRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $excludedClasses = [];

    private string $mode = 'warn';

    private string $message = "TODO: Replace magic string key '%s' with a class constant";

    /** @var array<int, true> */
    private array $excludedItemIds = [];

    public function configure(array $configuration): void
    {
        $this->excludedClasses = $configuration['excludedClasses'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: Replace magic string key '%s' with a class constant";
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above array items with magic string keys, prompting replacement with a class constant or enum',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$options = [
    'cache' => '/tmp',
];
Log::error('fail', ['exception' => $e::class]);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$options = [
    // TODO: Replace magic string key 'cache' with a class constant
    'cache' => '/tmp',
];
Log::error('fail', ['exception' => $e::class]);
CODE_SAMPLE,
                    ['excludedClasses' => ['App\\Log']]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FileNode::class, ArrayItem::class, Expression::class];
    }

    /**
     * @param FileNode|ArrayItem|Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if ($node instanceof FileNode) {
            $this->excludedItemIds = [];

            if ($this->excludedClasses !== []) {
                $this->collectExcludedItems($node->stmts);
            }

            return null;
        }

        if ($node instanceof ArrayItem) {
            return $this->refactorArrayItem($node);
        }

        if ($node instanceof Expression) {
            return $this->refactorExpression($node);
        }

        return null;
    }

    private function refactorArrayItem(ArrayItem $node): ?ArrayItem
    {
        if (!$node->key instanceof String_) {
            return null;
        }

        if (isset($this->excludedItemIds[spl_object_id($node)])) {
            return null;
        }

        return $this->addTodoComment($node, $node->key->value);
    }

    private function refactorExpression(Expression $node): ?Expression
    {
        $magicStrings = [];
        $this->traverseNodesWithCallable($node->expr, function (Node $subNode) use (&$magicStrings): null {
            if ($subNode instanceof ArrayDimFetch && $subNode->dim instanceof String_) {
                $magicStrings[] = $subNode->dim->value;
            }

            return null;
        });

        if ($magicStrings === []) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $existingComments = $node->getComments();

        foreach (array_unique($magicStrings) as $keyValue) {
            array_unshift($existingComments, new Comment('// ' . sprintf($this->message, $keyValue)));
        }

        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    /**
     * @param array<Node|mixed> $nodes
     */
    private function collectExcludedItems(array $nodes): void
    {
        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            if (($node instanceof StaticCall || $node instanceof MethodCall) && $this->isExcludedCall($node)) {
                foreach ($node->args as $arg) {
                    if (!$arg instanceof Arg || !$arg->value instanceof Array_) {
                        continue;
                    }

                    foreach ($arg->value->items as $item) {
                        if ($item !== null) {
                            $this->excludedItemIds[spl_object_id($item)] = true;
                        }
                    }
                }
            }

            foreach ($node->getSubNodeNames() as $name) {
                $subNode = $node->{$name};
                if ($subNode instanceof Node) {
                    $this->collectExcludedItems([$subNode]);
                } elseif (is_array($subNode)) {
                    $this->collectExcludedItems($subNode);
                }
            }
        }
    }

    private function isExcludedCall(StaticCall|MethodCall $node): bool
    {
        if ($node instanceof StaticCall) {
            $className = $this->getName($node->class);
        } else {
            $className = $this->getName($node->var);
        }

        return $className !== null && in_array($className, $this->excludedClasses, true);
    }

    /**
     * @template T of Node
     * @param T $node
     * @return T|null
     */
    private function addTodoComment(Node $node, string $keyValue): ?Node
    {
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . sprintf($this->message, $keyValue));

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
