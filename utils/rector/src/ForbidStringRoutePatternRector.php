<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidStringRoutePatternRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $methods = ['map'];

    private int $argPosition = 1;

    public function configure(array $configuration): void
    {
        if (isset($configuration['methods'])) {
            $this->methods = $configuration['methods'];
        }

        if (isset($configuration['argPosition'])) {
            $this->argPosition = $configuration['argPosition'];
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above Router->map() calls that use inline string literals as route patterns, prompting replacement with a class constant reference',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Router->map('GET', '/posts/{post}', $handler);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '/posts/{post}' to a Route class constant.
$Router->map('GET', '/posts/{post}', $handler);
CODE_SAMPLE,
                    ['methods' => ['map'], 'argPosition' => 1]
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
        if (!$node->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $node->expr;

        if (!$this->isNames($methodCall->name, $this->methods)) {
            return null;
        }

        $args = $methodCall->getArgs();

        if (!isset($args[$this->argPosition])) {
            return null;
        }

        $patternArg = $args[$this->argPosition]->value;

        if (!$patternArg instanceof String_) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), '[ForbidStringRoutePatternRector]')) {
                return null;
            }
        }

        $value = $patternArg->value;
        $todoComment = new Comment(
            "// TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '{$value}' to a Route class constant."
        );

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
