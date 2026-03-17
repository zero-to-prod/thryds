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

final class RequireConstForRepeatedArrayKeyRector extends AbstractRector implements ConfigurableRectorInterface
{
    private int $minOccurrences = 2;

    private int $minLength = 3;

    /** @var string[] */
    private array $excludedKeys = [];

    /** @var string[] */
    private array $excludedClasses = [];

    private string $mode = 'warn';

    private string $message = "TODO: [RequireConstForRepeatedArrayKeyRector] '%s' used %dx as array key — extract to a class constant.";

    /**
     * Object IDs of String_ nodes whose parent call is excluded.
     * @var array<int, true>
     */
    private array $excludedStringIds = [];

    /**
     * Maps repeated key value => [expressionObjectId => count].
     * Only the first Expression containing each repeated key should get the TODO.
     * @var array<string, array<int, int>>
     */
    private array $keyToExpressionIds = [];

    public function configure(array $configuration): void
    {
        $this->minOccurrences = $configuration['minOccurrences'] ?? 2;
        $this->minLength = $configuration['minLength'] ?? 3;
        $this->excludedKeys = $configuration['excludedKeys'] ?? [];
        $this->excludedClasses = $configuration['excludedClasses'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [RequireConstForRepeatedArrayKeyRector] '%s' used %dx as array key — extract to a class constant.";
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment on the first occurrence of a string array key that is used 2+ times in the same file',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$hits = $status['opcache_statistics']['hits'];
$misses = $status['opcache_statistics']['misses'];
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireConstForRepeatedArrayKeyRector] 'opcache_statistics' used 2x as array key — extract to a class constant.
$hits = $status['opcache_statistics']['hits'];
$misses = $status['opcache_statistics']['misses'];
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'minOccurrences' => 2,
                        'minLength' => 3,
                    ]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FileNode::class, Expression::class];
    }

    /**
     * @param FileNode|Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if ($node instanceof FileNode) {
            $this->collectFileData($node->stmts);
            return null;
        }

        return $this->refactorExpression($node);
    }

    private function refactorExpression(Expression $node): ?Expression
    {
        $nodeId = spl_object_id($node);
        $keysForThisNode = [];

        foreach ($this->keyToExpressionIds as $keyValue => $expressionIds) {
            if (array_key_first($expressionIds) === $nodeId) {
                $keysForThisNode[$keyValue] = array_sum($expressionIds);
            }
        }

        if ($keysForThisNode === []) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $existingComments = $node->getComments();

        foreach (array_reverse($keysForThisNode) as $keyValue => $totalCount) {
            array_unshift($existingComments, new Comment('// ' . sprintf($this->message, $keyValue, $totalCount)));
        }

        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    /**
     * @param array<Node|mixed> $stmts
     */
    private function collectFileData(array $stmts): void
    {
        $this->excludedStringIds = [];
        $this->keyToExpressionIds = [];

        // First pass: collect excluded string node IDs
        $this->walkNodes($stmts, function (Node $node): void {
            if (($node instanceof StaticCall || $node instanceof MethodCall) && $this->isExcludedCall($node)) {
                foreach ($node->args as $arg) {
                    if (!$arg instanceof Arg || !$arg->value instanceof Array_) {
                        continue;
                    }

                    foreach ($arg->value->items as $item) {
                        if ($item instanceof ArrayItem && $item->key instanceof String_) {
                            $this->excludedStringIds[spl_object_id($item->key)] = true;
                        }
                    }
                }
            }
        });

        // Second pass: for each top-level Expression, collect keys used in it
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }

            $stmtId = spl_object_id($stmt);
            $keys = $this->collectKeysInNode($stmt);

            foreach ($keys as $keyValue) {
                if (!isset($this->keyToExpressionIds[$keyValue])) {
                    $this->keyToExpressionIds[$keyValue] = [];
                }

                $this->keyToExpressionIds[$keyValue][$stmtId] = ($this->keyToExpressionIds[$keyValue][$stmtId] ?? 0) + 1;
            }
        }

        // Filter to only keys with >= minOccurrences across all expressions
        foreach ($this->keyToExpressionIds as $keyValue => $expressionIds) {
            $totalCount = array_sum($expressionIds);
            if ($totalCount < $this->minOccurrences) {
                unset($this->keyToExpressionIds[$keyValue]);
            }
        }
    }

    /**
     * @return string[]
     */
    private function collectKeysInNode(Node $node): array
    {
        $keys = [];

        $this->traverseNodesWithCallable($node, function (Node $subNode) use (&$keys): null {
            if ($subNode instanceof ArrayDimFetch && $subNode->dim instanceof String_) {
                $value = $subNode->dim->value;
                if ($this->isEligibleKey($value) && !isset($this->excludedStringIds[spl_object_id($subNode->dim)])) {
                    $keys[] = $value;
                }
            }

            if ($subNode instanceof ArrayItem && $subNode->key instanceof String_) {
                $value = $subNode->key->value;
                if ($this->isEligibleKey($value) && !isset($this->excludedStringIds[spl_object_id($subNode->key)])) {
                    $keys[] = $value;
                }
            }

            return null;
        });

        return $keys;
    }

    private function isEligibleKey(string $value): bool
    {
        if (strlen($value) < $this->minLength) {
            return false;
        }

        return !in_array($value, $this->excludedKeys, true);
    }

    private function isExcludedCall(StaticCall|MethodCall $node): bool
    {
        if ($node instanceof StaticCall) {
            $name = $this->getName($node->class);
        } else {
            $name = $this->getName($node->var);
        }

        return $name !== null && in_array($name, $this->excludedClasses, true);
    }

    /**
     * @param array<Node|mixed> $nodes
     */
    private function walkNodes(array $nodes, callable $callback): void
    {
        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            $callback($node);

            foreach ($node->getSubNodeNames() as $name) {
                $subNode = $node->{$name};
                if ($subNode instanceof Node) {
                    $this->walkNodes([$subNode], $callback);
                } elseif (is_array($subNode)) {
                    $this->walkNodes($subNode, $callback);
                }
            }
        }
    }
}
