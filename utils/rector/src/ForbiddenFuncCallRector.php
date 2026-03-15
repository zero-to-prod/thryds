<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitor;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbiddenFuncCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var string[]
     */
    private array $forbiddenFunctions = [];

    /**
     * @param string[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->forbiddenFunctions = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove calls to forbidden functions', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
error_log('debug info');
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
CODE_SAMPLE,
                ['error_log']
            ),
        ]);
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
    public function refactor(Node $node): ?int
    {
        if (! $node->expr instanceof FuncCall) {
            return null;
        }

        if (! $this->isNames($node->expr, $this->forbiddenFunctions)) {
            return null;
        }

        return NodeVisitor::REMOVE_NODE;
    }
}
