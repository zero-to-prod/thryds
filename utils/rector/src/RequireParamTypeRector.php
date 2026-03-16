<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireParamTypeRector extends AbstractRector implements ConfigurableRectorInterface
{
    private bool $skipVariadic = true;

    private bool $skipClosures = false;

    private bool $useDocblocks = true;

    private string $todoMessage = 'TODO: Add param type';

    public function __construct(
        private readonly StaticTypeMapper $staticTypeMapper,
    ) {}

    public function configure(array $configuration): void
    {
        $this->skipVariadic = $configuration['skipVariadic'] ?? true;
        $this->skipClosures = $configuration['skipClosures'] ?? false;
        $this->useDocblocks = $configuration['useDocblocks'] ?? true;
        $this->todoMessage = $configuration['todoMessage'] ?? 'TODO: Add param type';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add type declarations to untyped parameters by inferring from defaults, docblocks, and usage',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
function paginate($page = 1, $perPage = 25) {
    return [];
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function paginate(int $page = 1, int $perPage = 25) {
    return [];
}
CODE_SAMPLE,
                    [
                        'skipVariadic' => true,
                        'useDocblocks' => true,
                    ]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Function_::class, Closure::class, ArrowFunction::class];
    }

    /**
     * @param Class_|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->skipClosures && ($node instanceof Closure || $node instanceof ArrowFunction)) {
            return null;
        }

        if ($node instanceof Class_) {
            return $this->refactorClass($node);
        }

        return $this->refactorFunctionLike($node, null);
    }

    private function refactorClass(Class_ $class): ?Class_
    {
        $changed = false;

        foreach ($class->getMethods() as $method) {
            $result = $this->refactorFunctionLike($method, $class);
            if ($result !== null) {
                $changed = true;
            }
        }

        return $changed ? $class : null;
    }

    private function refactorFunctionLike(
        ClassMethod|Function_|Closure|ArrowFunction $node,
        ?Class_ $class,
    ): ?Node {
        if ($node->params === []) {
            return null;
        }

        $changed = false;
        $untypedParamNames = [];

        foreach ($node->params as $param) {
            if ($param->type !== null) {
                continue;
            }

            if ($this->skipVariadic && $param->variadic) {
                continue;
            }

            $paramName = $this->getName($param->var);
            if ($paramName === null) {
                continue;
            }

            $type = $this->inferTypeFromDefault($param)
                ?? ($this->useDocblocks ? $this->inferTypeFromDocblock($node, $paramName) : null)
                ?? $this->inferTypeFromPropertyAssignment($node, $paramName, $class);

            if ($type !== null) {
                $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PARAM);
                if ($typeNode !== null) {
                    $param->type = $typeNode;
                    $changed = true;
                    continue;
                }
            }

            $untypedParamNames[] = $paramName;
        }

        if ($untypedParamNames !== []) {
            $this->addTodoComments($node, $untypedParamNames);
            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function inferTypeFromDefault(Param $param): ?Type
    {
        $default = $param->default;
        if ($default === null) {
            return null;
        }

        if ($default instanceof Node\Expr\ConstFetch) {
            $name = strtolower($this->getName($default) ?? '');
            if ($name === 'true' || $name === 'false') {
                return new \PHPStan\Type\BooleanType();
            }
            // null default alone is not enough to infer a type
            return null;
        }

        if ($default instanceof Int_) {
            return new \PHPStan\Type\IntegerType();
        }

        if ($default instanceof DNumber) {
            return new \PHPStan\Type\FloatType();
        }

        if ($default instanceof String_) {
            return new \PHPStan\Type\StringType();
        }

        if ($default instanceof Node\Expr\Array_) {
            return new \PHPStan\Type\ArrayType(new MixedType(), new MixedType());
        }

        return null;
    }

    private function inferTypeFromDocblock(Node $node, string $paramName): ?Type
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return null;
        }

        $text = $docComment->getText();
        if (!preg_match('/@param\s+(\S+)\s+\$' . preg_quote($paramName, '/') . '(?:\s|$)/', $text, $matches)) {
            return null;
        }

        $typeString = $matches[1];

        if ($typeString === 'mixed') {
            return null;
        }

        return $this->mapDocblockTypeString($typeString);
    }

    private function mapDocblockTypeString(string $typeString): ?Type
    {
        $nullable = false;
        if (str_starts_with($typeString, '?')) {
            $nullable = true;
            $typeString = substr($typeString, 1);
        }

        if (str_contains($typeString, '|')) {
            $parts = explode('|', $typeString);
            $types = [];
            $hasNull = false;
            foreach ($parts as $part) {
                $part = trim($part);
                if (strtolower($part) === 'null') {
                    $hasNull = true;
                    continue;
                }
                $mapped = $this->mapSimpleType($part);
                if ($mapped === null) {
                    return null;
                }
                $types[] = $mapped;
            }
            if ($types === []) {
                return null;
            }
            if ($hasNull) {
                $types[] = new NullType();
            }
            if (count($types) === 1) {
                return $types[0];
            }
            return new UnionType($types);
        }

        $mapped = $this->mapSimpleType($typeString);
        if ($mapped === null) {
            return null;
        }

        if ($nullable) {
            return new UnionType([$mapped, new NullType()]);
        }

        return $mapped;
    }

    private function mapSimpleType(string $type): ?Type
    {
        return match (strtolower($type)) {
            'int', 'integer' => new \PHPStan\Type\IntegerType(),
            'float', 'double' => new \PHPStan\Type\FloatType(),
            'string' => new \PHPStan\Type\StringType(),
            'bool', 'boolean' => new \PHPStan\Type\BooleanType(),
            'array' => new \PHPStan\Type\ArrayType(new MixedType(), new MixedType()),
            'callable' => new \PHPStan\Type\CallableType(),
            default => null,
        };
    }

    private function inferTypeFromPropertyAssignment(
        Node $node,
        string $paramName,
        ?Class_ $class,
    ): ?Type {
        if (!$node instanceof ClassMethod || $class === null) {
            return null;
        }

        $stmts = $node->stmts;
        if ($stmts === null) {
            return null;
        }

        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Node\Stmt\Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }

            $assign = $stmt->expr;
            if (!$assign->var instanceof PropertyFetch) {
                continue;
            }
            if (!$assign->expr instanceof Variable) {
                continue;
            }
            if ($this->getName($assign->expr) !== $paramName) {
                continue;
            }

            $propName = $this->getName($assign->var->name);
            if ($propName === null) {
                continue;
            }

            foreach ($class->getProperties() as $property) {
                if ($this->getName($property) !== $propName) {
                    continue;
                }
                if ($property->type === null) {
                    continue;
                }

                $propType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($property->type);
                if ($propType instanceof MixedType) {
                    continue;
                }

                return $propType;
            }
        }

        return null;
    }

    private function addTodoComments(Node $node, array $paramNames): void
    {
        $existingComments = $node->getComments();

        $newComments = [];
        foreach ($paramNames as $name) {
            $alreadyPresent = false;
            foreach ($existingComments as $comment) {
                if (str_contains($comment->getText(), $this->todoMessage . ' for $' . $name)) {
                    $alreadyPresent = true;
                    break;
                }
            }
            if (!$alreadyPresent) {
                $newComments[] = new Comment('// ' . $this->todoMessage . ' for $' . $name);
            }
        }

        if ($newComments === []) {
            return;
        }

        $allComments = array_merge($newComments, $existingComments);
        $node->setAttribute('comments', $allComments);
    }
}
