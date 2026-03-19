<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Scalar\String_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class MigrateAddCaseListToHeredocRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Migrate addCase attribute argument from inline numbered list string to a heredoc with each item on its own line',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[ClosedSet(Domain::foo, addCase: '1. Add enum case. 2. Create file. 3. Update docs.')]
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
#[ClosedSet(Domain::foo, addCase: <<<TEXT
    1. Add enum case.
    2. Create file.
    3. Update docs.
    TEXT)]
CODE_SAMPLE,
                    [
                        'mode' => 'auto',
                    ],
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Attribute::class];
    }

    /**
     * @param Attribute $node
     */
    public function refactor(Node $node): ?Node
    {
        $addCaseArgIndex = $this->findAddCaseArgIndex($node);
        if ($addCaseArgIndex === null) {
            return null;
        }

        $arg = $node->args[$addCaseArgIndex];

        if (! $arg->value instanceof String_) {
            return null;
        }

        $value = $arg->value->value;

        // Only transform if it looks like a numbered list (starts with digit+dot)
        if (! preg_match('/^\d+\./', $value)) {
            return null;
        }

        // Already a heredoc — skip
        $kind = $arg->value->getAttribute('kind');
        if ($kind === String_::KIND_HEREDOC || $kind === String_::KIND_NOWDOC) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $items = preg_split('/(?<=\.) (?=\d+\.)/', $value);
        if ($items === false || count($items) < 2) {
            return null;
        }

        $items = array_map('trim', $items);
        $content = '    ' . implode("\n    ", $items);

        $newString = new String_($content, [
            'kind' => String_::KIND_HEREDOC,
            'docLabel' => 'TEXT',
            'docIndentation' => 'flexible',
        ]);

        $arg->value = $newString;

        return $node;
    }

    private function findAddCaseArgIndex(Attribute $node): ?int
    {
        foreach ($node->args as $index => $arg) {
            if ($arg->name !== null && $arg->name->name === 'addCase') {
                return $index;
            }
        }

        return null;
    }

    private function addMessageComment(Node $node): ?Node
    {
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
}
