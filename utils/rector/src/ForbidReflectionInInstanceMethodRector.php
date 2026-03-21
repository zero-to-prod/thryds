<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidReflectionInInstanceMethodRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md';

    /** @var string[] */
    private array $reflectionClasses = [
        'reflectionclass',
        'reflectionmethod',
        'reflectionproperty',
        'reflectionfunction',
        'reflectionenum',
    ];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if ($node->name->toString() === '__construct') {
            return null;
        }

        if ($node->isStatic()) {
            return null;
        }

        if ($node->stmts === null) {
            return null;
        }

        $nodeFinder = new NodeFinder();
        $hasChanged = false;

        foreach ($node->stmts as $stmt) {
            $newNodes = $nodeFinder->findInstanceOf([$stmt], New_::class);

            foreach ($newNodes as $newNode) {
                assert($newNode instanceof New_);

                if (!$newNode->class instanceof Name) {
                    continue;
                }

                if (!in_array($newNode->class->toLowerString(), $this->reflectionClasses, true)) {
                    continue;
                }

                if ($this->stmtAlreadyHasMessage($stmt)) {
                    continue;
                }

                $this->addTodoComment($stmt);
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
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
            'Flag Reflection API instantiation inside non-constructor instance methods, where it runs per-invocation instead of once at construction',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class Validator {
    public function validate(object $model): array {
        $ref = new ReflectionClass($model);
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class Validator {
    public function validate(object $model): array {
        // TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
        $ref = new ReflectionClass($model);
    }
}
CODE_SAMPLE,
                    ['mode' => 'warn'],
                ),
            ]
        );
    }
}
