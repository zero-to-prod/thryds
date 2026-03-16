<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidVariableVariablesRector extends AbstractRector
{
    private const TODO_MARKER = '[opcache]';

    private const TODO_TEXT = '// TODO: [opcache] variable variables prevent compile-time variable resolution';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag variable variables ($$var) which prevent OPcache from resolving variables at compile time',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$$name = 'value';
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [opcache] variable variables prevent compile-time variable resolution
$$name = 'value';
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
        $hasVariableVariable = false;

        $this->traverseNodesWithCallable($node, function (Node $innerNode) use (&$hasVariableVariable): ?Node {
            if ($innerNode instanceof Variable && !is_string($innerNode->name)) {
                $hasVariableVariable = true;
            }

            return null;
        });

        if (!$hasVariableVariable) {
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
