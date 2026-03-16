<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenameParamToMatchTypeNameRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename parameters to exactly match their type name', [
            new CodeSample(
                <<<'CODE_SAMPLE'
function handle(ServerRequestInterface $request, Router $r): void
{
    $r->dispatch($request);
}
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
function handle(ServerRequestInterface $ServerRequestInterface, Router $Router): void
{
    $Router->dispatch($ServerRequestInterface);
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }

    /**
     * @param ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->params as $param) {
            if ($param->variadic) {
                continue;
            }

            $typeName = $this->resolveTypeShortName($param);
            if ($typeName === null) {
                continue;
            }

            $expectedName = $typeName;
            $currentName = $this->getName($param);

            if ($currentName === null || $currentName === $expectedName) {
                continue;
            }

            if ($this->mode !== 'auto') {
                return $this->addMessageComment($node);
            }

            $param->var = new Variable($expectedName);

            $this->renameVariableInFunctionBody($node, $currentName, $expectedName);

            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
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

    private function resolveTypeShortName(Param $param): ?string
    {
        $type = $param->type;

        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        if (! $type instanceof Name) {
            return null;
        }

        return $type->getLast();
    }

    /**
     * @param ClassMethod|Function_|Closure|ArrowFunction $functionLike
     */
    private function renameVariableInFunctionBody(Node $functionLike, string $oldName, string $newName): void
    {
        $stmts = $functionLike instanceof ArrowFunction
            ? [$functionLike->expr]
            : (array) $functionLike->getStmts();

        $this->traverseNodesWithCallable($stmts, function (Node $node) use ($oldName, $newName): ?Node {
            if (! $node instanceof Variable) {
                return null;
            }

            if (! $this->isName($node, $oldName)) {
                return null;
            }

            $node->name = $newName;
            return $node;
        });
    }
}
