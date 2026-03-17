<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireNamesKeysOnConstantsClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = '';

    /** @var string[] */
    private array $excludedAttributes = [];

    private string $mode = 'warn';

    private string $message = 'TODO: [RequireNamesKeysOnConstantsClassRector] %s contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).';

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->excludedAttributes = $configuration['excludedAttributes'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[NamesKeys] attribute on readonly classes that contain only string constants',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireNamesKeysOnConstantsClassRector] CacheKey contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\NamesKeys',
                        'mode' => 'warn',
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
        // Must be readonly
        if (!$node->isReadonly()) {
            return null;
        }

        // Must not already have #[NamesKeys]
        if ($this->hasAttribute($node, $this->attributeClass)) {
            return null;
        }

        // Must not have an excluded attribute (e.g., #[ViewModel])
        foreach ($this->excludedAttributes as $excludedAttr) {
            if ($this->hasAttribute($node, $excludedAttr)) {
                return null;
            }
        }

        // Check if it's a pure constants class
        if (!$this->isPureConstantsClass($node)) {
            return null;
        }

        $className = (string) $node->name;

        if ($this->mode === 'auto') {
            return $this->addAttribute($node);
        }

        return $this->addTodoComment($node, $className);
    }

    private function isPureConstantsClass(Class_ $node): bool
    {
        $has_constants = false;

        foreach ($node->stmts as $stmt) {
            // Allow ClassConst (the whole point)
            if ($stmt instanceof ClassConst) {
                $has_constants = true;
                continue;
            }

            // Disallow methods and properties — not a pure constants class
            if ($stmt instanceof ClassMethod || $stmt instanceof Property) {
                return false;
            }

            // Allow trait uses (some constants classes use traits)
            // Allow comments, nops, etc.
        }

        return $has_constants;
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
        $attr = new Attribute(
            new FullyQualified($this->attributeClass),
            [
                new Arg(
                    value: new String_('TODO: describe source'),
                    name: new Identifier('source'),
                ),
            ],
        );

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
