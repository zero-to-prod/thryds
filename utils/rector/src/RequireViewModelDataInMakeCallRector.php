<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Warns when a Blade make() call renders a view that has a matching ViewModel
 * class but omits the data: argument, so the view always receives typed context.
 */
final class RequireViewModelDataInMakeCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const COMMENT_MARKER = '[RequireViewModelDataInMakeCallRector]';

    private string $viewEnumClass = '';

    private string $viewModelsNamespace = '';

    private string $methodName = 'make';

    private string $viewParamName = 'view';

    private string $dataParamName = 'data';

    private string $viewModelSuffix = 'ViewModel';

    private string $mode = 'warn';

    private string $message = "TODO: [RequireViewModelDataInMakeCallRector] make() renders '%s' which has a %s — pass data: [%s::view_key => %s::from([...])] so the view receives typed context. See: utils/rector/docs/RequireViewModelDataInMakeCallRector.md";

    public function configure(array $configuration): void
    {
        $this->viewEnumClass       = $configuration['viewEnumClass'] ?? '';
        $this->viewModelsNamespace = $configuration['viewModelsNamespace'] ?? '';
        $this->methodName          = $configuration['methodName'] ?? 'make';
        $this->viewParamName       = $configuration['viewParamName'] ?? 'view';
        $this->dataParamName       = $configuration['dataParamName'] ?? 'data';
        $this->viewModelSuffix     = $configuration['viewModelSuffix'] ?? 'ViewModel';
        $this->mode                = $configuration['mode'] ?? 'warn';
        $this->message             = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require data: argument in Blade make() calls when the rendered view has a matching ViewModel class',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Blade->make(view: View::register->value);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireViewModelDataInMakeCallRector] make() renders 'register' which has a RegisterViewModel — pass data: [RegisterViewModel::view_key => RegisterViewModel::from([...])]
$Blade->make(view: View::register->value);
CODE_SAMPLE,
                    [
                        'viewEnumClass'       => 'App\\Blade\\View',
                        'viewModelsNamespace' => 'App\\ViewModels',
                        'mode'                => 'warn',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Expression::class, Return_::class];
    }

    /**
     * @param Expression|Return_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $makeCall = $this->findMakeCall($node);
        if ($makeCall === null) {
            return null;
        }

        $viewArg = $this->findNamedArg($makeCall, $this->viewParamName);
        if ($viewArg === null) {
            return null;
        }

        $caseName = $this->resolveViewCaseName($viewArg->value);
        if ($caseName === null) {
            return null;
        }

        $viewModelShort = $this->resolveViewModelShortName($caseName);
        if ($viewModelShort === null) {
            return null;
        }

        // data: arg already present — remove any stale comment from the statement.
        $dataArg = $this->findNamedArg($makeCall, $this->dataParamName);
        if ($dataArg !== null) {
            return $this->removeStaleComment($node);
        }

        if ($this->mode === 'warn') {
            return $this->addTodoComment($node, $caseName, $viewModelShort);
        }

        return null;
    }

    /**
     * Finds a make() call directly inside the given statement node.
     */
    private function findMakeCall(Expression|Return_ $node): ?MethodCall
    {
        $expr = $node instanceof Return_ ? $node->expr : $node->expr;

        if (! $expr instanceof MethodCall) {
            return null;
        }

        if (! $this->isName($expr->name, $this->methodName)) {
            return null;
        }

        return $expr;
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

    /**
     * Extracts the View enum case name from a View::X->value expression.
     */
    private function resolveViewCaseName(Expr $expr): ?string
    {
        if (! $expr instanceof PropertyFetch) {
            return null;
        }

        if (! $expr->name instanceof Identifier || $expr->name->name !== 'value') {
            return null;
        }

        if (! $expr->var instanceof ClassConstFetch) {
            return null;
        }

        $className = (string) $expr->var->class;
        if ($className === '') {
            return null;
        }

        $classParts = explode('\\', $className);
        $shortClass = end($classParts);
        $enumParts  = explode('\\', $this->viewEnumClass);
        $shortEnum  = end($enumParts);

        if ($shortClass !== $shortEnum && $className !== $this->viewEnumClass) {
            return null;
        }

        if (! $expr->var->name instanceof Identifier) {
            return null;
        }

        return $expr->var->name->name;
    }

    /**
     * Derives the ViewModel short class name for a view case if the class exists.
     */
    private function resolveViewModelShortName(string $caseName): ?string
    {
        $pascalCase = str_replace('_', '', ucwords($caseName, '_'));
        $shortName  = $pascalCase . $this->viewModelSuffix;
        $fqcn       = rtrim($this->viewModelsNamespace, '\\') . '\\' . $shortName;

        if (! class_exists($fqcn)) {
            return null;
        }

        return $shortName;
    }

    private function addTodoComment(Expression|Return_ $node, string $caseName, string $viewModelShort): Expression|Return_
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), self::COMMENT_MARKER)) {
                return $node;
            }
        }

        $todoText = sprintf($this->message, $caseName, $viewModelShort, $viewModelShort, $viewModelShort);
        $comments  = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }

    private function removeStaleComment(Expression|Return_ $node): ?Node
    {
        $comments = $node->getComments();
        $filtered = array_values(array_filter(
            $comments,
            static fn(Comment $c): bool => ! str_contains($c->getText(), self::COMMENT_MARKER),
        ));

        if (count($filtered) === count($comments)) {
            return null;
        }

        $node->setAttribute('comments', $filtered);

        return $node;
    }
}
