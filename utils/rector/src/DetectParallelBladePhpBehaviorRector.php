<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class DetectParallelBladePhpBehaviorRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'Use constant or enum reference instead of hardcoded string value. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'Use constant or enum reference instead of hardcoded string value. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md';
    }

    public function getNodeTypes(): array
    {
        // TODO: return the AST node types this rule inspects
        return [];
    }

    public function refactor(Node $node): ?Node
    {
        // TODO: implement transformation logic
        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('DetectParallelBladePhpBehaviorRector description', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
// TODO: code before transformation
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
// TODO: code after transformation
CODE_SAMPLE,
                [
                    'mode' => 'warn',
                ],
            ),
        ]);
    }
}
