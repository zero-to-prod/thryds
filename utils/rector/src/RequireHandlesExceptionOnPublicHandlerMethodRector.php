<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireHandlesExceptionOnPublicHandlerMethodRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [RequireHandlesExceptionOnPublicHandlerMethodRector] Public method %s::%s accepts a Throwable subtype but is missing #[HandlesException] — it will never be dispatched. See: utils/rector/docs/RequireHandlesExceptionOnPublicHandlerMethodRector.md';

    private string $handlerAttributeClass = '';

    private string $throwableClass = 'Throwable';

    /** @var string[] */
    private array $excludeMethods = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->handlerAttributeClass = $configuration['handlerAttributeClass'] ?? '';
        $this->throwableClass = $configuration['throwableClass'] ?? 'Throwable';
        $this->excludeMethods = $configuration['excludeMethods'] ?? [];
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->handlerAttributeClass === '') {
            return null;
        }

        if (!$this->classUsesHandlerAttribute($node)) {
            return null;
        }

        $className = (string) $node->name;
        $changed = false;

        foreach ($node->getMethods() as $Method) {
            if (!$Method->isPublic()) {
                continue;
            }

            $methodName = (string) $Method->name;

            if (in_array($methodName, $this->excludeMethods, true)) {
                continue;
            }

            if ($this->hasAttribute($Method)) {
                continue;
            }

            if (!$this->acceptsThrowableSubtype($Method)) {
                continue;
            }

            $changed = $this->addTodoComment($Method, $className, $methodName) || $changed;
        }

        return $changed ? $node : null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag public methods on exception handler classes that accept a Throwable subtype but lack #[HandlesException]',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[\App\HandlesException(\RuntimeException::class)]
class ExceptionHandler
{
    public function handleRuntime(\RuntimeException $e): void {}

    public function handleValidation(\DomainException $e): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
#[\App\HandlesException(\RuntimeException::class)]
class ExceptionHandler
{
    public function handleRuntime(\RuntimeException $e): void {}

    // TODO: [RequireHandlesExceptionOnPublicHandlerMethodRector] Public method ExceptionHandler::handleValidation accepts a Throwable subtype but is missing #[HandlesException] — it will never be dispatched.
    public function handleValidation(\DomainException $e): void {}
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'handlerAttributeClass' => 'App\\HandlesException',
                        'excludeMethods' => ['handle'],
                    ],
                ),
            ]
        );
    }

    private function classUsesHandlerAttribute(Class_ $node): bool
    {
        foreach ($node->getMethods() as $Method) {
            if ($this->hasAttribute($Method)) {
                return true;
            }
        }

        return false;
    }

    private function hasAttribute(ClassMethod $Method): bool
    {
        $parts = explode('\\', $this->handlerAttributeClass);
        $short_name = end($parts);

        foreach ($Method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $this->handlerAttributeClass || $name === $short_name) {
                    return true;
                }
            }
        }

        return false;
    }

    private function acceptsThrowableSubtype(ClassMethod $Method): bool
    {
        foreach ($Method->params as $Param) {
            if ($Param->type === null) {
                continue;
            }

            $type_name = $this->getName($Param->type);
            if ($type_name === null) {
                continue;
            }

            if ($type_name === $this->throwableClass || $type_name === 'Throwable') {
                return true;
            }

            if (!$this->reflectionProvider->hasClass($type_name)) {
                continue;
            }

            $ClassReflection = $this->reflectionProvider->getClass($type_name);
            if ($ClassReflection->implementsInterface($this->throwableClass) || $ClassReflection->isSubclassOf($this->throwableClass)) {
                return true;
            }
        }

        return false;
    }

    private function addTodoComment(ClassMethod $Method, string $class_name, string $method_name): bool
    {
        $todo_text = sprintf($this->message, $class_name, $method_name);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($Method->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return false;
            }
        }

        $comments = $Method->getComments();
        array_unshift($comments, new Comment('// ' . $todo_text));
        $Method->setAttribute('comments', $comments);

        return true;
    }
}
