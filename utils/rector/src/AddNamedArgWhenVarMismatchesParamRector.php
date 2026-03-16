<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddNamedArgWhenVarMismatchesParamRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
    ) {}

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add named argument when variable name does not match parameter name', [
            new CodeSample(
                <<<'CODE_SAMPLE'
$Router->dispatch($ServerRequestInterface);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
$Router->dispatch(request: $ServerRequestInterface);
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class, FuncCall::class, New_::class];
    }

    /**
     * @param MethodCall|StaticCall|FuncCall|New_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
        if ($reflection === null) {
            return null;
        }

        $parameters = ParametersAcceptorSelector::combineAcceptors($reflection->getVariants())->getParameters();

        $hasChanged = false;
        $namedArgSeen = false;

        foreach ($node->args as $position => $arg) {
            // Track if we've seen any named argument (pre-existing or just added)
            if ($arg->name !== null) {
                $namedArgSeen = true;
                continue;
            }

            /** @var ParameterReflection|null $paramReflection */
            $paramReflection = $parameters[$position] ?? null;
            if ($paramReflection === null) {
                continue;
            }

            $paramName = $paramReflection->getName();

            $isVariable = $arg->value instanceof Variable;
            $varName = $isVariable ? $this->getName($arg->value) : null;

            // Add named arg if: variable name mismatches param, or a prior named arg forces it
            if ($namedArgSeen || ($isVariable && $varName !== null && $varName !== $paramName)) {
                $arg->name = new Identifier($paramName);
                $namedArgSeen = true;
                $hasChanged = true;
            }
        }

        if (! $hasChanged) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

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
