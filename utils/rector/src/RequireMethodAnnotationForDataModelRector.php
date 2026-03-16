<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireMethodAnnotationForDataModelRector extends AbstractRector
{
    private const DATAMODEL_TRAITS = [
        'Zerotoprod\DataModel\DataModel',
        'ZeroToProd\Thryds\Helpers\DataModel',
    ];
    private const DESCRIBE_ATTRIBUTE = 'Describe';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add @method static self from() PHPDoc annotation to classes using the DataModel trait',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Zerotoprod\DataModel\DataModel;

class UserProfile
{
    use DataModel;

    public string $username;
    public int $age;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use Zerotoprod\DataModel\DataModel;

/**
 * @method static self from(array{username: string, age: int} $data)
 */
class UserProfile
{
    use DataModel;

    public string $username;
    public int $age;
}
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
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->usesDataModelTrait($node)) {
            return null;
        }

        $shape = $this->buildArrayShape($node);
        $expectedAnnotation = "@method static self from(array{{$shape}} \$data)";

        $docComment = $node->getDocComment();
        if ($docComment !== null && str_contains($docComment->getText(), '@method') && str_contains($docComment->getText(), 'from(')) {
            $existingText = $docComment->getText();
            if (str_contains($existingText, $expectedAnnotation)) {
                return null;
            }

            $newText = preg_replace(
                '/@method\s+static\s+self\s+from\([^)]*\)/',
                $expectedAnnotation,
                $existingText,
            );

            if ($newText === $existingText) {
                return null;
            }

            $node->setDocComment(new Doc($newText));

            return $node;
        }

        if ($shape === '') {
            return null;
        }

        if ($docComment !== null) {
            $existingText = $docComment->getText();
            $newText = preg_replace(
                '/\s*\*\/\s*$/',
                "\n * {$expectedAnnotation}\n */",
                $existingText,
            );
            $node->setDocComment(new Doc($newText));
        } else {
            $node->setDocComment(new Doc(
                "/**\n * {$expectedAnnotation}\n */"
            ));
        }

        return $node;
    }

    private function usesDataModelTrait(Class_ $node): bool
    {
        $className = $this->getName($node);
        if ($className !== null && $this->reflectionProvider->hasClass($className)) {
            $classReflection = $this->reflectionProvider->getClass($className);
            foreach (self::DATAMODEL_TRAITS as $trait) {
                if ($classReflection->hasTraitUse($trait)) {
                    return true;
                }
            }
        }

        foreach ($node->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $traitName = $this->getName($trait);
                if (in_array($traitName, self::DATAMODEL_TRAITS, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function buildArrayShape(Class_ $node): string
    {
        $entries = [];

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $propName = $this->getName($prop->name);
                if ($propName === null) {
                    continue;
                }

                $typeString = $this->resolvePropertyType($stmt);
                if ($typeString === null) {
                    continue;
                }

                $hasDefault = $this->hasDescribeDefault($stmt);
                $key = $hasDefault ? "{$propName}?" : $propName;

                $entries[] = "{$key}: {$typeString}";
            }
        }

        return implode(', ', $entries);
    }

    private function resolvePropertyType(Property $property): ?string
    {
        if ($property->type === null) {
            return null;
        }

        return $this->getName($property->type) ?? $this->printNodeType($property->type);
    }

    private function printNodeType(Node $node): string
    {
        if ($node instanceof Node\UnionType) {
            $types = array_map(fn(Node $type): string => $this->getName($type) ?? $this->printNodeType($type), $node->types);

            return implode('|', $types);
        }

        if ($node instanceof Node\IntersectionType) {
            $types = array_map(fn(Node $type): string => $this->getName($type) ?? $this->printNodeType($type), $node->types);

            return implode('&', $types);
        }

        if ($node instanceof Node\NullableType) {
            $inner = $this->getName($node->type) ?? $this->printNodeType($node->type);

            return "?{$inner}";
        }

        if ($node instanceof Node\Identifier) {
            return $node->toString();
        }

        return 'mixed';
    }

    private function hasDescribeDefault(Property $property): bool
    {
        foreach ($property->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $this->getName($attr->name);
                if ($attrName === null) {
                    continue;
                }

                $shortName = substr(strrchr($attrName, '\\') ?: "\\{$attrName}", 1);
                if ($shortName !== self::DESCRIBE_ATTRIBUTE) {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    if (!$arg->value instanceof Node\Expr\Array_) {
                        continue;
                    }

                    foreach ($arg->value->items as $item) {
                        if ($item === null || $item->key === null) {
                            continue;
                        }

                        if ($this->isDescribeDefaultKey($item->key)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function isDescribeDefaultKey(Node\Expr $key): bool
    {
        if ($key instanceof Node\Scalar\String_ && $key->value === 'default') {
            return true;
        }

        if ($key instanceof Node\Expr\ClassConstFetch) {
            return $this->isName($key->name, 'default');
        }

        return false;
    }
}
