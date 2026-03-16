<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\EnumCase;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenameEnumCaseToMatchValueRector extends AbstractRector implements ConfigurableRectorInterface
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
        return new RuleDefinition('Rename enum case names to match the casing of their string value', [
            new CodeSample(
                <<<'CODE_SAMPLE'
enum Status: string
{
    case Active = 'active';
    case InProgress = 'in_progress';
}
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
enum Status: string
{
    case active = 'active';
    case in_progress = 'in_progress';
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
        return [EnumCase::class, ClassConstFetch::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof EnumCase) {
            return $this->refactorEnumCase($node);
        }

        if ($node instanceof ClassConstFetch) {
            return $this->refactorClassConstFetch($node);
        }

        return null;
    }

    private function refactorEnumCase(EnumCase $node): ?EnumCase
    {
        if (! $node->expr instanceof String_) {
            return null;
        }

        $value = $node->expr->value;
        $currentName = $this->getName($node);

        if ($currentName === $value) {
            return null;
        }

        if (! preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $value)) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $node->name = new Identifier($value);

        return $node;
    }

    private function refactorClassConstFetch(ClassConstFetch $node): ?ClassConstFetch
    {
        $className = $this->getName($node->class);
        if ($className === null) {
            return null;
        }

        $caseName = $this->getName($node->name);
        if ($caseName === null) {
            return null;
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (! $classReflection->isEnum()) {
            return null;
        }

        if (! $classReflection->hasEnumCase($caseName)) {
            return null;
        }

        $enumCaseReflection = $classReflection->getEnumCase($caseName);
        $backingType = $enumCaseReflection->getBackingValueType();

        if ($backingType === null) {
            return null;
        }

        if (! $backingType instanceof \PHPStan\Type\Constant\ConstantStringType) {
            return null;
        }

        $backingValue = $backingType->getValue();

        if ($caseName === $backingValue) {
            return null;
        }

        if (! preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $backingValue)) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $node->name = new Identifier($backingValue);

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
}
