<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Enum_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireClosedSetOnBackedEnumRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = '';

    private string $mode = 'warn';

    private string $message = 'TODO: [RequireClosedSetOnBackedEnumRector] Backed enum %s must declare #[ClosedSet] — enums define sets (ADR-007).';

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[ClosedSet] attribute on all backed enums',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireClosedSetOnBackedEnumRector] Backed enum Permission must declare #[ClosedSet] — enums define sets (ADR-007).
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\ClosedSet',
                        'mode' => 'warn',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Enum_::class];
    }

    /**
     * @param Enum_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // Skip non-backed enums (no scalarType means pure enum)
        if ($node->scalarType === null) {
            return null;
        }

        // Skip enums that already have the attribute
        if ($this->hasAttribute($node)) {
            return null;
        }

        $enumName = (string) $node->name;

        return $this->addTodoComment($node, $enumName);
    }

    private function hasAttribute(Enum_ $node): bool
    {
        $shortName = $this->shortAttributeName();

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

    private function shortAttributeName(): string
    {
        $parts = explode('\\', $this->attributeClass);

        return end($parts);
    }

    private function addTodoComment(Enum_ $node, string $enumName): Enum_
    {
        $todoText = sprintf($this->message, $enumName);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return $node;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
