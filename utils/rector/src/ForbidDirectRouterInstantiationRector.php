<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidDirectRouterInstantiationRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const TODO_MARKER = '[ForbidDirectRouterInstantiationRector]';

    /** @var string[] */
    private array $forbiddenClasses = [];

    public function configure(array $configuration): void
    {
        $this->forbiddenClasses = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment to flag direct instantiation of router classes that bypass route caching',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$router = new League\Route\Router();
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidDirectRouterInstantiationRector] Use League\Route\Cache\Router instead of instantiating League\Route\Router directly. Direct instantiation bypasses route caching.
$router = new League\Route\Router();
CODE_SAMPLE,
                    ['League\\Route\\Router']
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
        $matchedClassName = null;

        $this->traverseNodesWithCallable($node->expr, function (Node $subNode) use (&$matchedClassName): null {
            if (!$subNode instanceof New_) {
                return null;
            }

            $className = $this->getName($subNode->class);

            if ($className === null) {
                return null;
            }

            if (in_array($className, $this->forbiddenClasses, true)) {
                $matchedClassName = $className;
            }

            return null;
        });

        if ($matchedClassName === null) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), self::TODO_MARKER)) {
                return null;
            }
        }

        $todoComment = new Comment(
            '// TODO: [ForbidDirectRouterInstantiationRector] Use League\Route\Cache\Router instead of instantiating '
            . $matchedClassName
            . ' directly. Direct instantiation bypasses route caching.'
        );

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
