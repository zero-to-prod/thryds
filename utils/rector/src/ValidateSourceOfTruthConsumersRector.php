<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ValidateSourceOfTruthConsumersRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [ValidateSourceOfTruthConsumersRector] %s declares %s as a consumer, but it does not reference %s. Update the consumers list.';

    private string $projectDir = '';

    private string $attributeClass = 'ZeroToProd\\Thryds\\Helpers\\SourceOfTruth';

    /**
     * PSR-4 namespace prefix to directory path map for resolving class FQNs to files.
     * Example: ['App\\' => '/path/to/src/', 'Tests\\' => '/path/to/tests/']
     *
     * @var array<string, string>
     */
    private array $psr4Map = [];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->projectDir = $configuration['projectDir'] ?? '';
        $this->attributeClass = $configuration['attributeClass'] ?? 'ZeroToProd\\Thryds\\Helpers\\SourceOfTruth';
        $this->psr4Map = $configuration['psr4Map'] ?? [];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Validate that classes/enums listed as consumers in #[SourceOfTruth] actually reference the source class.',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[SourceOfTruth(
    for: 'route paths',
    consumers: [SomeConsumer::class],
)]
enum Route: string {}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ValidateSourceOfTruthConsumersRector] Route declares SomeConsumer as a consumer, but it does not reference Route. Update the consumers list.
#[SourceOfTruth(
    for: 'route paths',
    consumers: [SomeConsumer::class],
)]
enum Route: string {}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\SourceOfTruth',
                        'mode' => 'warn',
                        'message' => 'TODO: [ValidateSourceOfTruthConsumersRector] %s declares %s as a consumer, but it does not reference %s. Update the consumers list.',
                        'projectDir' => '/path/to/project',
                        'psr4Map' => ['App\\' => '/path/to/src/'],
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class, Enum_::class];
    }

    /**
     * @param Class_|Enum_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        $className = (string) $node->name;
        if ($className === '') {
            return null;
        }

        $sourceOfTruthAttr = $this->findSourceOfTruthAttribute($node);
        if ($sourceOfTruthAttr === null) {
            return null;
        }

        $consumers = $this->extractConsumers($sourceOfTruthAttr);
        if ($consumers === []) {
            return null;
        }

        $shortName = $this->shortName($className);
        $violations = [];

        foreach ($consumers as $consumer) {
            if ($this->isGlobConsumer($consumer)) {
                if (!$this->globConsumerReferences($consumer, $shortName)) {
                    $violations[] = $consumer;
                }
            } elseif ($this->looksLikeFqn($consumer)) {
                if (!$this->classConsumerReferences($consumer, $className, $shortName)) {
                    $violations[] = $this->shortName($consumer);
                }
            }
        }

        if ($violations === []) {
            return null;
        }

        return $this->addTodoComments($node, $className, $shortName, $violations);
    }

    /**
     * @param Class_|Enum_ $node
     */
    private function findSourceOfTruthAttribute(Node $node): ?\PhpParser\Node\Attribute
    {
        $parts = explode('\\', $this->attributeClass);
        $shortAttrName = end($parts);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $this->attributeClass || $name === $shortAttrName) {
                    return $attr;
                }
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function extractConsumers(\PhpParser\Node\Attribute $attr): array
    {
        $consumers = [];

        foreach ($attr->args as $arg) {
            // Accept the named 'consumers' arg, or the second positional arg
            if ($arg->name !== null && (string) $arg->name !== 'consumers') {
                continue;
            }

            $value = $arg->value;
            if (!$value instanceof \PhpParser\Node\Expr\Array_) {
                continue;
            }

            foreach ($value->items as $item) {
                if ($item === null) {
                    continue;
                }

                $itemValue = $item->value;

                if ($itemValue instanceof String_) {
                    $consumers[] = $itemValue->value;
                } elseif ($itemValue instanceof ClassConstFetch) {
                    $fqn = $this->getName($itemValue->class);
                    if ($fqn !== null) {
                        $consumers[] = $fqn;
                    }
                }
            }

            // Only process the first 'consumers' arg found
            break;
        }

        return $consumers;
    }

    private function isGlobConsumer(string $consumer): bool
    {
        return str_contains($consumer, '*') || (str_contains($consumer, '/') && !str_contains($consumer, '\\'));
    }

    private function looksLikeFqn(string $consumer): bool
    {
        // A FQN contains backslashes and no forward slashes
        return str_contains($consumer, '\\') && !str_contains($consumer, '/');
    }

    private function classConsumerReferences(string $consumer, string $fqn, string $shortName): bool
    {
        $file = $this->resolveClassFile($consumer);

        if ($file === null) {
            // Cannot locate file — assume ok to avoid false positives
            return true;
        }

        return $this->fileReferences($file, $fqn, $shortName);
    }

    private function resolveClassFile(string $fqn): ?string
    {
        // Try psr4Map first
        foreach ($this->psr4Map as $prefix => $dir) {
            if (str_starts_with($fqn, $prefix)) {
                $relative = substr($fqn, strlen($prefix));
                $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        // Fall back to reflection if class is loaded
        if (class_exists($fqn) || interface_exists($fqn)) {
            try {
                $ref = new \ReflectionClass($fqn);
                $file = $ref->getFileName();
                return ($file !== false) ? $file : null;
            } catch (\ReflectionException) {
                return null;
            }
        }

        return null;
    }

    private function globConsumerReferences(string $pattern, string $shortName): bool
    {
        if ($this->projectDir === '') {
            return true;
        }

        $files = glob($this->projectDir . '/' . $pattern);
        if ($files === false || $files === []) {
            return true; // No files matched — cannot verify, assume ok
        }

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }

            if (str_contains($contents, $shortName)) {
                return true;
            }
        }

        return false;
    }

    private function fileReferences(string $file, string $fqn, string $shortName): bool
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            return true;
        }

        return str_contains($contents, $fqn) || str_contains($contents, $shortName);
    }

    private function shortName(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        return end($parts);
    }

    /**
     * @param Class_|Enum_ $node
     * @param string[] $violations
     */
    private function addTodoComments(Node $node, string $className, string $shortName, array $violations): Node
    {
        $existingComments = $node->getComments();
        $newComments = [];

        foreach ($violations as $violatingConsumer) {
            $todoText = sprintf($this->message, $className, $violatingConsumer, $shortName);

            // Check if already present
            $alreadyPresent = false;
            foreach ($existingComments as $comment) {
                if (str_contains($comment->getText(), $violatingConsumer)) {
                    $alreadyPresent = true;
                    break;
                }
            }

            if (!$alreadyPresent) {
                $newComments[] = new Comment('// ' . $todoText);
            }
        }

        if ($newComments === []) {
            return $node;
        }

        $allComments = array_merge($newComments, $existingComments);
        $node->setAttribute('comments', $allComments);

        return $node;
    }
}
