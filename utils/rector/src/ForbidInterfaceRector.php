<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidInterfaceRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly. Attributes are discoverable, enforceable, and composable without coupling. See: utils/rector/docs/ForbidInterfaceRector.md';

    /** @var list<string> */
    private array $allowList = [];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->allowList = $configuration['allowList'] ?? [];
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Interface_::class];
    }

    /**
     * @param Interface_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        $name = (string) $node->namespacedName;

        if ($name === '' && $node->name !== null) {
            $name = $node->name->toString();
        }

        if (in_array($name, $this->allowList, strict: true)) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), '[ForbidInterfaceRector]')) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . $this->message);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag interface declarations — attributes declare properties explicitly without implicit coupling',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
interface Loggable
{
    public function toLogContext(): array;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly.
interface Loggable
{
    public function toLogContext(): array;
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'allowList' => [],
                    ],
                ),
            ]
        );
    }
}
