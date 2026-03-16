<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Type\TypeWithClassName;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Enum\NodeGroup;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenameVarToMatchReturnTypeRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string[]
     */
    private array $skipNames = [];

    /**
     * @param array{skipNames?: string[]} $configuration
     */
    public function configure(array $configuration): void
    {
        $this->skipNames = $configuration['skipNames'] ?? [];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename variables to exactly match the return type of the assigned call', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
$response = $router->dispatch($request);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
$ResponseInterface = $router->dispatch($request);
CODE_SAMPLE,
                ['skipNames' => ['Closure']]
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

        foreach ($stmts as $i => $stmt) {
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
                $this->traverseNodesWithCallable([$assign->expr], function (Node $node) use ($oldName, $newName): ?Node {
                    if (! $node instanceof Variable) {
                        return null;
                    }

                    if (! $this->isName($node, $oldName)) {
                        return null;
                    }

                    $node->name = $newName;
                    return $node;
                });
            }

            if (! $exprType instanceof TypeWithClassName) {
                continue;
            }

            $className = $exprType->getClassName();
            $expectedName = $this->resolveShortName($className);

            if ($currentName === $expectedName) {
                continue;
            }

            if (in_array($expectedName, $this->skipNames, strict: true)) {
                continue;
            }

            $assign->var = new Variable($expectedName);
            $renames[] = [$currentName, $expectedName];
            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        // Apply all renames to subsequent statements
        foreach ($renames as [$oldName, $newName]) {
            // Find the last assignment that introduced this rename, then rename everything after it
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
                $this->traverseNodesWithCallable([$stmts[$j]], function (Node $node) use ($oldName, $newName): ?Node {
                    if (! $node instanceof Variable) {
                        return null;
                    }

                    if (! $this->isName($node, $oldName)) {
                        return null;
                    }

                    $node->name = $newName;
                    return $node;
                });
            }
        }

        return $node;
    }

    private function resolveShortName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
