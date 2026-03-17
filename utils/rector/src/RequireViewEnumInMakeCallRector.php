<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireViewEnumInMakeCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = '';

    private string $methodName = 'make';

    private string $paramName = 'view';

    private string $mode = 'warn';

    private string $message = "TODO: [RequireViewEnumInMakeCallRector] Use View::%s->value instead of string '%s'.";

    /** @var array<string, string> value => caseName */
    private array $valueToCaseMap = [];

    public function configure(array $configuration): void
    {
        $this->enumClass = $configuration['enumClass'] ?? '';
        $this->methodName = $configuration['methodName'] ?? 'make';
        $this->paramName = $configuration['paramName'] ?? 'view';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->valueToCaseMap = [];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require View enum case ->value in Blade make() calls instead of string literals',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Blade->make(view: 'home');
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$Blade->make(view: \App\Helpers\View::home->value);
CODE_SAMPLE,
                    [
                        'enumClass' => 'App\\Helpers\\View',
                        'methodName' => 'make',
                        'paramName' => 'view',
                        'mode' => 'auto',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, $this->methodName)) {
            return null;
        }

        $viewArg = $this->findNamedArg($node, $this->paramName);
        if ($viewArg === null) {
            return null;
        }

        if (!$viewArg->value instanceof String_) {
            return null;
        }

        $stringValue = $viewArg->value->value;
        $caseName = $this->resolveEnumCase($stringValue);

        if ($caseName === null) {
            return null;
        }

        if ($this->mode === 'auto') {
            $viewArg->value = new PropertyFetch(
                new ClassConstFetch(
                    new FullyQualified($this->enumClass),
                    new Identifier($caseName)
                ),
                new Identifier('value')
            );

            return $node;
        }

        return $this->addTodoComment($node, $caseName, $stringValue);
    }

    private function findNamedArg(MethodCall $node, string $paramName): ?Arg
    {
        foreach ($node->getArgs() as $arg) {
            if ($arg->name instanceof Identifier && $arg->name->name === $paramName) {
                return $arg;
            }
        }

        return null;
    }

    private function resolveEnumCase(string $value): ?string
    {
        if ($this->valueToCaseMap === []) {
            $this->buildValueToCaseMap();
        }

        return $this->valueToCaseMap[$value] ?? null;
    }

    private function buildValueToCaseMap(): void
    {
        if (!enum_exists($this->enumClass)) {
            return;
        }

        $reflection = new \ReflectionEnum($this->enumClass);

        foreach ($reflection->getCases() as $case) {
            $backingValue = $case->getBackingValue();
            if (is_string($backingValue)) {
                $this->valueToCaseMap[$backingValue] = $case->getName();
            }
        }
    }

    private function addTodoComment(MethodCall $node, string $caseName, string $stringValue): MethodCall
    {
        $todoText = sprintf($this->message, $caseName, $stringValue);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return $node;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
