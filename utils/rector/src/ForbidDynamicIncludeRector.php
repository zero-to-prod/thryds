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
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidDynamicIncludeRector extends AbstractRector
{
    private const TODO_MARKER = '[opcache]';

    private const TODO_TEXT = '// TODO: [opcache] dynamic include prevents OPcache optimization';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag dynamic include/require statements where the path cannot be resolved at compile time',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
require $path;
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [opcache] dynamic include prevents OPcache optimization
require $path;
CODE_SAMPLE
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
        if (!$node->expr instanceof Include_) {
            return null;
        }

        if ($this->isStaticExpression($node->expr->expr)) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), self::TODO_MARKER)) {
                return null;
            }
        }

        $todoComment = new Comment(self::TODO_TEXT);
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
