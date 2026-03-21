<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SuggestEnumForKeyRegistryWithMethodsRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = '';

    private string $mode = 'warn';

    private string $message = 'TODO: [SuggestEnumForKeyRegistryWithMethodsRector] Enumerations define sets, attributes define properties — %s has #[KeyRegistry] but also contains methods. Extract constants to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForKeyRegistryWithMethodsRector.md';

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detect #[KeyRegistry] classes that also contain methods — constants should be extracted to a backed enum',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[KeyRegistry(Source::example)]
readonly class Directives
{
    public const string foo = 'foo';

    public static function register(): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [SuggestEnumForKeyRegistryWithMethodsRector] Enumerations define sets, attributes define properties — Directives has #[KeyRegistry] but also contains methods. Extract constants to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForKeyRegistryWithMethodsRector.md
#[KeyRegistry(Source::example)]
readonly class Directives
{
    public const string foo = 'foo';

    public static function register(): void {}
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\KeyRegistry',
                        'mode' => 'warn',
                    ]
                ),
            ]
        );
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

        if (!$this->hasAttribute($node)) {
            return null;
        }

        if (!$this->hasConstants($node) || !$this->hasMethods($node)) {
            return null;
        }

        return $this->addTodoComment($node, (string) $node->name);
    }

    private function hasAttribute(Class_ $node): bool
    {
        $parts = explode('\\', $this->attributeClass);
        $shortName = end($parts);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $this->attributeClass || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasConstants(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassConst) {
                return true;
            }
        }

        return false;
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

    private function addTodoComment(Class_ $node, string $className): ?Class_
    {
        $todoText = sprintf($this->message, $className);
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
