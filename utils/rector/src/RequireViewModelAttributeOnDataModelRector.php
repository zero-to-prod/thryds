<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireViewModelAttributeOnDataModelRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $traitClasses = [];

    private string $constantName = 'view_key';

    private string $attributeClass = '';

    private string $mode = 'auto';

    private string $message = 'TODO: [RequireViewModelAttributeOnDataModelRector] %s uses DataModel + view_key but is missing #[ViewModel].';

    public function configure(array $configuration): void
    {
        $this->traitClasses = $configuration['traitClasses'] ?? [];
        $this->constantName = $configuration['constantName'] ?? 'view_key';
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[ViewModel] attribute on classes that use DataModel trait and have a view_key constant',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class ProfileViewModel
{
    use \App\Helpers\DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
#[\App\Helpers\ViewModel]
readonly class ProfileViewModel
{
    use \App\Helpers\DataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}
CODE_SAMPLE,
                    [
                        'traitClasses' => ['App\\Helpers\\DataModel'],
                        'constantName' => 'view_key',
                        'attributeClass' => 'App\\Helpers\\ViewModel',
                        'mode' => 'auto',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // Must already have the attribute → skip
        if ($this->hasAttribute($node, $this->attributeClass)) {
            return null;
        }

        // Must use DataModel trait
        if (!$this->usesDataModelTrait($node)) {
            return null;
        }

        // Must have view_key constant
        if (!$this->hasConstant($node, $this->constantName)) {
            return null;
        }

        $className = (string) $node->name;

        if ($this->mode === 'auto') {
            return $this->addAttribute($node);
        }

        return $this->addTodoComment($node, $className);
    }

    private function usesDataModelTrait(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof TraitUse) {
                continue;
            }

            foreach ($stmt->traits as $trait) {
                $traitName = $this->getName($trait);
                if ($traitName !== null && in_array($traitName, $this->traitClasses, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasConstant(Class_ $node, string $name): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if ((string) $const->name === $name) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasAttribute(Class_ $node, string $attributeClass): bool
    {
        $parts = explode('\\', $attributeClass);
        $shortName = end($parts);

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                if ($name === $attributeClass || $name === $shortName) {
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

    private function addTodoComment(Class_ $node, string $className): Class_
    {
        $todoText = sprintf($this->message, $className);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return $node;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
