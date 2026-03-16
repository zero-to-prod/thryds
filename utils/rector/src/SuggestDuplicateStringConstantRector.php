<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SuggestDuplicateStringConstantRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const MIN_LENGTH = 3;

    private string $message = "TODO: Refactor duplicate string '%s' (used %dx) to a constant";

    public function configure(array $configuration): void
    {
        $this->message = $configuration['message'] ?? "TODO: Refactor duplicate string '%s' (used %dx) to a constant";
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detect string literals that appear 2 or more times in a file and add a TODO comment on the first occurrence suggesting refactoring to a single source of truth',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$a = doSomething('application/json');
$b = getHeader('application/json');
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: Refactor duplicate string 'application/json' (used 2x) to a constant
$a = doSomething('application/json');
$b = getHeader('application/json');
CODE_SAMPLE,
                    [
                        'message' => "TODO: Refactor duplicate string '%s' (used %dx) to a constant",
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
        return [FileNode::class];
    }

    /**
     * @param FileNode $node
     */
    public function refactor(Node $node): ?Node
    {
        // Resolve the flat list of statements to annotate against.
        // For a namespaced file, the namespace body is the right level;
        // for a non-namespaced file, the FileNode's stmts are the right level.
        $topStmts = $this->resolveTopStmts($node);

        // Build the set of String_ node object IDs that are const-declaration
        // values and should be excluded from duplicate detection.
        $excludedNodeIds = $this->collectConstValueNodeIds($node->stmts);

        /**
         * Collect every qualifying String_ node grouped by value.
         * Each entry records which top-level statement (in $topStmts) first
         * contains that value.
         *
         * @var array<string, list<array{stmt: Stmt, stmtIndex: int}>> $groups
         */
        $groups = [];

        foreach ($topStmts as $stmtIndex => $stmt) {
            $this->traverseNodesWithCallable($stmt, function (Node $inner) use ($stmt, $stmtIndex, $excludedNodeIds, &$groups): null {
                if (!$inner instanceof String_) {
                    return null;
                }

                if (isset($excludedNodeIds[spl_object_id($inner)])) {
                    return null;
                }

                $value = $inner->value;

                if (strlen($value) < self::MIN_LENGTH) {
                    return null;
                }

                $groups[$value][] = ['stmt' => $stmt, 'stmtIndex' => $stmtIndex];

                return null;
            });
        }

        $hasChanged = false;

        foreach ($groups as $value => $occurrences) {
            $count = count($occurrences);
            if ($count < 2) {
                continue;
            }

            $firstStmt = $occurrences[0]['stmt'];

            // Idempotent: skip if the TODO comment is already present on the statement.
            $marker = strstr($this->message, '%', true) ?: $this->message;
            $alreadyAnnotated = false;
            foreach ($firstStmt->getComments() as $comment) {
                if (str_contains($comment->getText(), $marker)
                    && str_contains($comment->getText(), "'{$value}'")) {
                    $alreadyAnnotated = true;
                    break;
                }
            }

            if ($alreadyAnnotated) {
                continue;
            }

            $todoText = '// ' . sprintf($this->message, $value, $count);

            $existingComments = $firstStmt->getComments();
            array_unshift($existingComments, new Comment($todoText));
            $firstStmt->setAttribute('comments', $existingComments);

            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * Return the list of statements that represent the top-level code units of
     * the file. For a namespaced file that is the namespace body; for a plain
     * file it is the FileNode's own stmts.
     *
     * @return Stmt[]
     */
    private function resolveTopStmts(FileNode $fileNode): array
    {
        foreach ($fileNode->stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                return $stmt->stmts;
            }
        }

        return $fileNode->stmts;
    }

    /**
     * Collect the spl_object_id() of every String_ node that is the value of a
     * const declaration (ClassConst, Const_) or a define() call.
     *
     * @param Stmt[] $stmts
     * @return array<int, true>
     */
    private function collectConstValueNodeIds(array $stmts): array
    {
        $ids = [];

        $this->traverseNodesWithCallable($stmts, function (Node $node) use (&$ids): null {
            // Class constants: public const string FOO = 'value';
            if ($node instanceof ClassConst) {
                foreach ($node->consts as $const) {
                    if ($const->value instanceof String_) {
                        $ids[spl_object_id($const->value)] = true;
                    }
                }
            }

            // Procedural constants: const FOO = 'value';
            if ($node instanceof Const_) {
                foreach ($node->consts as $const) {
                    if ($const->value instanceof String_) {
                        $ids[spl_object_id($const->value)] = true;
                    }
                }
            }

            // define('NAME', 'value') — skip both args
            if ($node instanceof FuncCall && $this->isName($node, 'define')) {
                foreach ($node->args as $arg) {
                    if ($arg instanceof Node\Arg && $arg->value instanceof String_) {
                        $ids[spl_object_id($arg->value)] = true;
                    }
                }
            }

            return null;
        });

        return $ids;
    }
}
