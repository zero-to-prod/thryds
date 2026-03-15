<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddNamedArgWhenVarMismatchesParamRector extends AbstractRector
{
    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
    ) {}

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

        foreach ($node->args as $position => $arg) {
            // Skip if already a named argument
            if ($arg->name !== null) {
                continue;
            }

            if (! $arg->value instanceof Variable) {
                continue;
            }

            $varName = $this->getName($arg->value);
            if ($varName === null) {
                continue;
            }

            /** @var ParameterReflection|null $paramReflection */
            $paramReflection = $parameters[$position] ?? null;
            if ($paramReflection === null) {
                continue;
            }

            $paramName = $paramReflection->getName();

            // No change if variable name matches the parameter name
            if ($varName === $paramName) {
                continue;
            }

            $arg->name = new Identifier($paramName);
            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        return $node;
    }
}
