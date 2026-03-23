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

final class RequireAllRouteCasesRegisteredRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = 'ZeroToProd\\Thryds\\Routes\\RouteList';

    /** @var string[] */
    private array $methods = ['map'];

    private int $argPosition = 1;

    private string $mode = 'warn';

    private string $message = "TODO: [RequireAllRouteCasesRegisteredRector] Route case '%s' is defined but never registered in any router map() call.";

    private string $scanDir = '';

    /** @var string[]|null null means not yet built */
    private ?array $registeredCases = null;

    private bool $hasDynamicRegistration = false;

    public function configure(array $configuration): void
    {
        $this->enumClass = $configuration['enumClass'] ?? 'ZeroToProd\\Thryds\\Routes\\RouteList';
        $this->methods = $configuration['methods'] ?? ['map'];
        $this->argPosition = $configuration['argPosition'] ?? 1;
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [RequireAllRouteCasesRegisteredRector] Route case '%s' is defined but never registered in any router map() call.";
        $this->scanDir = $configuration['scanDir'] ?? '';
        $this->registeredCases = null;
        $this->hasDynamicRegistration = false;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment on Route enum cases that are defined but never registered in a router map() call',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
enum Route: string
{
    case home = '/';
    case about = '/about';
    case unregistered = '/unregistered';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
enum Route: string
{
    case home = '/';
    case about = '/about';
    // TODO: [RequireAllRouteCasesRegisteredRector] Route case 'unregistered' is defined but never registered in any router map() call.
    case unregistered = '/unregistered';
}
CODE_SAMPLE,
                    [
                        'enumClass' => 'ZeroToProd\\Thryds\\Routes\\RouteList',
                        'methods' => ['map'],
                        'argPosition' => 1,
                        'scanDir' => __DIR__ . '/src/Routes',
                        'mode' => 'warn',
                        'message' => "TODO: [RequireAllRouteCasesRegisteredRector] Route case '%s' is defined but never registered in any router map() call.",
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
        $fqcn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($this->getName($node) ?? '');

        if ($fqcn !== $this->enumClass) {
            return null;
        }

        if ($this->registeredCases === null) {
            $this->registeredCases = $this->buildRegisteredCasesFromDir();
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;

        if ($this->hasDynamicRegistration) {
            $changed = false;

            foreach ($node->stmts as $stmt) {
                if (!$stmt instanceof EnumCase) {
                    continue;
                }

                $comments = $stmt->getComments();
                $filtered = array_values(array_filter(
                    $comments,
                    static fn(Comment $c): bool => !str_contains($c->getText(), $marker)
                ));

                if (count($filtered) !== count($comments)) {
                    $stmt->setAttribute('comments', $filtered);
                    $changed = true;
                }
            }

            return $changed ? $node : null;
        }

        $changed = false;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof EnumCase) {
                continue;
            }

            $caseName = $this->getName($stmt);
            if ($caseName === null) {
                continue;
            }

            $isRegistered = in_array($caseName, $this->registeredCases, true);

            if ($isRegistered) {
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
    private function buildRegisteredCasesFromDir(): array
    {
        if ($this->scanDir === '' || !is_dir($this->scanDir)) {
            return [];
        }

        $cases = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->scanDir, \FilesystemIterator::SKIP_DOTS)
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

            if ($this->hasDynamicEnumCasesLoop($contents)) {
                $this->hasDynamicRegistration = true;
            }

            $found = $this->extractRegisteredCasesFromContents($contents);
            foreach ($found as $caseName) {
                $cases[] = $caseName;
            }
        }

        return array_unique($cases);
    }

    private function hasDynamicEnumCasesLoop(string $contents): bool
    {
        $shortName = $this->resolveShortEnumClassName();
        $fqEscaped = preg_quote($this->enumClass, '/');
        $shortEscaped = preg_quote($shortName, '/');

        $pattern = '/foreach\s*\(\s*(?:' . $shortEscaped . '|' . $fqEscaped . ')::cases\s*\(\s*\)\s+as\b/';

        return (bool) preg_match($pattern, $contents);
    }

    /** @return string[] */
    private function extractRegisteredCasesFromContents(string $contents): array
    {
        $cases = [];
        $shortEnumClass = $this->resolveShortEnumClassName();

        foreach ($this->methods as $method) {
            $methodPattern = '/\->' . preg_quote($method, '/') . '\s*\(/';
            $offset = 0;

            while (preg_match($methodPattern, $contents, $methodMatch, PREG_OFFSET_CAPTURE, $offset)) {
                $parenStart = $methodMatch[0][1] + strlen($methodMatch[0][0]) - 1;
                $argsString = $this->extractBalancedArgs($contents, $parenStart);

                if ($argsString === null) {
                    $offset = $methodMatch[0][1] + 1;
                    continue;
                }

                $caseName = $this->extractCaseNameFromArgs($argsString, $shortEnumClass);
                if ($caseName !== null) {
                    $cases[] = $caseName;
                }

                $offset = $methodMatch[0][1] + 1;
            }
        }

        return $cases;
    }

    private function resolveShortEnumClassName(): string
    {
        $parts = explode('\\', $this->enumClass);

        return end($parts);
    }

    private function extractBalancedArgs(string $contents, int $parenStart): ?string
    {
        if (!isset($contents[$parenStart]) || $contents[$parenStart] !== '(') {
            return null;
        }

        $depth = 0;
        $length = strlen($contents);

        for ($i = $parenStart; $i < $length; $i++) {
            $char = $contents[$i];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    return substr($contents, $parenStart + 1, $i - $parenStart - 1);
                }
            }
        }

        return null;
    }

    private function extractCaseNameFromArgs(string $argsString, string $shortEnumClass): ?string
    {
        $args = $this->splitTopLevelArgs($argsString);

        if (!isset($args[$this->argPosition])) {
            return null;
        }

        $patternArg = trim($args[$this->argPosition]);

        // Match: Route::caseName->value (short class name, as imported via use statement)
        $shortPattern = '/\b' . preg_quote($shortEnumClass, '/') . '::(\w+)\s*->\s*value\b/';
        if (preg_match($shortPattern, $patternArg, $match)) {
            return $match[1];
        }

        // Match: Fully\Qualified\Route::caseName->value
        $fqPattern = '/' . preg_quote($this->enumClass, '/') . '::(\w+)\s*->\s*value\b/';
        if (preg_match($fqPattern, $patternArg, $match)) {
            return $match[1];
        }

        return null;
    }

    /** @return string[] */
    private function splitTopLevelArgs(string $argsString): array
    {
        $args = [];
        $current = '';
        $depth = 0;

        for ($i = 0, $len = strlen($argsString); $i < $len; $i++) {
            $char = $argsString[$i];

            if ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
                $current .= $char;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $args[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $args[] = $current;
        }

        return $args;
    }
}
