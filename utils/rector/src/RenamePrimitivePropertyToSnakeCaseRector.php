<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\TypeWithClassName;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenamePrimitivePropertyToSnakeCaseRector extends AbstractRector
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename primitive-typed class properties to snake_case', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class Config
{
    public const string bladeCacheDir = 'bladeCacheDir';
    public string $bladeCacheDir;

    public function getDir(): string
    {
        return $this->bladeCacheDir;
    }
}
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
class Config
{
    public const string blade_cache_dir = 'blade_cache_dir';
    public string $blade_cache_dir;

    public function getDir(): string
    {
        return $this->blade_cache_dir;
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, PropertyFetch::class, ClassConstFetch::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Class_) {
            return $this->refactorClass($node);
        }

        if ($node instanceof PropertyFetch) {
            return $this->refactorPropertyFetch($node);
        }

        if ($node instanceof ClassConstFetch) {
            return $this->refactorClassConstFetch($node);
        }

        return null;
    }

    private function refactorClass(Class_ $node): ?Class_
    {
        $renames = $this->collectPropertyRenames($node);
        if ($renames === []) {
            return null;
        }

        $this->renamePropertyDeclarations($node, $renames);
        $this->renameMatchingConsts($node, $renames);
        $this->renameThisPropertyFetches($node, $renames);

        return $node;
    }

    /**
     * @return array<string, string> old name => new name
     */
    private function collectPropertyRenames(Class_ $node): array
    {
        $renames = [];

        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof Property) {
                continue;
            }

            if (! $this->hasPrimitiveType($stmt)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $currentName = $this->getName($prop->name);
                if ($currentName === null) {
                    continue;
                }

                $snakeName = $this->toSnakeCase($currentName);
                if ($currentName === $snakeName) {
                    continue;
                }

                $renames[$currentName] = $snakeName;
            }
        }

        return $renames;
    }

    /**
     * @param array<string, string> $renames
     */
    private function renamePropertyDeclarations(Class_ $node, array $renames): void
    {
        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof Property) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $currentName = $this->getName($prop->name);
                if ($currentName === null || ! isset($renames[$currentName])) {
                    continue;
                }

                $prop->name = new VarLikeIdentifier($renames[$currentName]);
            }
        }
    }

    /**
     * @param array<string, string> $renames
     */
    private function renameMatchingConsts(Class_ $node, array $renames): void
    {
        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                $constName = $this->getName($const->name);
                if ($constName === null || ! isset($renames[$constName])) {
                    continue;
                }

                if (! $const->value instanceof String_) {
                    continue;
                }

                if ($const->value->value !== $constName) {
                    continue;
                }

                $newName = $renames[$constName];
                $const->name = new Identifier($newName);
                $const->value = new String_($newName);

                $docComment = $stmt->getDocComment();
                if ($docComment !== null) {
                    $updated = str_replace(
                        '@see $' . $constName,
                        '@see $' . $newName,
                        $docComment->getText()
                    );
                    $stmt->setDocComment(new Doc($updated));
                }
            }
        }
    }

    /**
     * @param array<string, string> $renames
     */
    private function renameThisPropertyFetches(Class_ $node, array $renames): void
    {
        $this->traverseNodesWithCallable($node->stmts, function (Node $innerNode) use ($renames): ?Node {
            if (! $innerNode instanceof PropertyFetch) {
                return null;
            }

            if (! $this->isName($innerNode->var, 'this')) {
                return null;
            }

            $propName = $this->getName($innerNode->name);
            if ($propName === null || ! isset($renames[$propName])) {
                return null;
            }

            $innerNode->name = new Identifier($renames[$propName]);
            return $innerNode;
        });
    }

    private function refactorPropertyFetch(PropertyFetch $node): ?PropertyFetch
    {
        if ($this->isName($node->var, 'this')) {
            return null;
        }

        $propName = $this->getName($node->name);
        if ($propName === null) {
            return null;
        }

        $snakeName = $this->toSnakeCase($propName);
        if ($propName === $snakeName) {
            return null;
        }

        $objectType = $this->getType($node->var);
        if (! $objectType instanceof TypeWithClassName) {
            return null;
        }

        $className = $objectType->getClassName();
        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if (! $classReflection->hasNativeProperty($propName)) {
            return null;
        }

        $propertyReflection = $classReflection->getNativeProperty($propName);
        $nativeType = $propertyReflection->getNativeType();

        if ($nativeType instanceof TypeWithClassName) {
            return null;
        }

        $node->name = new Identifier($snakeName);
        return $node;
    }

    private function refactorClassConstFetch(ClassConstFetch $node): ?ClassConstFetch
    {
        $constName = $this->getName($node->name);
        if ($constName === null) {
            return null;
        }

        $snakeName = $this->toSnakeCase($constName);
        if ($constName === $snakeName) {
            return null;
        }

        $className = $this->getName($node->class);
        if ($className === null) {
            return null;
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (! $classReflection->hasNativeProperty($constName)) {
            return null;
        }

        $propertyReflection = $classReflection->getNativeProperty($constName);
        $nativeType = $propertyReflection->getNativeType();

        if ($nativeType instanceof TypeWithClassName) {
            return null;
        }

        $node->name = new Identifier($snakeName);
        return $node;
    }

    private function hasPrimitiveType(Property $property): bool
    {
        $type = $property->type;

        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        return $type instanceof Identifier;
    }

    private function toSnakeCase(string $name): string
    {
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
        return strtolower((string) $snake);
    }
}
