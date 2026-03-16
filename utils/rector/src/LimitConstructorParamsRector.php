<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class LimitConstructorParamsRector extends AbstractRector implements ConfigurableRectorInterface
{
    private int $maxParams = 5;

    private string $dtoSuffix = 'Deps';

    private string $dtoOutputDir = '';

    private string $todoMessage = 'TODO: Too many constructor parameters';

    public function configure(array $configuration): void
    {
        $this->maxParams = $configuration['maxParams'] ?? 5;
        $this->dtoSuffix = $configuration['dtoSuffix'] ?? 'Deps';
        $this->dtoOutputDir = $configuration['dtoOutputDir'] ?? '';
        $this->todoMessage = $configuration['todoMessage'] ?? 'TODO: Too many constructor parameters';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Limit constructor parameters by extracting excess into a parameter object (DTO)',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class OrderProcessor {
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly TaxCalculator $TaxCalculator,
        private readonly NotificationService $NotificationService,
        private readonly AuditLogger $AuditLogger,
    ) {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class OrderProcessor {
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly TaxCalculator $TaxCalculator,
        private readonly OrderProcessorDeps $OrderProcessorDeps,
    ) {}
}
CODE_SAMPLE,
                    ['maxParams' => 5, 'dtoSuffix' => 'Deps']
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
        $constructor = $node->getMethod('__construct');
        if ($constructor === null) {
            return null;
        }

        $paramCount = count($constructor->params);
        if ($paramCount <= $this->maxParams) {
            return null;
        }

        $excessCount = $paramCount - $this->maxParams;

        if ($excessCount < 2) {
            return $this->addTodo($node, $constructor, $paramCount);
        }

        // Account for the DTO param taking one slot
        $extractCount = $excessCount + 1;

        if (!$this->isExtractionSafe($constructor)) {
            return $this->addTodo($node, $constructor, $paramCount);
        }

        $className = $node->name?->toString();
        if ($className === null) {
            return $this->addTodo($node, $constructor, $paramCount);
        }

        $dtoClassName = $className . $this->dtoSuffix;

        $usageMap = $this->buildUsageMap($node, $constructor);
        $paramsToExtract = $this->selectParamsToExtract($constructor, $usageMap, $extractCount);

        $namespace = $this->resolveNamespace($node);
        $dtoFqcn = $namespace !== null ? $namespace . '\\' . $dtoClassName : $dtoClassName;

        $this->generateDtoFile($dtoClassName, $namespace, $paramsToExtract);

        $extractedNames = [];
        foreach ($paramsToExtract as $param) {
            $name = $this->getName($param->var);
            if ($name !== null) {
                $extractedNames[] = $name;
            }
        }

        $this->rewritePropertyReferences($node, $extractedNames, $dtoClassName);
        $this->rewriteConstructor($constructor, $paramsToExtract, $dtoFqcn, $dtoClassName);

        return $node;
    }

    private function isExtractionSafe(ClassMethod $constructor): bool
    {
        foreach ($constructor->params as $param) {
            if ($param->flags === 0) {
                return false;
            }
            if ($param->type === null) {
                return false;
            }
        }

        if ($constructor->stmts !== null && $constructor->stmts !== []) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildUsageMap(Class_ $class, ClassMethod $constructor): array
    {
        $promotedNames = [];
        foreach ($constructor->params as $param) {
            $name = $this->getName($param->var);
            if ($name !== null) {
                $promotedNames[] = $name;
            }
        }

        $usageMap = array_fill_keys($promotedNames, []);

        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() === '__construct') {
                continue;
            }

            $methodName = $method->name->toString();
            $this->traverseNodesWithCallable($method, function (Node $inner) use (&$usageMap, $methodName, $promotedNames): null {
                if (!$inner instanceof PropertyFetch) {
                    return null;
                }
                if (!$inner->var instanceof Variable || !$this->isName($inner->var, 'this')) {
                    return null;
                }

                $propName = $this->getName($inner->name);
                if ($propName !== null && in_array($propName, $promotedNames, true)) {
                    $usageMap[$propName][] = $methodName;
                }

                return null;
            });
        }

        return $usageMap;
    }

    /**
     * @param array<string, list<string>> $usageMap
     * @return Param[]
     */
    private function selectParamsToExtract(ClassMethod $constructor, array $usageMap, int $excessCount): array
    {
        // Try type-name prefix grouping
        $typeGroups = $this->groupByTypePrefix($constructor->params);
        foreach ($typeGroups as $group) {
            if (count($group) >= $excessCount) {
                return array_slice($group, 0, $excessCount);
            }
        }

        // Try co-occurrence grouping
        $coGroups = $this->groupByCoOccurrence($constructor->params, $usageMap);
        foreach ($coGroups as $group) {
            if (count($group) >= $excessCount) {
                return array_slice($group, 0, $excessCount);
            }
        }

        // Fallback: last N params
        return array_slice($constructor->params, -$excessCount);
    }

    /**
     * @param Param[] $params
     * @return array<string, Param[]>
     */
    private function groupByTypePrefix(array $params): array
    {
        $groups = [];
        foreach ($params as $param) {
            if ($param->type === null) {
                continue;
            }
            $typeName = $this->resolveSimpleTypeName($param->type);
            if ($typeName === null) {
                continue;
            }
            $parts = preg_split('/(?=[A-Z])/', $typeName, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 2) {
                continue;
            }
            $prefix = $parts[0];
            $groups[$prefix][] = $param;
        }

        // Only return groups with 2+ members
        return array_filter($groups, static fn(array $g): bool => count($g) >= 2);
    }

    /**
     * Find params that are used exclusively together — they share methods
     * but those methods don't also use params outside the group.
     *
     * @param Param[] $params
     * @param array<string, list<string>> $usageMap
     * @return array<int, Param[]>
     */
    private function groupByCoOccurrence(array $params, array $usageMap): array
    {
        $paramByName = [];
        foreach ($params as $param) {
            $name = $this->getName($param->var);
            if ($name !== null) {
                $paramByName[$name] = $param;
            }
        }

        // Build method → set of param names used in that method
        $methodParamNames = [];
        foreach ($paramByName as $name => $_param) {
            foreach ($usageMap[$name] ?? [] as $method) {
                $methodParamNames[$method][] = $name;
            }
        }

        // Group params that share a method exclusively (no other params in that method)
        $allNames = array_keys($paramByName);
        $groups = [];

        foreach ($methodParamNames as $names) {
            if (count($names) < 2) {
                continue;
            }
            // Check exclusivity: these params are only used with each other in this method
            $otherNames = array_diff($allNames, $names);
            $exclusive = true;
            foreach ($otherNames as $otherName) {
                if (in_array($otherName, $names, true)) {
                    $exclusive = false;
                    break;
                }
            }
            if ($exclusive) {
                $group = [];
                foreach ($names as $name) {
                    $group[] = $paramByName[$name];
                }
                $groups[] = $group;
            }
        }

        usort($groups, static fn(array $a, array $b): int => count($b) <=> count($a));

        return $groups;
    }

    private function resolveSimpleTypeName(Node $type): ?string
    {
        if ($type instanceof Identifier) {
            return $type->name;
        }
        if ($type instanceof Node\Name) {
            return $type->getLast();
        }

        return null;
    }

    private function resolveFullTypeName(Node $type): string
    {
        if ($type instanceof Identifier) {
            return $type->name;
        }
        if ($type instanceof FullyQualified) {
            return '\\' . $type->toString();
        }
        if ($type instanceof Node\Name) {
            return $type->toString();
        }

        return '';
    }

    private function resolveNamespace(Class_ $class): ?string
    {
        if ($class->namespacedName !== null) {
            $parts = $class->namespacedName->getParts();
            if (count($parts) > 1) {
                array_pop($parts);

                return implode('\\', $parts);
            }
        }

        return null;
    }

    /**
     * @param Param[] $params
     */
    private function generateDtoFile(string $dtoClassName, ?string $namespace, array $params): void
    {
        $outputDir = $this->dtoOutputDir !== '' ? $this->dtoOutputDir : dirname($this->file->getFilePath());
        $filePath = rtrim($outputDir, '/') . '/' . $dtoClassName . '.php';

        if (file_exists($filePath)) {
            return;
        }

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        if ($namespace !== null) {
            $lines[] = 'namespace ' . $namespace . ';';
            $lines[] = '';
        }
        $lines[] = 'readonly class ' . $dtoClassName;
        $lines[] = '{';
        $lines[] = '    public function __construct(';

        $paramLines = [];
        foreach ($params as $param) {
            $typeName = $param->type !== null ? $this->resolveFullTypeName($param->type) . ' ' : '';
            $varName = '$' . $this->getName($param->var);
            $paramLines[] = '        public ' . $typeName . $varName . ',';
        }
        $lines[] = implode("\n", $paramLines);

        $lines[] = '    ) {}';
        $lines[] = '}';
        $lines[] = '';

        if (!is_dir($outputDir)) {
            return;
        }

        file_put_contents($filePath, implode("\n", $lines));
    }

    /**
     * @param string[] $extractedNames
     */
    private function rewritePropertyReferences(Class_ $class, array $extractedNames, string $dtoParamName): void
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() === '__construct') {
                continue;
            }

            $this->traverseNodesWithCallable($method, function (Node $inner) use ($extractedNames, $dtoParamName): ?PropertyFetch {
                if (!$inner instanceof PropertyFetch) {
                    return null;
                }
                if (!$inner->var instanceof Variable || !$this->isName($inner->var, 'this')) {
                    return null;
                }

                $propName = $this->getName($inner->name);
                if ($propName === null || !in_array($propName, $extractedNames, true)) {
                    return null;
                }

                return new PropertyFetch(
                    new PropertyFetch(new Variable('this'), new Identifier($dtoParamName)),
                    new Identifier($propName)
                );
            });
        }
    }

    /**
     * @param Param[] $extractedParams
     */
    private function rewriteConstructor(
        ClassMethod $constructor,
        array $extractedParams,
        string $dtoFqcn,
        string $dtoParamName,
    ): void {
        $extractedIds = [];
        foreach ($extractedParams as $param) {
            $extractedIds[spl_object_id($param)] = true;
        }

        $remainingParams = [];
        foreach ($constructor->params as $param) {
            if (!isset($extractedIds[spl_object_id($param)])) {
                $remainingParams[] = $param;
            }
        }

        $dtoParam = new Param(
            new Variable($dtoParamName),
            null,
            new FullyQualified($dtoFqcn),
            false,
            false,
            [],
            Class_::MODIFIER_PRIVATE | Class_::MODIFIER_READONLY,
        );

        $remainingParams[] = $dtoParam;
        $constructor->params = $remainingParams;
    }

    private function addTodo(Class_ $class, ClassMethod $constructor, int $paramCount): ?Node
    {
        $message = $this->todoMessage . ' (current: ' . $paramCount . ', max: ' . $this->maxParams . ')';

        foreach ($constructor->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->todoMessage)) {
                return null;
            }
        }

        $existingComments = $constructor->getComments();
        $todoComment = new Comment('// ' . $message);
        array_unshift($existingComments, $todoComment);
        $constructor->setAttribute('comments', $existingComments);

        return $class;
    }
}
