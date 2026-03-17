<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireSpecificResponseReturnTypeRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var list<string> */
    private array $controllerNamespaces = [];

    private string $genericInterface = 'Psr\Http\Message\ResponseInterface';

    private string $mode = 'warn';

    private string $message = 'TODO: [RequireSpecificResponseReturnTypeRector] Replace generic ResponseInterface return type with the specific response class actually returned (e.g. HtmlResponse or JsonResponse).';

    public function __construct(
        private readonly BetterNodeFinder $betterNodeFinder,
    ) {}

    public function configure(array $configuration): void
    {
        $this->controllerNamespaces = $configuration['controllerNamespaces'] ?? [];
        $this->genericInterface = $configuration['genericInterface'] ?? 'Psr\Http\Message\ResponseInterface';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Warn or auto-fix __invoke() methods on controller classes that declare a generic ResponseInterface return type instead of the specific response class they actually return',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;

class HomeController
{
    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use Laminas\Diactoros\Response\HtmlResponse;

class HomeController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}
CODE_SAMPLE,
                    [
                        'controllerNamespaces' => ['App\Controllers'],
                        'genericInterface' => 'Psr\Http\Message\ResponseInterface',
                        'mode' => 'auto',
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
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isControllerClass($node)) {
            return null;
        }

        $invoke = $node->getMethod('__invoke');
        if ($invoke === null) {
            return null;
        }

        if (!$this->hasGenericInterfaceReturnType($invoke)) {
            return null;
        }

        if ($this->hasTodoComment($invoke)) {
            return null;
        }

        $concreteClass = $this->resolveConcreteReturnClass($invoke);

        if ($concreteClass === null) {
            $this->addTodoComment($invoke);
            return $this->mode !== 'auto' ? $node : null;
        }

        if ($this->mode !== 'auto') {
            $this->addTodoComment($invoke);
            return $node;
        }

        $invoke->returnType = new FullyQualified($concreteClass);

        return $node;
    }

    private function isControllerClass(Class_ $node): bool
    {
        if ($this->controllerNamespaces === []) {
            return true;
        }

        $fqn = $node->namespacedName?->toString() ?? '';

        foreach ($this->controllerNamespaces as $ns) {
            if (str_starts_with($fqn, $ns)) {
                return true;
            }
        }

        return false;
    }

    private function hasGenericInterfaceReturnType(ClassMethod $node): bool
    {
        if ($node->returnType === null) {
            return false;
        }

        $returnType = $node->returnType;
        $normalised = ltrim($this->genericInterface, '\\');

        if ($returnType instanceof FullyQualified) {
            return $returnType->toString() === $normalised;
        }

        if ($returnType instanceof Name) {
            return $returnType->toString() === $normalised
                || $returnType->getLast() === $this->getShortName($normalised);
        }

        return false;
    }

    /**
     * Inspects return statements and returns the class name as written in the
     * source if every non-null return is a `new ConcreteClass(...)` of the same
     * class; returns null when the concrete type cannot be determined.
     */
    private function resolveConcreteReturnClass(ClassMethod $node): ?string
    {
        $returns = $this->betterNodeFinder->findReturnsScoped($node);

        if ($returns === []) {
            return null;
        }

        $found = null;

        foreach ($returns as $return) {
            if ($return->expr === null) {
                return null;
            }

            if (!$return->expr instanceof New_) {
                return null;
            }

            $new = $return->expr;

            if (!$new->class instanceof Name) {
                return null;
            }

            $className = $new->class->toString();

            if ($found === null) {
                $found = $className;
            } elseif ($found !== $className) {
                // Multiple different concrete types — cannot collapse to one
                return null;
            }
        }

        return $found;
    }

    private function getShortName(string $fqn): string
    {
        $parts = explode('\\', $fqn);

        return end($parts);
    }

    private function hasTodoComment(Node $node): bool
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return true;
            }
        }

        return false;
    }

    private function addTodoComment(ClassMethod $node): void
    {
        if ($this->mode === 'auto') {
            return;
        }

        $todoComment = new Comment('// ' . $this->message);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);
    }
}
