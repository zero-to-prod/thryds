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
 * Flags Route enum cases missing #[RouteInfo] so the inventory graph
 * always has a description for every route.
 */
final class RouteInfoRequiredRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = '';

    private string $attributeClass = 'RouteInfo';

    private string $mode = 'warn';

    private string $message = "TODO: [RouteInfoRequiredRector] Route case '%s' must declare #[RouteInfo] so the inventory graph can emit a description for this route. See: utils/rector/docs/RouteInfoRequiredRector.md";

    public function configure(array $configuration): void
    {
        $this->enumClass = $configuration['enumClass'] ?? '';
        $this->attributeClass = $configuration['attributeClass'] ?? 'RouteInfo';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[RouteInfo] on every Route enum case so the inventory graph can emit a description',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
enum Route: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';

    #[RouteOperation(HttpMethod::GET, 'Company information')]
    case about = '/about';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
enum Route: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';

    // TODO: [RouteInfoRequiredRector] Route case 'about' must declare #[RouteInfo] so the inventory graph can emit a description for this route. See: utils/rector/docs/RouteInfoRequiredRector.md
    #[RouteOperation(HttpMethod::GET, 'Company information')]
    case about = '/about';
}
CODE_SAMPLE,
                    [
                        'enumClass' => 'ZeroToProd\\Thryds\\Routes\\Route',
                        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\RouteInfo',
                        'mode' => 'warn',
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
            if (!$stmt instanceof EnumCase) {
                continue;
            }

            $hasAttribute = $this->caseHasAttribute($stmt);

            if ($hasAttribute) {
                // Remove stale TODO if the attribute has since been added.
                $comments = $stmt->getComments();
                $filtered = array_values(array_filter(
                    $comments,
                    static fn(Comment $c): bool => !str_contains($c->getText(), $marker)
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
