<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Removes default values from function/method/attribute parameters and
 * explicitly adds those defaults at every call site that was relying on them.
 *
 * Configure targetFunctions, targetMethods, and/or targetAttributes to specify
 * which callables to migrate. Leave all three empty to make the rule a no-op
 * (safe for a permanent rector.php registration).
 *
 * targetFunctions: list of fully-qualified function names, e.g. ['MyNs\\myFunc']
 * targetMethods:   list of 'ClassName::methodName' strings, e.g. ['App\\Mailer::send']
 * targetAttributes: list of attribute FQCNs, e.g. ['App\\Attributes\\MyAttr']
 *
 * Leaving all three empty (the default) is a no-op.
 */
final class RemoveDefaultsAndApplyAtCallsiteRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    /**
     * Fully-qualified function names to migrate, e.g. ['MyNs\\greet'].
     * Empty = migrate ALL functions (use with care).
     *
     * @var string[]
     */
    private array $targetFunctions = [];

    /**
     * 'ClassName::method' strings to migrate, e.g. ['App\\Mailer::send'].
     * Empty = migrate ALL methods of known classes (use with care).
     *
     * @var string[]
     */
    private array $targetMethods = [];

    /**
     * Attribute FQCNs to migrate, e.g. ['App\\Attributes\\MyAttr'].
     * Empty = migrate ALL attributes.
     *
     * @var string[]
     */
    private array $targetAttributes = [];

    /**
     * When true, all three target lists are explicitly empty and the rule is a no-op.
     * Set to false when any target list is provided (even empty is provided meaning
     * "match all").
     */
    private bool $noopMode = true;

    /**
     * Registry of callables whose parameters have defaults that should be inlined.
     *
     * Key format:
     *   "function:<fqn>"              – top-level function
     *   "method:<ClassName>::<name>"  – instance/static method
     *   "attribute:<FQCN>"            – PHP attribute (via __construct)
     *
     * Value: list of param info:
     *   ['name' => string, 'position' => int, 'default' => Node\Expr]
     *
     * @var array<string, list<array{name: string, position: int, default: Node\Expr}>>
     */
    private static array $registry = [];

    /**
     * Tracks which registry keys have had their defaults removed so we don't
     * double-remove on subsequent Rector passes.
     *
     * @var array<string, true>
     */
    private static array $defaultsRemoved = [];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';

        // Reset static state on each configure() call so tests stay isolated.
        self::$registry = [];
        self::$defaultsRemoved = [];

        // If none of the target keys appear in config, treat as no-op for safety.
        $hasTargets = array_key_exists('targetFunctions', $configuration)
            || array_key_exists('targetMethods', $configuration)
            || array_key_exists('targetAttributes', $configuration);

        $this->noopMode = !$hasTargets;

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

        if ($this->noopMode) {
            return null;
        }

        $namespace = $this->resolveNamespaceFromFile($node);

        // Phase 1: collect definitions with defaulted params from this file.
        $this->collectDefinitions($node, $namespace);

        // Phase 2: apply modifications (add explicit args at call sites, remove defaults from defs).
        return $this->applyModifications($node, $namespace);
    }

    // -------------------------------------------------------------------------
    // Phase 1: collection
    // -------------------------------------------------------------------------

    private function collectDefinitions(FileNode $fileNode, string $namespace): void
    {
        $stmts = $this->resolveTopStmts($fileNode);

        $this->traverseNodesWithCallable($stmts, function (Node $innerNode) use ($namespace): null {
            if ($innerNode instanceof Function_) {
                $this->collectFunctionDefinition($innerNode, $namespace);
            } elseif ($innerNode instanceof Class_) {
                $this->collectClassDefinition($innerNode);
            }
            return null;
        });
    }

    private function collectFunctionDefinition(Function_ $node, string $namespace): void
    {
        $funcName = $this->getName($node);
        if ($funcName === null) {
            return;
        }

        // Prefer namespacedName when available (set by PhpParser's NameResolver).
        $fqn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($namespace !== '' ? $namespace . '\\' . $funcName : $funcName);

        // Filter: only collect if in targetFunctions (empty list = collect all).
        if ($this->targetFunctions !== [] && !in_array($fqn, $this->targetFunctions, true)) {
            return;
        }

        $key = 'function:' . $fqn;
        $this->registerDefaultedParams($key, $node->params);
    }

    private function collectClassDefinition(Class_ $node): void
    {
        $className = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : $this->getName($node);

        if ($className === null) {
            return;
        }

        $isAttributeClass = $this->classHasAttributeAttribute($node);

        foreach ($node->getMethods() as $method) {
            $methodName = $this->getName($method);
            if ($methodName === null) {
                continue;
            }

            $methodKey = 'method:' . $className . '::' . $methodName;

            // Filter: only collect if in targetMethods (empty list = collect all).
            if ($this->targetMethods === [] || in_array($className . '::' . $methodName, $this->targetMethods, true)) {
                $this->registerDefaultedParams($methodKey, $method->params);
            }

            // If this is a constructor on a PHP #[Attribute] class, also register
            // an attribute key so we can handle #[ClassName(...)] usages.
            if ($methodName === '__construct' && $isAttributeClass) {
                $attrKey = 'attribute:' . $className;

                // Filter: only collect if in targetAttributes (empty list = collect all).
                if ($this->targetAttributes === [] || in_array($className, $this->targetAttributes, true)) {
                    $this->registerDefaultedParams($attrKey, $method->params);
                }
            }
        }
    }

    /**
     * @param Param[] $params
     */
    private function registerDefaultedParams(string $key, array $params): void
    {
        // Only register if not already removed (second Rector pass: defaults are gone).
        if (isset(self::$defaultsRemoved[$key])) {
            return;
        }

        $defaultedParams = [];

        foreach ($params as $position => $param) {
            if ($param->default === null) {
                continue;
            }
            if ($param->variadic) {
                continue;
            }

            $paramName = $this->getName($param);
            if ($paramName === null) {
                continue;
            }

            $defaultedParams[] = [
                'name' => $paramName,
                'position' => $position,
                'default' => $param->default,
            ];
        }

        if ($defaultedParams === []) {
            return;
        }

        self::$registry[$key] = $defaultedParams;
    }

    // -------------------------------------------------------------------------
    // Phase 2: modifications
    // -------------------------------------------------------------------------

    private function applyModifications(FileNode $fileNode, string $namespace): ?FileNode
    {
        if (self::$registry === []) {
            return null;
        }

        $changed = false;
        $stmts = $this->resolveTopStmts($fileNode);

        // Modify call sites.
        $this->traverseNodesWithCallable($stmts, function (Node $node) use (&$changed, $namespace): ?Node {
            if ($node instanceof FuncCall) {
                $result = $this->applyToFuncCall($node, $namespace);
                if ($result !== null) {
                    $changed = true;
                    return $result;
                }
            } elseif ($node instanceof MethodCall) {
                $result = $this->applyToMethodOrStaticCall($node);
                if ($result !== null) {
                    $changed = true;
                    return $result;
                }
            } elseif ($node instanceof StaticCall) {
                $result = $this->applyToMethodOrStaticCall($node);
                if ($result !== null) {
                    $changed = true;
                    return $result;
                }
            } elseif ($node instanceof Attribute) {
                $result = $this->applyToAttribute($node);
                if ($result !== null) {
                    $changed = true;
                    return $result;
                }
            }
            return null;
        });

        // Modify definitions: remove defaults from params.
        $this->traverseNodesWithCallable($stmts, function (Node $node) use (&$changed, $namespace): null {
            if ($node instanceof Function_) {
                if ($this->removeDefaultsFromFunction($node, $namespace)) {
                    $changed = true;
                }
            } elseif ($node instanceof Class_) {
                if ($this->removeDefaultsFromClass($node)) {
                    $changed = true;
                }
            }
            return null;
        });

        return $changed ? $fileNode : null;
    }

    private function applyToFuncCall(FuncCall $node, string $namespace): ?FuncCall
    {
        if (!$node->name instanceof Name) {
            return null;
        }

        $name = $node->name->toString();
        $key = $this->findFunctionKey($name, $namespace);
        if ($key === null) {
            return null;
        }

        $defaultedParams = self::$registry[$key];

        if (!$this->addMissingArgs($node, $defaultedParams)) {
            return null;
        }

        return $node;
    }

    /**
     * @param MethodCall|StaticCall $node
     * @return MethodCall|StaticCall|null
     */
    private function applyToMethodOrStaticCall(Node $node): ?Node
    {
        $methodName = $this->getName($node->name);
        if ($methodName === null) {
            return null;
        }

        $key = $this->resolveMethodKey($node, $methodName);
        if ($key === null) {
            return null;
        }

        $defaultedParams = self::$registry[$key];

        if (!$this->addMissingArgs($node, $defaultedParams)) {
            return null;
        }

        return $node;
    }

    private function applyToAttribute(Attribute $node): ?Attribute
    {
        $attrName = $this->getName($node->name);
        if ($attrName === null) {
            return null;
        }

        $key = $this->findAttributeKey($attrName);
        if ($key === null) {
            return null;
        }

        $defaultedParams = self::$registry[$key];

        if (!$this->addMissingArgs($node, $defaultedParams)) {
            return null;
        }

        return $node;
    }

    /**
     * Add missing args to a call/attribute node using the default values.
     * Returns true if any args were added.
     *
     * @param FuncCall|MethodCall|StaticCall|Attribute $node
     * @param list<array{name: string, position: int, default: Node\Expr}> $defaultedParams
     */
    private function addMissingArgs(Node $node, array $defaultedParams): bool
    {
        $existingArgs = $node->args;

        // Count positional args passed and collect named arg param names.
        $positionalCount = 0;
        $explicitParamNames = [];

        foreach ($existingArgs as $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }
            if ($arg->name !== null) {
                $explicitParamNames[$arg->name->toString()] = true;
            } else {
                $positionalCount++;
            }
        }

        $added = false;

        foreach ($defaultedParams as $paramInfo) {
            $paramName = $paramInfo['name'];
            $paramPosition = $paramInfo['position'];
            $defaultValue = $paramInfo['default'];

            // Already explicitly passed by name.
            if (isset($explicitParamNames[$paramName])) {
                continue;
            }

            // Already covered by a positional arg.
            if ($paramPosition < $positionalCount) {
                continue;
            }

            // Always use named syntax when adding a previously-defaulted arg —
            // it makes the intent explicit and self-documenting.
            $newArg = new Arg(
                value: $defaultValue,
                name: new Identifier($paramName),
            );

            $node->args[] = $newArg;
            $explicitParamNames[$paramName] = true;
            $added = true;
        }

        return $added;
    }

    private function removeDefaultsFromFunction(Function_ $node, string $namespace): bool
    {
        $funcName = $this->getName($node);
        if ($funcName === null) {
            return false;
        }

        $fqn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($namespace !== '' ? $namespace . '\\' . $funcName : $funcName);

        $key = 'function:' . $fqn;

        return $this->removeDefaultsFromParams($node->params, $key);
    }

    private function removeDefaultsFromClass(Class_ $node): bool
    {
        $className = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : $this->getName($node);

        if ($className === null) {
            return false;
        }

        $changed = false;

        foreach ($node->getMethods() as $method) {
            $methodName = $this->getName($method);
            if ($methodName === null) {
                continue;
            }

            $key = 'method:' . $className . '::' . $methodName;

            if ($this->removeDefaultsFromParams($method->params, $key)) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param Param[] $params
     */
    private function removeDefaultsFromParams(array $params, string $key): bool
    {
        $defaultedParams = self::$registry[$key] ?? null;
        if ($defaultedParams === null) {
            return false;
        }

        if (isset(self::$defaultsRemoved[$key])) {
            return false;
        }

        $defaultedParamNames = [];
        foreach ($defaultedParams as $info) {
            $defaultedParamNames[$info['name']] = true;
        }

        $changed = false;

        foreach ($params as $param) {
            if ($param->default === null) {
                continue;
            }

            $paramName = $this->getName($param);
            if ($paramName === null || !isset($defaultedParamNames[$paramName])) {
                continue;
            }

            $param->default = null;
            $changed = true;
        }

        if ($changed) {
            self::$defaultsRemoved[$key] = true;
        }

        return $changed;
    }

    // -------------------------------------------------------------------------
    // Key resolution helpers
    // -------------------------------------------------------------------------

    private function findFunctionKey(string $name, string $namespace): ?string
    {
        // FQ name (starts with \).
        if (str_starts_with($name, '\\')) {
            $plain = ltrim($name, '\\');
            $key = 'function:' . $plain;
            return isset(self::$registry[$key]) ? $key : null;
        }

        // Try namespaced key first.
        if ($namespace !== '') {
            $fqKey = 'function:' . $namespace . '\\' . $name;
            if (isset(self::$registry[$fqKey])) {
                return $fqKey;
            }
        }

        // Try unqualified (global).
        $globalKey = 'function:' . $name;
        if (isset(self::$registry[$globalKey])) {
            return $globalKey;
        }

        return null;
    }

    /**
     * @param MethodCall|StaticCall $node
     */
    private function resolveMethodKey(Node $node, string $methodName): ?string
    {
        // For StaticCall, try to resolve the class name from the call.
        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $candidateClass = $node->class->toString();

            // Exact FQ match.
            $directKey = 'method:' . $candidateClass . '::' . $methodName;
            if (isset(self::$registry[$directKey])) {
                return $directKey;
            }

            // Short-name match (e.g. `Mailer::send` where Mailer is imported).
            foreach (array_keys(self::$registry) as $key) {
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

        // For instance method calls: if there's exactly one definition of this
        // method name in the registry, we can safely match it.
        $matches = [];
        foreach (array_keys(self::$registry) as $key) {
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

    private function findAttributeKey(string $attrName): ?string
    {
        // Direct FQ match.
        $directKey = 'attribute:' . $attrName;
        if (isset(self::$registry[$directKey])) {
            return $directKey;
        }

        // Short name match (attribute used without FQ import, or short alias).
        foreach (array_keys(self::$registry) as $key) {
            if (!str_starts_with($key, 'attribute:')) {
                continue;
            }
            $fqcn = substr($key, strlen('attribute:'));
            if ($this->resolveShortName($fqcn) === $attrName) {
                return $key;
            }
        }

        return null;
    }

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
