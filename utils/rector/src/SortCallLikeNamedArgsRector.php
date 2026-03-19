<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SortCallLikeNamedArgsRector extends AbstractRector
{
    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Sort named arguments in function/method calls to match the order they appear in the function/method signature',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
function createUser(string $name, int $age, string $email): void {}

createUser(age: 30, email: 'a@b.com', name: 'Alice');
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function createUser(string $name, int $age, string $email): void {}

createUser(name: 'Alice', age: 30, email: 'a@b.com');
CODE_SAMPLE,
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, MethodCall::class, StaticCall::class, New_::class];
    }

    /**
     * @param FuncCall|MethodCall|StaticCall|New_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->allArgsAreNamed($node->args)) {
            return null;
        }

        $reflection = $this->resolveReflection($node);

        if ($reflection === null) {
            return null;
        }

        // Skip built-in PHP functions — their parameter names may vary across extensions/versions
        if ($reflection instanceof NativeFunctionReflection) {
            return null;
        }

        $parameters = ParametersAcceptorSelector::combineAcceptors($reflection->getVariants())->getParameters();

        $paramOrder = [];
        foreach ($parameters as $position => $parameter) {
            $paramOrder[$parameter->getName()] = $position;
        }

        /** @var Arg[] $args */
        $args = $node->args;

        $sorted = $this->sortArgsByParamOrder($args, $paramOrder);

        if ($sorted === null) {
            return null;
        }

        if ($this->argsAlreadySorted($args, $sorted)) {
            return null;
        }

        $node->args = $sorted;

        return $node;
    }

    /**
     * Resolve method/function reflection, with a direct-name fallback for static calls.
     *
     * PHPStan's type resolver returns a class-string type (not an object type) for the
     * class-name side of a static call (e.g. `Logger` in `Logger::log(...)`), so
     * `resolveFunctionLikeReflectionFromCall` cannot determine the class. The fallback
     * reads the class name directly from the Name node and queries ReflectionProvider.
     *
     * @param FuncCall|MethodCall|StaticCall|New_ $node
     * @return \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null
     */
    private function resolveReflection(Node $node)
    {
        if ($node instanceof StaticCall && $node->class instanceof Name && $node->name instanceof Identifier) {
            return $this->resolveStaticCallReflection($node);
        }

        return $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
    }

    /**
     * Resolve method reflection for a static call by reading the class Name directly.
     *
     * `ReflectionResolver::resolveMethodReflectionFromStaticCall` resolves the class via
     * PHPStan type inference (`getType($node->class)`), which returns a class-string type
     * (not an object type) for static calls — so `getObjectClassNames()` returns empty.
     * This method bypasses type inference and reads the class name from the Name node.
     */
    private function resolveStaticCallReflection(StaticCall $node): ?\PHPStan\Reflection\MethodReflection
    {
        assert($node->class instanceof Name);
        assert($node->name instanceof Identifier);

        $className = (string) $node->class;
        $methodName = $node->name->name;
        $scope = $node->getAttribute(AttributeKey::SCOPE);

        return $this->reflectionResolver->resolveMethodReflection(
            $className,
            $methodName,
            $scope instanceof Scope ? $scope : null,
        );
    }

    /**
     * Returns null when any arg name is absent from the signature (cannot safely reorder).
     *
     * @param Arg[] $args
     * @param array<string, int> $paramOrder
     * @return Arg[]|null
     */
    private function sortArgsByParamOrder(array $args, array $paramOrder): ?array
    {
        $indexed = [];

        foreach ($args as $arg) {
            $name = $arg->name?->name;

            if ($name === null || ! array_key_exists($name, $paramOrder)) {
                return null;
            }

            $indexed[$paramOrder[$name]] = $arg;
        }

        ksort($indexed);

        return array_values($indexed);
    }

    /**
     * @param Arg[] $original
     * @param Arg[] $sorted
     */
    private function argsAlreadySorted(array $original, array $sorted): bool
    {
        foreach ($original as $i => $arg) {
            if ($arg !== $sorted[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, Arg|\PhpParser\Node\VariadicPlaceholder> $args
     */
    private function allArgsAreNamed(array $args): bool
    {
        if ($args === []) {
            return false;
        }

        foreach ($args as $arg) {
            if (! $arg instanceof Arg) {
                return false;
            }

            if ($arg->name === null) {
                return false;
            }
        }

        return true;
    }
}
