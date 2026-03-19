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
 * Flags Route enum cases that declare #[RouteOperation] without a paired #[RouteInfo],
 * ensuring the inventory graph always has both HTTP method and description on every route.
 */
final class RouteOperationRequiresRouteInfoRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $enumClass = '';

    private string $triggerAttributeClass = 'RouteOperation';

    private string $requiredAttributeClass = 'RouteInfo';

    private string $mode = 'warn';

    private string $message = "TODO: [RouteOperationRequiresRouteInfoRector] Route case '%s' declares #[RouteOperation] but is missing #[RouteInfo]. Both attributes are required together: #[RouteOperation] declares HTTP methods, #[RouteInfo] declares the route description. See: utils/rector/docs/RouteOperationRequiresRouteInfoRector.md";

    public function configure(array $configuration): void
    {
        $this->enumClass = $configuration['enumClass'] ?? '';
        $this->triggerAttributeClass = $configuration['triggerAttributeClass'] ?? 'RouteOperation';
        $this->requiredAttributeClass = $configuration['requiredAttributeClass'] ?? 'RouteInfo';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[RouteInfo] on every Route enum case that carries #[RouteOperation] so the inventory graph always has both HTTP method and description',
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

    // TODO: [RouteOperationRequiresRouteInfoRector] Route case 'about' declares #[RouteOperation] but is missing #[RouteInfo]. Both attributes are required together: #[RouteOperation] declares HTTP methods, #[RouteInfo] declares the route description. See: utils/rector/docs/RouteOperationRequiresRouteInfoRector.md
    #[RouteOperation(HttpMethod::GET, 'Company information')]
    case about = '/about';
}
CODE_SAMPLE,
                    [
                        'enumClass' => 'ZeroToProd\\Thryds\\Routes\\Route',
                        'triggerAttributeClass' => 'ZeroToProd\\Thryds\\Attributes\\RouteOperation',
                        'requiredAttributeClass' => 'ZeroToProd\\Thryds\\Attributes\\RouteInfo',
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
            if (! $stmt instanceof EnumCase) {
                continue;
            }

            $hasTrigger  = $this->caseHasAttribute($stmt, $this->triggerAttributeClass);
            $hasRequired = $this->caseHasAttribute($stmt, $this->requiredAttributeClass);

            if ($hasRequired) {
                // Required attribute present — remove any stale TODO.
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

            if (! $hasTrigger) {
                // Neither attribute present — not this rule's concern.
                continue;
            }

            // Has trigger but not required — add TODO if not already present.
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

    private function caseHasAttribute(EnumCase $case, string $attributeClass): bool
    {
        $parts     = explode('\\', $attributeClass);
        $shortName = end($parts);

        foreach ($case->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $attributeClass || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }
}
