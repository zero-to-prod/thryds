<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidDynamicIncludeRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: Dynamic include prevents compile-time optimization';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag dynamic include/require statements where the path cannot be resolved at compile time',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
require $path;
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: Dynamic include prevents compile-time optimization
require $path;
CODE_SAMPLE,
                    ['message' => 'TODO: Dynamic include prevents compile-time optimization']
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Expression::class];
    }

    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if (!$node->expr instanceof Include_) {
            return null;
        }

        if ($this->isStaticExpression($node->expr->expr)) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . $this->message);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    private function isStaticExpression(Node\Expr $expr): bool
    {
        if ($expr instanceof String_) {
            return true;
        }

        if ($expr instanceof Dir || $expr instanceof File) {
            return true;
        }

        if ($expr instanceof Concat) {
            return $this->isStaticExpression($expr->left) && $this->isStaticExpression($expr->right);
        }

        return false;
    }
}
