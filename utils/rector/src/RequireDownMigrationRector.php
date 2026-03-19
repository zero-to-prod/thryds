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

final class RequireDownMigrationRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback. See: utils/rector/docs/RequireDownMigrationRector.md';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flags migration classes that are missing a down() rollback method',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable implements MigrationInterface
{
    public function up(Database $Database): void
    {
        $Database->execute('CREATE TABLE users (id INT)');
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback. See: utils/rector/docs/RequireDownMigrationRector.md
#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable implements MigrationInterface
{
    public function up(Database $Database): void
    {
        $Database->execute('CREATE TABLE users (id INT)');
    }
}
CODE_SAMPLE,
                    ['mode' => 'warn'],
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /** @param Class_ $node */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode !== 'warn') {
            return null;
        }

        if (!$this->hasMigrationAttribute(node: $node)) {
            return null;
        }

        if ($this->hasDownMethod(node: $node)) {
            return null;
        }

        $marker = '[RequireDownMigrationRector]';
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $existing = $node->getComments();
        array_unshift($existing, new Comment('// ' . $this->message));
        $node->setAttribute('comments', $existing);

        return $node;
    }

    private function hasMigrationAttribute(Class_ $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name !== null && str_ends_with($name, 'Migration')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasDownMethod(Class_ $node): bool
    {
        foreach ($node->getMethods() as $method) {
            if ($this->getName($method->name) === 'down') {
                return true;
            }
        }

        return false;
    }
}
