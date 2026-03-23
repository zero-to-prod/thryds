<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ValidateChecklistPathsRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = "TODO: [ValidateChecklistPathsRector] %s references '%s' in %s, but this file does not exist. Update the checklist.";

    private string $projectDir = '';

    /** @var list<array{attributeClass: string, paramName: string}> */
    private array $attributes = [];

    /**
     * File path pattern: matches filenames with extensions, or paths containing slashes.
     * Excludes patterns like {case} (template placeholders) and Class::method() references.
     */
    private const string PATH_PATTERN = '/(?:^|\s)([a-zA-Z0-9_.\-\/]+\.(?:php|yaml|yml|json|js|ts|env|example|blade\.php))\b/';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->projectDir = $configuration['projectDir'] ?? '';
        $this->attributes = $configuration['attributes'] ?? [];
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
        if ($this->projectDir === '' || $this->attributes === []) {
            return null;
        }

        $className = (string) $node->name;
        if ($className === '') {
            return null;
        }

        $violations = [];

        foreach ($this->attributes as $attrConfig) {
            $checklist = $this->extractChecklistString($node, $attrConfig['attributeClass'], $attrConfig['paramName']);
            if ($checklist === null) {
                continue;
            }

            foreach ($this->extractPaths($checklist) as $path) {
                if (!$this->pathExists($path)) {
                    $violations[] = [$path, $attrConfig['paramName']];
                }
            }
        }

        if ($violations === []) {
            return null;
        }

        return $this->addTodoComments($node, $className, $violations);
    }

    private function extractChecklistString(Class_|Enum_ $node, string $attributeClass, string $paramName): ?string
    {
        $shortName = $this->shortName($attributeClass);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name !== $attributeClass && $name !== $shortName) {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    if ($arg->name !== null && $this->getName($arg->name) === $paramName && $arg->value instanceof String_) {
                        return $arg->value->value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function extractPaths(string $checklist): array
    {
        if (preg_match_all(self::PATH_PATTERN, $checklist, $matches) === 0) {
            return [];
        }

        $paths = [];
        foreach ($matches[1] as $match) {
            // Skip template placeholders like templates/{case}.blade.php
            if (str_contains($match, '{')) {
                continue;
            }

            $paths[] = $match;
        }

        return $paths;
    }

    private function pathExists(string $path): bool
    {
        // Try as-is from project root
        if (file_exists($this->projectDir . '/' . $path)) {
            return true;
        }

        // Try common subdirectories
        foreach (['scripts/', 'framework/', 'src/', 'public/', 'templates/'] as $prefix) {
            if (file_exists($this->projectDir . '/' . $prefix . $path)) {
                return true;
            }
        }

        // Hidden files like .env.example
        if (str_starts_with($path, '.') && file_exists($this->projectDir . '/' . $path)) {
            return true;
        }

        return false;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * @param Class_|Enum_ $node
     * @param list<array{string, string}> $violations Each is [path, paramName].
     */
    private function addTodoComments(Node $node, string $className, array $violations): Node
    {
        $comments = $node->getComments();
        $changed = false;

        foreach ($violations as [$path, $paramName]) {
            $todoText = sprintf($this->message, $className, $path, $paramName);

            $alreadyPresent = false;
            foreach ($comments as $comment) {
                if (str_contains($comment->getText(), $path)) {
                    $alreadyPresent = true;
                    break;
                }
            }

            if (!$alreadyPresent) {
                array_unshift($comments, new Comment('// ' . $todoText));
                $changed = true;
            }
        }

        if (!$changed) {
            return $node;
        }

        $node->setAttribute('comments', $comments);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Validate that file paths referenced in addCase/addKey checklist strings actually exist on disk',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[SourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update scripts/nonexistent.php.')]
enum Example: string
{
    case foo = 'foo';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ValidateChecklistPathsRector] Example references 'scripts/nonexistent.php' in addCase, but this file does not exist. Update the checklist.
#[SourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update scripts/nonexistent.php.')]
enum Example: string
{
    case foo = 'foo';
}
CODE_SAMPLE,
                    [
                        'attributes' => [
                            ['attributeClass' => 'App\\Helpers\\SourceOfTruth', 'paramName' => 'addCase'],
                        ],
                        'projectDir' => '/path/to/project',
                        'mode' => 'warn',
                    ],
                ),
            ]
        );
    }
}
