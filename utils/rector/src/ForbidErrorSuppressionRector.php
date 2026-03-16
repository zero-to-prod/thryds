<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidErrorSuppressionRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $message = 'TODO: Error suppression adds per-call overhead — handle errors explicitly';

    public function configure(array $configuration): void
    {
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag the @ error suppression operator, which installs/restores error handlers per call and prevents OPcache from optimizing error handling paths',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
@file_get_contents('missing.txt');
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: Error suppression adds per-call overhead — handle errors explicitly
@file_get_contents('missing.txt');
CODE_SAMPLE,
                    ['message' => 'TODO: Error suppression adds per-call overhead — handle errors explicitly']
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
