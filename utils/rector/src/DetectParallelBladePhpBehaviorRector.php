<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Detects string literals in PHP that duplicate a value already defined as a
 * class constant or backed enum case value elsewhere in the codebase.
 *
 * Two-pass approach (all within one Rector run, using static state):
 *   Phase 1 — collect all constant/enum string values and the class/enum FQN that owns them.
 *   Phase 2 — flag String_ literals in non-defining files that match a known value.
 */
final class DetectParallelBladePhpBehaviorRector extends AbstractRector implements ConfigurableRectorInterface
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

    private string $message = "TODO: [DetectParallelBladePhpBehaviorRector] Use %s::%s instead of hardcoded '%s'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md";

    /**
     * Map of string value => ['class' => FQN, 'const' => CONST_NAME].
     * First definition wins — subsequent files should reference it, not redefine.
     *
     * @var array<string, array{class: string, const: string}>
     */
    private static array $knownValues = [];

    /**
     * Allow tests to pre-seed the registry.
     *
     * @var array<string, array{class: string, const: string}>
     */
    private array $preSeeded = [];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [DetectParallelBladePhpBehaviorRector] Use %s::%s instead of hardcoded '%s'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md";

        if (isset($configuration['preSeededValues'])) {
            $this->preSeeded = $configuration['preSeededValues'];
            foreach ($this->preSeeded as $value => $meta) {
                if (!isset(self::$knownValues[$value])) {
                    self::$knownValues[$value] = $meta;
                }
            }
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag string literals that duplicate a value already defined as a class constant or backed enum case value',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$variant = 'primary';
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [DetectParallelBladePhpBehaviorRector] Use ButtonVariant::Primary instead of hardcoded 'primary'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
$variant = 'primary';
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
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

        // Phase 1: collect constant/enum values defined in this file.
        $this->collectDefinedValues($topStmts, $filePath);

        // Phase 2: flag string literals that match a constant defined in a different file.
        return $this->annotateStatements($node, $topStmts, $filePath);
    }

    /**
     * @param Stmt[] $topStmts
     */
    private function collectDefinedValues(array $topStmts, string $filePath): void
    {
        $this->traverseNodesWithCallable($topStmts, function (Node $node) use ($filePath, $topStmts): null {
            // Backed enum cases: case Foo = 'bar';
            if ($node instanceof EnumCase && $node->expr instanceof String_) {
                $value = $node->expr->value;
                $caseName = (string) $node->name;
                $enumFqn = $this->resolveCurrentFqn($node, $topStmts);

                if ($this->isQualifyingString($value) && !isset(self::$knownValues[$value])) {
                    self::$knownValues[$value] = [
                        'class' => $enumFqn ?? $caseName,
                        'const' => $caseName,
                    ];
                }

                return null;
            }

            // Class constants: public const string FOO = 'bar';
            if ($node instanceof ClassConst) {
                foreach ($node->consts as $const) {
                    if (!$const->value instanceof String_) {
                        continue;
                    }

                    $value = $const->value->value;
                    $constName = (string) $const->name;

                    if (!$this->isQualifyingString($value)) {
                        continue;
                    }

                    if (!isset(self::$knownValues[$value])) {
                        $classFqn = $this->resolveCurrentFqn($node, $topStmts);
                        self::$knownValues[$value] = [
                            'class' => $classFqn ?? $constName,
                            'const' => $constName,
                        ];
                    }
                }

                return null;
            }

            // Top-level constants: const FOO = 'bar';
            if ($node instanceof Const_) {
                foreach ($node->consts as $const) {
                    if (!$const->value instanceof String_) {
                        continue;
                    }

                    $value = $const->value->value;
                    $constName = (string) $const->name;

                    if (!$this->isQualifyingString($value)) {
                        continue;
                    }

                    if (!isset(self::$knownValues[$value])) {
                        self::$knownValues[$value] = [
                            'class' => $constName,
                            'const' => $constName,
                        ];
                    }
                }

                return null;
            }

            return null;
        });
    }

    /**
     * Annotate statements whose string literals match a constant defined in another file.
     *
     * @param Stmt[] $topStmts
     */
    private function annotateStatements(FileNode $fileNode, array $topStmts, string $filePath): ?FileNode
    {
        // Collect all String_ node IDs that are const/enum definitions in this file.
        // These must be skipped — the definition itself is the source of truth.
        $definingNodeIds = $this->collectDefiningNodeIds($fileNode->stmts);

        // Also collect the FQNs of classes/enums defined in this file so we can skip
        // literals that live inside the defining class.
        $definingClassFqns = $this->collectDefiningClassFqns($topStmts);

        $hasChanged = false;

        foreach ($topStmts as $stmt) {
            $matches = [];

            $this->traverseNodesWithCallable($stmt, function (Node $inner) use ($definingNodeIds, $definingClassFqns, &$matches): null {
                if (!$inner instanceof String_) {
                    return null;
                }

                if (isset($definingNodeIds[spl_object_id($inner)])) {
                    return null;
                }

                // Skip if it is already a class-const-fetch (ClassName::CONST) or ->value access.
                // (The parent visitor handles this automatically — String_ nodes inside those
                //  are not visited as standalone children, so this check is mostly defensive.)
                $value = $inner->value;

                if (!$this->isQualifyingString($value)) {
                    return null;
                }

                if (!isset(self::$knownValues[$value])) {
                    return null;
                }

                $meta = self::$knownValues[$value];

                // Skip if the owning class is one defined in this very file.
                if (in_array($meta['class'], $definingClassFqns, true)) {
                    return null;
                }

                $matches[$value] = $meta;

                return null;
            });

            foreach ($matches as $value => $meta) {
                if ($this->hasMarkerComment($stmt, $value)) {
                    continue;
                }

                $todoText = '// ' . sprintf($this->message, $meta['class'], $meta['const'], $value);
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

        // Skip strings with spaces — these are natural-language phrases, not identifiers.
        if (str_contains($value, ' ')) {
            return false;
        }

        // Skip HTML/XML fragments.
        if (str_contains($value, '<') || str_contains($value, '>')) {
            return false;
        }

        // Skip URLs, file paths, regex patterns.
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
                return $stmt->stmts ?? [];
            }
        }

        return $fileNode->stmts ?? [];
    }

    /**
     * Collect spl_object_id() values of all String_ nodes that are const/enum definitions
     * in any part of the file tree (including inside namespaces).
     *
     * @param Stmt[] $stmts
     * @return array<int, true>
     */
    private function collectDefiningNodeIds(array $stmts): array
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

            if ($node instanceof EnumCase && $node->expr instanceof String_) {
                $ids[spl_object_id($node->expr)] = true;
            }

            return null;
        });

        return $ids;
    }

    /**
     * Collect FQNs of all classes/enums declared in the top-level statements.
     *
     * @param Stmt[] $topStmts
     * @return string[]
     */
    private function collectDefiningClassFqns(array $topStmts): array
    {
        $fqns = [];

        $this->traverseNodesWithCallable($topStmts, function (Node $node) use (&$fqns): null {
            if ($node instanceof \PhpParser\Node\Stmt\Class_ || $node instanceof Enum_) {
                $name = $this->getName($node);
                if ($name !== null) {
                    $fqns[] = $name;
                }

                // Also add namespacedName if available.
                if ($node->namespacedName !== null) {
                    $fqns[] = $node->namespacedName->toString();
                }
            }

            return null;
        });

        return $fqns;
    }

    /**
     * Attempt to resolve the FQN of the class/enum that contains the given node.
     * Uses namespacedName set by PhpParser's NameResolver visitor.
     *
     * @param Stmt[] $topStmts
     */
    private function resolveCurrentFqn(Node $targetNode, array $topStmts): ?string
    {
        // Walk the top-level statements looking for the class/enum that contains targetNode.
        $result = null;

        $this->traverseNodesWithCallable($topStmts, function (Node $node) use ($targetNode, &$result): ?int {
            if (!$node instanceof \PhpParser\Node\Stmt\Class_ && !$node instanceof Enum_) {
                return null;
            }

            // Check if targetNode is a descendant.
            $found = false;
            $this->traverseNodesWithCallable([$node], function (Node $inner) use ($targetNode, &$found): null {
                if ($inner === $targetNode) {
                    $found = true;
                }

                return null;
            });

            if ($found) {
                if ($node->namespacedName !== null) {
                    $result = $node->namespacedName->toString();
                } else {
                    $result = $this->getName($node);
                }
            }

            return null;
        });

        return $result;
    }
}
