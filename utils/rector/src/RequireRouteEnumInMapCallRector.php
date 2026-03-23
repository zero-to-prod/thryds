<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeFinder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireRouteEnumInMapCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = 'ZeroToProd\\Thryds\\Routes\\RouteList';

    /** @var string[] */
    private array $methods = ['map'];

    private int $argPosition = 1;

    private string $mode = 'warn';

    private string $message = "TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '%s' instead.";

    /** @var array<int, true> */
    private array $exemptExpressionIds = [];

    public function configure(array $configuration): void
    {
        if (isset($configuration['enumClass'])) {
            $this->enumClass = $configuration['enumClass'];
        }

        if (isset($configuration['methods'])) {
            $this->methods = $configuration['methods'];
        }

        if (isset($configuration['argPosition'])) {
            $this->argPosition = $configuration['argPosition'];
        }

        if (isset($configuration['mode'])) {
            $this->mode = $configuration['mode'];
        }

        if (isset($configuration['message'])) {
            $this->message = $configuration['message'];
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above Router->map() calls whose route pattern argument is not a Route enum ->value property fetch',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Router->map('GET', '/posts/{post}', $handler);
$Router->map('GET', SomeClass::PATTERN, $handler);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '/posts/{post}' instead.
$Router->map('GET', '/posts/{post}', $handler);
// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found 'SomeClass::PATTERN' instead.
$Router->map('GET', SomeClass::PATTERN, $handler);
CODE_SAMPLE,
                    [
                        'enumClass' => 'ZeroToProd\\Thryds\\Routes\\RouteList',
                        'methods' => ['map'],
                        'argPosition' => 1,
                        'mode' => 'warn',
                        'message' => "TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '%s' instead.",
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
        return [FileNode::class, Foreach_::class, Expression::class];
    }

    /**
     * @param FileNode|Foreach_|Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof FileNode) {
            $this->exemptExpressionIds = [];

            return null;
        }

        if ($node instanceof Foreach_) {
            if ($this->isForeachOverEnumCases($node)) {
                $nodeFinder = new NodeFinder();
                $expressions = $nodeFinder->findInstanceOf($node->stmts, Expression::class);
                foreach ($expressions as $expr) {
                    $this->exemptExpressionIds[spl_object_id($expr)] = true;
                }
            }

            return null;
        }

        if (isset($this->exemptExpressionIds[spl_object_id($node)])) {
            return null;
        }

        if (!$node->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $node->expr;

        if (!$this->isNames($methodCall->name, $this->methods)) {
            return null;
        }

        $args = $methodCall->getArgs();

        if (!isset($args[$this->argPosition])) {
            return null;
        }

        $patternArg = $args[$this->argPosition]->value;

        if ($this->isRouteEnumValue($patternArg)) {
            return null;
        }

        $displayValue = $this->resolveDisplayValue($patternArg);

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoText = str_contains($this->message, '%s')
            ? sprintf($this->message, $displayValue)
            : $this->message;

        $todoComment = new Comment('// ' . $todoText);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    private function isForeachOverEnumCases(Foreach_ $foreach): bool
    {
        $expr = $foreach->expr;

        if (!$expr instanceof StaticCall) {
            return false;
        }

        if (!$this->isName($expr->name, 'cases')) {
            return false;
        }

        $className = $this->getName($expr->class);
        $shortName = $this->resolveShortEnumClassName();

        return $className === $this->enumClass || $className === $shortName;
    }

    private function resolveShortEnumClassName(): string
    {
        $parts = explode('\\', $this->enumClass);

        return end($parts);
    }

    private function isRouteEnumValue(Node $node): bool
    {
        if (!$node instanceof PropertyFetch) {
            return false;
        }

        if (!$this->isName($node->name, 'value')) {
            return false;
        }

        if (!$node->var instanceof ClassConstFetch) {
            return false;
        }

        $className = $this->getName($node->var->class);

        return $className === $this->enumClass;
    }

    private function resolveDisplayValue(Node $node): string
    {
        if ($node instanceof String_) {
            return $node->value;
        }

        if ($node instanceof ClassConstFetch) {
            $className = $this->getName($node->class);
            $constName = $this->getName($node->name);
            if ($className !== null && $constName !== null) {
                return $className . '::' . $constName;
            }
        }

        return '(expression)';
    }
}
