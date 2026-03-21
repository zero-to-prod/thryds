<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeFinder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidHttpMethodBranchingInControllerRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [ForbidHttpMethodBranchingInControllerRector] Controllers must not branch on HTTP method — declare separate #[RouteOperation] handler methods and let the router dispatch. See: utils/rector/docs/ForbidHttpMethodBranchingInControllerRector.md';

    /** @var string[] */
    private array $controllerSuffixes = ['Controller'];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->controllerSuffixes = $configuration['controllerSuffixes'] ?? $this->controllerSuffixes;
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if ($node->name === null) {
            return null;
        }

        if (!$this->isController($node->name->toString())) {
            return null;
        }

        $nodeFinder = new NodeFinder();
        $hasChanged = false;

        foreach ($node->getMethods() as $method) {
            if ($method->stmts === null) {
                continue;
            }

            foreach ($method->stmts as $stmt) {
                $ifNodes = $nodeFinder->findInstanceOf([$stmt], If_::class);

                foreach ($ifNodes as $ifNode) {
                    assert($ifNode instanceof If_);

                    if (!$this->conditionBranchesOnHttpMethod($ifNode->cond)) {
                        continue;
                    }

                    if ($this->stmtAlreadyHasMessage($stmt)) {
                        continue;
                    }

                    $this->addTodoComment($stmt);
                    $hasChanged = true;
                }
            }
        }

        return $hasChanged ? $node : null;
    }

    private function isController(string $className): bool
    {
        foreach ($this->controllerSuffixes as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function conditionBranchesOnHttpMethod(Node $cond): bool
    {
        if (!$cond instanceof Identical
            && !$cond instanceof Equal
            && !$cond instanceof NotIdentical
            && !$cond instanceof NotEqual
        ) {
            return false;
        }

        $left = $cond->left;
        $right = $cond->right;

        return ($this->isGetMethodCall($left) && $this->isHttpMethodValue($right))
            || ($this->isGetMethodCall($right) && $this->isHttpMethodValue($left));
    }

    private function isGetMethodCall(Node $node): bool
    {
        if (!$node instanceof MethodCall) {
            return false;
        }

        if (!$node->name instanceof Identifier) {
            return false;
        }

        return $node->name->toString() === 'getMethod';
    }

    private function isHttpMethodValue(Node $node): bool
    {
        if ($node instanceof String_) {
            return in_array($node->value, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true);
        }

        if ($node instanceof PropertyFetch
            && $node->name instanceof Identifier
            && $node->name->toString() === 'value'
        ) {
            return true;
        }

        return false;
    }

    private function stmtAlreadyHasMessage(Stmt $stmt): bool
    {
        foreach ($stmt->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return true;
            }
        }

        return false;
    }

    private function addTodoComment(Stmt $stmt): void
    {
        $todoComment = new Comment('// ' . $this->message);
        $existingComments = $stmt->getComments();
        array_unshift($existingComments, $todoComment);
        $stmt->setAttribute('comments', $existingComments);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag if-statements that branch on HTTP request method inside controller classes',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class RegisterController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === HttpMethod::POST->value) {
            return new RedirectResponse('/login');
        }
        return new HtmlResponse('form');
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
readonly class RegisterController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: [ForbidHttpMethodBranchingInControllerRector] Controllers must not branch on HTTP method — declare separate #[RouteOperation] handler methods and let the router dispatch. See: utils/rector/docs/ForbidHttpMethodBranchingInControllerRector.md
        if ($request->getMethod() === HttpMethod::POST->value) {
            return new RedirectResponse('/login');
        }
        return new HtmlResponse('form');
    }
}
CODE_SAMPLE,
                    ['mode' => 'warn'],
                ),
            ]
        );
    }
}
