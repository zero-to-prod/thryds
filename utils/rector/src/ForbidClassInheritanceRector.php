<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidClassInheritanceRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [ForbidClassInheritanceRector] Inheritance couples classes to parent implementation — use PHP attributes and composition instead. See: utils/rector/docs/ForbidClassInheritanceRector.md';

    /** @var list<string> */
    private array $allowList = [
        'PHPUnit\Framework\TestCase',
        'Rector\Rector\AbstractRector',
        'Rector\Testing\PHPUnit\AbstractRectorTestCase',
        'PhpParser\NodeVisitorAbstract',
        'PhpParser\PrettyPrinter\Standard',
        'Symfony\Component\Console\Command\Command',
        'Symfony\Component\EventDispatcher\EventSubscriberInterface',
        'Exception',
        'RuntimeException',
        'LogicException',
        'InvalidArgumentException',
        'BadMethodCallException',
        'OverflowException',
        'UnderflowException',
        'OutOfRangeException',
        'DomainException',
        'LengthException',
        'RangeException',
        'UnexpectedValueException',
        'OutOfBoundsException',
    ];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;

        if (isset($configuration['allowList'])) {
            $this->allowList = array_merge($this->allowList, $configuration['allowList']);
        }
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if ($node->extends === null) {
            return null;
        }

        $parentName = $node->extends->toString();

        foreach ($this->allowList as $allowed) {
            $allowedShort = ltrim(strrchr($allowed, '\\') ?: $allowed, '\\');
            if ($parentName === $allowed || $parentName === $allowedShort) {
                return null;
            }
        }

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), '[ForbidClassInheritanceRector]')) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . $this->message);
        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag class inheritance — attributes and composition declare relationships explicitly without coupling to parent implementation',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class UserService extends BaseService
{
    public function find(int $id): User {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidClassInheritanceRector] Inheritance couples classes to parent implementation — use PHP attributes and composition instead. See: utils/rector/docs/ForbidClassInheritanceRector.md
class UserService extends BaseService
{
    public function find(int $id): User {}
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'allowList' => [],
                    ],
                ),
            ]
        );
    }
}
