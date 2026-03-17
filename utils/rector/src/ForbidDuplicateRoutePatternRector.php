<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidDuplicateRoutePatternRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $classSuffix = 'Route';

    /** @var string[] */
    private array $constNames = ['pattern'];

    private string $scanDir = '';

    private string $mode = 'warn';

    private string $message = "TODO: Duplicate route pattern '%s' — already defined in %s::%s";

    /**
     * Map of pattern value => first class name that defines it.
     * Built from scanDir on configure(), then extended at runtime.
     *
     * @var array<string, string>
     */
    private array $patternMap = [];

    public function configure(array $configuration): void
    {
        $this->classSuffix = $configuration['classSuffix'] ?? 'Route';
        $this->constNames = $configuration['constNames'] ?? ['pattern'];
        $this->scanDir = $configuration['scanDir'] ?? '';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: Duplicate route pattern '%s' — already defined in %s::%s";

        $this->patternMap = [];

        if ($this->scanDir !== '' && is_dir($this->scanDir)) {
            $this->buildPatternMapFromDir($this->scanDir);
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Forbid the same URL pattern string from appearing as a constant value in more than one Route class',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class LandingRoute
{
    public const string pattern = '/';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class LandingRoute
{
    // TODO: Duplicate route pattern '/' — already defined in HomeRoute::pattern
    public const string pattern = '/';
}
CODE_SAMPLE,
                    [
                        'classSuffix' => 'Route',
                        'constNames' => ['pattern'],
                        'scanDir' => __DIR__ . '/src/Routes',
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
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        $shortName = $this->getName($node);
        if ($shortName === null) {
            return null;
        }

        if (!str_ends_with($shortName, $this->classSuffix)) {
            return null;
        }

        $className = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : $shortName;

        $changed = false;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                $constName = $this->getName($const);
                if ($constName === null) {
                    continue;
                }

                if (!in_array($constName, $this->constNames, true)) {
                    continue;
                }

                if (!$const->value instanceof String_) {
                    continue;
                }

                $patternValue = $const->value->value;

                if (!isset($this->patternMap[$patternValue])) {
                    $this->patternMap[$patternValue] = $className;
                    continue;
                }

                $firstClass = $this->patternMap[$patternValue];

                if ($firstClass === $className) {
                    continue;
                }

                $marker = strstr($this->message, '%', true) ?: $this->message;
                $alreadyAnnotated = false;
                foreach ($stmt->getComments() as $comment) {
                    if (str_contains($comment->getText(), $marker)) {
                        $alreadyAnnotated = true;
                        break;
                    }
                }

                if ($alreadyAnnotated) {
                    continue;
                }

                $todoText = '// ' . sprintf($this->message, $patternValue, $firstClass, $constName);

                $existingComments = $stmt->getComments();
                array_unshift($existingComments, new Comment($todoText));
                $stmt->setAttribute('comments', $existingComments);

                $changed = true;
            }
        }

        return $changed ? $node : null;
    }

    private function buildPatternMapFromDir(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents === false) {
                continue;
            }

            $this->extractPatternsFromContents($contents);
        }
    }

    private function extractPatternsFromContents(string $contents): void
    {
        if (!preg_match('/\bclass\s+(\w+' . preg_quote($this->classSuffix, '/') . ')\b/', $contents, $classMatch)) {
            return;
        }

        $shortName = $classMatch[1];

        $namespace = '';
        if (preg_match('/\bnamespace\s+([\w\\\\]+)\s*;/', $contents, $nsMatch)) {
            $namespace = $nsMatch[1];
        }

        $className = $namespace !== '' ? $namespace . '\\' . $shortName : $shortName;

        foreach ($this->constNames as $constName) {
            $pattern = '/\bconst\b[^;]*\b' . preg_quote($constName, '/') . '\s*=\s*[\'"]([^\'"]*)[\'"]\\s*;/';
            if (preg_match($pattern, $contents, $constMatch)) {
                $value = $constMatch[1];
                if (!isset($this->patternMap[$value])) {
                    $this->patternMap[$value] = $className;
                }
            }
        }
    }
}
