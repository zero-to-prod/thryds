<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ExtractRoutePatternToRouteClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $methods = [];

    private int $argPosition = 1;

    private string $namespace = '';

    private string $outputDir = '';

    public function configure(array $configuration): void
    {
        $this->methods = $configuration['methods'] ?? [];
        $this->argPosition = $configuration['argPosition'] ?? 1;
        $this->namespace = $configuration['namespace'] ?? '';
        $this->outputDir = $configuration['outputDir'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Extract string route patterns into Route class constants',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Router->map('GET', '/posts/{post}', $handler);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$Router->map('GET', \ZeroToProd\Thryds\Routes\PostsRoute::pattern, $handler);
CODE_SAMPLE,
                    [
                        'methods' => ['map'],
                        'argPosition' => 1,
                        'namespace' => 'ZeroToProd\\Thryds\\Routes',
                        'outputDir' => __DIR__ . '/src/Routes',
                    ]
                ),
            ]
        );
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
        if ($this->methods === [] || $this->namespace === '' || $this->outputDir === '') {
            return null;
        }

        if (!$this->isNames($node->name, $this->methods)) {
            return null;
        }

        $args = $node->args;
        if (!isset($args[$this->argPosition])) {
            return null;
        }

        $arg = $args[$this->argPosition];
        if (!$arg instanceof Node\Arg) {
            return null;
        }

        if (!$arg->value instanceof String_) {
            return null;
        }

        $pattern = $arg->value->value;
        $className = $this->deriveClassName($pattern);
        $fqcn = $this->namespace . '\\' . $className;

        $this->generateRouteClassFile($className, $fqcn, $pattern);

        $arg->value = new ClassConstFetch(
            new FullyQualified($fqcn),
            new Identifier('pattern')
        );

        return $node;
    }

    private function deriveClassName(string $pattern): string
    {
        $trimmed = ltrim($pattern, '/');

        if ($trimmed === '') {
            return 'HomeRoute';
        }

        $segments = explode('/', $trimmed);

        $segment = 'home';
        foreach ($segments as $s) {
            if ($s !== '' && !str_contains($s, '{')) {
                $segment = $s;
                break;
            }
        }

        return ucfirst($segment) . 'Route';
    }

    /**
     * @return string[]
     */
    private function extractParams(string $pattern): array
    {
        preg_match_all('/\{(\w+)\}/', $pattern, $matches);

        return $matches[1] ?? [];
    }

    private function generateRouteClassFile(string $className, string $fqcn, string $pattern): void
    {
        if (!is_dir($this->outputDir)) {
            return;
        }

        $filePath = rtrim($this->outputDir, '/') . '/' . $className . '.php';

        if (file_exists($filePath)) {
            return;
        }

        $params = $this->extractParams($pattern);

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'namespace ' . $this->namespace . ';';
        $lines[] = '';
        $lines[] = 'readonly class ' . $className;
        $lines[] = '{';
        $lines[] = "    public const string pattern = '" . $pattern . "';";

        foreach ($params as $param) {
            $lines[] = "    public const string {$param} = '{$param}';";
        }

        $lines[] = '}';
        $lines[] = '';

        file_put_contents($filePath, implode("\n", $lines));
    }
}
