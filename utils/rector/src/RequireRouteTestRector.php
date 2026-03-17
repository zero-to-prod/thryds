<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Flags Route enum cases that have no corresponding test referencing them.
 *
 * A route case is considered "tested" when any test file contains a reference
 * to the enum case, e.g. `Route::home` or `TestRoute::about`.
 */
final class RequireRouteTestRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = '';

    private string $testDir = '';

    private string $mode = 'warn';

    private string $message = "TODO: [RequireRouteTestRector] Route case '%s' has no corresponding test. Add a test that exercises this route.";

    /** @var string[]|null null means not yet built */
    private ?array $testedCases = null;

    public function configure(array $configuration): void
    {
        $this->enumClass = $configuration['enumClass'] ?? '';
        $this->testDir = $configuration['testDir'] ?? '';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [RequireRouteTestRector] Route case '%s' has no corresponding test. Add a test that exercises this route.";
        $this->testedCases = null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment on Route enum cases that have no test referencing them',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
enum Route: string
{
    case home = '/';
    case about = '/about';
    case untested = '/untested';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
enum Route: string
{
    case home = '/';
    case about = '/about';
    // TODO: [RequireRouteTestRector] Route case 'untested' has no corresponding test. Add a test that exercises this route.
    case untested = '/untested';
}
CODE_SAMPLE,
                    [
                        'enumClass' => 'App\\Routes\\Route',
                        'testDir' => __DIR__ . '/tests',
                        'mode' => 'warn',
                        'message' => "TODO: [RequireRouteTestRector] Route case '%s' has no corresponding test. Add a test that exercises this route.",
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
        return [Enum_::class];
    }

    /**
     * @param Enum_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        $fqcn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($this->getName($node) ?? '');

        if ($this->enumClass !== '' && $fqcn !== $this->enumClass) {
            return null;
        }

        if ($this->testedCases === null) {
            $this->testedCases = $this->buildTestedCasesFromDir();
        }

        $shortEnumName = $this->resolveShortClassName($this->enumClass !== '' ? $this->enumClass : $fqcn);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        $changed = false;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof EnumCase) {
                continue;
            }

            $caseName = $this->getName($stmt);
            if ($caseName === null) {
                continue;
            }

            $isTested = in_array($caseName, $this->testedCases, true);

            if ($isTested) {
                $comments = $stmt->getComments();
                $filtered = array_values(array_filter(
                    $comments,
                    static fn(Comment $c): bool => !str_contains($c->getText(), $marker)
                ));

                if (count($filtered) !== count($comments)) {
                    $stmt->setAttribute('comments', $filtered);
                    $changed = true;
                }

                continue;
            }

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

            $todoText = str_contains($this->message, '%s')
                ? sprintf($this->message, $caseName)
                : $this->message;

            $todoComment = new Comment('// ' . $todoText);
            $existingComments = $stmt->getComments();
            array_unshift($existingComments, $todoComment);
            $stmt->setAttribute('comments', $existingComments);

            $changed = true;
        }

        return $changed ? $node : null;
    }

    /** @return string[] */
    private function buildTestedCasesFromDir(): array
    {
        if ($this->testDir === '' || !is_dir($this->testDir)) {
            return [];
        }

        $shortEnumName = $this->resolveShortClassName($this->enumClass);
        $cases = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->testDir, \FilesystemIterator::SKIP_DOTS)
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

            $found = $this->extractTestedCasesFromContents($contents, $shortEnumName);
            foreach ($found as $caseName) {
                $cases[] = $caseName;
            }
        }

        return array_unique($cases);
    }

    /** @return string[] */
    private function extractTestedCasesFromContents(string $contents, string $shortEnumName): array
    {
        $cases = [];

        // Match: Route::caseName (short class name reference — with or without ->value / ->... suffix)
        if ($shortEnumName !== '') {
            $shortPattern = '/\b' . preg_quote($shortEnumName, '/') . '::(\w+)\b/';
            if (preg_match_all($shortPattern, $contents, $matches)) {
                foreach ($matches[1] as $caseName) {
                    // Exclude static method calls like Route::from() — only include lowercase_with_underscores names
                    if (ctype_lower($caseName[0]) || $caseName[0] === '_') {
                        $cases[] = $caseName;
                    }
                }
            }
        }

        // Match: Fully\Qualified\Route::caseName
        if ($this->enumClass !== '') {
            $fqPattern = '/' . preg_quote($this->enumClass, '/') . '::(\w+)\b/';
            if (preg_match_all($fqPattern, $contents, $matches)) {
                foreach ($matches[1] as $caseName) {
                    if (ctype_lower($caseName[0]) || $caseName[0] === '_') {
                        $cases[] = $caseName;
                    }
                }
            }
        }

        return $cases;
    }

    private function resolveShortClassName(string $fqcn): string
    {
        if ($fqcn === '') {
            return '';
        }

        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
