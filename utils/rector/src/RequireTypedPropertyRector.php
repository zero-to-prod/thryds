<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireTypedPropertyRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $message = 'TODO: Add a type declaration to improve optimization';

    public function configure(array $configuration): void
    {
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag untyped class properties that prevent OPcache from optimizing memory layout',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class User
{
    public $name;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class User
{
    // TODO: Add a type declaration to improve optimization
    public $name;
}
CODE_SAMPLE,
                    ['message' => 'TODO: Add a type declaration to improve optimization']
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }

    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->type !== null) {
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
