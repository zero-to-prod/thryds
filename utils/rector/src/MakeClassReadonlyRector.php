<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class MakeClassReadonlyRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add readonly modifier to classes that do not use mutable state',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class UserDto
{
    public function __construct(
        public readonly string $name,
    ) {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
readonly class UserDto
{
    public function __construct(
        public string $name,
    ) {}
}
CODE_SAMPLE
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
        if ($node->isReadonly()) {
            return null;
        }

        if ($node->isAnonymous()) {
            return null;
        }

        if ($node->isAbstract()) {
            return null;
        }

        if ($this->hasState($node)) {
            return null;
        }

        if ($node->extends !== null && $this->parentHasState($node)) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $node->flags |= Class_::MODIFIER_READONLY;

        $this->removeRedundantPropertyReadonly($node);

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

    private function hasState(Class_ $node): bool
    {
        foreach ($node->getProperties() as $property) {
            if ($property->isStatic()) {
                return true;
            }

            if ($property->type === null) {
                return true;
            }

            if (!$property->isReadonly() && !$this->isPromotedProperty($property, $node)) {
                return true;
            }
        }

        if ($this->hasPropertyWriteOutsideConstructor($node)) {
            return true;
        }

        return false;
    }

    private function isPromotedProperty(Node\Stmt\Property $property, Class_ $class): bool
    {
        foreach ($class->getMethods() as $method) {
            if (!$this->isName($method, '__construct')) {
                continue;
            }

            foreach ($method->params as $param) {
                if ($param->flags === 0) {
                    continue;
                }

                if ($param->var instanceof Variable
                    && $this->getName($param->var) === $this->getName($property)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasPropertyWriteOutsideConstructor(Class_ $node): bool
    {
        $hasWrite = false;

        foreach ($node->getMethods() as $method) {
            if ($this->isName($method, '__construct')) {
                continue;
            }

            $this->traverseNodesWithCallable($method->stmts ?? [], function (Node $inner) use (&$hasWrite): void {
                if ($inner instanceof Assign
                    && $inner->var instanceof PropertyFetch
                    && $inner->var->var instanceof Variable
                    && $this->isName($inner->var->var, 'this')) {
                    $hasWrite = true;
                }
            });

            if ($hasWrite) {
                return true;
            }
        }

        return false;
    }

    private function parentHasState(Class_ $node): bool
    {
        $parentName = $this->getName($node->extends);
        if ($parentName === null) {
            return false;
        }

        if (!$this->reflectionProvider->hasClass($parentName)) {
            return true;
        }

        $classReflection = $this->reflectionProvider->getClass($parentName);

        if ($classReflection->isReadOnly()) {
            return false;
        }

        $nativeReflection = $classReflection->getNativeReflection();

        foreach ($nativeReflection->getProperties() as $property) {
            if ($property->isStatic()) {
                return true;
            }

            if (!$property->isReadOnly()) {
                return true;
            }
        }

        return false;
    }

    private function removeRedundantPropertyReadonly(Class_ $node): void
    {
        foreach ($node->getProperties() as $property) {
            if ($property->isReadonly()) {
                $property->flags &= ~Class_::MODIFIER_READONLY;
            }
        }

        foreach ($node->getMethods() as $method) {
            if (!$this->isName($method, '__construct')) {
                continue;
            }

            foreach ($method->params as $param) {
                if ($param->flags & Class_::MODIFIER_READONLY) {
                    $param->flags &= ~Class_::MODIFIER_READONLY;
                }
            }
        }
    }
}
