<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeWithClassName;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidStringComparisonOnEnumPropertyRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $enumClasses = [];

    private string $mode = 'warn';

    private string $message = "TODO: [ForbidStringComparisonOnEnumPropertyRector] Compare against %s::%s instead of string '%s'.";

    /** @var array<string, array<string, string>>|null enumClass => (value => case name), null means not yet built */
    private ?array $enumValueMaps = null;

    /**
     * Cache of class property type maps built from AST declarations.
     * class short name => [property name => resolved FQN of type]
     *
     * @var array<string, array<string, string>>
     */
    private array $classPropertyTypeCache = [];

    public function configure(array $configuration): void
    {
        $this->enumClasses = $configuration['enumClasses'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [ForbidStringComparisonOnEnumPropertyRector] Compare against %s::%s instead of string '%s'.";
        $this->enumValueMaps = null;
        $this->classPropertyTypeCache = [];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rewrite $enum->value === \'string\' comparisons to use enum case directly, removing ->value bypasses',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
if ($Config->AppEnv->value === 'production') { }
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
if ($Config->AppEnv === AppEnv::production) { }
CODE_SAMPLE,
                    [
                        'enumClasses' => [\ZeroToProd\Framework\AppEnv::class],
                        'mode' => 'auto',
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
        return [Class_::class, Identical::class, NotIdentical::class];
    }

    /**
     * @param Class_|Identical|NotIdentical $node
     */
    public function refactor(Node $node): ?Node
    {
        // Build class property type cache from Class_ declarations
        if ($node instanceof Class_) {
            $this->cacheClassPropertyTypes($node);
            return null;
        }

        $this->ensureEnumValueMaps();

        $match = $this->detectEnumValueComparison($node);
        if ($match === null) {
            return null;
        }

        [$enumPropertyFetch, $stringValue, $isLeftEnum, $enumClass] = $match;

        $valueMap = $this->enumValueMaps[$enumClass] ?? [];
        if (!isset($valueMap[$stringValue])) {
            return null;
        }

        $caseName = $valueMap[$stringValue];

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node, $enumClass, $caseName, $stringValue);
        }

        // Build replacement: $enum === EnumClass::caseName
        $enumCaseFetch = new ClassConstFetch(
            new FullyQualified($enumClass),
            new Identifier($caseName)
        );

        if ($isLeftEnum) {
            $node->left = $enumPropertyFetch->var;
            $node->right = $enumCaseFetch;
        } else {
            $node->left = $enumCaseFetch;
            $node->right = $enumPropertyFetch->var;
        }

        return $node;
    }

    /**
     * Cache property type declarations from Class_ AST nodes.
     * This avoids relying on PHPStan's reflection for inline classes.
     */
    private function cacheClassPropertyTypes(Class_ $class): void
    {
        $className = $class->name?->toString();
        if ($className === null) {
            return;
        }

        // Build the namespace context from namespacedName
        $fqn = $class->namespacedName !== null
            ? $class->namespacedName->toString()
            : $className;

        $propertyTypes = [];

        foreach ($class->stmts as $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }

            $type = $stmt->type;

            if ($type instanceof NullableType) {
                $type = $type->type;
            }

            if (!$type instanceof Name) {
                continue;
            }

            // The type name — resolve it. If it's a FullyQualified name, use as is.
            // Otherwise, it will be resolved via scope/use statements by PHPStan.
            // We store the type name as PHPParser sees it (which is resolved by NameResolver).
            $typeName = $type->toString();

            foreach ($stmt->props as $prop) {
                $propName = $prop->name->toString();
                $propertyTypes[$propName] = $typeName;
            }
        }

        $this->classPropertyTypeCache[$fqn] = $propertyTypes;
    }

    /**
     * Returns [enumValuePropertyFetch, stringValue, isLeftEnum, enumClass] or null.
     *
     * @return array{PropertyFetch, string, bool, string}|null
     */
    private function detectEnumValueComparison(Identical|NotIdentical $node): ?array
    {
        // Check left === EnumProp->value, right === 'string'
        $leftMatch = $this->extractEnumValueFetch($node->left);
        if ($leftMatch !== null && $node->right instanceof String_) {
            return [$leftMatch[0], $node->right->value, true, $leftMatch[1]];
        }

        // Check right === EnumProp->value, left === 'string'
        $rightMatch = $this->extractEnumValueFetch($node->right);
        if ($rightMatch !== null && $node->left instanceof String_) {
            return [$rightMatch[0], $node->left->value, false, $rightMatch[1]];
        }

        return null;
    }

    /**
     * Returns [PropertyFetch, enumClass] if $expr is a PropertyFetch with name 'value'
     * on an expression whose type matches one of the configured enum classes.
     *
     * @return array{PropertyFetch, string}|null
     */
    private function extractEnumValueFetch(Node $expr): ?array
    {
        if (!$expr instanceof PropertyFetch) {
            return null;
        }

        if (!$expr->name instanceof Identifier) {
            return null;
        }

        if ($expr->name->toString() !== 'value') {
            return null;
        }

        // $expr->var is the enum expression (e.g. $Config->AppEnv)
        // Try PHPStan scope-based type resolution first
        $enumClass = $this->resolveEnumClassFromType($expr->var);
        if ($enumClass !== null) {
            return [$expr, $enumClass];
        }

        // Fallback: resolve via AST class declaration cache
        $enumClass = $this->resolveEnumClassViaAstCache($expr->var);
        if ($enumClass !== null) {
            return [$expr, $enumClass];
        }

        return null;
    }

    /**
     * Try to resolve the enum class using PHPStan's isObjectType helper.
     */
    private function resolveEnumClassFromType(Node $node): ?string
    {
        foreach ($this->enumClasses as $enumClass) {
            $normalized = ltrim($enumClass, '\\');
            if ($this->isObjectType($node, new ObjectType($normalized))) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * Fallback: for PropertyFetch nodes, use PHPStan scope to get the container class name,
     * then use the AST class property cache to resolve the property type.
     * This handles inline class declarations that PHPStan's reflection provider may not know about.
     */
    private function resolveEnumClassViaAstCache(Node $node): ?string
    {
        if (!$node instanceof PropertyFetch) {
            return null;
        }

        if (!$node->name instanceof Identifier) {
            return null;
        }

        $propertyName = $node->name->toString();

        // Get the type of the container object ($node->var) from PHPStan scope
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return null;
        }

        $containerType = $scope->getType($node->var);
        if (!$containerType instanceof TypeWithClassName) {
            return null;
        }

        $containerClass = $containerType->getClassName();

        // Look up the property type in our AST cache
        if (!isset($this->classPropertyTypeCache[$containerClass])) {
            return null;
        }

        $propertyTypes = $this->classPropertyTypeCache[$containerClass];
        if (!isset($propertyTypes[$propertyName])) {
            return null;
        }

        $typeName = $propertyTypes[$propertyName];

        foreach ($this->enumClasses as $enumClass) {
            $normalized = ltrim($enumClass, '\\');
            // Compare short name or FQN
            if ($typeName === $normalized || $this->shortName($normalized) === $typeName || ltrim($typeName, '\\') === $normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function addMessageComment(
        Identical|NotIdentical $node,
        string $enumClass,
        string $caseName,
        string $stringValue
    ): ?Node {
        $shortName = $this->shortName($enumClass);
        $text = sprintf($this->message, $shortName, $caseName, $stringValue);

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $text));
        $node->setAttribute('comments', $comments);

        return $node;
    }

    private function shortName(string $fqn): string
    {
        $parts = explode('\\', $fqn);
        return end($parts);
    }

    private function ensureEnumValueMaps(): void
    {
        if ($this->enumValueMaps !== null) {
            return;
        }

        $this->enumValueMaps = [];

        foreach ($this->enumClasses as $enumClass) {
            $normalized = ltrim($enumClass, '\\');
            $this->enumValueMaps[$normalized] = $this->buildEnumValueMap($normalized);
        }
    }

    /** @return array<string, string> value => case name */
    private function buildEnumValueMap(string $enumClass): array
    {
        if (!class_exists($enumClass, autoload: false)) {
            return [];
        }

        try {
            $reflection = new \ReflectionEnum($enumClass);
        } catch (\ReflectionException) {
            return [];
        }

        $map = [];

        foreach ($reflection->getCases() as $case) {
            if ($case instanceof \ReflectionEnumBackedCase) {
                /** @var string $value */
                $value = $case->getBackingValue();
                $map[$value] = $case->getName();
            }
        }

        return $map;
    }
}
