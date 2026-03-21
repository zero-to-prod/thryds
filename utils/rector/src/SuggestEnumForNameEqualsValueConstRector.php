<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SuggestEnumForNameEqualsValueConstRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [SuggestEnumForNameEqualsValueConstRector] Enumerations define sets — %s has %d string constants where name equals value. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForNameEqualsValueConstRector.md';

    private int $minConstants = 2;

    /** @var string[] */
    private array $excludedAttributes = [];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [SuggestEnumForNameEqualsValueConstRector] Enumerations define sets — %s has %d string constants where name equals value. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForNameEqualsValueConstRector.md';
        $this->minConstants = $configuration['minConstants'] ?? 2;
        $this->excludedAttributes = $configuration['excludedAttributes'] ?? [];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detect readonly classes where all public string constants have name === value — suggest migrating to a backed enum',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [SuggestEnumForNameEqualsValueConstRector] Enumerations define sets — BladeDirectives has 3 string constants where name equals value. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForNameEqualsValueConstRector.md
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'message' => 'TODO: [SuggestEnumForNameEqualsValueConstRector] Enumerations define sets — %s has %d string constants where name equals value. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForNameEqualsValueConstRector.md',
                        'minConstants' => 2,
                        'excludedAttributes' => [],
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
            if ($this->hasAttribute($node, $excludedAttr)) {
                return null;
            }
        }

        if (!$this->isPureConstantsClass($node)) {
            return null;
        }

        $count = $this->countNameEqualsValueStringConsts($node);

        if ($count === null || $count < $this->minConstants) {
            return null;
        }

        $className = (string) $node->name;

        return $this->addTodoComment($node, $className, $count);
    }

    private function isPureConstantsClass(Class_ $node): bool
    {
        $hasConstants = false;

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassConst) {
                $hasConstants = true;
                continue;
            }

            if ($stmt instanceof ClassMethod || $stmt instanceof Property) {
                return false;
            }
        }

        return $hasConstants;
    }

    /**
     * Returns the count of public string constants where name === value,
     * or null if any public string constant has name !== value (disqualifies the class).
     */
    private function countNameEqualsValueStringConsts(Class_ $node): ?int
    {
        $count = 0;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst || !$stmt->isPublic()) {
                continue;
            }

            if (!$stmt->type instanceof Identifier || $stmt->type->name !== 'string') {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if (!$const->value instanceof String_) {
                    return null;
                }

                if ($const->name->toString() !== $const->value->value) {
                    return null;
                }

                $count++;
            }
        }

        return $count;
    }

    private function hasAttribute(Class_ $node, string $attributeClass): bool
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
}
