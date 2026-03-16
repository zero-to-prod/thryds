<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class MigrateArrayToDataModelRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var array<int, array{methodName: string, dataParam: string, viewParam: string, viewModelNamespace: string, viewModelDir: string, templateDir: string, dataModelTrait: string}> */
    private array $mappings = [];

    public function configure(array $configuration): void
    {
        $this->mappings = $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Migrate raw string-keyed arrays in Blade make() calls to DataModel ViewModels',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Blade->make(view: View::error, data: [
    'status_code' => $HttpException->getStatusCode(),
    'message' => $HttpException->getMessage(),
])->render()
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$Blade->make(view: View::error, data: [
    class_basename(ErrorViewModel::class) => ErrorViewModel::from([
        ErrorViewModel::status_code => $HttpException->getStatusCode(),
        ErrorViewModel::message => $HttpException->getMessage(),
    ]),
])->render()
CODE_SAMPLE,
                    [
                        [
                            'methodName' => 'make',
                            'dataParam' => 'data',
                            'viewParam' => 'view',
                            'viewModelNamespace' => 'App\\ViewModels',
                            'viewModelDir' => __DIR__ . '/src/ViewModels',
                            'templateDir' => __DIR__ . '/templates',
                            'dataModelTrait' => 'Zerotoprod\\DataModel\\DataModel',
                        ],
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
        foreach ($this->mappings as $mapping) {
            $result = $this->refactorWithMapping($node, $mapping);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param array{methodName: string, dataParam: string, viewParam: string, viewModelNamespace: string, viewModelDir: string, templateDir: string, dataModelTrait: string} $mapping
     */
    private function refactorWithMapping(MethodCall $node, array $mapping): ?MethodCall
    {
        if (! $this->isName($node->name, $mapping['methodName'])) {
            return null;
        }

        $viewArg = $this->findNamedArg($node, $mapping['viewParam']);
        $dataArg = $this->findNamedArg($node, $mapping['dataParam']);

        if ($dataArg === null) {
            return null;
        }

        if (! $dataArg->value instanceof Array_) {
            return null;
        }

        $dataArray = $dataArg->value;
        $stringKeyedItems = $this->collectStringKeyedItems($dataArray);

        if ($stringKeyedItems === []) {
            return null;
        }

        $viewName = $this->resolveViewName($viewArg);
        if ($viewName === null) {
            return null;
        }

        $viewModelClassName = $this->toViewModelClassName($viewName);
        $viewModelFqcn = $mapping['viewModelNamespace'] . '\\' . $viewModelClassName;

        $this->generateViewModelFile($viewModelFqcn, $viewModelClassName, $mapping['viewModelDir'], $mapping['dataModelTrait'], $stringKeyedItems);
        $this->updateBladeTemplate($mapping['templateDir'], $viewName, $viewModelFqcn, $viewModelClassName, $stringKeyedItems);

        $innerItems = [];
        foreach ($stringKeyedItems as $keyString => $valueExpr) {
            $innerItems[] = new ArrayItem(
                $valueExpr,
                new ClassConstFetch(new FullyQualified($viewModelFqcn), new Identifier($keyString))
            );
        }

        $fromCall = new StaticCall(
            new FullyQualified($viewModelFqcn),
            new Identifier('from'),
            [new Arg(new Array_($innerItems))]
        );

        $classBnArg = new Arg(
            new ClassConstFetch(new FullyQualified($viewModelFqcn), new Identifier('class'))
        );
        $keyExpr = new FuncCall(new Name('class_basename'), [$classBnArg]);

        $newItem = new ArrayItem($fromCall, $keyExpr);

        $remainingItems = $this->collectNonStringKeyedItems($dataArray);

        $dataArray->items = array_merge([$newItem], $remainingItems);

        return $node;
    }

    private function findNamedArg(MethodCall $node, string $paramName): ?Arg
    {
        foreach ($node->args as $arg) {
            if (! $arg instanceof Arg) {
                continue;
            }

            if ($arg->name === null) {
                continue;
            }

            if ($arg->name->name === $paramName) {
                return $arg;
            }
        }

        return null;
    }

    private function resolveViewName(?Arg $viewArg): ?string
    {
        if ($viewArg === null) {
            return null;
        }

        if ($viewArg->value instanceof ClassConstFetch) {
            $constName = $viewArg->value->name;
            if ($constName instanceof Identifier) {
                return $constName->name;
            }

            return null;
        }

        if ($viewArg->value instanceof String_) {
            return $viewArg->value->value;
        }

        return null;
    }

    private function toViewModelClassName(string $viewName): string
    {
        $parts = preg_split('/[._]/', $viewName) ?: [$viewName];
        $pascal = implode('', array_map('ucfirst', $parts));

        return $pascal . 'ViewModel';
    }

    /**
     * @return array<string, Node\Expr>
     */
    private function collectStringKeyedItems(Array_ $array): array
    {
        $result = [];
        foreach ($array->items as $item) {
            if ($item === null) {
                continue;
            }

            if (! $item->key instanceof String_) {
                continue;
            }

            $result[$item->key->value] = $item->value;
        }

        return $result;
    }

    /**
     * @return ArrayItem[]
     */
    private function collectNonStringKeyedItems(Array_ $array): array
    {
        $result = [];
        foreach ($array->items as $item) {
            if ($item === null) {
                continue;
            }

            if ($item->key instanceof String_) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array<string, Node\Expr> $properties
     */
    private function generateViewModelFile(
        string $fqcn,
        string $className,
        string $viewModelDir,
        string $dataModelTrait,
        array $properties
    ): void {
        if (! is_dir($viewModelDir)) {
            return;
        }

        $filePath = rtrim($viewModelDir, '/') . '/' . $className . '.php';

        if (file_exists($filePath)) {
            return;
        }

        $namespaceParts = explode('\\', $fqcn);
        array_pop($namespaceParts);
        $namespace = implode('\\', $namespaceParts);

        $traitParts = explode('\\', $dataModelTrait);
        $traitShort = array_pop($traitParts);
        $traitNamespace = implode('\\', $traitParts);

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'namespace ' . $namespace . ';';
        $lines[] = '';
        $lines[] = 'use ' . $traitNamespace . '\\' . $traitShort . ';';
        $lines[] = '';
        $lines[] = 'class ' . $className;
        $lines[] = '{';
        $lines[] = '    use ' . $traitShort . ';';

        foreach ($properties as $propName => $_expr) {
            $lines[] = '';
            $lines[] = '    /** @see $' . $propName . ' */';
            $lines[] = '    public const string ' . $propName . ' = \'' . $propName . '\';';
            $lines[] = '    public string $' . $propName . ';';
        }

        $lines[] = '}';
        $lines[] = '';

        file_put_contents($filePath, implode("\n", $lines));
    }

    /**
     * @param array<string, Node\Expr> $properties
     */
    private function updateBladeTemplate(
        string $templateDir,
        string $viewName,
        string $viewModelFqcn,
        string $viewModelClassName,
        array $properties
    ): void {
        $templatePath = $this->resolveTemplatePath($templateDir, $viewName);

        if ($templatePath === null || ! file_exists($templatePath)) {
            return;
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            return;
        }

        $varName = '$' . $viewModelClassName;
        $phpBlock = '@php' . "\n"
            . 'use ' . $viewModelFqcn . ';' . "\n"
            . '/** @var ' . $viewModelClassName . ' ' . $varName . ' */' . "\n"
            . '        @endphp' . "\n";

        if (! str_contains($content, 'use ' . $viewModelFqcn . ';')) {
            $content = $phpBlock . $content;
        }

        foreach ($properties as $propName => $_expr) {
            $content = str_replace(
                '{{ $' . $propName . ' }}',
                '{{ ' . $varName . '->' . $propName . ' }}',
                $content
            );
            $content = preg_replace(
                '/(\@section\([^)]*)\$' . preg_quote($propName, '/') . '/',
                '${1}\\$' . substr($varName, 1) . '->' . $propName,
                $content
            ) ?? $content;
        }

        file_put_contents($templatePath, $content);
    }

    private function resolveTemplatePath(string $templateDir, string $viewName): ?string
    {
        $relativePath = str_replace('.', '/', $viewName) . '.blade.php';

        return rtrim($templateDir, '/') . '/' . $relativePath;
    }
}
