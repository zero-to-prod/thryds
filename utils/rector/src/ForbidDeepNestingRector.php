<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\While_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidDeepNestingRector extends AbstractRector implements ConfigurableRectorInterface
{
    private int $maxDepth = 3;

    private int $maxNegationComplexity = 2;

    private string $todoMessage = 'TODO: Reduce nesting depth';

    public function configure(array $configuration): void
    {
        $this->maxDepth = $configuration['maxDepth'] ?? 3;
        $this->maxNegationComplexity = $configuration['maxNegationComplexity'] ?? 2;
        $this->todoMessage = $configuration['todoMessage'] ?? 'TODO: Reduce nesting depth';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Reduce nesting depth by inverting conditions into early returns, continues, and guard clauses',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
function process(Order $Order): void {
    if ($Order->isValid()) {
        $this->validate($Order);
        $this->save($Order);
        $this->notify($Order);
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function process(Order $Order): void {
    if (!$Order->isValid()) {
        return;
    }
    $this->validate($Order);
    $this->save($Order);
    $this->notify($Order);
}
CODE_SAMPLE,
                    ['maxDepth' => 3, 'maxNegationComplexity' => 2]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }

    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null) {
            return null;
        }

        $depth = $this->measureDepth($node->stmts);
        if ($depth <= $this->maxDepth) {
            return null;
        }

        $changed = false;

        do {
            $passChanged = $this->reduceNesting($node->stmts, false);
            if ($passChanged) {
                $changed = true;
            }
            $depth = $this->measureDepth($node->stmts);
        } while ($passChanged && $depth > $this->maxDepth);

        if (!$changed) {
            return $this->addTodoToNode($node, $depth);
        }

        if ($depth > $this->maxDepth) {
            $this->addTodoToNode($node, $depth);
        }

        return $node;
    }

    /**
     * @param Stmt[] $stmts
     */
    private function measureDepth(array $stmts, int $current = 0): int
    {
        $max = $current;

        foreach ($stmts as $stmt) {
            $max = max($max, $this->measureNodeDepth($stmt, $current));
        }

        return $max;
    }

    private function measureNodeDepth(Node $node, int $current): int
    {
        $max = $current;

        if ($this->isNestingNode($node)) {
            $current++;
            $max = $current;
        }

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            return $max;
        }

        foreach ($node->getSubNodeNames() as $name) {
            $sub = $node->{$name};
            if ($sub instanceof Node) {
                $max = max($max, $this->measureNodeDepth($sub, $current));
            } elseif (is_array($sub)) {
                foreach ($sub as $child) {
                    if ($child instanceof Node) {
                        $max = max($max, $this->measureNodeDepth($child, $current));
                    }
                }
            }
        }

        return $max;
    }

    private function isNestingNode(Node $node): bool
    {
        return $node instanceof If_
            || $node instanceof ElseIf_
            || $node instanceof Else_
            || $node instanceof For_
            || $node instanceof Foreach_
            || $node instanceof While_
            || $node instanceof Do_
            || $node instanceof Switch_
            || $node instanceof Case_
            || $node instanceof TryCatch
            || $node instanceof Catch_
            || $node instanceof Match_;
    }

    /**
     * @param Stmt[] $stmts
     */
    private function reduceNesting(array &$stmts, bool $inLoop): bool
    {
        $changed = false;

        for ($i = 0; $i < count($stmts); $i++) {
            $stmt = $stmts[$i];

            if ($stmt instanceof If_) {
                if ($this->tryInvertIfToGuard($stmts, $i, $inLoop)) {
                    $changed = true;
                    continue;
                }

                if ($this->tryMergeNestedIf($stmts, $i)) {
                    $changed = true;
                    $i--;
                    continue;
                }
            }

            if ($stmt instanceof Foreach_ || $stmt instanceof For_ || $stmt instanceof While_ || $stmt instanceof Do_) {
                if ($this->reduceNesting($stmt->stmts, true)) {
                    $changed = true;
                }
            }

            if ($stmt instanceof If_) {
                if ($this->reduceNesting($stmt->stmts, $inLoop)) {
                    $changed = true;
                }
                foreach ($stmt->elseifs as $elseif) {
                    if ($this->reduceNesting($elseif->stmts, $inLoop)) {
                        $changed = true;
                    }
                }
                if ($stmt->else !== null && $this->reduceNesting($stmt->else->stmts, $inLoop)) {
                    $changed = true;
                }
            }

            if ($stmt instanceof TryCatch) {
                if ($this->reduceNesting($stmt->stmts, $inLoop)) {
                    $changed = true;
                }
                foreach ($stmt->catches as $catch) {
                    if ($this->reduceNesting($catch->stmts, $inLoop)) {
                        $changed = true;
                    }
                }
                if ($stmt->finally !== null && $this->reduceNesting($stmt->finally->stmts, $inLoop)) {
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * @param Stmt[] $stmts
     */
    private function tryInvertIfToGuard(array &$stmts, int $index, bool $inLoop): bool
    {
        $if = $stmts[$index];
        if (!$if instanceof If_) {
            return false;
        }

        if ($if->elseifs !== [] || $if->else !== null) {
            return false;
        }

        if (!$this->isLastMeaningfulStmt($stmts, $index)) {
            return false;
        }

        $inverted = $this->invertCondition($if->cond);
        if ($inverted === null) {
            return false;
        }

        if ($this->countBooleanOperators($inverted) > $this->maxNegationComplexity) {
            return false;
        }

        $guard = new If_($inverted);
        $guard->stmts = $inLoop ? [new Continue_()] : [new Return_()];

        $comments = $if->getComments();
        if ($comments !== []) {
            $guard->setAttribute('comments', $comments);
        }

        array_splice($stmts, $index, 1, array_merge([$guard], $if->stmts));

        return true;
    }

    /**
     * @param Stmt[] $stmts
     */
    private function tryMergeNestedIf(array &$stmts, int $index): bool
    {
        $if = $stmts[$index];
        if (!$if instanceof If_) {
            return false;
        }

        if ($if->elseifs !== [] || $if->else !== null) {
            return false;
        }

        if (count($if->stmts) !== 1) {
            return false;
        }

        $inner = $if->stmts[0];
        if (!$inner instanceof If_) {
            return false;
        }

        if ($inner->elseifs !== [] || $inner->else !== null) {
            return false;
        }

        $if->cond = new BooleanAnd($if->cond, $inner->cond);
        $if->stmts = $inner->stmts;

        return true;
    }

    /**
     * @param Stmt[] $stmts
     */
    private function isLastMeaningfulStmt(array $stmts, int $index): bool
    {
        for ($i = $index + 1; $i < count($stmts); $i++) {
            if ($stmts[$i] instanceof Nop) {
                continue;
            }

            return false;
        }

        return true;
    }

    private function invertCondition(Node\Expr $expr): ?Node\Expr
    {
        if ($expr instanceof BooleanNot) {
            return $expr->expr;
        }

        if ($expr instanceof Identical) {
            return new NotIdentical($expr->left, $expr->right);
        }
        if ($expr instanceof NotIdentical) {
            return new Identical($expr->left, $expr->right);
        }
        if ($expr instanceof Equal) {
            return new NotEqual($expr->left, $expr->right);
        }
        if ($expr instanceof NotEqual) {
            return new Equal($expr->left, $expr->right);
        }
        if ($expr instanceof Greater) {
            return new SmallerOrEqual($expr->left, $expr->right);
        }
        if ($expr instanceof GreaterOrEqual) {
            return new Smaller($expr->left, $expr->right);
        }
        if ($expr instanceof Smaller) {
            return new GreaterOrEqual($expr->left, $expr->right);
        }
        if ($expr instanceof SmallerOrEqual) {
            return new Greater($expr->left, $expr->right);
        }

        return new BooleanNot($expr);
    }

    private function countBooleanOperators(Node\Expr $expr): int
    {
        if (!$expr instanceof BinaryOp && !$expr instanceof BooleanNot) {
            return 0;
        }

        $count = 0;
        if ($expr instanceof BooleanAnd || $expr instanceof BooleanOr) {
            $count = 1;
        }

        if ($expr instanceof BinaryOp) {
            $count += $this->countBooleanOperators($expr->left);
            $count += $this->countBooleanOperators($expr->right);
        }

        if ($expr instanceof BooleanNot) {
            $count += $this->countBooleanOperators($expr->expr);
        }

        return $count;
    }

    private function addTodoToNode(ClassMethod|Function_ $node, int $depth): ?Node
    {
        $message = $this->todoMessage . ' (current: ' . $depth . ', max: ' . $this->maxDepth . ')';

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->todoMessage)) {
                return null;
            }
        }

        $existingComments = $node->getComments();
        $todoComment = new Comment('// ' . $message);
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
