<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Type\TypeWithClassName;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Enum\NodeGroup;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenamePrimitiveVarToSnakeCaseRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Rename local variables holding primitive types to snake_case', [
            new CodeSample(
                <<<'CODE_SAMPLE'
$myString = 'hello';
echo $myString;
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
$my_string = 'hello';
echo $my_string;
CODE_SAMPLE
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return NodeGroup::STMTS_AWARE;
    }

    public function refactor(Node $node): ?Node
    {
        $stmts = $node->stmts;
        if ($stmts === null || $stmts === []) {
            return null;
        }

        $hasChanged = false;
        $renames = [];

        foreach ($stmts as $stmt) {
            if (! $stmt instanceof Expression) {
                continue;
            }

            if (! $stmt->expr instanceof Assign) {
                continue;
            }

            $assign = $stmt->expr;

            if (! $assign->var instanceof Variable) {
                continue;
            }

            $currentName = $this->getName($assign->var);
            if ($currentName === null) {
                continue;
            }

            // Resolve the type BEFORE applying any prior renames to the RHS,
            // since PHPStan's scope uses the original variable names.
            $exprType = $this->getType($assign->expr);

            // Apply any prior renames to the right-hand side of this assignment
            foreach ($renames as [$oldName, $newName]) {
                $this->traverseNodesWithCallable([$assign->expr], function (Node $innerNode) use ($oldName, $newName): ?Node {
                    if (! $innerNode instanceof Variable) {
                        return null;
                    }

                    if (! $this->isName($innerNode, $oldName)) {
                        return null;
                    }

                    $innerNode->name = $newName;
                    return $innerNode;
                });
            }

            if (! $this->isPrimitiveType($exprType)) {
                continue;
            }

            $snakeName = $this->toSnakeCase($currentName);
            if ($currentName === $snakeName) {
                continue;
            }

            $assign->var = new Variable($snakeName);
            $renames[] = [$currentName, $snakeName];
            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        // Apply all renames to subsequent statements
        foreach ($renames as [$oldName, $newName]) {
            $lastAssignIndex = null;
            foreach ($stmts as $i => $stmt) {
                if ($stmt instanceof Expression
                    && $stmt->expr instanceof Assign
                    && $stmt->expr->var instanceof Variable
                    && $this->isName($stmt->expr->var, $newName)
                ) {
                    $lastAssignIndex = $i;
                }
            }

            if ($lastAssignIndex === null) {
                continue;
            }

            for ($j = $lastAssignIndex + 1; $j < count($stmts); $j++) {
                $this->traverseNodesWithCallable([$stmts[$j]], function (Node $innerNode) use ($oldName, $newName): ?Node {
                    if (! $innerNode instanceof Variable) {
                        return null;
                    }

                    if (! $this->isName($innerNode, $oldName)) {
                        return null;
                    }

                    $innerNode->name = $newName;
                    return $innerNode;
                });
            }
        }

        return $node;
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

    private function isPrimitiveType(\PHPStan\Type\Type $type): bool
    {
        if ($type instanceof TypeWithClassName) {
            return false;
        }

        if ($type->isScalar()->yes()) {
            return true;
        }

        if ($type->isArray()->yes()) {
            return true;
        }

        if ($type->isNull()->yes()) {
            return true;
        }

        return false;
    }

    private function toSnakeCase(string $name): string
    {
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
        return strtolower((string) $snake);
    }
}
