<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\UseUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ReplaceFullyQualifiedNameRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var array<string, string> */
    private array $nameMap = [];

    private string $mode = 'auto';

    private string $message = '';

    /**
     * @param array<string, string> $configuration
     */
    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
        $this->nameMap = $configuration['replacements'] ?? $configuration;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace fully qualified class/trait names with configured replacements', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
use Zerotoprod\DataModel\DataModel;

class Config
{
    use DataModel;
}
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
use App\Helpers\DataModel;

class Config
{
    use DataModel;
}
CODE_SAMPLE,
                [
                    'Zerotoprod\DataModel\DataModel' => 'App\Helpers\DataModel',
                ]
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [UseUse::class];
    }

    /**
     * @param UseUse $node
     */
    public function refactor(Node $node): ?UseUse
    {
        $currentName = $node->name->toString();

        if (!isset($this->nameMap[$currentName])) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $node->name = new Name($this->nameMap[$currentName]);

        return $node;
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
