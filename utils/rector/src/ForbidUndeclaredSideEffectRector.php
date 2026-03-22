<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidUndeclaredSideEffectRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [ForbidUndeclaredSideEffectRector] Side-effecting call must be inside a query class with #[InsertsInto], #[UpdatesIn], or #[DeletesFrom]. See: utils/rector/docs/ForbidUndeclaredSideEffectRector.md';

    private string $databaseClass = 'ZeroToProd\\Thryds\\Database';

    /** @var list<string> */
    private array $writeMethodNames = ['execute', 'insert'];

    /** @var list<string> */
    private array $allowedAttributes = ['InsertsInto', 'UpdatesIn', 'DeletesFrom', 'MigrationsSource', 'Infrastructure'];

    /** @var list<string> */
    private array $allowedClassSuffixes = ['Database'];

    /** @var list<string> */
    private array $variableNameFallbacks = ['Database', 'db', 'resolvedDb'];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->databaseClass = $configuration['databaseClass'] ?? $this->databaseClass;
        $this->writeMethodNames = $configuration['writeMethodNames'] ?? $this->writeMethodNames;
        $this->allowedAttributes = $configuration['allowedAttributes'] ?? $this->allowedAttributes;
        $this->allowedClassSuffixes = $configuration['allowedClassSuffixes'] ?? $this->allowedClassSuffixes;
        $this->variableNameFallbacks = $configuration['variableNameFallbacks'] ?? $this->variableNameFallbacks;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag side-effecting Database calls outside of properly-attributed query classes',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class SomeService {
    public function save(Database $Database): void {
        $Database->execute('INSERT INTO ...');
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidUndeclaredSideEffectRector] Side-effecting call must be inside a query class with #[InsertsInto], #[UpdatesIn], or #[DeletesFrom]. See: utils/rector/docs/ForbidUndeclaredSideEffectRector.md
class SomeService {
    public function save(Database $Database): void {
        $Database->execute('INSERT INTO ...');
    }
}
CODE_SAMPLE,
                    ['mode' => 'warn'],
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Trait_::class];
    }

    /**
     * @param Class_|Trait_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode !== 'warn') {
            return null;
        }

        if ($this->isAllowedByClassName(node: $node)) {
            return null;
        }

        if ($this->hasAllowedAttribute(node: $node)) {
            return null;
        }

        if (!$this->containsDatabaseWriteCall(node: $node)) {
            return null;
        }

        $marker = '[ForbidUndeclaredSideEffectRector]';
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

    private function isAllowedByClassName(Class_|Trait_ $node): bool
    {
        $name = $this->getName($node);
        if ($name === null) {
            return false;
        }

        foreach ($this->allowedClassSuffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function hasAllowedAttribute(Class_|Trait_ $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === null) {
                    continue;
                }
                foreach ($this->allowedAttributes as $allowed) {
                    if (str_ends_with($name, $allowed)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function containsDatabaseWriteCall(Class_|Trait_ $node): bool
    {
        $nodeFinder = new NodeFinder();
        $methodCalls = $nodeFinder->findInstanceOf($node, MethodCall::class);

        foreach ($methodCalls as $methodCall) {
            if (!$methodCall->name instanceof Identifier) {
                continue;
            }

            if (!in_array($methodCall->name->toString(), $this->writeMethodNames, true)) {
                continue;
            }

            if ($this->isDatabaseReceiver(methodCall: $methodCall)) {
                return true;
            }
        }

        return false;
    }

    private function isDatabaseReceiver(MethodCall $methodCall): bool
    {
        if ($this->isObjectType($methodCall->var, new ObjectType($this->databaseClass))) {
            return true;
        }

        $varName = $this->getName($methodCall->var);
        if ($varName === null) {
            return false;
        }

        return in_array($varName, $this->variableNameFallbacks, true);
    }
}
