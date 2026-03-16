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
    /** @var string[] */
    private array $forbiddenClasses = [];

    private string $message = 'TODO: Avoid direct instantiation of %s — use a cached router instead';

    public function configure(array $configuration): void
    {
        $this->forbiddenClasses = $configuration['forbiddenClasses'] ?? [];
        $this->message = $configuration['message'] ?? 'TODO: Avoid direct instantiation of %s — use a cached router instead';
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
// TODO: Avoid direct instantiation of League\Route\Router — use a cached router instead
$router = new League\Route\Router();
CODE_SAMPLE,
                    ['forbiddenClasses' => ['League\\Route\\Router']]
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

        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . sprintf($this->message, $matchedClassName));

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
