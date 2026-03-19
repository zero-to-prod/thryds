<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidHardcodedNamespacePrefixRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [ForbidHardcodedNamespacePrefixRector] Hardcoded namespace prefix should be passed in as configuration';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [ForbidHardcodedNamespacePrefixRector] Hardcoded namespace prefix should be passed in as configuration';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detects hardcoded namespace prefix strings concatenated with a variable and requires them to be passed in as configuration',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$fqcn = 'ZeroToProd\\Thryds\\Migrations\\' . $className;
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidHardcodedNamespacePrefixRector] Hardcoded namespace prefix should be passed in as configuration
$fqcn = 'ZeroToProd\\Thryds\\Migrations\\' . $className;
CODE_SAMPLE,
                    ['mode' => 'warn', 'message' => 'TODO: [ForbidHardcodedNamespacePrefixRector] Hardcoded namespace prefix should be passed in as configuration'],
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
        if (! $this->containsNamespacePrefixConcat($node)) {
            return null;
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $this->message));
        $node->setAttribute('comments', $comments);

        return $node;
    }

    private function containsNamespacePrefixConcat(Node $node): bool
    {
        $nodeFinder = new NodeFinder();
        $found = $nodeFinder->findFirst($node, fn(Node $n): bool => $n instanceof Concat
            && ($this->isNamespacePrefix($n->left) || $this->isNamespacePrefix($n->right)));

        return $found !== null;
    }

    private function isNamespacePrefix(Node $node): bool
    {
        if (! $node instanceof String_) {
            return false;
        }

        // A namespace prefix ends with a backslash and contains at least one segment separator
        return str_ends_with($node->value, '\\')
            && str_contains(rtrim($node->value, '\\'), '\\');
    }
}
