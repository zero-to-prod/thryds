<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\StringType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireEnumValueAccessRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $enumClasses = [];

    private string $mode = 'warn';

    private string $message = 'TODO: [RequireEnumValueAccessRector] %s::%s is a backed enum case — use ->value to get the string.';

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
    ) {}

    public function configure(array $configuration): void
    {
        $this->enumClasses = $configuration['enumClasses'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [RequireEnumValueAccessRector] %s::%s is a backed enum case — use ->value to get the string.';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Append ->value to backed enum cases used in string contexts (string-typed args, concatenation, array keys)',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Blade->make(view: View::home);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$Blade->make(view: View::home->value);
CODE_SAMPLE,
                    [
                        'enumClasses' => [\ZeroToProd\Thryds\Blade\View::class],
                        'mode' => 'auto',
                    ],
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, MethodCall::class, StaticCall::class, Concat::class];
    }

    /**
     * @param FuncCall|MethodCall|StaticCall|Concat $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Concat) {
            return $this->refactorConcat($node);
        }

        return $this->refactorCall($node);
    }

    /**
     * @param FuncCall|MethodCall|StaticCall $call
     */
    private function refactorCall(FuncCall|MethodCall|StaticCall $call): ?Node
    {
        $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($call);
        if ($reflection === null) {
            return null;
        }

        $parameters = ParametersAcceptorSelector::combineAcceptors($reflection->getVariants())->getParameters();

        $hasChanged = false;

        foreach ($call->args as $position => $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            // Resolve parameter position (named vs positional)
            $paramIndex = $position;
            if ($arg->name !== null) {
                $paramIndex = null;
                foreach ($parameters as $idx => $param) {
                    if ($param->getName() === $arg->name->toString()) {
                        $paramIndex = $idx;
                        break;
                    }
                }
            }

            if ($paramIndex === null) {
                continue;
            }

            $paramReflection = $parameters[$paramIndex] ?? null;
            if ($paramReflection === null) {
                continue;
            }

            if (!$paramReflection->getType() instanceof StringType) {
                continue;
            }

            $enumFetch = $this->findEnumFetchNeedingValue($arg->value);
            if ($enumFetch === null) {
                continue;
            }

            if ($this->mode !== 'auto') {
                $caseName = $enumFetch->name instanceof Identifier ? $enumFetch->name->toString() : null;
                $className = $this->resolveClassName($enumFetch);
                $todoMessage = ($caseName !== null && $className !== null)
                    ? sprintf($this->message, $className, $caseName)
                    : $this->message;

                return $this->addMessageComment($call, $todoMessage);
            }

            $arg->value = new PropertyFetch($enumFetch, new Identifier('value'));
            $hasChanged = true;
        }

        return $hasChanged ? $call : null;
    }

    private function refactorConcat(Concat $concat): ?Node
    {
        $hasChanged = false;

        $leftFetch = $this->findEnumFetchNeedingValue($concat->left);
        if ($leftFetch !== null) {
            if ($this->mode !== 'auto') {
                return $this->addConcatMessageComment($concat, $leftFetch);
            }
            $concat->left = new PropertyFetch($leftFetch, new Identifier('value'));
            $hasChanged = true;
        }

        $rightFetch = $this->findEnumFetchNeedingValue($concat->right);
        if ($rightFetch !== null) {
            if ($this->mode !== 'auto') {
                return $this->addConcatMessageComment($concat, $rightFetch);
            }
            $concat->right = new PropertyFetch($rightFetch, new Identifier('value'));
            $hasChanged = true;
        }

        return $hasChanged ? $concat : null;
    }

    private function addConcatMessageComment(Concat $concat, ClassConstFetch $enumFetch): ?Node
    {
        $caseName = $enumFetch->name instanceof Identifier ? $enumFetch->name->toString() : null;
        $className = $this->resolveClassName($enumFetch);
        $todoMessage = ($caseName !== null && $className !== null)
            ? sprintf($this->message, $className, $caseName)
            : $this->message;

        return $this->addMessageComment($concat, $todoMessage);
    }

    /**
     * Returns the ClassConstFetch if the given expression is an enum case fetch that needs ->value appended.
     * Returns null if it's not an enum case or already has ->value.
     */
    private function findEnumFetchNeedingValue(Node $expr): ?ClassConstFetch
    {
        // Skip already-wrapped: PropertyFetch with name 'value' on top of ClassConstFetch
        if ($expr instanceof PropertyFetch
            && $expr->var instanceof ClassConstFetch
            && $expr->name instanceof Identifier
            && $expr->name->toString() === 'value'
        ) {
            return null;
        }

        if (!$expr instanceof ClassConstFetch) {
            return null;
        }

        if (!$this->isConfiguredEnumClass($expr)) {
            return null;
        }

        // Skip CLASS pseudo-constant
        if ($expr->name instanceof Identifier && $expr->name->toString() === 'class') {
            return null;
        }

        return $expr;
    }

    private function isConfiguredEnumClass(ClassConstFetch $node): bool
    {
        $className = $this->resolveClassName($node);
        if ($className === null) {
            return false;
        }

        foreach ($this->enumClasses as $enumClass) {
            if (ltrim($className, '\\') === ltrim($enumClass, '\\')) {
                return true;
            }
        }

        return false;
    }

    private function resolveClassName(ClassConstFetch $node): ?string
    {
        $class = $node->class;

        if ($class instanceof Name) {
            return $class->toString();
        }

        return null;
    }

    private function addMessageComment(Node $node, string $message): ?Node
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $message)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $message));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
