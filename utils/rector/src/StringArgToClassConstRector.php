<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class StringArgToClassConstRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var array<int, array{class: string, methodName: string, paramName: string}> */
    private array $mappings = [];

    private string $mode = 'auto';

    private string $message = '';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
        $this->mappings = $configuration['mappings'] ?? $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace string literal named arguments with class constant fetches, creating missing constants on the target class', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
$Blade->make(view: 'error', data: []);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
$Blade->make(view: \App\View::error, data: []);
CODE_SAMPLE,
                [
                    [
                        'class' => 'App\View',
                        'methodName' => 'make',
                        'paramName' => 'view',
                    ],
                ]
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        $changed = false;

        foreach ($this->mappings as $mapping) {
            if (! $this->isName($node->name, $mapping['methodName'])) {
                continue;
            }

            foreach ($node->args as $arg) {
                if (! $arg instanceof Arg) {
                    continue;
                }

                if ($arg->name === null) {
                    continue;
                }

                if ($arg->name->name !== $mapping['paramName']) {
                    continue;
                }

                if (! $arg->value instanceof String_) {
                    continue;
                }

                $stringValue = $arg->value->value;
                $constName = str_replace('.', '_', $stringValue);

                if ($this->mode !== 'auto') {
                    $this->addMessageComment($node);
                    $changed = true;
                    continue;
                }

                $this->addConstantToClassFile($mapping['class'], $constName, $stringValue);

                $arg->value = new ClassConstFetch(
                    new FullyQualified($mapping['class']),
                    new Identifier($constName)
                );
                $changed = true;
            }
        }

        if (! $changed) {
            return null;
        }

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

    private function addConstantToClassFile(string $className, string $constName, string $value): void
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

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

        $constLine = "    public const string {$constName} = '{$value}';";

        if (str_contains($content, $constLine)) {
            return;
        }

        // Insert before the last closing brace of the class
        $lastBrace = strrpos($content, '}');
        if ($lastBrace === false) {
            return;
        }

        $content = substr($content, 0, $lastBrace) . $constLine . "\n" . substr($content, $lastBrace);

        file_put_contents($fileName, $content);
    }
}
