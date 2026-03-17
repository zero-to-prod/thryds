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

final class SuggestAttributeForRepeatedPropertyPatternRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var array<array{trait: string, constant: string, attribute: string}> */
    private array $patterns = [];

    private string $mode = 'auto';

    private string $message = 'TODO: [SuggestAttributeForRepeatedPropertyPatternRector] %s uses %s + %s — add #[%s] attribute.';

    public function configure(array $configuration): void
    {
        $this->patterns = $configuration['patterns'] ?? [];
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a marker attribute to classes that use a configured trait and have a configured constant',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
#[ViewModel]
class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
}
CODE_SAMPLE,
                    [
                        'patterns' => [
                            [
                                'trait' => 'App\\Helpers\\DataModel',
                                'constant' => 'view_key',
                                'attribute' => 'App\\Helpers\\ViewModel',
                            ],
                        ],
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
        foreach ($this->patterns as $pattern) {
            if (!$this->usesTrait($node, $pattern['trait'])) {
                continue;
            }

            if (!$this->hasConstant($node, $pattern['constant'])) {
                continue;
            }

            if ($this->hasAttribute($node, $pattern['attribute'])) {
                continue;
            }

            if ($this->mode === 'auto') {
                return $this->addAttribute($node, $pattern['attribute']);
            }

            $className = (string) $node->name;
            $traitParts = explode('\\', $pattern['trait']);
            $traitShortName = end($traitParts);
            $attrParts = explode('\\', $pattern['attribute']);
            $attrShortName = end($attrParts);

            return $this->addTodoComment($node, $className, $traitShortName, $pattern['constant'], $attrShortName);
        }

        return null;
    }

    private function usesTrait(Class_ $node, string $traitClass): bool
    {
        $parts = explode('\\', $traitClass);
        $shortName = end($parts);

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof TraitUse) {
                continue;
            }

            foreach ($stmt->traits as $trait) {
                $traitName = $this->getName($trait);
                if ($traitName !== null && ($traitName === $traitClass || $traitName === $shortName)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasConstant(Class_ $node, string $constantName): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if ((string) $const->name === $constantName) {
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

    private function addAttribute(Class_ $node, string $attributeClass): Class_
    {
        $attr = new Attribute(new FullyQualified($attributeClass));
        array_unshift($node->attrGroups, new AttributeGroup([$attr]));

        return $node;
    }

    private function addTodoComment(Class_ $node, string $className, string $traitShortName, string $constantName, string $attrShortName): Class_
    {
        $todoText = sprintf($this->message, $className, $traitShortName, $constantName, $attrShortName);
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
