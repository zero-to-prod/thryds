<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Removes default values from function/method/attribute parameters and
 * explicitly adds those defaults at every call site that was relying on them.
 *
 * Attributes: auto-discovered via PHP reflection. All #[Attribute] classes with
 * defaulted constructor params are processed unless targetAttributes is non-empty
 * (in which case only those FQCNs are processed). Reflection is used so this rule
 * works correctly across files in Rector's parallel worker mode.
 *
 * Functions/methods: opt-in only. Provide non-empty targetFunctions / targetMethods
 * lists to process them; empty (the default) means skip that category entirely.
 * Reflection is attempted; if the function/class is not loaded, it is skipped.
 *
 * targetFunctions:  list of fully-qualified function names, e.g. ['MyNs\\myFunc']
 * targetMethods:    list of 'ClassName::methodName' strings, e.g. ['App\\Mailer::send']
 * targetAttributes: list of attribute FQCNs to restrict to, e.g. ['App\\Attributes\\MyAttr']
 *                   Leave empty (default) to process ALL #[Attribute] classes.
 */
final class RemoveDefaultsAndApplyAtCallsiteRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    /**
     * Fully-qualified function names to migrate, e.g. ['MyNs\\greet'].
     * Empty (default) = skip all functions (opt-in only).
     *
     * @var string[]
     */
    private array $targetFunctions = [];

    /**
     * 'ClassName::method' strings to migrate, e.g. ['App\\Mailer::send'].
     * Empty (default) = skip all methods (opt-in only).
     *
     * @var string[]
     */
    private array $targetMethods = [];

    /**
     * Attribute FQCNs to restrict to, e.g. ['App\\Attributes\\MyAttr'].
     * Empty (default) = process ALL #[Attribute] classes automatically.
     *
     * @var string[]
     */
    private array $targetAttributes = [];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
        $this->targetFunctions = $configuration['targetFunctions'] ?? [];
        $this->targetMethods = $configuration['targetMethods'] ?? [];
        $this->targetAttributes = $configuration['targetAttributes'] ?? [];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove default values from function/method/attribute parameters and apply them explicitly at every call site',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
function greet(string $name, string $greeting = 'Hello'): string
{
    return "{$greeting}, {$name}!";
}

greet('Alice');
greet('Bob', 'Hi');
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function greet(string $name, string $greeting): string
{
    return "{$greeting}, {$name}!";
}

