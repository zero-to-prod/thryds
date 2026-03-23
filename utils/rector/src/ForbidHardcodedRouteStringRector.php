<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidHardcodedRouteStringRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = '';

    private string $mode = 'warn';

    private string $message = "TODO: [ForbidHardcodedRouteStringRector] Use Route::%s->value instead of hardcoded '%s'.";

    /** @var array<string, string>|null value => case name, null means not yet built */
    private ?array $routeValueMap = null;

    public function configure(array $configuration): void
    {
        if (isset($configuration['enumClass'])) {
            $this->enumClass = $configuration['enumClass'];
        }

        if (isset($configuration['mode'])) {
            $this->mode = $configuration['mode'];
        }

        if (isset($configuration['message'])) {
            $this->message = $configuration['message'];
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above statements containing hardcoded string literals that match a Route enum case value',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$url = '/about';
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidHardcodedRouteStringRector] Use Route::about->value instead of hardcoded '/about'.
$url = '/about';
CODE_SAMPLE,
                    [
                        'enumClass' => 'ZeroToProd\\Thryds\\Routes\\RouteList',
                        'mode' => 'warn',
                        'message' => "TODO: [ForbidHardcodedRouteStringRector] Use Route::%s->value instead of hardcoded '%s'.",
                    ]
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
        if ($this->mode !== 'warn') {
            return null;
        }

        if ($this->routeValueMap === null) {
            $this->routeValueMap = $this->enumClass !== ''
                ? $this->buildRouteValueMap($this->enumClass)
                : [];
        }

        if ($this->routeValueMap === []) {
            return null;
        }

        $matchedCaseName = null;
        $matchedValue = null;

        $this->traverseNodesWithCallable($node, function (Node $inner) use (&$matchedCaseName, &$matchedValue): ?int {
            if (!$inner instanceof String_) {
                return null;
            }

            $value = $inner->value;

            if (!isset($this->routeValueMap[$value])) {
                return null;
            }

            $matchedValue = $value;
            $matchedCaseName = $this->routeValueMap[$value];

            return null;
        });

        if ($matchedCaseName === null || $matchedValue === null) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoText = sprintf($this->message, $matchedCaseName, $matchedValue);
        $todoComment = new Comment('// ' . $todoText);

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    /** @return array<string, string> */
    private function buildRouteValueMap(string $enumClass): array
    {
        if (!class_exists($enumClass, autoload: false)) {
            return [];
        }

        try {
            $reflection = new \ReflectionEnum($enumClass);
        } catch (\ReflectionException) {
            return [];
        }

        $map = [];

        foreach ($reflection->getCases() as $case) {
            if ($case instanceof \ReflectionEnumBackedCase) {
                /** @var string $value */
                $value = $case->getBackingValue();
                $map[$value] = $case->getName();
            }
        }

        return $map;
    }
}
