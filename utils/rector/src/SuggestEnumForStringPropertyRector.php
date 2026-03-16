<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SuggestEnumForStringPropertyRector extends AbstractRector
{
    private const DATAMODEL_TRAITS = [
        'Zerotoprod\DataModel\DataModel',
        'ZeroToProd\Thryds\Helpers\DataModel',
    ];

    private const DESCRIBE_ATTRS = [
        'Zerotoprod\DataModel\Describe',
        'ZeroToProd\Thryds\Helpers\Describe',
    ];

    private const TODO_MARKER = '[SuggestEnumForStringPropertyRector]';
    private const CALL_SITE_MARKER = '[SuggestEnumForStringPropertyRector]';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detect string properties on DataModel classes that should likely be enums, and add a TODO comment with detected values',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class Config
{
    use DataModel;

    #[Describe(['default' => 'production'])]
    public string $appEnv;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class Config
{
    use DataModel;

    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $appEnv has values: 'production'. Extract to a backed enum.
    #[Describe(['default' => 'production'])]
    public string $appEnv;
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
        return [Class_::class, StaticCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof StaticCall) {
            return $this->refactorStaticCall($node);
        }

        if (!$node instanceof Class_) {
            return null;
        }

        if (!$this->usesDataModelTrait($node)) {
            return null;
        }

        $hasChanged = false;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }

            if (!$this->isStringType($stmt)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $propName = $this->getName($prop->name);
                if ($propName === null) {
                    continue;
                }

                if ($this->hasExistingTodoComment($stmt)) {
                    continue;
                }

                $values = $this->collectKnownValues($stmt, $propName, $node);

                if ($values === []) {
                    continue;
                }

                $this->addTodoComment($stmt, $propName, $values);
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

        if (!$classReflection->hasTraitUse('Zerotoprod\DataModel\DataModel')
            && !$classReflection->hasTraitUse('ZeroToProd\Thryds\Helpers\DataModel')) {
            return null;
        }

        if ($node->args === []) {
            return null;
        }

        $firstArg = $node->args[0];
        if (!$firstArg instanceof Node\Arg || !$firstArg->value instanceof Array_) {
            return null;
        }

        $hasChanged = false;

        foreach ($firstArg->value->items as $item) {
            if ($item === null) {
                continue;
            }

            $propName = $this->resolvePropertyName($item, $classReflection->getName());
            if ($propName === null) {
                continue;
            }

            if (!$classReflection->hasNativeProperty($propName)) {
                continue;
            }

            $nativeType = $classReflection->getNativeProperty($propName)->getNativeType();
            if (!$nativeType instanceof \PHPStan\Type\StringType) {
                continue;
            }

            $stringLiterals = $this->extractStringLiteralsFromValue($item->value);
            if ($stringLiterals === []) {
                continue;
            }

            if ($this->hasCallSiteTodoComment($item)) {
                continue;
            }

            $quotedValues = array_map(static fn(string $v): string => "'{$v}'", $stringLiterals);
            $valuesStr = implode(', ', $quotedValues);

            $comment = new Comment(
                "// TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. {$valuesStr} is a value of {$this->resolveShortClassName($className)}::\${$propName}. Replace with enum case."
            );

            $existingComments = $item->getComments();
            array_unshift($existingComments, $comment);
            $item->setAttribute('comments', $existingComments);
            $hasChanged = true;
        }

        if (!$hasChanged) {
            return null;
        }

        return $node;
    }

    private function resolvePropertyName(ArrayItem $item, string $className): ?string
    {
        if ($item->key instanceof String_) {
            return $item->key->value;
        }

        if ($item->key instanceof ClassConstFetch) {
            $constClass = $this->getName($item->key->class);
            $constName = $this->getName($item->key->name);
            if ($constClass === null || $constName === null) {
                return null;
            }

            if (!$this->reflectionProvider->hasClass($constClass)) {
                return null;
            }

            $classReflection = $this->reflectionProvider->getClass($constClass);
            if (!$classReflection->hasConstant($constName)) {
                return null;
            }

            $valueExpr = $classReflection->getConstant($constName)->getValueExpr();
            if ($valueExpr instanceof String_) {
                return $valueExpr->value;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extractStringLiteralsFromValue(Node\Expr $expr): array
    {
        $values = [];

        if ($expr instanceof String_) {
            $values[] = $expr->value;
        }

        if ($expr instanceof Coalesce && $expr->right instanceof String_) {
            $values[] = $expr->right->value;
        }

        return $values;
    }

    private function hasCallSiteTodoComment(ArrayItem $item): bool
    {
        foreach ($item->getComments() as $comment) {
            if (str_contains($comment->getText(), self::CALL_SITE_MARKER)) {
                return true;
            }
        }

        return false;
    }

    private function resolveShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    private function usesDataModelTrait(Class_ $node): bool
    {
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

    private function isStringType(Property $property): bool
    {
        $type = $property->type;

        if ($type === null) {
            return false;
        }

        if ($type instanceof NullableType) {
            return false;
        }

        if (!$type instanceof Identifier) {
            return false;
        }

        return $type->name === 'string';
    }

    private function hasExistingTodoComment(Property $property): bool
    {
        foreach ($property->getComments() as $comment) {
            if (str_contains($comment->getText(), self::TODO_MARKER)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function collectKnownValues(Property $property, string $propName, Class_ $class): array
    {
        $values = [];

        $values = array_merge($values, $this->extractDescribeDefault($property));
        $values = array_merge($values, $this->extractComparisonValues($propName, $class));

        $values = array_unique($values);
        sort($values);

        return array_values($values);
    }

    /**
     * @return list<string>
     */
    private function extractDescribeDefault(Property $property): array
    {
        $values = [];

        foreach ($property->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $this->getName($attr->name);
                if (!in_array($attrName, self::DESCRIBE_ATTRS, true)) {
                    continue;
                }

                if (!isset($attr->args[0])) {
                    continue;
                }

                $arg = $attr->args[0]->value;
                if (!$arg instanceof Node\Expr\Array_) {
                    continue;
                }

                foreach ($arg->items as $item) {
                    if ($item === null) {
                        continue;
                    }

                    if ($item->key instanceof String_
                        && $item->key->value === 'default'
                        && $item->value instanceof String_) {
                        $values[] = $item->value->value;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function extractComparisonValues(string $propName, Class_ $class): array
    {
        $values = [];

        $this->traverseNodesWithCallable($class->stmts, function (Node $node) use ($propName, &$values): void {
            if ($node instanceof Identical || $node instanceof NotIdentical) {
                $left = $node->left;
                $right = $node->right;

                if ($right instanceof String_ && $this->isPropertyReference($left, $propName)) {
                    $values[] = $right->value;
                } elseif ($left instanceof String_ && $this->isPropertyReference($right, $propName)) {
                    $values[] = $left->value;
                }
            }

            if ($node instanceof Coalesce) {
                if ($node->left instanceof ArrayDimFetch
                    && $node->left->dim instanceof String_
                    && $node->left->dim->value === $propName
                    && $node->right instanceof String_) {
                    $values[] = $node->right->value;
                }
            }
        });

        return $values;
    }

    private function isPropertyReference(Node $node, string $propName): bool
    {
        if ($node instanceof ArrayDimFetch
            && $node->dim instanceof String_
            && $node->dim->value === $propName) {
            return true;
        }

        if ($node instanceof PropertyFetch
            && $node->name instanceof Identifier
            && $node->name->name === $propName) {
            return true;
        }

        // Also unwrap coalesce: ($context['prop'] ?? 'fallback') === 'value'
        if ($node instanceof Coalesce) {
            return $this->isPropertyReference($node->left, $propName);
        }

        return false;
    }

    /**
     * @param list<string> $values
     */
    private function addTodoComment(Property $property, string $propName, array $values): void
    {
        $quotedValues = array_map(static fn(string $v): string => "'{$v}'", $values);
        $valuesStr = implode(', ', $quotedValues);

        $text = "// TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. \${$propName} has values: {$valuesStr}. Extract to a backed enum.";

        $comment = new Comment($text);
        $existingComments = $property->getComments();
        array_unshift($existingComments, $comment);
        $property->setAttribute('comments', $existingComments);
    }
}
