<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Enum\NodeGroup;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ExtractRepeatedExpressionToVariableRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string[]
     */
    private array $pureFunctions = [];

    private string $mode = 'auto';

    private string $message = '';

    /**
     * @param string[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
        $this->pureFunctions = $configuration['functions'] ?? $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Extract repeated pure function calls into a variable assigned once before first usage',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$a = dirname(__DIR__) . '/var/cache';
$b = dirname(__DIR__) . '/templates';
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$baseDir = dirname(__DIR__);
$a = $baseDir . '/var/cache';
$b = $baseDir . '/templates';
CODE_SAMPLE,
                    ['dirname']
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

        // Collect all FuncCall occurrences grouped by their printed form
        // Each entry: ['node' => FuncCall, 'stmtIndex' => int]
        /** @var array<string, list<array{node: FuncCall, stmtIndex: int}>> $groups */
        $groups = [];

        foreach ($stmts as $stmtIndex => $stmt) {
            $this->traverseNodesWithCallable($stmt, function (Node $inner) use ($stmtIndex, &$groups): null {
                if (! $inner instanceof FuncCall) {
                    return null;
                }

                if (! $this->isNames($inner, $this->pureFunctions)) {
                    return null;
                }

                $key = $this->nodeComparator->printWithoutComments($inner);
                $groups[$key][] = ['node' => $inner, 'stmtIndex' => $stmtIndex];

                return null;
            });
        }

        $hasChanged = false;

        foreach ($groups as $key => $occurrences) {
            if (count($occurrences) < 2) {
                continue;
            }

            if ($this->mode !== 'auto') {
                $firstStmt = $stmts[$occurrences[0]['stmtIndex']];
                $this->addMessageComment($firstStmt);
                $hasChanged = true;
                continue;
            }

            $representativeCall = $occurrences[0]['node'];
            $varName = $this->resolveVariableName($representativeCall);

            $variable = new Variable($varName);

            // Replace all occurrences of the FuncCall with the variable
            foreach ($occurrences as $occurrence) {
                // Replace by reference: we must mutate the original node object
                // We do this by walking the stmt and replacing matching FuncCall nodes
                $this->traverseNodesWithCallable(
                    $stmts[$occurrence['stmtIndex']],
                    function (Node $inner) use ($key, $variable): ?Variable {
                        if (! $inner instanceof FuncCall) {
                            return null;
                        }

                        if ($this->nodeComparator->printWithoutComments($inner) !== $key) {
                            return null;
                        }

                        return $variable;
                    }
                );
            }

            // Insert the assignment before the first statement that contained this call
            $firstStmtIndex = $occurrences[0]['stmtIndex'];
            $assignExpr = new Assign(new Variable($varName), $representativeCall);
            $assignStmt = new Expression($assignExpr);

            array_splice($stmts, $firstStmtIndex, 0, [$assignStmt]);

            // Shift subsequent stmtIndex values for remaining groups (not needed since we iterate
            // groups independently and splice only once per group, adjusting stmts reference)
            $node->stmts = $stmts;

            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        $node->stmts = $stmts;

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

    private function resolveVariableName(FuncCall $funcCall): string
    {
        $funcName = $this->getName($funcCall);
        if ($funcName === null) {
            return 'extracted';
        }

        // If the only argument is __DIR__, use a semantic name: funcName + 'Dir' (e.g. dirname -> baseDir)
        if (count($funcCall->args) === 1) {
            $arg = $funcCall->args[0];
            if ($arg instanceof Arg) {
                $argValue = $arg->value;
                if ($argValue instanceof \PhpParser\Node\Scalar\MagicConst\Dir) {
                    // e.g. dirname(__DIR__) -> baseDir
                    return 'baseDir';
                }
            }
        }

        return $funcName;
    }
}
