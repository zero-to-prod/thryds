<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidErrorSuppressionRector extends AbstractRector
{
    private const TODO_MARKER = '[opcache]';

    private const TODO_TEXT = '// TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag the @ error suppression operator, which installs/restores error handlers per call and prevents OPcache from optimizing error handling paths',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
@file_get_contents('missing.txt');
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly
@file_get_contents('missing.txt');
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
        return [Expression::class];
    }

    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasErrorSuppress = false;
        $this->traverseNodesWithCallable([$node->expr], function (Node $inner) use (&$hasErrorSuppress): ?Node {
            if ($inner instanceof ErrorSuppress) {
                $hasErrorSuppress = true;
            }

            return null;
        });

        if (!$hasErrorSuppress) {
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
