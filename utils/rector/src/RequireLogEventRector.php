<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireLogEventRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $logClass = '';

    private string $mode = 'warn';

    private string $message = 'TODO: Add a durable event identifier — `%s::%s => %s::<event_label>`';

    private string $eventKey = 'event';

    /** @var string[] */
    private array $methods = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->logClass = $configuration['logClass'];
        $this->eventKey = $configuration['eventKey'] ?? 'event';
        $this->methods = $configuration['methods'] ?? ['debug', 'info', 'warn', 'error'];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: Add a durable event identifier — `%s::%s => %s::<event_label>`';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment when Log calls are missing an event key with a class constant label',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
Log::error('fail', [
    'exception' => $e::class,
]);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::error('fail', [
    'exception' => $e::class,
]);
CODE_SAMPLE,
                    [
                        'logClass' => 'App\Log',
                        'eventKey' => 'event',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Expression::class];
    }

    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if (!$node->expr instanceof StaticCall) {
            return null;
        }

        $staticCall = $node->expr;

        $className = $this->getName($staticCall->class);
        if ($className === null) {
            return null;
        }

        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if ($classReflection->getName() !== $this->logClass) {
            return null;
        }

        $methodName = $this->getName($staticCall->name);
        if ($methodName === null || !in_array($methodName, $this->methods, true)) {
            return null;
        }

        if ($this->hasEventKey($staticCall)) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $shortName = $this->getShortClassName($this->logClass);
        $todoComment = new Comment('// ' . sprintf($this->message, $shortName, $this->eventKey, $shortName));

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    private function hasEventKey(StaticCall $node): bool
    {
        foreach ($node->args as $arg) {
            if (!$arg instanceof Arg || !$arg->value instanceof Array_) {
                continue;
            }

            foreach ($arg->value->items as $item) {
                if ($item === null || !$item->key instanceof ClassConstFetch) {
                    continue;
                }

                $constName = $this->getName($item->key->name);
                if ($constName === $this->eventKey) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
