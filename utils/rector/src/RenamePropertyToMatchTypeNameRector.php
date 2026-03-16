<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\TypeWithClassName;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenamePropertyToMatchTypeNameRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename class properties to exactly match their class/enum type name', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class Config
{
    public const string appEnv = 'appEnv';
    public AppEnv $appEnv;

    public function isProduction(): bool
    {
        return $this->appEnv === AppEnv::production;
    }
}
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
class Config
{
    public const string AppEnv = 'AppEnv';
    public AppEnv $AppEnv;

    public function isProduction(): bool
    {
        return $this->AppEnv === AppEnv::production;
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

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
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

            $typeName = $this->resolvePropertyTypeShortName($stmt);
            if ($typeName === null) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $currentName = $this->getName($prop->name);
                if ($currentName === null || $currentName === $typeName) {
                    continue;
                }

                $renames[$currentName] = $typeName;
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

        if (! $nativeType instanceof TypeWithClassName) {
            return null;
        }

        $typeParts = explode('\\', $nativeType->getClassName());
        $expectedName = end($typeParts);

        if ($propName === $expectedName) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $node->name = new Identifier($expectedName);
        return $node;
    }

    private function refactorClassConstFetch(ClassConstFetch $node): ?ClassConstFetch
    {
        $constName = $this->getName($node->name);
        if ($constName === null) {
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

        if (! $nativeType instanceof TypeWithClassName) {
            return null;
        }

        $typeParts = explode('\\', $nativeType->getClassName());
        $expectedName = end($typeParts);

        if ($constName === $expectedName) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $node->name = new Identifier($expectedName);
        return $node;
    }

    private function addMessageComment(Node $node): ?Node
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return null;
            }
        }
        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $this->message));
        $node->setAttribute('comments', $comments);
        return $node;
    }

    private function resolvePropertyTypeShortName(Property $property): ?string
    {
        $type = $property->type;

        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        if (! $type instanceof Name) {
            return null;
        }

        return $type->getLast();
    }
}
