<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Type\MixedType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidArrayShapeReturnRector extends AbstractRector implements ConfigurableRectorInterface
{
    private int $minKeys = 2;

    private bool $skipPrivateMethods = false;

    private string $classSuffix = 'Result';

    private string $outputDir = '';

    private string $dataModelTrait = '';

    private bool $allowMixed = false;

    private string $mode = 'warn';

    private string $message = 'TODO: Replace array return with a typed class';

    public function __construct(
        private readonly BetterNodeFinder $betterNodeFinder,
        private readonly StaticTypeMapper $staticTypeMapper,
    ) {}

    public function configure(array $configuration): void
    {
        $this->minKeys = $configuration['minKeys'] ?? 2;
        $this->skipPrivateMethods = $configuration['skipPrivateMethods'] ?? false;
        $this->classSuffix = $configuration['classSuffix'] ?? 'Result';
        $this->outputDir = $configuration['outputDir'] ?? '';
        $this->dataModelTrait = $configuration['dataModelTrait'] ?? '';
        $this->allowMixed = $configuration['allowMixed'] ?? false;
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: Replace array return with a typed class';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace associative-array returns with a typed readonly DTO class',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class UserService {
    public function getProfile(int $id): array {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class UserService {
    public function getProfile(int $id): UserServiceGetProfileResult {
        return new UserServiceGetProfileResult(
            name: $this->name,
            email: $this->email,
        );
    }
}
CODE_SAMPLE,
                    [
                        'minKeys' => 2,
                        'classSuffix' => 'Result',
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
        return [Class_::class, Function_::class];
    }

    /**
     * @param Class_|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Function_) {
            return $this->refactorFunctionLike($node, '');
        }

        return $this->refactorClass($node);
    }

    private function refactorClass(Class_ $node): ?Class_
    {
        $className = $node->name?->name ?? '';
        $changed = false;

        foreach ($node->getMethods() as $method) {
            if ($this->refactorFunctionLike($method, $className) !== null) {
                $changed = true;
            }
        }

        return $changed ? $node : null;
    }

    private function refactorFunctionLike(ClassMethod|Function_ $node, string $ownerClassName): ?Node
    {
        if (!$this->hasArrayReturnType($node)) {
            return null;
        }

        if ($this->hasTodoComment($node)) {
            return null;
        }

        if ($this->skipPrivateMethods && $node instanceof ClassMethod && $node->isPrivate()) {
            return null;
        }

        $returns = $this->betterNodeFinder->findReturnsScoped($node);

        if ($returns === []) {
            return null;
        }

        // Verify all returns are array literals
        foreach ($returns as $return) {
            if ($return->expr === null || !$return->expr instanceof Array_) {
                $this->addTodoComment($node);
                return $node;
            }
        }

        // Collect keys from each return and verify consistency
        $allKeys = null;
        /** @var list<array<string, Node\Expr>> $allReturnMaps */
        $allReturnMaps = [];

        foreach ($returns as $return) {
            /** @var Array_ $array */
            $array = $return->expr;
            $keyMap = $this->collectStringKeyedValues($array);

            if ($keyMap === null) {
                $this->addTodoComment($node);
                return $node;
            }

            $keys = array_keys($keyMap);

            if ($allKeys === null) {
                $allKeys = $keys;
            } elseif ($keys !== $allKeys) {
                $this->addTodoComment($node);
                return $node;
            }

            $allReturnMaps[] = $keyMap;
        }

        if ($allKeys === null || count($allKeys) < $this->minKeys) {
            return null;
        }

        // Infer types from the first return's values
        $firstMap = $allReturnMaps[0];
        /** @var array<string, Node\ComplexType|Node\Identifier|Node\Name|null> $propertyTypes */
        $propertyTypes = [];

        foreach ($firstMap as $key => $valueExpr) {
            $phpStanType = $this->getType($valueExpr);

            if ($phpStanType instanceof MixedType) {
                if (!$this->allowMixed) {
                    $this->addTodoComment($node);
                    return $node;
                }
                $propertyTypes[$key] = new Identifier('mixed');
                continue;
            }

            $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($phpStanType, TypeKind::PROPERTY);
            if ($typeNode === null) {
                if (!$this->allowMixed) {
                    $this->addTodoComment($node);
                    return $node;
                }
                $propertyTypes[$key] = new Identifier('mixed');
                continue;
            }

            $propertyTypes[$key] = $typeNode;
        }

        // Build class name
        $dtoClassName = $this->buildClassName($node, $ownerClassName);

        // Generate the DTO class file
        $this->generateClassFile($dtoClassName, $propertyTypes);

        // Rewrite all return statements
        foreach ($returns as $index => $return) {
            $keyMap = $allReturnMaps[$index];

            $args = [];
            foreach ($keyMap as $key => $valueExpr) {
                $arg = new Arg($valueExpr);
                $arg->name = new Identifier($key);
                $args[] = $arg;
            }

            $return->expr = new New_(new Name($dtoClassName), $args);
        }

        // Rewrite return type
        $node->returnType = new Name($dtoClassName);

        return $node;
    }

    private function hasArrayReturnType(ClassMethod|Function_ $node): bool
    {
        if ($node->returnType === null) {
            return false;
        }

        return $this->isName($node->returnType, 'array');
    }

    /**
     * @return array<string, Node\Expr>|null  null when keys are dynamic
     */
    private function collectStringKeyedValues(Array_ $array): ?array
    {
        $result = [];

        foreach ($array->items as $item) {
            if ($item === null) {
                return null;
            }

            if ($item->unpack) {
                return null;
            }

            if (!$item->key instanceof String_) {
                return null;
            }

            $result[$item->key->value] = $item->value;
        }

        return $result;
    }

    private function buildClassName(ClassMethod|Function_ $node, string $ownerClassName): string
    {
        if ($node instanceof ClassMethod) {
            $methodName = (string) $node->name;

            return $ownerClassName . ucfirst($methodName) . $this->classSuffix;
        }

        $functionName = (string) $node->name;

        return ucfirst($functionName) . $this->classSuffix;
    }

    /**
     * @param array<string, Node\ComplexType|Node\Identifier|Node\Name|null> $propertyTypes
     */
    private function generateClassFile(string $className, array $propertyTypes): void
    {
        $dir = $this->outputDir;
        if ($dir === '' || !is_dir($dir)) {
            return;
        }

        $filePath = rtrim($dir, '/') . '/' . $className . '.php';

        if (file_exists($filePath)) {
            return;
        }

        if ($this->dataModelTrait !== '') {
            $this->generateDataModelClassFile($filePath, $className, $propertyTypes);
            return;
        }

        $this->generateReadonlyClassFile($filePath, $className, $propertyTypes);
    }

    /**
     * @param array<string, Node\ComplexType|Node\Identifier|Node\Name|null> $propertyTypes
     */
    private function generateReadonlyClassFile(string $filePath, string $className, array $propertyTypes): void
    {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'readonly class ' . $className;
        $lines[] = '{';
        $lines[] = '    public function __construct(';

        foreach ($propertyTypes as $key => $typeNode) {
            $typeStr = $typeNode !== null ? $this->typeNodeToString($typeNode) . ' ' : '';
            $lines[] = '        public ' . $typeStr . '$' . $key . ',';
        }

        $lines[] = '    ) {}';
        $lines[] = '}';
        $lines[] = '';

        file_put_contents($filePath, implode("\n", $lines));
    }

    /**
     * @param array<string, Node\ComplexType|Node\Identifier|Node\Name|null> $propertyTypes
     */
    private function generateDataModelClassFile(string $filePath, string $className, array $propertyTypes): void
    {
        $traitParts = explode('\\', $this->dataModelTrait);
        $traitShort = array_pop($traitParts);
        $traitNamespace = implode('\\', $traitParts);

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';

        if ($traitNamespace !== '') {
            $lines[] = 'use ' . $traitNamespace . '\\' . $traitShort . ';';
            $lines[] = '';
        }

        $lines[] = 'class ' . $className;
        $lines[] = '{';
        $lines[] = '    use ' . $traitShort . ';';

        foreach ($propertyTypes as $key => $typeNode) {
            $typeStr = $typeNode !== null ? $this->typeNodeToString($typeNode) . ' ' : '';
            $lines[] = '';
            $lines[] = '    public ' . $typeStr . '$' . $key . ';';
        }

        $lines[] = '}';
        $lines[] = '';

        file_put_contents($filePath, implode("\n", $lines));
    }

    private function typeNodeToString(Node\ComplexType|Node\Identifier|Node\Name $node): string
    {
        if ($node instanceof Node\Identifier) {
            return $node->name;
        }

        if ($node instanceof Node\Name) {
            return $node->toString();
        }

        if ($node instanceof Node\NullableType) {
            return '?' . $this->typeNodeToString($node->type);
        }

        if ($node instanceof Node\UnionType) {
            $parts = [];
            foreach ($node->types as $type) {
                $parts[] = $this->typeNodeToString($type);
            }
            return implode('|', $parts);
        }

        if ($node instanceof Node\IntersectionType) {
            $parts = [];
            foreach ($node->types as $type) {
                $parts[] = $this->typeNodeToString($type);
            }
            return implode('&', $parts);
        }

        return 'mixed';
    }

    private function hasTodoComment(Node $node): bool
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return true;
            }
        }

        return false;
    }

    private function addTodoComment(Node $node): void
    {
        if ($this->mode === 'auto') {
            return;
        }

        $todoComment = new Comment('// ' . $this->message);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);
    }
}
