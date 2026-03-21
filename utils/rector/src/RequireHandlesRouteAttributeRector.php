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

/**
 * Flags classes in the Controllers namespace that are missing #[HandlesRoute].
 *
 * Every controller must declare which route it handles so the router can
 * discover it via reflection, eliminating manual wiring in the registrar.
 */
final class RequireHandlesRouteAttributeRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — %s in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md';

    private string $attributeClass = 'HandlesRoute';

    /** @var string[] */
    private array $controllerSuffixes = ['Controller', 'Handler'];

    private string $controllersNamespace = '';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->attributeClass = $configuration['attributeClass'] ?? $this->attributeClass;
        $this->controllerSuffixes = $configuration['controllerSuffixes'] ?? $this->controllerSuffixes;
        $this->controllersNamespace = $configuration['controllersNamespace'] ?? '';
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

        if ($node->name === null) {
            return null;
        }

        $className = $node->name->toString();

        if (!$this->isController($className)) {
            return null;
        }

        if ($this->controllersNamespace !== '') {
            $fqcn = $node->namespacedName !== null
                ? $node->namespacedName->toString()
                : ($this->getName($node) ?? '');

            if (!str_starts_with($fqcn, $this->controllersNamespace)) {
                return null;
            }
        }

        if ($this->hasAttribute($node)) {
            return $this->removeStaleComment($node);
        }

        if ($this->alreadyHasComment($node)) {
            return null;
        }

        $todoText = sprintf($this->message, $className);
        $existingComments = $node->getComments();
        array_unshift($existingComments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    private function isController(string $className): bool
    {
        foreach ($this->controllerSuffixes as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function hasAttribute(Class_ $node): bool
    {
        $parts = explode('\\', $this->attributeClass);
        $shortName = end($parts);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name) ?? $attr->name->toString();
                if ($name === $this->attributeClass || str_ends_with($name, '\\' . $shortName) || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function alreadyHasComment(Class_ $node): bool
    {
        $marker = '[RequireHandlesRouteAttributeRector]';
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return true;
            }
        }

        return false;
    }

    private function removeStaleComment(Class_ $node): ?Node
    {
        $marker = '[RequireHandlesRouteAttributeRector]';
        $comments = $node->getComments();
        $filtered = array_values(array_filter(
            $comments,
            static fn(Comment $c): bool => !str_contains($c->getText(), $marker)
        ));

        if (count($filtered) !== count($comments)) {
            $node->setAttribute('comments', $filtered);

            return $node;
        }

        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[HandlesRoute] on every class in Controllers/ so the router discovers all handlers via reflection',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class LoginController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('login');
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — LoginController in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md
readonly class LoginController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('login');
    }
}
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\HandlesRoute',
                        'controllerSuffixes' => ['Controller', 'Handler'],
                        'controllersNamespace' => 'ZeroToProd\\Thryds\\Controllers',
                    ],
                ),
            ]
        );
    }
}
