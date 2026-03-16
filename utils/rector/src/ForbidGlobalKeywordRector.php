<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Global_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidGlobalKeywordRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: Global keyword prevents scope-level optimization';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag usage of the global keyword, which prevents OPcache scope-level optimizations by making variable bindings dynamic',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
function foo(): void {
    global $config;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function foo(): void {
    // TODO: Global keyword prevents scope-level optimization
    global $config;
}
CODE_SAMPLE,
                    ['message' => 'TODO: Global keyword prevents scope-level optimization']
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Global_::class];
    }

    /**
     * @param Global_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . $this->message);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
