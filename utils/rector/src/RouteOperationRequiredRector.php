<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Flags Route enum cases missing #[RouteOperation] so the inventory graph
 * always has HTTP methods for every route.
 */
final class RouteOperationRequiredRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = '';

    private string $attributeClass = 'RouteOperation';

    private string $mode = 'warn';

    private string $message = "TODO: [RouteOperationRequiredRector] Route case '%s' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route. See: utils/rector/docs/RouteOperationRequiredRector.md";

    public function configure(array $configuration): void
    {
        $this->enumClass = $configuration['enumClass'] ?? '';
        $this->attributeClass = $configuration['attributeClass'] ?? 'RouteOperation';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require at least one #[RouteOperation] on every Route enum case so the inventory graph can emit HTTP methods',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
enum Route: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page', info: 'Home')]
    case home = '/';

    case about = '/about';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
enum Route: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page', info: 'Home')]
    case home = '/';

    // TODO: [RouteOperationRequiredRector] Route case 'about' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route. See: utils/rector/docs/RouteOperationRequiredRector.md
    case about = '/about';
}
CODE_SAMPLE,
                    [
                        'enumClass'      => 'ZeroToProd\\Thryds\\Routes\\RouteList',
                        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\Route',
                        'mode'           => 'warn',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Enum_::class];
    }

    /**
     * @param Enum_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $fqcn = $node->namespacedName !== null
            ? $node->namespacedName->toString()
            : ($this->getName($node) ?? '');

        if ($this->enumClass !== '' && $fqcn !== $this->enumClass) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;
        $changed = false;

        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof EnumCase) {
                continue;
            }

            $hasAttribute = $this->caseHasAttribute($stmt);

            if ($hasAttribute) {
                // Remove stale TODO if the attribute has since been added.
                $comments = $stmt->getComments();
                $filtered = array_values(array_filter(
                    $comments,
                    static fn(Comment $c): bool => ! str_contains($c->getText(), $marker)
                ));

                if (count($filtered) !== count($comments)) {
                    $stmt->setAttribute('comments', $filtered);
                    $changed = true;
                }

                continue;
            }

            // Skip if already annotated.
            foreach ($stmt->getComments() as $comment) {
                if (str_contains($comment->getText(), $marker)) {
                    continue 2;
                }
            }

            $caseName = $this->getName($stmt) ?? '';
            $todoText = str_contains($this->message, '%s')
                ? sprintf($this->message, $caseName)
                : $this->message;

            $existingComments = $stmt->getComments();
            array_unshift($existingComments, new Comment('// ' . $todoText));
            $stmt->setAttribute('comments', $existingComments);
            $changed = true;
        }

        return $changed ? $node : null;
    }

    private function caseHasAttribute(EnumCase $case): bool
    {
        $parts = explode('\\', $this->attributeClass);
        $shortName = end($parts);

        foreach ($case->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $this->attributeClass || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }
}
