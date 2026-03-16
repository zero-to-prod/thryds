<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PHPStanStaticTypeMapper\Enum\TypeKind;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireReturnTypeRector extends AbstractRector implements ConfigurableRectorInterface
{
    private bool $skipMagicMethods = true;

    private bool $skipClosures = false;

    private string $todoMessage = 'TODO: Add return type';

    private const MAGIC_METHODS = [
        '__construct',
        '__destruct',
        '__call',
        '__callStatic',
        '__get',
        '__set',
        '__isset',
        '__unset',
        '__sleep',
        '__wakeup',
        '__serialize',
        '__unserialize',
        '__toString',
        '__invoke',
        '__set_state',
        '__clone',
        '__debugInfo',
    ];

    public function __construct(
        private readonly BetterNodeFinder $betterNodeFinder,
        private readonly StaticTypeMapper $staticTypeMapper,
        private readonly TypeFactory $typeFactory,
    ) {}

    public function configure(array $configuration): void
    {
        $this->skipMagicMethods = $configuration['skipMagicMethods'] ?? true;
        $this->skipClosures = $configuration['skipClosures'] ?? false;
        $this->todoMessage = $configuration['todoMessage'] ?? 'TODO: Add return type';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add return type declarations by inferring from the AST, or a TODO comment when inference fails',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
function createUser(string $name) {
    return new User($name);
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
function createUser(string $name): User {
    return new User($name);
}
CODE_SAMPLE,
                    [
                        'skipMagicMethods' => true,
                        'skipClosures' => false,
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
        return [ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }

    /**
     * @param ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->returnType !== null) {
            return null;
        }

        if ($this->shouldSkip($node)) {
            return null;
        }

        if ($this->hasTodoComment($node)) {
            return null;
        }

        if ($node instanceof ArrowFunction) {
            return $this->refactorArrowFunction($node);
        }

        return $this->refactorFunctionLike($node);
    }

    private function shouldSkip(ClassMethod|Function_|Closure|ArrowFunction $node): bool
    {
        if ($this->skipClosures && ($node instanceof Closure || $node instanceof ArrowFunction)) {
            return true;
        }

        if ($this->skipMagicMethods && $node instanceof ClassMethod) {
            $name = $this->getName($node);
            if ($name !== null && in_array($name, self::MAGIC_METHODS, true)) {
                return true;
            }
        }

        return false;
    }

    private function hasTodoComment(Node $node): bool
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->todoMessage)) {
                return true;
            }
        }

        return false;
    }

    private function refactorArrowFunction(ArrowFunction $node): ?ArrowFunction
    {
        $exprType = $this->getType($node->expr);

        if ($exprType instanceof MixedType) {
            $this->addTodoComment($node);
            return $node;
        }

        $returnTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($exprType, TypeKind::RETURN);
        if ($returnTypeNode === null) {
            $this->addTodoComment($node);
            return $node;
        }

        $node->returnType = $returnTypeNode;
        return $node;
    }

    private function refactorFunctionLike(ClassMethod|Function_|Closure $node): ?Node
    {
        $returns = $this->betterNodeFinder->findReturnsScoped($node);

        // No return statements and not abstract → void
        if ($returns === []) {
            if ($node instanceof ClassMethod && $node->isAbstract()) {
                return null;
            }

            $node->returnType = new Node\Identifier('void');
            return $node;
        }

        // Check if all returns are bare (return;) → void
        $allBare = true;
        foreach ($returns as $return) {
            if ($return->expr !== null) {
                $allBare = false;
                break;
            }
        }

        if ($allBare) {
            $node->returnType = new Node\Identifier('void');
            return $node;
        }

        $types = [];
        foreach ($returns as $return) {
            if ($return->expr === null) {
                // Bare return mixed with value returns → treat as returning null
                $types[] = new NullType();
                continue;
            }

            $returnType = $this->getType($return->expr);

            if ($returnType instanceof MixedType) {
                $this->addTodoComment($node);
                return $node;
            }

            $types[] = $returnType;
        }

        if ($types === []) {
            return null;
        }

        $resultType = $this->typeFactory->createMixedPassedOrUnionType($types);

        if ($resultType instanceof MixedType) {
            $this->addTodoComment($node);
            return $node;
        }

        $returnTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($resultType, TypeKind::RETURN);
        if ($returnTypeNode === null) {
            $this->addTodoComment($node);
            return $node;
        }

        $node->returnType = $returnTypeNode;
        return $node;
    }

    private function addTodoComment(Node $node): void
    {
        $todoComment = new Comment('// ' . $this->todoMessage);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);
    }
}
