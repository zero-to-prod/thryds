<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Replaces `short_class_name(SomeClass::class)` array keys with `SomeClass::view_key`
 * when the class has a `view_key` constant.
 */
final class ReplaceShortClassNameWithViewKeyRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const VIEW_KEY_CONST = 'view_key';

    private const SHORT_CLASS_NAME_FUNCTION = 'short_class_name';

    private string $shortClassNameFunction = self::SHORT_CLASS_NAME_FUNCTION;

    private string $mode = 'auto';

    private string $message = '';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->shortClassNameFunction = $configuration['shortClassNameFunction'] ?? self::SHORT_CLASS_NAME_FUNCTION;
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace short_class_name(SomeClass::class) array keys with SomeClass::view_key',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$data = [
    short_class_name(ErrorViewModel::class) => ErrorViewModel::from([]),
];
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$data = [
    ErrorViewModel::view_key => ErrorViewModel::from([]),
];
CODE_SAMPLE,
                    [
                        'shortClassNameFunction' => 'ZeroToProd\\Thryds\\Helpers\\short_class_name',
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
        return [Array_::class];
    }

    /**
     * @param Array_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->items as $item) {
            if ($item === null) {
                continue;
            }

            // Case 1: short_class_name(SomeClass::class) => ...
            if ($item->key instanceof FuncCall) {
                $funcCall = $item->key;

                if (!$this->isShortClassNameCall($funcCall)) {
                    continue;
                }

                $className = $this->extractClassNameFromArg($funcCall);
                if ($className === null) {
                    continue;
                }

                if (!$this->classHasViewKeyConst($className)) {
                    continue;
                }

                if ($this->mode !== 'auto') {
                    $this->addMessageComment($item->key);
                    $hasChanged = true;
                    continue;
                }

                $item->key = new ClassConstFetch(
                    new Name\FullyQualified($className),
                    self::VIEW_KEY_CONST,
                );
                $hasChanged = true;
                continue;
            }

            // Case 2: 'string_key' => SomeClass::from([...])
            if ($item->key instanceof String_ && $item->value instanceof StaticCall) {
                $className = $this->extractClassNameFromStaticCall($item->value);
                if ($className === null) {
                    continue;
                }

                if (!$this->classHasViewKeyConst($className)) {
                    continue;
                }

                if ($this->mode !== 'auto') {
                    $this->addMessageComment($item->key);
                    $hasChanged = true;
                    continue;
                }

                $item->key = new ClassConstFetch(
                    new Name\FullyQualified($className),
                    self::VIEW_KEY_CONST,
                );
                $hasChanged = true;
            }
        }

        if (!$hasChanged) {
            return null;
        }

        return $node;
    }

    private function isShortClassNameCall(FuncCall $funcCall): bool
    {
        $name = $this->getName($funcCall);
        if ($name === null) {
            return false;
        }

        // Match fully-qualified or unqualified form
        if ($name === $this->shortClassNameFunction) {
            return true;
        }

        $short = substr(strrchr($this->shortClassNameFunction, '\\') ?: "\\{$this->shortClassNameFunction}", 1);

        return $name === $short;
    }

    private function extractClassNameFromArg(FuncCall $funcCall): ?string
    {
        if ($funcCall->args === []) {
            return null;
        }

        $firstArg = $funcCall->args[0];
        if (!$firstArg instanceof Node\Arg) {
            return null;
        }

        if (!$firstArg->value instanceof ClassConstFetch) {
            return null;
        }

        $classConstFetch = $firstArg->value;

        if (!$classConstFetch->name instanceof Node\Identifier) {
            return null;
        }

        if ($classConstFetch->name->toString() !== 'class') {
            return null;
        }

        return $this->getName($classConstFetch->class);
    }

    private function extractClassNameFromStaticCall(StaticCall $staticCall): ?string
    {
        return $this->getName($staticCall->class);
    }

    private function classHasViewKeyConst(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->hasConstant(self::VIEW_KEY_CONST);
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
