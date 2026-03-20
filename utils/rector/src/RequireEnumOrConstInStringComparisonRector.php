<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\While_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireEnumOrConstInStringComparisonRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = "TODO: [RequireEnumOrConstInStringComparisonRector] Raw string '%s' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md";

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [RequireEnumOrConstInStringComparisonRector] Raw string '%s' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md";
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag identical comparisons (=== / !==) that compare against a raw string literal, prompting replacement with an enum case or named constant',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
if ($request->getMethod() === 'POST') {
    // handle POST
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'POST' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
if ($request->getMethod() === 'POST') {
    // handle POST
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                    ],
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [If_::class, ElseIf_::class, Expression::class, Return_::class, While_::class, Do_::class];
    }

    /**
     * @param If_|ElseIf_|Expression|Return_|While_|Do_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        $expr = $this->extractExpr($node);

        if ($expr === null) {
            return null;
        }

        $rawString = $this->findRawStringInExpr($expr);

        if ($rawString === null) {
            return null;
        }

        return $this->addTodoComment($node, $rawString);
    }

    /**
     * Extract the condition or expression from a statement node.
     */
    private function extractExpr(Node $node): ?Expr
    {
        if ($node instanceof If_ || $node instanceof ElseIf_ || $node instanceof While_ || $node instanceof Do_) {
            return $node->cond;
        }

        if ($node instanceof Expression) {
            return $node->expr;
        }

        if ($node instanceof Return_) {
            return $node->expr;
        }

        return null;
    }

    /**
     * Search an expression for the first string literal used in a === or !== comparison.
     * Walks into compound boolean expressions (&&, ||, ternary, etc.) but stops at
     * the first match so a single statement only gets one TODO comment.
     */
    private function findRawStringInExpr(Expr $expr): ?string
    {
        $found = null;

        $this->traverseNodesWithCallable([$expr], function (Node $inner) use (&$found): ?int {
            if (!$inner instanceof Identical && !$inner instanceof NotIdentical) {
                return null;
            }

            if ($inner->left instanceof String_ && $inner->left->value !== '') {
                $found = $inner->left->value;
                return \PhpParser\NodeVisitor::STOP_TRAVERSAL;
            }

            if ($inner->right instanceof String_ && $inner->right->value !== '') {
                $found = $inner->right->value;
                return \PhpParser\NodeVisitor::STOP_TRAVERSAL;
            }

            return null;
        });

        return $found;
    }

    private function addTodoComment(Node $node, string $rawString): ?Node
    {
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoText = sprintf($this->message, $rawString);
        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
