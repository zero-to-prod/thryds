<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SuggestConstArrayToEnumRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [SuggestConstArrayToEnumRector] Enumerations define sets — migrate const arrays to a backed enum with #[ClosedSet] and #[Group] attributes. See: utils/rector/docs/SuggestConstArrayToEnumRector.md';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [SuggestConstArrayToEnumRector] Enumerations define sets — migrate const arrays to a backed enum with #[ClosedSet] and #[Group] attributes. See: utils/rector/docs/SuggestConstArrayToEnumRector.md';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Detect readonly classes with 2+ public const array constants whose values are lists of string literals, and suggest migrating to a backed enum with #[Group] attributes',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class DevFilter
{
    public const array dev_vendors = [
        '/vendor/phpunit/',
        '/vendor/phpstan/',
    ];

    public const array excluded_dirs = [
        '/var/cache/',
        '/tests/',
    ];
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [SuggestConstArrayToEnumRector] Enumerations define sets — migrate const arrays to a backed enum with #[ClosedSet] and #[Group] attributes. See: utils/rector/docs/SuggestConstArrayToEnumRector.md
readonly class DevFilter
{
    public const array dev_vendors = [
        '/vendor/phpunit/',
        '/vendor/phpstan/',
    ];

    public const array excluded_dirs = [
        '/var/cache/',
        '/tests/',
    ];
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'message' => 'TODO: [SuggestConstArrayToEnumRector] Enumerations define sets — migrate const arrays to a backed enum with #[ClosedSet] and #[Group] attributes. See: utils/rector/docs/SuggestConstArrayToEnumRector.md',
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

        if (!$node->isReadonly()) {
            return null;
        }

        if ($this->countQualifyingConstArrays($node) < 2) {
            return null;
        }

        return $this->addTodoComment($node);
    }

    private function countQualifyingConstArrays(Class_ $node): int
    {
        $count = 0;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst || !$stmt->isPublic()) {
                continue;
            }

            if (!$stmt->type instanceof Identifier || $stmt->type->name !== 'array') {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if (!$const->value instanceof Array_) {
                    continue;
                }

                if ($const->value->items === []) {
                    continue;
                }

                if (!$this->isStringLiteralList($const->value)) {
                    continue;
                }

                $count++;
            }
        }

        return $count;
    }

    private function isStringLiteralList(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if ($item === null) {
                return false;
            }

            if ($item->key !== null) {
                return false;
            }

            if (!$item->value instanceof String_) {
                return false;
            }
        }

        return true;
    }

    private function addTodoComment(Class_ $node): ?Class_
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
