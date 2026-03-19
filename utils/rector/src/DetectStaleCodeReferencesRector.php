<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Detects stale PHP code references in @see / @link docblock tags.
 * When a referenced ClassName::member no longer exists in the codebase,
 * a TODO comment is prepended to the annotated node.
 */
final class DetectStaleCodeReferencesRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = "TODO: [DetectStaleCodeReferencesRector] Comment references '%s' which does not exist. Verify or remove.";

    /** @var array<string, string> short name → FQCN, built from file's use statements */
    private array $useMap = [];

    private string $currentNamespace = '';

    private const PSEUDO_TYPES = [
        'self', 'static', 'parent', 'null', 'true', 'false',
        'string', 'int', 'float', 'bool', 'array', 'object',
        'callable', 'iterable', 'void', 'never', 'mixed',
    ];

    /**
     * Matches @see / @link / {@link} followed by ClassName::member.
     * The optional leading backslash is consumed outside the capture group.
     * Matches both short names (Foo) and partially/fully qualified names (Foo\Bar\Baz).
     */
    private const string CODE_REF_PATTERN = '/(?:@see|@link|\{@link)\s+\\\\?([A-Z][A-Za-z0-9_\\\\]*)::([a-zA-Z_][a-zA-Z0-9_]*)/';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [DetectStaleCodeReferencesRector] Comment references '%s' which does not exist. Verify or remove.";
    }

    public function getNodeTypes(): array
    {
        return [FileNode::class];
    }

    /**
     * @param FileNode $node
     */
    public function refactor(Node $node): ?Node
    {
        $this->useMap = [];
        $this->currentNamespace = '';

        // Build use-map from the file's namespace and use statements.
        $namespaceStmt = null;
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                $namespaceStmt = $stmt;
                break;
            }
        }

        if ($namespaceStmt !== null) {
            $this->currentNamespace = $namespaceStmt->name?->toString() ?? '';
            $this->buildUseMap($namespaceStmt->stmts);
        } else {
            $this->buildUseMap($node->stmts);
        }

        $changed = false;

        $this->traverseNodesWithCallable($node->stmts, function (Node $inner) use (&$changed): null {
            if (!$this->isDocumentableNode($inner)) {
                return null;
            }

            $comments = $inner->getComments();
            if ($comments === []) {
                return null;
            }

            foreach ($comments as $comment) {
                $text = $comment->getText();
                if (!str_contains($text, '::')) {
                    continue;
                }

                foreach ($this->extractCodeReferences($text) as [$shortClass, $member]) {
                    $fqcn = $this->resolveClass($shortClass);
                    if ($fqcn === null) {
                        // Can't resolve the class name — skip to avoid false positives.
                        continue;
                    }

                    $refString = "{$shortClass}::{$member}";
                    if (!$this->memberExists($fqcn, $member)) {
                        if ($this->addTodoComment($inner, $refString)) {
                            $changed = true;
                        }
                    }
                }
            }

            return null;
        });

        return $changed ? $node : null;
    }

    /** @param array<\PhpParser\Node\Stmt> $stmts */
    private function buildUseMap(array $stmts): void
    {
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Use_ || $stmt->type !== Use_::TYPE_NORMAL) {
                continue;
            }
            foreach ($stmt->uses as $use) {
                $alias = $use->alias?->name ?? $use->name->getLast();
                $this->useMap[$alias] = $use->name->toString();
            }
        }
    }

    private function isDocumentableNode(Node $node): bool
    {
        return $node instanceof Class_
            || $node instanceof ClassMethod
            || $node instanceof Function_
            || $node instanceof Property
            || $node instanceof ClassConst;
    }

    /**
     * @return list<array{string, string}> Each entry is [className, memberName].
     */
    private function extractCodeReferences(string $commentText): array
    {
        if (preg_match_all(self::CODE_REF_PATTERN, $commentText, $matches, PREG_SET_ORDER) === 0) {
            return [];
        }

        $refs = [];
        foreach ($matches as $match) {
            $className = $match[1];
            $member = $match[2];

            if (in_array(strtolower($className), self::PSEUDO_TYPES, true)) {
                continue;
            }

            $refs[] = [$className, $member];
        }

        return $refs;
    }

    private function resolveClass(string $shortName): ?string
    {
        // Name with backslashes is already (partially or fully) qualified.
        if (str_contains($shortName, '\\')) {
            return ltrim($shortName, '\\');
        }

        // Short name imported via a use statement.
        if (isset($this->useMap[$shortName])) {
            return $this->useMap[$shortName];
        }

        // Short name in the current namespace.
        if ($this->currentNamespace !== '') {
            $candidate = $this->currentNamespace . '\\' . $shortName;
            if ($this->reflectionProvider->hasClass($candidate)) {
                return $candidate;
            }
        }

        // Can't resolve — skip to avoid false positives.
        return null;
    }

    private function memberExists(string $fqcn, string $member): bool
    {
        if (!$this->reflectionProvider->hasClass($fqcn)) {
            return false;
        }

        $class = $this->reflectionProvider->getClass($fqcn);

        return $class->hasMethod($member)
            || $class->hasConstant($member)
            || $class->hasProperty($member);
    }

    private function addTodoComment(Node $node, string $reference): bool
    {
        $todoText = sprintf($this->message, $reference);
        $marker = "[DetectStaleCodeReferencesRector] Comment references '{$reference}'";

        $comments = $node->getComments();
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return false; // Already flagged.
            }
        }

        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return true;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detect stale @see / @link references to PHP class members (methods, constants, properties) that no longer exist',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
use App\Routes\RouteRegistrar;

class Dispatcher
{
    /**
     * @see RouteRegistrar::register()
     */
    public function dispatch(): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use App\Routes\RouteRegistrar;

class Dispatcher
{
    // TODO: [DetectStaleCodeReferencesRector] Comment references 'RouteRegistrar::register' which does not exist. Verify or remove.
    /**
     * @see RouteRegistrar::register()
     */
    public function dispatch(): void {}
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                    ],
                ),
            ]
        );
    }
}
