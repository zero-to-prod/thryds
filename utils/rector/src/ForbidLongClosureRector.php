<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidLongClosureRector extends AbstractRector implements ConfigurableRectorInterface
{
    private int $maxStatements = 5;

    private bool $skipArrowFunctions = true;

    private string $message = 'TODO: Extract closure to a named function or method';

    public function configure(array $configuration): void
    {
        $this->maxStatements = $configuration['maxStatements'] ?? 5;
        $this->skipArrowFunctions = $configuration['skipArrowFunctions'] ?? true;
        $this->message = $configuration['message'] ?? 'TODO: Extract closure to a named function or method';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Extract long closures into named private methods (class context) or named functions (file scope)',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class Handler {
    public function boot(): void {
        $render = function (string $message, int $code) use ($Blade): void {
            $html = $Blade->make('error', ['message' => $message])->render();
            (new SapiEmitter())->emit(new HtmlResponse($html, $code));
            log_something();
            do_more();
            cleanup();
            finish();
        };
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class Handler {
    public function boot(): void {
        $render = fn(string $message, int $code): void => $this->render($Blade, $message, $code);
    }
    private function render(mixed $Blade, string $message, int $code): void {
        $html = $Blade->make('error', ['message' => $message])->render();
        (new SapiEmitter())->emit(new HtmlResponse($html, $code));
        log_something();
        do_more();
        cleanup();
        finish();
    }
}
CODE_SAMPLE,
                    ['maxStatements' => 5, 'skipArrowFunctions' => true]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Namespace_::class];
    }

    /**
     * @param Class_|Namespace_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Class_) {
            return $this->refactorClass($node);
        }

        if ($node instanceof Namespace_) {
            return $this->refactorNamespace($node);
        }

        return null;
    }

    private function refactorClass(Class_ $class): ?Class_
    {
        $changed = false;
        $newMethods = [];

        foreach ($class->getMethods() as $method) {
            if ($method->stmts === null) {
                continue;
            }

            foreach ($method->stmts as $stmt) {
                if (!$stmt instanceof Expression) {
                    continue;
                }
                if (!$stmt->expr instanceof Assign) {
                    continue;
                }

                $assign = $stmt->expr;
                if (!$assign->expr instanceof Closure) {
                    continue;
                }

                $closure = $assign->expr;
                if (!$this->isLongClosure($closure)) {
                    continue;
                }

                if (!$this->isSafeToExtract($closure)) {
                    if ($this->addTodoComment($stmt, $this->getUnsafeReason($closure))) {
                        $changed = true;
                    }
                    continue;
                }

                $varName = $this->getName($assign->var);
                if ($varName === null) {
                    if ($this->addTodoComment($stmt)) {
                        $changed = true;
                    }
                    continue;
                }

                $methodName = $this->snakeToCamel($varName);
                if ($class->getMethod($methodName) !== null) {
                    if ($this->addTodoComment($stmt, 'method name conflict')) {
                        $changed = true;
                    }
                    continue;
                }

                $isStatic = $closure->static;
                $newMethods[] = $this->createClassMethod($methodName, $closure, $isStatic);
                $this->replaceWithClassForwardingClosure($assign, $closure, $methodName, $isStatic);
                $changed = true;
            }
        }

        if (!$changed) {
            return null;
        }

        foreach ($newMethods as $newMethod) {
            $class->stmts[] = $newMethod;
        }

        return $class;
    }

    private function refactorNamespace(Namespace_ $namespace): ?Namespace_
    {
        $changed = false;
        $insertions = [];

        foreach ($namespace->stmts as $i => $stmt) {
            if ($stmt instanceof Class_) {
                continue;
            }
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }

            $assign = $stmt->expr;
            if (!$assign->expr instanceof Closure) {
                continue;
            }

            $closure = $assign->expr;
            if (!$this->isLongClosure($closure)) {
                continue;
            }

            if (!$this->isSafeToExtract($closure)) {
                if ($this->addTodoComment($stmt, $this->getUnsafeReason($closure))) {
                    $changed = true;
                }
                continue;
            }

            $varName = $this->getName($assign->var);
            if ($varName === null) {
                if ($this->addTodoComment($stmt)) {
                    $changed = true;
                }
                continue;
            }

            $funcName = $this->toSnakeCase($varName);
            $insertions[$i] = $this->createNamedFunction($funcName, $closure);
            $this->replaceWithFileScopeForwardingClosure($assign, $closure, $funcName);
            $changed = true;
        }

        if (!$changed) {
            return null;
        }

        krsort($insertions);
        foreach ($insertions as $index => $function) {
            array_splice($namespace->stmts, $index, 0, [$function]);
        }

        return $namespace;
    }

    private function isLongClosure(Closure|ArrowFunction $node): bool
    {
        if ($node instanceof ArrowFunction) {
            return !$this->skipArrowFunctions;
        }

        return count($node->stmts) > $this->maxStatements;
    }

    private function isSafeToExtract(Closure $closure): bool
    {
        foreach ($closure->uses as $use) {
            if ($use->byRef) {
                return false;
            }
        }

        $capturedNames = $this->getCapturedNames($closure);
        if ($capturedNames === []) {
            return true;
        }

        $NodeFinder = new NodeFinder();
        $assigns = $NodeFinder->findInstanceOf($closure->stmts, Assign::class);
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof Variable) {
                continue;
            }
            $varName = $this->getName($assign->var);
            if (in_array($varName, $capturedNames, true)) {
                return false;
            }
        }

        return true;
    }

    private function getUnsafeReason(Closure $closure): string
    {
        foreach ($closure->uses as $use) {
            if ($use->byRef) {
                return 'captures mutable references';
            }
        }

        $capturedNames = $this->getCapturedNames($closure);
        $NodeFinder = new NodeFinder();
        $assigns = $NodeFinder->findInstanceOf($closure->stmts, Assign::class);
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof Variable) {
                continue;
            }
            if (in_array($this->getName($assign->var), $capturedNames, true)) {
                return 'modifies captured variables';
            }
        }

        return 'extraction not safe';
    }

    /**
     * @return string[]
     */
    private function getCapturedNames(Closure $closure): array
    {
        $names = [];
        foreach ($closure->uses as $use) {
            $name = $this->getName($use->var);
            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function snakeToCamel(string $name): string
    {
        $name = ltrim($name, '_');

        return lcfirst(str_replace('_', '', ucwords($name, '_')));
    }

    private function toSnakeCase(string $name): string
    {
        $name = ltrim($name, '_');
        $result = preg_replace('/[A-Z]/', '_$0', $name);

        return strtolower(ltrim($result, '_'));
    }

    private function createClassMethod(string $methodName, Closure $closure, bool $isStatic): ClassMethod
    {
        $params = [];
        foreach ($closure->uses as $use) {
            $param = new Param(clone $use->var);
            $param->type = new Identifier('mixed');
            $params[] = $param;
        }
        foreach ($closure->params as $closureParam) {
            $params[] = clone $closureParam;
        }

        $method = new ClassMethod($methodName);
        $flags = Class_::MODIFIER_PRIVATE;
        if ($isStatic) {
            $flags |= Class_::MODIFIER_STATIC;
        }
        $method->flags = $flags;
        $method->params = $params;
        $method->returnType = $closure->returnType !== null ? clone $closure->returnType : null;
        $method->stmts = $closure->stmts;

        return $method;
    }

    private function replaceWithClassForwardingClosure(
        Assign $assign,
        Closure $closure,
        string $methodName,
        bool $isStatic,
    ): void {
        $args = [];
        foreach ($closure->uses as $use) {
            $args[] = new Arg(clone $use->var);
        }
        foreach ($closure->params as $param) {
            $args[] = new Arg(clone $param->var);
        }

        if ($isStatic) {
            $call = new StaticCall(new Name('self'), $methodName, $args);
        } else {
            $call = new MethodCall(new Variable('this'), $methodName, $args);
        }

        $assign->expr = new ArrowFunction([
            'expr' => $call,
            'params' => array_map(static fn(Param $p): Param => clone $p, $closure->params),
            'returnType' => $closure->returnType !== null ? clone $closure->returnType : null,
            'static' => $isStatic,
        ]);
    }

    private function createNamedFunction(string $funcName, Closure $closure): Function_
    {
        $params = [];
        foreach ($closure->uses as $use) {
            $param = new Param(clone $use->var);
            $param->type = new Identifier('mixed');
            $params[] = $param;
        }
        foreach ($closure->params as $closureParam) {
            $params[] = clone $closureParam;
        }

        $function = new Function_($funcName, [
            'params' => $params,
            'returnType' => $closure->returnType !== null ? clone $closure->returnType : null,
            'stmts' => $closure->stmts,
        ]);
        $function->namespacedName = new Name($funcName);

        return $function;
    }

    private function replaceWithFileScopeForwardingClosure(
        Assign $assign,
        Closure $closure,
        string $funcName,
    ): void {
        $args = [];
        foreach ($closure->uses as $use) {
            $args[] = new Arg(clone $use->var);
        }
        foreach ($closure->params as $param) {
            $args[] = new Arg(clone $param->var);
        }

        $call = new FuncCall(new Name($funcName), $args);

        $assign->expr = new ArrowFunction([
            'expr' => $call,
            'params' => array_map(static fn(Param $p): Param => clone $p, $closure->params),
            'returnType' => $closure->returnType !== null ? clone $closure->returnType : null,
            'static' => $closure->static,
        ]);
    }

    private function addTodoComment(Expression $stmt, ?string $reason = null): bool
    {
        $message = $this->message;
        if ($reason !== null) {
            $message .= ' (' . $reason . ')';
        }

        foreach ($stmt->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return false;
            }
        }

        $existingComments = $stmt->getComments();
        $todoComment = new Comment('// ' . $message);
        array_unshift($existingComments, $todoComment);
        $stmt->setAttribute('comments', $existingComments);

        return true;
    }
}
