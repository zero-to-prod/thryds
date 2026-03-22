<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SuggestEnumForInternalOnlyConstantsRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private int $minConstants = 2;

    /** @var string[] */
    private array $excludedAttributes = [];

    private string $message = 'TODO: [SuggestEnumForInternalOnlyConstantsRector] Enumerations define sets — %s has %d string constants only referenced internally. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForInternalOnlyConstantsRector.md';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->minConstants = $configuration['minConstants'] ?? 2;
        $this->excludedAttributes = $configuration['excludedAttributes'] ?? [];
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if (!$node->isReadonly()) {
            return null;
        }

        foreach ($this->excludedAttributes as $excludedAttr) {
            if ($this->hasAttributeByName($node, $excludedAttr)) {
                return null;
            }
        }

        if (!$this->hasMethods($node)) {
            return null;
        }

        $constNames = $this->collectStringConstantNames($node);

        if (count($constNames) < $this->minConstants) {
            return null;
        }

        if ($this->constantsMirrorProperties($node, $constNames)) {
            return null;
        }

        $referencedNames = $this->collectSelfConstReferences($node);

        // Every string constant must be referenced via self:: within the class
        if (array_diff($constNames, $referencedNames) !== []) {
            return null;
        }

        $className = (string) $node->name;

        return $this->addTodoComment($node, $className, count($constNames));
    }

    /**
     * @return string[]
     */
    private function collectStringConstantNames(Class_ $node): array
    {
        $names = [];

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst || !$stmt->isPublic()) {
                continue;
            }

            if (!$stmt->type instanceof Identifier || $stmt->type->name !== 'string') {
                continue;
            }

            foreach ($stmt->consts as $const) {
                $names[] = $const->name->toString();
            }
        }

        return $names;
    }

    /**
     * @param string[] $constNames
     */
    private function constantsMirrorProperties(Class_ $node, array $constNames): bool
    {
        $propertyNames = [];

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Property && $stmt->isPublic() && !$stmt->isStatic()) {
                foreach ($stmt->props as $prop) {
                    $propertyNames[] = $prop->name->toString();
                }
            }
        }

        return $constNames !== [] && array_diff($constNames, $propertyNames) === [];
    }

    private function hasMethods(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function collectSelfConstReferences(Class_ $node): array
    {
        $names = [];

        $this->traverseNodesWithCallable($node->stmts, static function (Node $node) use (&$names): null {
            if (!$node instanceof ClassConstFetch) {
                return null;
            }

            if (!$node->class instanceof Name || $node->class->toString() !== 'self') {
                return null;
            }

            if ($node->name instanceof Identifier) {
                $names[] = $node->name->toString();
            }

            return null;
        });

        return array_unique($names);
    }

    private function hasAttributeByName(Class_ $node, string $attributeClass): bool
    {
        $parts = explode('\\', $attributeClass);
        $shortName = end($parts);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $attributeClass || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addTodoComment(Class_ $node, string $className, int $count): ?Class_
    {
        $todoText = sprintf($this->message, $className, $count);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Suggest migrating string constants to a backed enum when they are only referenced internally via self::',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';

    public static function register(): void
    {
        echo self::production;
        echo self::env;
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [SuggestEnumForInternalOnlyConstantsRector] Enumerations define sets — BladeDirectives has 2 string constants only referenced internally. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForInternalOnlyConstantsRector.md
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';

    public static function register(): void
    {
        echo self::production;
        echo self::env;
    }
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'minConstants' => 2,
                    ]
                ),
            ]
        );
    }
}
