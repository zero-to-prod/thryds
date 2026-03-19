<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Flags controller classes that import a Tables-namespace class but lack
 * a matching #[Persists(TableClass::class)] attribute, so the inventory
 * graph always has a persistence edge for every write path.
 */
final class RequirePersistsOnTableReferenceRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $tablesNamespace = '';

    private string $attributeClass = 'Persists';

    private string $controllersNamespace = '';

    private string $mode = 'warn';

    private string $message = "TODO: [RequirePersistsOnTableReferenceRector] '%s' imports '%s' from the tables namespace but is missing #[Persists(%s::class)]. Add it so the inventory graph shows the persistence edge. See: utils/rector/docs/RequirePersistsOnTableReferenceRector.md";

    public function configure(array $configuration): void
    {
        $this->tablesNamespace       = $configuration['tablesNamespace'] ?? '';
        $this->attributeClass        = $configuration['attributeClass'] ?? 'Persists';
        $this->controllersNamespace  = $configuration['controllersNamespace'] ?? '';
        $this->mode                  = $configuration['mode'] ?? 'warn';
        $this->message               = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[Persists(TableClass::class)] on any controller class that imports a class from the tables namespace',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
namespace App\Controllers;

use App\Tables\User;

class RegisterController
{
    public function __invoke(): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
namespace App\Controllers;

use App\Tables\User;

// TODO: [RequirePersistsOnTableReferenceRector] 'RegisterController' imports 'User' from the tables namespace but is missing #[Persists(User::class)].
class RegisterController
{
    public function __invoke(): void {}
}
CODE_SAMPLE,
                    [
                        'tablesNamespace'      => 'App\\Tables',
                        'attributeClass'       => 'App\\Attributes\\Persists',
                        'controllersNamespace' => 'App\\Controllers',
                        'mode'                 => 'warn',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [FileNode::class];
    }

    /**
     * @param FileNode $node
     */
    public function refactor(Node $node): ?Node
    {
        $namespaceStmt = null;
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                $namespaceStmt = $stmt;
                break;
            }
        }

        $stmts         = $namespaceStmt ? $namespaceStmt->stmts : $node->stmts;
        $fileNamespace = $namespaceStmt?->name?->toString() ?? '';

        // Build short-name → FQCN map from file-level use statements.
        $useMap = [];
        foreach ($stmts as $stmt) {
            if (! $stmt instanceof Use_ || $stmt->type !== Use_::TYPE_NORMAL) {
                continue;
            }
            foreach ($stmt->uses as $use) {
                $alias          = $use->alias?->name ?? $use->name->getLast();
                $useMap[$alias] = $use->name->toString();
            }
        }

        // Collect table imports: shortName → FQCN.
        $tableImports = [];
        foreach ($useMap as $shortName => $fqcn) {
            if ($this->tablesNamespace !== '' && str_starts_with($fqcn, $this->tablesNamespace . '\\')) {
                $tableImports[$shortName] = $fqcn;
            }
        }

        if ($tableImports === []) {
            return null;
        }

        $changed = false;

        $this->traverseNodesWithCallable($stmts, function (Node $inner) use ($tableImports, $fileNamespace, &$changed): null {
            if (! $inner instanceof Class_) {
                return null;
            }

            // Use the declared short name, not the rector-resolved FQCN.
            $className = (string) $inner->name;

            // Restrict to configured namespace when set.
            if ($this->controllersNamespace !== '') {
                $classFqcn = $fileNamespace !== '' ? $fileNamespace . '\\' . $className : $className;
                if (! str_starts_with($classFqcn, $this->controllersNamespace . '\\')) {
                    return null;
                }
            }

            // Compare by short name — avoids FQCN resolution variance on ::class args.
            $declaredShortNames = $this->getDeclaredPersistsShortNames($inner);

            foreach ($tableImports as $shortName => $tableFqcn) {
                if (in_array($shortName, $declaredShortNames, true)) {
                    if ($this->removeStaleComment($inner, $shortName)) {
                        $changed = true;
                    }
                    continue;
                }

                if ($this->mode === 'warn') {
                    $this->addTodoComment($inner, $className, $shortName, $changed);
                }
            }

            return null;
        });

        return $changed ? $node : null;
    }

    /**
     * Returns the short class names declared in existing #[Persists(...)] attributes.
     *
     * Compares short names rather than FQCNs to avoid variance in how PHP-Parser
     * resolves ::class expressions inside attribute arguments across environments.
     *
     * @return string[]
     */
    private function getDeclaredPersistsShortNames(Class_ $node): array
    {
        $parts     = explode('\\', $this->attributeClass);
        $shortAttr = end($parts);
        $names     = [];

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name           = $this->getName($attr->name) ?? '';
                $nameParts      = explode('\\', $name);
                $resolvedShort  = end($nameParts);
                if ($name !== $this->attributeClass && $name !== $shortAttr && $resolvedShort !== $shortAttr) {
                    continue;
                }
                if (! isset($attr->args[0])) {
                    continue;
                }
                $arg = $attr->args[0]->value;
                if ($arg instanceof ClassConstFetch) {
                    $resolved = $this->getName($arg->class);
                    if ($resolved !== null) {
                        $segments = explode('\\', $resolved);
                        $names[]  = end($segments);
                    }
                }
            }
        }

        return $names;
    }

    private function addTodoComment(Class_ $node, string $className, string $shortTableName, bool &$changed): void
    {
        $todoText = sprintf($this->message, $className, $shortTableName, $shortTableName);
        $marker   = '// ' . $todoText;

        foreach ($node->getComments() as $comment) {
            if ($comment->getText() === $marker) {
                return;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment($marker));
        $node->setAttribute('comments', $comments);
        $changed = true;
    }

    /** Returns true when a stale comment was removed. */
    private function removeStaleComment(Class_ $node, string $shortTableName): bool
    {
        $comments = $node->getComments();
        $filtered = array_values(array_filter(
            $comments,
            fn(Comment $c): bool => ! str_contains($c->getText(), "'{$shortTableName}'"),
        ));

        if (count($filtered) === count($comments)) {
            return false;
        }

        $node->setAttribute('comments', $filtered);

        return true;
    }
}
