<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeVisitor;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Enum\NodeGroup;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class InlineSingleUseVariableRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Inline a variable that is assigned once and used exactly once on the very next statement',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$request_id = RequestId::init($server_request_interface);
$response = handle($request_id);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$response = handle(RequestId::init($server_request_interface));
CODE_SAMPLE,
                    ['mode' => 'auto']
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return NodeGroup::STMTS_AWARE;
    }

    /**
     * @param FileNode|Node $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null || $node->stmts === []) {
            return null;
        }

        $stmts = $node->stmts;
        $hasChanged = false;

        $i = 0;
        while ($i < count($stmts) - 1) {
            $assignStmt = $stmts[$i];
            $nextStmt = $stmts[$i + 1];

            if (! $this->isSimpleAssignStatement($assignStmt)) {
                $i++;
                continue;
            }

            /** @var Expression $assignStmt */
            /** @var Assign $assign */
            $assign = $assignStmt->expr;

            /** @var Variable $var */
            $var = $assign->var;
            $varName = $this->getName($var);

            if ($varName === null) {
                $i++;
                continue;
            }

            // Count total assignments and uses in the entire scope
            $assignCount = $this->countAssignments($stmts, $varName);
            $useCount = $this->countUsages($stmts, $varName, $i);

            if ($assignCount !== 1 || $useCount !== 1) {
                $i++;
                continue;
            }

            // Verify the single usage is on the very next statement
            if (! $this->hasVariableUsage($nextStmt, $varName)) {
                $i++;
                continue;
            }

            // Ensure the next statement does not contain a control structure that would
            // change evaluation order (loop/conditional/closure wrapping the usage)
            if ($this->usageIsInsideControlStructure($nextStmt, $varName)) {
                $i++;
                continue;
            }

            // Ensure the variable is not passed by reference in the next statement
            if ($this->isPassedByReference($nextStmt, $varName)) {
                $i++;
                continue;
            }

            if ($this->mode !== 'auto') {
                if ($this->addMessageComment($assignStmt) !== null) {
                    $hasChanged = true;
                }
                $i++;
                continue;
            }

            // Replace the variable usage in nextStmt with the assigned expression
            $inlinedExpr = $assign->expr;
            $this->traverseNodesWithCallable($nextStmt, function (Node $inner) use ($varName, $inlinedExpr): ?Node {
                if (! $inner instanceof Variable) {
                    return null;
                }

                if ($this->getName($inner) !== $varName) {
                    return null;
                }

                return $inlinedExpr;
            });

            // Remove the assignment statement by splicing it out
            array_splice($stmts, $i, 1);
            $hasChanged = true;
            // Do not increment $i: the next statement is now at position $i
        }

        if (! $hasChanged) {
            return null;
        }

        $node->stmts = $stmts;

        return $node;
    }

    private function isSimpleAssignStatement(Node $stmt): bool
    {
        if (! $stmt instanceof Expression) {
            return false;
        }

        if (! $stmt->expr instanceof Assign) {
            return false;
        }

        return $stmt->expr->var instanceof Variable;
    }

    /**
     * Count the number of times $varName is assigned (via simple `=`) in the flat $stmts list.
     *
     * @param Node[] $stmts
     */
    private function countAssignments(array $stmts, string $varName): int
    {
        $count = 0;

        foreach ($stmts as $stmt) {
            $this->traverseNodesWithCallable($stmt, function (Node $node) use ($varName, &$count): null {
                if ($node instanceof Assign && $node->var instanceof Variable && $this->getName($node->var) === $varName) {
                    $count++;
                }

                return null;
            });
        }

        return $count;
    }

    /**
     * Count usages of $varName as a read-reference across all $stmts.
     * Excludes the LHS of the assignment statement at $assignStmtIndex (that is a write, not a read).
     * Also excludes the RHS of that assignment from counting (it is already the definition).
     *
     * @param Node[] $stmts
     */
    private function countUsages(array $stmts, string $varName, int $assignStmtIndex): int
    {
        $count = 0;

        foreach ($stmts as $idx => $stmt) {
            if ($idx === $assignStmtIndex) {
                // For the assignment statement, only count uses in the RHS (the assigned expression),
                // not the LHS Variable itself (which is a write).
                // A variable appearing in its own RHS (e.g. $x = $x + 1) counts as a use.
                if (
                    $stmt instanceof Expression
                    && $stmt->expr instanceof Assign
                    && $stmt->expr->var instanceof Variable
                    && $this->getName($stmt->expr->var) === $varName
                ) {
                    $this->traverseNodesWithCallable($stmt->expr->expr, function (Node $node) use ($varName, &$count): null {
                        if ($node instanceof Variable && $this->getName($node) === $varName) {
                            $count++;
                        }

                        return null;
                    });
                }

                continue;
            }

            $this->traverseNodesWithCallable($stmt, function (Node $node) use ($varName, &$count): null {
                if ($node instanceof Variable && $this->getName($node) === $varName) {
                    $count++;
                }

                return null;
            });
        }

        return $count;
    }

    /**
     * Check whether $varName appears as a Variable read anywhere inside $stmt.
     */
    private function hasVariableUsage(Node $stmt, string $varName): bool
    {
        $found = false;

        $this->traverseNodesWithCallable($stmt, function (Node $node) use ($varName, &$found): null {
            if ($node instanceof Variable && $this->getName($node) === $varName) {
                $found = true;
            }

            return null;
        });

        return $found;
    }

    /**
     * Returns true if the variable usage in $stmt is nested inside a control structure
     * (loop, conditional, or closure) that would change evaluation semantics.
     */
    private function usageIsInsideControlStructure(Node $stmt, string $varName): bool
    {
        $isNested = false;

        $this->traverseNodesWithCallable($stmt, function (Node $node) use ($varName, &$isNested): ?int {
            if (
                $node instanceof Foreach_
                || $node instanceof For_
                || $node instanceof While_
                || $node instanceof Do_
                || $node instanceof If_
                || $node instanceof Switch_
                || $node instanceof Closure
            ) {
                // Check if the variable is used within this control structure
                $usedInside = false;
                $this->traverseNodesWithCallable($node, function (Node $inner) use ($varName, &$usedInside): null {
                    if ($inner instanceof Variable && $this->getName($inner) === $varName) {
                        $usedInside = true;
                    }

                    return null;
                });

                if ($usedInside) {
                    $isNested = true;
                }

                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            return null;
        });

        return $isNested;
    }

    /**
     * Returns true if $varName is passed by reference (e.g. foo(&$var)) in $stmt.
     */
    private function isPassedByReference(Node $stmt, string $varName): bool
    {
        $byRef = false;

        $this->traverseNodesWithCallable($stmt, function (Node $node) use ($varName, &$byRef): null {
            if (! $node instanceof Node\Arg) {
                return null;
            }

            if (! $node->byRef) {
                return null;
            }

            if (! $node->value instanceof Variable) {
                return null;
            }

            if ($this->getName($node->value) === $varName) {
                $byRef = true;
            }

            return null;
        });

        return $byRef;
    }

    private function addMessageComment(Node $node): ?Node
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $this->message));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
