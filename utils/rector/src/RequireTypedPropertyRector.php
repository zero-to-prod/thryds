<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireTypedPropertyRector extends AbstractRector
{
    private const TODO_MARKER = '[opcache]';

    private const TODO_TEXT = '// TODO: [opcache] add a type declaration to improve OPcache optimization';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag untyped class properties that prevent OPcache from optimizing memory layout',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class User
{
    public $name;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class User
{
    // TODO: [opcache] add a type declaration to improve OPcache optimization
    public $name;
}
CODE_SAMPLE
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
            if (str_contains($comment->getText(), self::TODO_MARKER)) {
                return null;
            }
        }

        $todoComment = new Comment(self::TODO_TEXT);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
