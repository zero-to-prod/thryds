<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidMagicStringArrayKeyRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above array items with magic string keys, prompting replacement with a class constant or enum',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$options = [
    'cache' => '/tmp',
];
$value = $_ENV['APP_ENV'];
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$options = [
    // TODO: [AI] Replace magic string 'cache' with a class constant or enum. Define a public const on the appropriate class with value 'cache', then reference it here.
    'cache' => '/tmp',
];
// TODO: [AI] Replace magic string 'APP_ENV' with a class constant or enum. Define a public const on the appropriate class with value 'APP_ENV', then reference it here.
$value = $_ENV['APP_ENV'];
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
        return [ArrayItem::class, Expression::class];
    }

    /**
     * @param ArrayItem|Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof ArrayItem) {
            return $this->refactorArrayItem($node);
        }

        if ($node instanceof Expression) {
            return $this->refactorExpression($node);
        }

        return null;
    }

    private function refactorArrayItem(ArrayItem $node): ?ArrayItem
    {
        if (!$node->key instanceof String_) {
            return null;
        }

        return $this->addTodoComment($node, $node->key->value);
    }

    private function refactorExpression(Expression $node): ?Expression
    {
        $magicStrings = [];
        $this->traverseNodesWithCallable($node->expr, function (Node $subNode) use (&$magicStrings): null {
            if ($subNode instanceof ArrayDimFetch && $subNode->dim instanceof String_) {
                $magicStrings[] = $subNode->dim->value;
            }

            return null;
        });

        if ($magicStrings === []) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), '[AI]')) {
                return null;
            }
        }

        $existingComments = $node->getComments();

        foreach (array_unique($magicStrings) as $keyValue) {
            array_unshift($existingComments, new Comment(
                "// TODO: [AI] Replace magic string '{$keyValue}' with a class constant or enum. Define a public const on the appropriate class with value '{$keyValue}', then reference it here."
            ));
        }

        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    /**
     * @template T of Node
     * @param T $node
     * @return T|null
     */
    private function addTodoComment(Node $node, string $keyValue): ?Node
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), '[AI]')) {
                return null;
            }
        }

        $todoComment = new Comment(
            "// TODO: [AI] Replace magic string '{$keyValue}' with a class constant or enum. Define a public const on the appropriate class with value '{$keyValue}', then reference it here."
        );

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