greet('Alice', greeting: 'Hello');
greet('Bob', 'Hi');
CODE_SAMPLE,
                    [
                        'mode' => 'auto',
                        'targetFunctions' => ['Fixture\\greet'],
                    ],
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FileNode::class];
    }

    /**
     * @param FileNode $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode !== 'auto') {
            return null;
        }

        $namespace = $this->resolveNamespaceFromFile($node);
        $stmts = $this->resolveTopStmts($node);

        // --- Phase 1: collect function/method defaults from AST (for same-file callsites) ---

        /**
         * Local param registry (not static — avoids parallel worker contamination).
         * Key: 'function:<fqn>' or 'method:<Class>::<method>'
         * Value: list of defaulted param info built from AST nodes
         *
         * @var array<string, list<array{name: string, position: int, default: Node\Expr}>>
         */
        $localRegistry = [];

        $this->traverseNodesWithCallable($stmts, function (Node $inner) use (&$localRegistry, $namespace): null {
            if ($inner instanceof Function_) {
                $this->collectFunctionIntoRegistry($inner, $namespace, $localRegistry);
            } elseif ($inner instanceof Class_) {
                $this->collectClassMethodsIntoRegistry($inner, $localRegistry);
            }
            return null;
        });

        // --- Phase 2: apply modifications ---

        $changed = false;

        // Modify call sites.
        $this->traverseNodesWithCallable($stmts, function (Node $inner) use (&$changed, $namespace, $localRegistry): ?Node {
            if ($inner instanceof FuncCall) {
                $result = $this->applyToFuncCall($inner, $namespace, $localRegistry);
                if ($result !== null) {
                    $changed = true;
                    return $result;
                }
            } elseif ($inner instanceof MethodCall || $inner instanceof StaticCall) {
                $result = $this->applyToMethodOrStaticCall($inner, $localRegistry);
                if ($result !== null) {
                    $changed = true;
                    return $result;
                }
            } elseif ($inner instanceof Attribute) {
                $result = $this->applyToAttribute($inner);
                if ($result !== null) {
                    $changed = true;
                    return $result;
                }
            }
            return null;
        });

        // Modify definitions: remove defaults from params.
        $this->traverseNodesWithCallable($stmts, function (Node $inner) use (&$changed, $namespace, $localRegistry): null {
            if ($inner instanceof Function_) {
                if ($this->removeDefaultsFromFunction($inner, $namespace, $localRegistry)) {
                    $changed = true;
                }
            } elseif ($inner instanceof Class_) {
                if ($this->removeDefaultsFromClass($inner, $localRegistry)) {
                    $changed = true;
                }
            }
            return null;
        });

        return $changed ? $node : null;
    }

    // -------------------------------------------------------------------------
    // Local registry collection (functions/methods only — attributes use reflection)
    // -------------------------------------------------------------------------

    /**
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function collectFunctionIntoRegistry(Function_ $node, string $namespace, array &$localRegistry): void
    {
        $funcName = $this->getName($node);
        if ($funcName === null) {
            return;
        }

        $fqn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($namespace !== '' ? $namespace . '\\' . $funcName : $funcName);

        if ($this->targetFunctions === [] || !in_array($fqn, $this->targetFunctions, true)) {
            return;
        }

        $key = 'function:' . $fqn;
        $this->registerDefaultedParamsFromAst($key, $node->params, $localRegistry);
    }

    /**
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function collectClassMethodsIntoRegistry(Class_ $node, array &$localRegistry): void
    {
        $className = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : $this->getName($node);

        if ($className === null) {
            return;
        }

        foreach ($node->getMethods() as $method) {
            $methodName = $this->getName($method);
            if ($methodName === null) {
                continue;
            }

            $spec = $className . '::' . $methodName;
            if ($this->targetMethods !== [] && in_array($spec, $this->targetMethods, true)) {
                $key = 'method:' . $spec;
                $this->registerDefaultedParamsFromAst($key, $method->params, $localRegistry);
            }
        }
    }

    /**
     * @param Param[] $params
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function registerDefaultedParamsFromAst(string $key, array $params, array &$localRegistry): void
    {
        $defaultedParams = [];

        foreach ($params as $position => $param) {
            if ($param->default === null || $param->variadic) {
                continue;
            }

            $paramName = $this->getName($param);
            if ($paramName === null) {
                continue;
            }

            $defaultedParams[] = [
                'name' => $paramName,
                'position' => $position,
                'default' => clone $param->default,
            ];
        }

        if ($defaultedParams !== []) {
            $localRegistry[$key] = $defaultedParams;
        }
    }

    // -------------------------------------------------------------------------
    // Call-site modification
    // -------------------------------------------------------------------------

    /**
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function applyToFuncCall(FuncCall $node, string $namespace, array $localRegistry): ?FuncCall
    {
        if (!$node->name instanceof Name) {
            return null;
        }

        $name = $node->name->toString();
        $key = $this->findFunctionKey($name, $namespace, $localRegistry);
        if ($key === null) {
            return null;
        }

        $defaultedParams = $localRegistry[$key];

        if (!$this->addMissingArgsFromAst($node, $defaultedParams)) {
            return null;
        }

        return $node;
    }

    /**
     * @param MethodCall|StaticCall $node
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     * @return MethodCall|StaticCall|null
     */
    private function applyToMethodOrStaticCall(Node $node, array $localRegistry): ?Node
    {
        $methodName = $this->getName($node->name);
        if ($methodName === null) {
            return null;
        }

        $key = $this->resolveMethodKey($node, $methodName, $localRegistry);
        if ($key === null) {
            return null;
        }

        $defaultedParams = $localRegistry[$key];

        if (!$this->addMissingArgsFromAst($node, $defaultedParams)) {
            return null;
        }

        return $node;
    }

    private function applyToAttribute(Attribute $node): ?Attribute
    {
        $attrFqcn = $this->getName($node->name);
        if ($attrFqcn === null) {
            return null;
        }

        // Filter by targetAttributes if configured.
        if ($this->targetAttributes !== [] && !in_array($attrFqcn, $this->targetAttributes, true)) {
            // Also try short-name match.
            $shortName = $this->resolveShortName($attrFqcn);
            $matched = false;
            foreach ($this->targetAttributes as $target) {
                if ($target === $attrFqcn || $this->resolveShortName($target) === $shortName) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return null;
            }
        }

        $defaultedParams = $this->getDefaultedParamsViaReflection($attrFqcn);
        if ($defaultedParams === null || $defaultedParams === []) {
            return null;
        }

        if (!$this->addMissingArgsFromReflected($node, $defaultedParams)) {
            return null;
        }

        return $node;
    }

    // -------------------------------------------------------------------------
    // Reflection helpers
    // -------------------------------------------------------------------------

    /**
     * Returns null if the class cannot be reflected, is a PHP internal class,
     * or has no constructor. Returns an empty array if the constructor has no
     * defaulted params.
     *
     * @return list<array{name: string, position: int, default: mixed, isConstant: bool, constantName: string|null}>|null
     */
    private function getDefaultedParamsViaReflection(string $fqcn): ?array
    {
        try {
            $rc = new ReflectionClass($fqcn);
        } catch (ReflectionException) {
            return null;
        }

        // Skip PHP built-in classes (e.g. \Attribute, \Iterator) — they are not
        // user-defined and their defaults should not be inlined at call sites.
        if ($rc->isInternal()) {
            return null;
        }

        $constructor = $rc->getConstructor();
        if ($constructor === null) {
            return null;
        }

        $defaultedParams = [];

        foreach ($constructor->getParameters() as $position => $param) {
            if (!$param->isOptional() || $param->isVariadic()) {
                continue;
            }

            $isConstant = $param->isDefaultValueConstant();
            $defaultedParams[] = [
                'name' => $param->getName(),
                'position' => $position,
                'default' => $param->getDefaultValue(),
                'isConstant' => $isConstant,
                'constantName' => $isConstant ? $param->getDefaultValueConstantName() : null,
            ];
        }

        return $defaultedParams;
    }

    /**
     * Returns null if the function cannot be reflected or has no defaulted params.
     *
     * @return list<array{name: string, position: int, default: mixed, isConstant: bool, constantName: string|null}>|null
     */
    private function getDefaultedFunctionParamsViaReflection(string $fqn): ?array
    {
        try {
            $rf = new ReflectionFunction($fqn);
        } catch (ReflectionException) {
            return null;
        }

        return $this->extractDefaultedParams($rf->getParameters());
    }

    /**
     * Returns null if the method cannot be reflected or has no defaulted params.
     *
     * @return list<array{name: string, position: int, default: mixed, isConstant: bool, constantName: string|null}>|null
     */
    private function getDefaultedMethodParamsViaReflection(string $className, string $methodName): ?array
    {
        try {
            $rm = new ReflectionMethod($className, $methodName);
        } catch (ReflectionException) {
            return null;
        }

        return $this->extractDefaultedParams($rm->getParameters());
    }

    /**
     * @param ReflectionParameter[] $params
     * @return list<array{name: string, position: int, default: mixed, isConstant: bool, constantName: string|null}>
     */
    private function extractDefaultedParams(array $params): array
    {
        $result = [];

        foreach ($params as $position => $param) {
            if (!$param->isOptional() || $param->isVariadic()) {
                continue;
            }

            $isConstant = $param->isDefaultValueConstant();
            $result[] = [
                'name' => $param->getName(),
                'position' => $position,
                'default' => $param->getDefaultValue(),
                'isConstant' => $isConstant,
                'constantName' => $isConstant ? $param->getDefaultValueConstantName() : null,
            ];
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Arg injection helpers
    // -------------------------------------------------------------------------

    /**
     * Add missing args from AST-collected defaults (functions/methods).
     * Returns true if any args were added.
     *
     * @param FuncCall|MethodCall|StaticCall $node
     * @param list<array{name: string, position: int, default: Node\Expr}> $defaultedParams
     */
    private function addMissingArgsFromAst(Node $node, array $defaultedParams): bool
    {
        [$positionalCount, $explicitParamNames] = $this->analyzeExistingArgs($node->args);

        $added = false;

        foreach ($defaultedParams as $paramInfo) {
            $paramName = $paramInfo['name'];
            $paramPosition = $paramInfo['position'];
            $defaultExpr = $paramInfo['default'];

            if (isset($explicitParamNames[$paramName])) {
                continue;
            }

            if ($paramPosition < $positionalCount) {
                continue;
            }

            $node->args[] = new Arg(
                value: clone $defaultExpr,
                name: new Identifier($paramName),
            );
            $explicitParamNames[$paramName] = true;
            $added = true;
        }

        return $added;
    }

    /**
     * Add missing args from reflected defaults (attributes, and optionally functions/methods).
     * Returns true if any args were added.
     *
     * @param FuncCall|MethodCall|StaticCall|Attribute $node
     * @param list<array{name: string, position: int, default: mixed, isConstant: bool, constantName: string|null}> $defaultedParams
     */
    private function addMissingArgsFromReflected(Node $node, array $defaultedParams): bool
    {
        [$positionalCount, $explicitParamNames] = $this->analyzeExistingArgs($node->args);

        $added = false;

        foreach ($defaultedParams as $paramInfo) {
            $paramName = $paramInfo['name'];
            $paramPosition = $paramInfo['position'];

            if (isset($explicitParamNames[$paramName])) {
                continue;
            }

            if ($paramPosition < $positionalCount) {
                continue;
            }

            $defaultExpr = $paramInfo['isConstant'] && $paramInfo['constantName'] !== null
                ? $this->buildExprFromConstantName($paramInfo['constantName'])
                : $this->buildExprFromReflected($paramInfo['default']);

            $node->args[] = new Arg(
                value: $defaultExpr,
                name: new Identifier($paramName),
            );
            $explicitParamNames[$paramName] = true;
            $added = true;
        }

        return $added;
    }

    /**
     * @param array<Arg|\PhpParser\Node\VariadicPlaceholder> $args
     * @return array{int, array<string, true>}
     */
    private function analyzeExistingArgs(array $args): array
    {
        $positionalCount = 0;
        $explicitParamNames = [];

        foreach ($args as $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }
            if ($arg->name !== null) {
                $explicitParamNames[$arg->name->toString()] = true;
            } else {
                $positionalCount++;
            }
        }

        return [$positionalCount, $explicitParamNames];
    }

    // -------------------------------------------------------------------------
    // AST node building from reflected values
    // -------------------------------------------------------------------------

    private function buildExprFromReflected(mixed $value): Node\Expr
    {
        if (is_string($value)) {
            return new String_($value);
        }
        if (is_int($value)) {
            return new Int_($value);
        }
        if (is_float($value)) {
            return new Float_($value);
        }
        if ($value === true) {
            return new ConstFetch(new Name('true'));
        }
        if ($value === false) {
            return new ConstFetch(new Name('false'));
        }
        if ($value === null) {
            return new ConstFetch(new Name('null'));
        }
        if (is_array($value)) {
            $items = [];
            foreach ($value as $k => $v) {
                $key = is_string($k) || is_int($k) ? $this->buildExprFromReflected($k) : null;
                $items[] = new ArrayItem($this->buildExprFromReflected($v), $key);
            }
            return new Array_($items, ['kind' => Array_::KIND_SHORT]);
        }

        // Enum instance or other object: fall back to string representation.
        return new String_((string) $value);
    }

    private function buildExprFromConstantName(string $constantName): Node\Expr
    {
        if (str_contains($constantName, '::')) {
            [$className, $constName] = explode('::', $constantName, 2);
            return new ClassConstFetch(new FullyQualified($className), new Identifier($constName));
        }

        return new ConstFetch(new Name($constantName));
    }

    // -------------------------------------------------------------------------
    // Definition modification (remove defaults from params)
    // -------------------------------------------------------------------------

    /**
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function removeDefaultsFromFunction(Function_ $node, string $namespace, array $localRegistry): bool
    {
        $funcName = $this->getName($node);
        if ($funcName === null) {
            return false;
        }

        $fqn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($namespace !== '' ? $namespace . '\\' . $funcName : $funcName);

        $key = 'function:' . $fqn;

        if (!isset($localRegistry[$key])) {
            return false;
        }

        return $this->removeDefaultsFromParamsByNames(
            $node->params,
            array_column($localRegistry[$key], 'name'),
        );
    }

    /**
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function removeDefaultsFromClass(Class_ $node, array $localRegistry): bool
    {
        $className = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : $this->getName($node);

        if ($className === null) {
            return false;
        }

        $changed = false;
        $isAttributeClass = $this->classHasAttributeAttribute($node);

        foreach ($node->getMethods() as $method) {
            $methodName = $this->getName($method);
            if ($methodName === null) {
                continue;
            }

            // Opt-in methods.
            $key = 'method:' . $className . '::' . $methodName;
            if (isset($localRegistry[$key])) {
                $names = array_column($localRegistry[$key], 'name');
                if ($this->removeDefaultsFromParamsByNames($method->params, $names)) {
                    $changed = true;
                }
            }

            // Attribute class constructors: remove ALL defaults (they are inlined at callsites).
            if ($methodName === '__construct' && $isAttributeClass) {
                $shouldProcess = $this->targetAttributes === []
                    || in_array($className, $this->targetAttributes, true);

                if ($shouldProcess && $this->removeAllDefaultsFromParams($method->params)) {
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * @param Param[] $params
     * @param string[] $names
     */
    private function removeDefaultsFromParamsByNames(array $params, array $names): bool
    {
        $nameSet = array_flip($names);
        $changed = false;

        foreach ($params as $param) {
            if ($param->default === null) {
                continue;
            }

            $paramName = $this->getName($param);
            if ($paramName === null || !isset($nameSet[$paramName])) {
                continue;
            }

            $param->default = null;
            $changed = true;
        }

        return $changed;
    }

    /**
     * Remove ALL default values from params (used for attribute constructors).
     *
     * @param Param[] $params
     */
    private function removeAllDefaultsFromParams(array $params): bool
    {
        $changed = false;

        foreach ($params as $param) {
            if ($param->default === null || $param->variadic) {
                continue;
            }

            $param->default = null;
            $changed = true;
        }

        return $changed;
    }

    // -------------------------------------------------------------------------
    // Key resolution helpers (local registry)
    // -------------------------------------------------------------------------

    /**
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function findFunctionKey(string $name, string $namespace, array $localRegistry): ?string
    {
        if (str_starts_with($name, '\\')) {
            $plain = ltrim($name, '\\');
            $key = 'function:' . $plain;
            return isset($localRegistry[$key]) ? $key : null;
        }

        if ($namespace !== '') {
            $fqKey = 'function:' . $namespace . '\\' . $name;
            if (isset($localRegistry[$fqKey])) {
                return $fqKey;
            }
        }

        $globalKey = 'function:' . $name;
        return isset($localRegistry[$globalKey]) ? $globalKey : null;
    }

    /**
     * @param MethodCall|StaticCall $node
     * @param array<string, list<array{name: string, position: int, default: Node\Expr}>> $localRegistry
     */
    private function resolveMethodKey(Node $node, string $methodName, array $localRegistry): ?string
    {
        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $candidateClass = $node->class->toString();

            $directKey = 'method:' . $candidateClass . '::' . $methodName;
            if (isset($localRegistry[$directKey])) {
                return $directKey;
            }

            foreach (array_keys($localRegistry) as $key) {
                if (!str_starts_with($key, 'method:')) {
                    continue;
                }
                $suffix = '::' . $methodName;
                if (!str_ends_with($key, $suffix)) {
                    continue;
                }
                $registeredClass = substr($key, strlen('method:'), -strlen($suffix));
                $shortClass = $this->resolveShortName($registeredClass);
                if ($shortClass === $candidateClass || $registeredClass === $candidateClass) {
                    return $key;
                }
            }
        }

        $matches = [];
        foreach (array_keys($localRegistry) as $key) {
            if (!str_starts_with($key, 'method:')) {
                continue;
            }
            if (str_ends_with($key, '::' . $methodName)) {
                $matches[] = $key;
            }
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    private function resolveShortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }

    private function resolveNamespaceFromFile(FileNode $fileNode): string
    {
        foreach ($fileNode->stmts as $stmt) {
            if ($stmt instanceof Namespace_ && $stmt->name !== null) {
                return $stmt->name->toString();
            }
        }
        return '';
    }

    /**
     * @return Node\Stmt[]
     */
    private function resolveTopStmts(FileNode $fileNode): array
    {
        foreach ($fileNode->stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                return $stmt->stmts;
            }
        }
        return $fileNode->stmts;
    }

    private function classHasAttributeAttribute(Class_ $class): bool
    {
        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === 'Attribute' || $name === '\Attribute' || $name === 'attribute') {
                    return true;
                }
            }
        }
        return false;
    }
}
