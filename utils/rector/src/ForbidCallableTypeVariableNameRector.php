<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidCallableTypeVariableNameRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const TODO_MARKER = '[ForbidCallableTypeVariableNameRector]';

    /**
     * @var string[]
     */
    private array $forbiddenNames = [];

    /**
     * @param string[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->forbiddenNames = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment when a variable is named after a PHP callable type rather than describing its behaviour',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Closure = static function (): void {};
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidCallableTypeVariableNameRector] rename $Closure to describe its behaviour
$Closure = static function (): void {};
CODE_SAMPLE,
                    ['Closure', 'Callable', 'Callback', 'Function', 'Func']
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
        if (! $node->expr instanceof Assign) {
            return null;
        }

        $assign = $node->expr;

        if (! $assign->var instanceof Variable) {
            return null;
        }

        $varName = $this->getName($assign->var);

        if ($varName === null) {
            return null;
        }

        if (! in_array($varName, $this->forbiddenNames, strict: true)) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), self::TODO_MARKER)) {
                return null;
            }
        }

        $todoComment = new Comment(
            '// TODO: ' . self::TODO_MARKER . " rename \${$varName} to describe its behaviour"
        );

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
