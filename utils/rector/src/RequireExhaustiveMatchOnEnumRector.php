<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireExhaustiveMatchOnEnumRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [RequireExhaustiveMatchOnEnumRector] match() on %s must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [RequireExhaustiveMatchOnEnumRector] match() on %s must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require match() on a backed enum to explicitly cover all cases — prevents new cases from silently falling through a default',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
function processStatus(Status $status): string
{
    return match($status) {
        Status::active => 'Active',
        default => 'Other',
    };
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function processStatus(Status $status): string
{
    // TODO: [RequireExhaustiveMatchOnEnumRector] match() on Status must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md
    return match($status) {
        Status::active => 'Active',
        default => 'Other',
    };
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'message' => 'TODO: [RequireExhaustiveMatchOnEnumRector] match() on %s must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md',
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
        return [Expression::class, Return_::class];
    }

    /**
     * @param Expression|Return_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        $match = $this->findMatchExpr($node);
        if ($match === null) {
            return null;
        }

        $enumClass = $this->resolveBackedEnumClass($match->cond);
        if ($enumClass === null) {
            return null;
        }

        $allCases = $this->getEnumCaseNames($enumClass);
        if ($allCases === []) {
            return null;
        }

        $hasDefault = $this->hasDefaultArm($match->arms);
        $coveredCases = $this->collectCoveredCases($match->arms, $enumClass);

        // All cases explicitly listed (with or without default) — exhaustive
        if (count($coveredCases) === count($allCases)) {
            return null;
        }

        $shortName = $this->shortName($enumClass);

        return $this->addTodoComment($node, $shortName);
    }

    private function findMatchExpr(Expression|Return_ $node): ?Match_
    {
        $expr = $node instanceof Return_ ? $node->expr : $node->expr;

        if ($expr === null) {
            return null;
        }

        if ($expr instanceof Match_) {
            return $expr;
        }

        // Also handle: $var = match(...)
        if ($expr instanceof Assign && $expr->expr instanceof Match_) {
            return $expr->expr;
        }

        return null;
    }

    /**
     * Resolve the fully qualified class name of the backed enum type of $expr,
     * or null if the expression is not a backed enum.
     */
    private function resolveBackedEnumClass(Node\Expr $expr): ?string
    {
        $type = $this->getType($expr);

        if (!$type instanceof ObjectType) {
            return null;
        }

        if (!$type->isEnum()->yes()) {
            return null;
        }

        $className = $type->getClassName();

        if (!class_exists($className)) {
            return null;
        }

        try {
            $reflection = new \ReflectionEnum($className);
        } catch (\ReflectionException) {
            return null;
        }

        if (!$reflection->isBacked()) {
            return null;
        }

        return $className;
    }

    /**
     * @return string[]
     */
    private function getEnumCaseNames(string $enumClass): array
    {
        try {
            $reflection = new \ReflectionEnum($enumClass);
        } catch (\ReflectionException) {
            return [];
        }

        $names = [];
        foreach ($reflection->getCases() as $case) {
            $names[] = $case->getName();
        }

        return $names;
    }

    /**
     * @param MatchArm[] $arms
     */
    private function hasDefaultArm(array $arms): bool
    {
        foreach ($arms as $arm) {
            if ($arm->conds === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collect the enum case names explicitly listed in match arms.
     *
     * @param MatchArm[] $arms
     * @return string[]
     */
    private function collectCoveredCases(array $arms, string $enumClass): array
    {
        $covered = [];
        $shortName = $this->shortName($enumClass);

        foreach ($arms as $arm) {
            if ($arm->conds === null) {
                continue; // default arm
            }

            foreach ($arm->conds as $cond) {
                if (!$cond instanceof ClassConstFetch) {
                    continue;
                }

                if (!$cond->name instanceof Identifier) {
                    continue;
                }

                if (!$cond->class instanceof Name) {
                    continue;
                }

                $className = $cond->class->toString();

                // Match by short name or FQN
                if ($className !== $shortName && $className !== $enumClass && ltrim($className, '\\') !== $enumClass) {
                    continue;
                }

                $covered[] = $cond->name->toString();
            }
        }

        return $covered;
    }

    private function addTodoComment(Expression|Return_ $node, string $shortName): ?Node
    {
        $todoText = sprintf($this->message, $shortName);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }

    private function shortName(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        return end($parts);
    }
}
