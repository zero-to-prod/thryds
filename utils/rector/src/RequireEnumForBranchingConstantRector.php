<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireEnumForBranchingConstantRector extends AbstractRector implements ConfigurableRectorInterface
{
    private int $minCases = 3;

    private string $mode = 'warn';

    private string $message = 'TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $%s is compared against %d literals (%s), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md';

    public function configure(array $configuration): void
    {
        $this->minCases = $configuration['minCases'] ?? 3;
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $%s is compared against %d literals (%s), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md';
    }

    public function getNodeTypes(): array
    {
        return [If_::class, Switch_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if ($node instanceof If_) {
            return $this->refactorIf($node);
        }

        if ($node instanceof Switch_) {
            return $this->refactorSwitch($node);
        }

        return null;
    }

    private function refactorIf(If_ $node): ?Node
    {
        // Only process top-level if chains — skip elseif nodes (they are handled by the parent If_)
        // We detect an if/elseif chain here; ElseIf_ nodes are children of If_, so we only enter
        // on If_ nodes that either have elseifs or whose parent is not an If_/ElseIf_.

        // Collect all branches of the if/elseif chain
        $conditions = [$node->cond];
        foreach ($node->elseifs as $elseif) {
            $conditions[] = $elseif->cond;
        }

        // Need at least minCases branches to be interesting
        if (count($conditions) < $this->minCases) {
            return null;
        }

        /** @var array<string, list<string>> $varLiterals map varName -> list of literal reprs */
        $varLiterals = [];

        foreach ($conditions as $cond) {
            $extracted = $this->extractVarLiteralComparison($cond);
            if ($extracted === null) {
                continue;
            }

            [$varName, $literalRepr] = $extracted;
            $varLiterals[$varName][] = $literalRepr;
        }

        foreach ($varLiterals as $varName => $literals) {
            $unique = array_values(array_unique($literals));

            if (count($unique) < $this->minCases) {
                continue;
            }

            $marker = strstr($this->message, '%', true) ?: $this->message;
            foreach ($node->getComments() as $comment) {
                if (str_contains($comment->getText(), $marker)) {
                    return null;
                }
            }

            $valuesStr = implode(', ', $unique);
            $todoText = sprintf($this->message, $varName, count($unique), $valuesStr);

            $comments = $node->getComments();
            array_unshift($comments, new Comment('// ' . $todoText));
            $node->setAttribute('comments', $comments);

            return $node;
        }

        return null;
    }

    private function refactorSwitch(Switch_ $node): ?Node
    {
        /** @var array<string, list<string>> $varLiterals */
        $varLiterals = [];

        foreach ($node->cases as $case) {
            if ($case->cond === null) {
                // default case — skip
                continue;
            }

            $literal = $this->extractLiteralRepr($case->cond);
            if ($literal === null) {
                continue;
            }

            // The switch subject must be a variable
            $varName = $this->extractVariableName($node->cond);
            if ($varName === null) {
                continue;
            }

            $varLiterals[$varName][] = $literal;
        }

        foreach ($varLiterals as $varName => $literals) {
            $unique = array_values(array_unique($literals));

            if (count($unique) < $this->minCases) {
                continue;
            }

            $marker = strstr($this->message, '%', true) ?: $this->message;
            foreach ($node->getComments() as $comment) {
                if (str_contains($comment->getText(), $marker)) {
                    return null;
                }
            }

            $valuesStr = implode(', ', $unique);
            $todoText = sprintf($this->message, $varName, count($unique), $valuesStr);

            $comments = $node->getComments();
            array_unshift($comments, new Comment('// ' . $todoText));
            $node->setAttribute('comments', $comments);

            return $node;
        }

        return null;
    }

    /**
     * Extracts [varName, literalRepr] from a binary comparison like $x === 'foo' or 'foo' === $x,
     * using === or == only (not range operators).
     *
     * @return array{string, string}|null
     */
    private function extractVarLiteralComparison(Node\Expr $expr): ?array
    {
        if (!$expr instanceof Identical && !$expr instanceof Equal) {
            return null;
        }

        $left = $expr->left;
        $right = $expr->right;

        if ($this->isLiteral($right)) {
            $varName = $this->extractVariableName($left);
            if ($varName !== null) {
                return [$varName, $this->extractLiteralRepr($right) ?? ''];
            }
        }

        if ($this->isLiteral($left)) {
            $varName = $this->extractVariableName($right);
            if ($varName !== null) {
                return [$varName, $this->extractLiteralRepr($left) ?? ''];
            }
        }

        return null;
    }

    private function extractVariableName(Node\Expr $expr): ?string
    {
        if ($expr instanceof Variable && is_string($expr->name)) {
            return $expr->name;
        }

        return null;
    }

    private function isLiteral(Node\Expr $expr): bool
    {
        return $expr instanceof String_ || $expr instanceof Int_;
    }

    private function extractLiteralRepr(Node\Expr $expr): ?string
    {
        if ($expr instanceof String_) {
            return "'{$expr->value}'";
        }

        if ($expr instanceof Int_) {
            return (string) $expr->value;
        }

        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detect if/elseif chains or switch statements comparing a variable against 3+ string/int literals — flag as an implicit closed set that should be extracted to a backed enum',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
function handle(string $status): string
{
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    } elseif ($status === 'pending') {
        return 'Pending';
    }
    return 'Unknown';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function handle(string $status): string
{
    // TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $status is compared against 3 literals ('active', 'inactive', 'pending'), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    } elseif ($status === 'pending') {
        return 'Pending';
    }
    return 'Unknown';
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'minCases' => 3,
                    ]
                ),
            ]
        );
    }
}
