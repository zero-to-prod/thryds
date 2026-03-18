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

final class ForbidCrossFileStringDuplicationRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const MIN_LENGTH = 4;

    /** @var string[] */
    private const EXCLUDED_VALUES = [
        '',
        ' ',
        'true',
        'false',
        'null',
        'utf-8',
        'UTF-8',
        'application/json',
        'text/html',
        'text/plain',
        'Content-Type',
        'Authorization',
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'HEAD',
        'OPTIONS',
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        'yyyy-mm-dd',
        'Y-m-d',
        'H:i:s',
        'Y-m-d H:i:s',
    ];

    private string $mode = 'warn';

    private string $message = "TODO: [ForbidCrossFileStringDuplicationRector] string '%s' appears in %d files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md";

    private int $minFiles = 3;

    /** @var array<string, list<string>> — value => list of file paths */
    private static array $filesByValue = [];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [ForbidCrossFileStringDuplicationRector] string '%s' appears in %d files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md";
        $this->minFiles = $configuration['minFiles'] ?? 3;

        // Allow tests to pre-seed the collector with known cross-file occurrences.
        if (isset($configuration['preSeededFilesByValue'])) {
            foreach ($configuration['preSeededFilesByValue'] as $value => $paths) {
                self::$filesByValue[$value] = array_values(array_unique(array_merge(
                    self::$filesByValue[$value] ?? [],
                    $paths
                )));
            }
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag string literals that appear in 3 or more distinct files, suggesting extraction to a shared constant',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$class = 'button-primary';
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidCrossFileStringDuplicationRector] string 'button-primary' appears in 3 files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md
$class = 'button-primary';
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'minFiles' => 3,
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
        if ($this->mode === 'auto') {
            return null;
        }

        $filePath = $this->file->getFilePath();

        $topStmts = $this->resolveTopStmts($node);
        $excludedNodeIds = $this->collectConstValueNodeIds($node->stmts);

        // Phase 1: collect all qualifying strings from this file into static state.
        $this->collectStrings($topStmts, $excludedNodeIds, $filePath);

        // Phase 2: annotate any statement whose string value is now seen in minFiles+ files.
        return $this->annotateStatements($node, $topStmts, $excludedNodeIds);
    }

    /**
     * Collect qualifying string values from this file into the static accumulator.
     *
     * @param Stmt[] $topStmts
     * @param array<int, true> $excludedNodeIds
     */
    private function collectStrings(array $topStmts, array $excludedNodeIds, string $filePath): void
    {
        $seenInFile = [];

        $this->traverseNodesWithCallable($topStmts, function (Node $inner) use ($excludedNodeIds, $filePath, &$seenInFile): null {
            if (!$inner instanceof String_) {
                return null;
            }

            if (isset($excludedNodeIds[spl_object_id($inner)])) {
                return null;
            }

            $value = $inner->value;

            if (!$this->isQualifyingString($value)) {
                return null;
            }

            if (isset($seenInFile[$value])) {
                return null;
            }

            $seenInFile[$value] = true;

            if (!isset(self::$filesByValue[$value])) {
                self::$filesByValue[$value] = [];
            }

            if (!in_array($filePath, self::$filesByValue[$value], true)) {
                self::$filesByValue[$value][] = $filePath;
            }

            return null;
        });
    }

    /**
     * Annotate the first statement containing a string that appears in minFiles+ files.
     *
     * @param Stmt[] $topStmts
     * @param array<int, true> $excludedNodeIds
     */
    private function annotateStatements(FileNode $fileNode, array $topStmts, array $excludedNodeIds): ?FileNode
    {
        $hasChanged = false;

        foreach ($topStmts as $stmt) {
            $stringsInStmt = [];

            $this->traverseNodesWithCallable($stmt, function (Node $inner) use ($excludedNodeIds, &$stringsInStmt): null {
                if (!$inner instanceof String_) {
                    return null;
                }

                if (isset($excludedNodeIds[spl_object_id($inner)])) {
                    return null;
                }

                $value = $inner->value;

                if (!$this->isQualifyingString($value)) {
                    return null;
                }

                $stringsInStmt[$value] = true;

                return null;
            });

            foreach (array_keys($stringsInStmt) as $value) {
                $fileCount = count(self::$filesByValue[$value] ?? []);

                if ($fileCount < $this->minFiles) {
                    continue;
                }

                if ($this->hasMarkerComment($stmt, $value)) {
                    continue;
                }

                $todoText = '// ' . sprintf($this->message, $value, $fileCount);
                $comments = $stmt->getComments();
                array_unshift($comments, new Comment($todoText));
                $stmt->setAttribute('comments', $comments);
                $hasChanged = true;
            }
        }

        return $hasChanged ? $fileNode : null;
    }

    private function hasMarkerComment(Stmt $stmt, string $value): bool
    {
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($stmt->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker) && str_contains($comment->getText(), "'{$value}'")) {
                return true;
            }
        }

        return false;
    }

    private function isQualifyingString(string $value): bool
    {
        if (strlen($value) < self::MIN_LENGTH) {
            return false;
        }

        if (is_numeric($value)) {
            return false;
        }

        if (in_array($value, self::EXCLUDED_VALUES, true)) {
            return false;
        }

        // Skip HTML/XML fragments — these are structural, not named constants.
        if (str_contains($value, '<') || str_contains($value, '>')) {
            return false;
        }

        // Skip strings that look like file paths, URLs, or regex patterns.
        if (str_starts_with($value, '/') || str_starts_with($value, 'http')) {
            return false;
        }

        return true;
    }

    /**
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
     * @param Stmt[] $stmts
     * @return array<int, true>
     */
    private function collectConstValueNodeIds(array $stmts): array
    {
        $ids = [];

        $this->traverseNodesWithCallable($stmts, function (Node $node) use (&$ids): null {
            if ($node instanceof ClassConst) {
                foreach ($node->consts as $const) {
                    if ($const->value instanceof String_) {
                        $ids[spl_object_id($const->value)] = true;
                    }
                }
            }

            if ($node instanceof Const_) {
                foreach ($node->consts as $const) {
                    if ($const->value instanceof String_) {
                        $ids[spl_object_id($const->value)] = true;
                    }
                }
            }

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
