<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Detects when the same Route enum case is registered with the same HTTP method more than once.
 */
final class ForbidDuplicateRouteRegistrationRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $methods = ['map'];

    private int $methodArgPosition = 0;

    private int $routeArgPosition = 1;

    private string $mode = 'warn';

    private string $message = "TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: '%s %s' was already registered above.";

    /**
     * Map of "HTTP_METHOD:enum_case_name" => line number of the first-seen occurrence (within current file).
     *
     * @var array<string, int>
     */
    private array $seenKeys = [];

    /** Tracks the file path for which seenKeys was last built. */
    private string $currentFilePath = '';

    public function configure(array $configuration): void
    {
        $this->methods = $configuration['methods'] ?? ['map'];
        $this->methodArgPosition = $configuration['methodArgPosition'] ?? 0;
        $this->routeArgPosition = $configuration['routeArgPosition'] ?? 1;
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: '%s %s' was already registered above.";
        $this->seenKeys = [];
        $this->currentFilePath = '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment when the same Route enum case is registered with the same HTTP method more than once',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Router->map('GET', Route::home->value, $handlerA);
$Router->map('GET', Route::home->value, $handlerB);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$Router->map('GET', Route::home->value, $handlerA);
// TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: 'GET home' was already registered above.
$Router->map('GET', Route::home->value, $handlerB);
CODE_SAMPLE,
                    [
                        'methods' => ['map'],
                        'methodArgPosition' => 0,
                        'routeArgPosition' => 1,
                        'mode' => 'warn',
                        'message' => "TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: '%s %s' was already registered above.",
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
        return [Expression::class];
    }

    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        // Reset per-file state when Rector moves to a new file.
        $filePath = $this->file->getFilePath();
        if ($filePath !== $this->currentFilePath) {
            $this->currentFilePath = $filePath;
            $this->seenKeys = [];
        }

        if (!$node->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $node->expr;

        if (!$this->isNames($methodCall->name, $this->methods)) {
            return null;
        }

        $args = $methodCall->getArgs();

        if (!isset($args[$this->methodArgPosition], $args[$this->routeArgPosition])) {
            return null;
        }

        $httpMethod = $this->resolveHttpMethod($args[$this->methodArgPosition]->value);
        if ($httpMethod === null) {
            return null;
        }

        $caseName = $this->resolveEnumCaseName($args[$this->routeArgPosition]->value);
        if ($caseName === null) {
            return null;
        }

        $key = strtoupper($httpMethod) . ':' . $caseName;
        $currentLine = $node->getStartLine();

        if (!isset($this->seenKeys[$key])) {
            // First time seeing this key in this file — record its line number.
            $this->seenKeys[$key] = $currentLine;
            return null;
        }

        if ($this->seenKeys[$key] === $currentLine) {
            // Same line as first-seen: this is the first occurrence (revisited on a later Rector pass).
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoText = '// ' . sprintf($this->message, strtoupper($httpMethod), $caseName);
        $existingComments = $node->getComments();
        array_unshift($existingComments, new Comment($todoText));
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    /**
     * Resolves the HTTP method string from a call argument.
     *
     * Supports plain strings ('GET') and enum->value property fetches (HTTP_METHOD::GET->value).
     */
    private function resolveHttpMethod(Node $valueNode): ?string
    {
        // Plain string: 'GET'
        if ($valueNode instanceof String_) {
            return $valueNode->value;
        }

        // Enum property fetch: HTTP_METHOD::GET->value
        if ($valueNode instanceof PropertyFetch) {
            $propertyName = $valueNode->name;
            if ($propertyName instanceof Identifier && $propertyName->name === 'value') {
                $innerExpr = $valueNode->var;
                if ($innerExpr instanceof ClassConstFetch) {
                    $caseName = $innerExpr->name;
                    if ($caseName instanceof Identifier) {
                        return $caseName->name;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolves the enum case name from a call argument.
     *
     * Supports: Route::home->value (PropertyFetch over ClassConstFetch).
     */
    private function resolveEnumCaseName(Node $valueNode): ?string
    {
        if (!$valueNode instanceof PropertyFetch) {
            return null;
        }

        $propertyName = $valueNode->name;
        if (!$propertyName instanceof Identifier || $propertyName->name !== 'value') {
            return null;
        }

        $innerExpr = $valueNode->var;
        if (!$innerExpr instanceof ClassConstFetch) {
            return null;
        }

        $caseName = $innerExpr->name;
        if (!$caseName instanceof Identifier) {
            return null;
        }

        return $caseName->name;
    }
}
