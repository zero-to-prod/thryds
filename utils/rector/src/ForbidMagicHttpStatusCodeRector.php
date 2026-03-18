<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidMagicHttpStatusCodeRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const HTTP_STATUS_CODES = [
        100, 101,
        200, 201, 202, 203, 204, 205, 206,
        300, 301, 302, 303, 304, 307, 308,
        400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 422, 423, 424, 425, 426, 428, 429, 431, 451,
        500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511,
    ];

    /** @var string[] */
    private array $functionNames = ['response'];

    /** @var string[] */
    private array $methodNames = ['withStatus'];

    /** @var string[] */
    private array $newClassNames = ['Response', 'JsonResponse', 'RedirectResponse', 'HtmlResponse'];

    private string $mode = 'warn';

    private string $message = 'TODO: Replace %d with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: Replace %d with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md';

        if (isset($configuration['functionNames'])) {
            $this->functionNames = $configuration['functionNames'];
        }

        if (isset($configuration['methodNames'])) {
            $this->methodNames = $configuration['methodNames'];
        }

        if (isset($configuration['newClassNames'])) {
            $this->newClassNames = $configuration['newClassNames'];
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above response construction calls that pass a raw HTTP status code integer, prompting replacement with a named constant or enum case',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
return response('Not Found', 404);
return new Response('OK', 200);
return $response->withStatus(503);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: Replace 404 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return response('Not Found', 404);
// TODO: Replace 200 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return new Response('OK', 200);
// TODO: Replace 503 with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md
return $response->withStatus(503);
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                    ],
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Expression::class, Return_::class];
    }

    /**
     * @param Expression|Return_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        $expr = $node instanceof Return_ ? $node->expr : $node->expr;

        if ($expr === null) {
            return null;
        }

        $statusCode = $this->findMagicStatusCode($expr);

        if ($statusCode === null) {
            return null;
        }

        return $this->addTodoComment($node, $statusCode);
    }

    private function findMagicStatusCode(Node $expr): ?int
    {
        if ($expr instanceof FuncCall && $this->isNames($expr, $this->functionNames)) {
            return $this->findStatusCodeInArgs($expr->getArgs());
        }

        if ($expr instanceof MethodCall && $this->isNames($expr->name, $this->methodNames)) {
            return $this->findStatusCodeInArgs($expr->getArgs());
        }

        if ($expr instanceof New_) {
            $className = $this->getName($expr->class);
            if ($className !== null) {
                foreach ($this->newClassNames as $allowedClass) {
                    // Match short name or suffix
                    if ($className === $allowedClass || str_ends_with($className, '\\' . $allowedClass)) {
                        return $this->findStatusCodeInArgs($expr->getArgs());
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param Arg[] $args
     */
    private function findStatusCodeInArgs(array $args): ?int
    {
        foreach ($args as $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            if (!$arg->value instanceof Int_) {
                continue;
            }

            $value = $arg->value->value;

            if (in_array($value, self::HTTP_STATUS_CODES, true)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @template T of Node
     * @param T $node
     * @return T|null
     */
    private function addTodoComment(Node $node, int $statusCode): ?Node
    {
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . sprintf($this->message, $statusCode));

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
