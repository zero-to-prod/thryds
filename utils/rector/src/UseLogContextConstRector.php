<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class UseLogContextConstRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string */
    private string $logClass = '';

    /** @var string[] */
    private array $keys = [];

    /** @var string[] */
    private array $methods = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->logClass = $configuration['logClass'];
        $this->keys = $configuration['keys'];
        $this->methods = $configuration['methods'] ?? ['debug', 'info', 'warn', 'error'];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace string context array keys in Log method calls with class constants',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
Log::error('fail', [
    'exception' => $e::class,
    'file' => $e->getFile(),
    'line' => $e->getLine(),
]);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
Log::error('fail', [
    Log::exception => $e::class,
    Log::file => $e->getFile(),
    Log::line => $e->getLine(),
]);
CODE_SAMPLE,
                    [
                        'logClass' => 'App\Log',
                        'keys' => ['exception', 'file', 'line'],
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }

    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        $className = $this->getName($node->class);
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

        $methodName = $this->getName($node->name);
        if ($methodName === null || !in_array($methodName, $this->methods, true)) {
            return null;
        }

        $contextArg = $this->findContextArg($node);
        if ($contextArg === null || !$contextArg->value instanceof Array_) {
            return null;
        }

        $changed = false;

        foreach ($contextArg->value->items as $item) {
            if ($item === null || !$item->key instanceof String_) {
                continue;
            }

            if (!in_array($item->key->value, $this->keys, true)) {
                continue;
            }

            $constName = $item->key->value;

            $this->addConstantToClassFile($constName);

            $item->key = new ClassConstFetch(
                new FullyQualified($this->logClass),
                new Identifier($constName)
            );
            $changed = true;
        }

        if (!$changed) {
            return null;
        }

        return $node;
    }

    private function findContextArg(StaticCall $node): ?Arg
    {
        foreach ($node->args as $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            if ($arg->value instanceof Array_) {
                return $arg;
            }
        }

        return null;
    }

    private function addConstantToClassFile(string $constName): void
    {
        if (!$this->reflectionProvider->hasClass($this->logClass)) {
            return;
        }

        $classReflection = $this->reflectionProvider->getClass($this->logClass);

        if ($classReflection->hasConstant($constName)) {
            return;
        }

        $fileName = $classReflection->getFileName();
        if ($fileName === null || $fileName === false) {
            return;
        }

        $content = file_get_contents($fileName);
        if ($content === false) {
            return;
        }

        $constLine = "    public const string {$constName} = '{$constName}';";

        if (str_contains($content, $constLine)) {
            return;
        }

        $lastBrace = strrpos($content, '}');
        if ($lastBrace === false) {
            return;
        }

        $content = substr($content, 0, $lastBrace) . $constLine . "\n" . substr($content, $lastBrace);

        file_put_contents($fileName, $content);
    }
}
