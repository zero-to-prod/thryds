<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Ensures every class in the configured ViewModel namespace carries the
 * #[ViewModel] attribute so tooling that reflects on the attribute can
 * discover all ViewModels.
 */
final class AddViewModelAttributeRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $namespace = '';

    private string $attributeClass = '';

    private string $mode = 'auto';

    private string $message = '';

    public function configure(array $configuration): void
    {
        $this->namespace = $configuration['namespace'] ?? '';
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add #[ViewModel] attribute to all classes in the ViewModel namespace that are missing it',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
namespace ZeroToProd\Thryds\ViewModels;

readonly class UserViewModel
{
    use \ZeroToProd\Framework\Attributes\DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    use \ZeroToProd\Framework\Attributes\DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
CODE_SAMPLE,
                    [
                        'namespace' => 'ZeroToProd\\Thryds\\ViewModels',
                        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\ViewModel',
                        'mode' => 'auto',
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
        if (!$this->isInConfiguredNamespace($node)) {
            return null;
        }

        if ($this->hasViewModelAttribute($node)) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return null;
        }

        return $this->addAttribute($node);
    }

    private function isInConfiguredNamespace(Class_ $node): bool
    {
        if ($this->namespace === '' || $node->namespacedName === null) {
            return false;
        }

        $parts = $node->namespacedName->getParts();
        if (count($parts) < 2) {
            return false;
        }

        array_pop($parts);
        $classNamespace = implode('\\', $parts);

        return $classNamespace === $this->namespace;
    }

    private function hasViewModelAttribute(Class_ $node): bool
    {
        if ($this->attributeClass === '') {
            return false;
        }

        $parts = explode('\\', $this->attributeClass);
        $shortName = end($parts);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $this->attributeClass || $name === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addAttribute(Class_ $node): Class_
    {
        $attr = new Attribute(new FullyQualified($this->attributeClass));
        array_unshift($node->attrGroups, new AttributeGroup([$attr]));

        return $node;
    }
}
