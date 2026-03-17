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

final class ForbidStringArgForEnumParamRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $enumClasses = [];

    private string $mode = 'warn';

    private string $message = "TODO: [ForbidStringArgForEnumParamRector] '%s' matches %s::%s — use %s::%s->value.";

    private int $minLength = 2;

    /**
     * Combined value => ['class' => FQN, 'case' => caseName] map built from all enumClasses.
     * null means not yet built.
     *
     * @var array<string, array{class: string, case: string}>|null
     */
    private ?array $enumValueMap = null;

    /**
     * Functions whose string arguments should not be flagged (string-manipulation context).
     *
     * @var string[]
     */
    private array $skipFunctions = [
        'str_contains',
        'str_starts_with',
        'str_ends_with',
        'preg_match',
        'preg_replace',
        'strpos',
        'strrpos',
        'substr',
        'explode',
        'implode',
        'trim',
        'ltrim',
        'rtrim',
        'str_replace',
        'strlen',
        'strtolower',
        'strtoupper',
        'sprintf',
        'vsprintf',
        'printf',
        'number_format',
    ];

    public function configure(array $configuration): void
    {
        $this->enumClasses = $configuration['enumClasses'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [ForbidStringArgForEnumParamRector] '%s' matches %s::%s — use %s::%s->value.";
        $this->minLength = $configuration['minLength'] ?? 2;
        $this->enumValueMap = null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above statements where a string literal matches a backed enum case value',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$env = 'production';
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidStringArgForEnumParamRector] 'production' matches AppEnv::production — use AppEnv::production->value.
$env = 'production';
CODE_SAMPLE,
                    [
                        'enumClasses' => ['ZeroToProd\\Thryds\\AppEnv'],
                        'mode' => 'warn',
                        'message' => "TODO: [ForbidStringArgForEnumParamRector] '%s' matches %s::%s — use %s::%s->value.",
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
        if ($this->mode === 'auto') {
            return null;
        }

        $this->ensureEnumValueMap();

        if ($this->enumValueMap === []) {
            return null;
        }

        $match = $this->findFirstMatchingString($node);
        if ($match === null) {
            return null;
        }

        [$stringValue, $entry] = $match;
        $shortName = $this->shortName($entry['class']);
        $caseName = $entry['case'];

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoText = sprintf($this->message, $stringValue, $shortName, $caseName, $shortName, $caseName);
        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }

    /**
     * Walk child nodes and return the first String_ that matches an enum value,
     * skipping nodes that are already enum case->value expressions or inside
     * string-manipulation function calls.
     *
     * @return array{string, array{class: string, case: string}}|null
     */
    private function findFirstMatchingString(Node $node): ?array
    {
        // First, collect all string values that appear inside skipped function calls.
        $skippedValues = $this->collectSkippedStringValues($node);

        $result = null;

        $this->traverseNodesWithCallable($node, function (Node $inner) use (&$result, $skippedValues): ?int {
            if ($result !== null) {
                return null;
            }

            if (!$inner instanceof String_) {
                return null;
            }

            $value = $inner->value;

            // Skip short strings
            if (strlen($value) < $this->minLength) {
                return null;
            }

            // Skip if not in the combined map
            if (!isset($this->enumValueMap[$value])) {
                return null;
            }

            // Skip strings that only appear inside skipped function calls
            if (isset($skippedValues[$value])) {
                return null;
            }

            $result = [$value, $this->enumValueMap[$value]];

            return null;
        });

        return $result;
    }

    /**
     * Collect all string values that appear as arguments inside skipped function calls.
     * Returns a set of string values that should not be flagged.
     *
     * @return array<string, true>
     */
    private function collectSkippedStringValues(Node $node): array
    {
        $skipped = [];

        $this->traverseNodesWithCallable($node, function (Node $inner) use (&$skipped): void {
            if (!$inner instanceof \PhpParser\Node\Expr\FuncCall) {
                return;
            }

            if (!$inner->name instanceof \PhpParser\Node\Name) {
                return;
            }

            $funcName = strtolower($inner->name->toString());
            if (!in_array($funcName, $this->skipFunctions, strict: true)) {
                return;
            }

            // Collect all String_ values that are direct args to this function
            foreach ($inner->args as $arg) {
                if ($arg instanceof \PhpParser\Node\Arg && $arg->value instanceof String_) {
                    $skipped[$arg->value->value] = true;
                }
            }
        });

        return $skipped;
    }

    private function ensureEnumValueMap(): void
    {
        if ($this->enumValueMap !== null) {
            return;
        }

        $this->enumValueMap = [];

        foreach ($this->enumClasses as $enumClass) {
            $normalized = ltrim($enumClass, '\\');
            $map = $this->buildEnumValueMap($normalized);
            foreach ($map as $value => $caseName) {
                // First registered enum wins on collision
                if (!isset($this->enumValueMap[$value])) {
                    $this->enumValueMap[$value] = ['class' => $normalized, 'case' => $caseName];
                }
            }
        }
    }

    /** @return array<string, string> value => case name */
    private function buildEnumValueMap(string $enumClass): array
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

    private function shortName(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        return end($parts);
    }
}
