<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Global_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidGlobalKeywordRector extends AbstractRector
{
    private const TODO_MARKER = '[opcache]';

    private const TODO_TEXT = '// TODO: [opcache] global keyword prevents scope-level optimization';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag usage of the global keyword, which prevents OPcache scope-level optimizations by making variable bindings dynamic',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
function foo(): void {
    global $config;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function foo(): void {
    // TODO: [opcache] global keyword prevents scope-level optimization
    global $config;
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
        return [Global_::class];
    }

    /**
     * @param Global_ $node
     */
    public function refactor(Node $node): ?Node
    {
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
