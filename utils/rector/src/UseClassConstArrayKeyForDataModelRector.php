<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class UseClassConstArrayKeyForDataModelRector extends AbstractRector
{
    private const DATAMODEL_TRAIT = 'Zerotoprod\DataModel\DataModel';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add class constants for DataModel properties and replace string array keys in ::from() calls',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Zerotoprod\DataModel\DataModel;

class Config
{
    use DataModel;

    public string $appEnv;
}

$Config = Config::from([
    'appEnv' => 'production',
]);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use Zerotoprod\DataModel\DataModel;

class Config
{
    use DataModel;

    /** @see $appEnv */
    public const string appEnv = 'appEnv';
    public string $appEnv;
}

$Config = Config::from([
    Config::appEnv => 'production',
]);
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, StaticCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Class_) {
            return $this->refactorClass($node);
        }

        if ($node instanceof StaticCall) {
            return $this->refactorStaticCall($node);
        }

        return null;
    }

    private function refactorClass(Class_ $node): ?Class_
    {
        if (!$this->usesDataModelTrait($node)) {
            return null;
        }

        $existingConstants = $this->getExistingConstantNames($node);
        $hasChanged = false;

        foreach ($node->stmts as $index => $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $propName = $this->getName($prop->name);
                if ($propName === null) {
                    continue;
                }

                if (in_array($propName, $existingConstants, true)) {
                    continue;
                }

                $const = $this->createConstForProperty($propName);
                array_splice($node->stmts, $index, 0, [$const]);
                $existingConstants[] = $propName;
                $hasChanged = true;
            }
        }

        if (!$hasChanged) {
            return null;
        }

        return $node;
    }

    private function refactorStaticCall(StaticCall $node): ?StaticCall
    {
        if (!$this->isName($node->name, 'from')) {
            return null;
        }

        $className = $this->getName($node->class);
        if ($className === null) {
            return null;
        }

        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (!$classReflection->hasTraitUse(self::DATAMODEL_TRAIT)) {
            return null;
        }

        if ($node->args === []) {
            return null;
        }

        $firstArg = $node->args[0];
        if (!$firstArg instanceof Node\Arg) {
            return null;
        }

        if (!$firstArg->value instanceof Array_) {
            return null;
        }

        $array = $firstArg->value;
        $hasChanged = false;

        foreach ($array->items as $item) {
            if ($item === null) {
                continue;
            }

            if (!$item->key instanceof String_) {
                continue;
            }

            $keyString = $item->key->value;

            if (!$this->classHasProperty($classReflection, $keyString)) {
                continue;
            }

            $item->key = $this->nodeFactory->createClassConstFetch($className, $keyString);
            $hasChanged = true;
        }

        if (!$hasChanged) {
            return null;
        }

        return $node;
    }

    private function usesDataModelTrait(Class_ $node): bool
    {
        foreach ($node->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $traitName = $this->getName($trait);
                if ($traitName === self::DATAMODEL_TRAIT) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getExistingConstantNames(Class_ $node): array
    {
        $names = [];

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                $names[] = $this->getName($const->name);
            }
        }

        return $names;
    }

    private function createConstForProperty(string $propName): ClassConst
    {
        $const = new Node\Const_(
            new Identifier($propName),
            new String_($propName),
        );

        $classConst = new ClassConst([$const], Class_::MODIFIER_PUBLIC);
        $classConst->type = new Identifier('string');

        $classConst->setDocComment(new \PhpParser\Comment\Doc(
            '/** @see $' . $propName . ' */'
        ));

        return $classConst;
    }

    private function classHasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $classReflection->hasNativeProperty($propertyName);
    }
}
