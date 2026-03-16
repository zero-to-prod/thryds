<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireNamedArgForBoolParamRector extends AbstractRector implements ConfigurableRectorInterface
{
    private bool $skipBuiltinFunctions = false;

    private bool $skipWhenOnlyArg = true;

    private string $todoMessage = 'TODO: Add named argument for boolean literal';

    private string $mode = 'auto';

    private string $message = '';

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
    ) {}

    public function configure(array $configuration): void
    {
        $this->skipBuiltinFunctions = $configuration['skipBuiltinFunctions'] ?? false;
        $this->skipWhenOnlyArg = $configuration['skipWhenOnlyArg'] ?? true;
        $this->todoMessage = $configuration['todoMessage'] ?? 'TODO: Add named argument for boolean literal';
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add named argument labels to calls that pass boolean literals as positional arguments',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$cache->set('key', $value, true);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$cache->set('key', $value, compress: true);
CODE_SAMPLE,
                    [
                        'skipBuiltinFunctions' => false,
                        'skipWhenOnlyArg' => true,
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
        return [FuncCall::class, MethodCall::class, StaticCall::class];
    }

    /**
     * @param FuncCall|MethodCall|StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->skipWhenOnlyArg && count($node->args) === 1) {
            return null;
        }

        $boolArgPosition = $this->findFirstPositionalBoolArgPosition($node->args);
        if ($boolArgPosition === null) {
            return null;
        }

        $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);

        if ($reflection === null) {
            return $this->addTodoComment($node);
        }

        if ($this->skipBuiltinFunctions && $reflection instanceof NativeFunctionReflection) {
            return null;
        }

        $parameters = ParametersAcceptorSelector::combineAcceptors($reflection->getVariants())->getParameters();

        // Validate all args from the first bool position onward can be resolved
        for ($i = $boolArgPosition; $i < count($node->args); $i++) {
            $arg = $node->args[$i];

            // Skip already-named args
            if (!$arg instanceof Arg || $arg->name !== null) {
                continue;
            }

            /** @var ParameterReflection|null $paramReflection */
            $paramReflection = $parameters[$i] ?? null;

            if ($paramReflection === null || $paramReflection->isVariadic()) {
                // Cannot resolve — fall back to TODO
                return $this->addTodoComment($node);
            }
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        // Apply: name all positional args from the first bool position onward
        $hasChanged = false;
        for ($i = $boolArgPosition; $i < count($node->args); $i++) {
            $arg = $node->args[$i];

            if (!$arg instanceof Arg || $arg->name !== null) {
                continue;
            }

            /** @var ParameterReflection $paramReflection */
            $paramReflection = $parameters[$i];
            $arg->name = new Identifier($paramReflection->getName());
            $hasChanged = true;
        }

        if (!$hasChanged) {
            return null;
        }

        return $node;
    }

    /**
     * @param array<int, Arg|\PhpParser\Node\VariadicPlaceholder> $args
     */
    private function findFirstPositionalBoolArgPosition(array $args): ?int
    {
        foreach ($args as $position => $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            if ($arg->name !== null) {
                continue;
            }

            if (!$arg->value instanceof ConstFetch) {
                continue;
            }

            $name = $this->getName($arg->value);
            if ($name === 'true' || $name === 'false') {
                return $position;
            }
        }

        return null;
    }

    /**
     * @param FuncCall|MethodCall|StaticCall $node
     * @return FuncCall|MethodCall|StaticCall
     */
    private function addTodoComment(Node $node): Node
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->todoMessage)) {
                return $node;
            }
        }

        $todoComment = new Comment('// ' . $this->todoMessage);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    private function addMessageComment(Node $node): ?Node
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return null;
            }
        }
        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $this->message));
        $node->setAttribute('comments', $comments);
        return $node;
    }
}
