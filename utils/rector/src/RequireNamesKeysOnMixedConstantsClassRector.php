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
use PhpParser\Node\Stmt\TraitUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireNamesKeysOnMixedConstantsClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $attributeClass = '';

    private int $minConstants = 3;

    /** @var string[] */
    private array $excludedTraits = [];

    /** @var string[] */
    private array $excludedAttributes = [];

    private string $mode = 'warn';

    private string $message = 'TODO: [RequireNamesKeysOnMixedConstantsClassRector] %s has %d string constants — add #[NamesKeys] to declare what they name (ADR-007).';

    public function configure(array $configuration): void
    {
        $this->attributeClass = $configuration['attributeClass'] ?? '';
        $this->minConstants = $configuration['minConstants'] ?? 3;
        $this->excludedTraits = $configuration['excludedTraits'] ?? [];
        $this->excludedAttributes = $configuration['excludedAttributes'] ?? [];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require #[NamesKeys] attribute on classes that have 3+ string constants alongside methods',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireNamesKeysOnMixedConstantsClassRector] Metrics has 3 string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}
CODE_SAMPLE,
                    [
                        'attributeClass' => 'App\\Helpers\\NamesKeys',
                        'minConstants' => 3,
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
        // Already has #[NamesKeys]
        if ($this->hasAttribute($node, $this->attributeClass)) {
            return null;
        }

        // Has an excluded attribute
        foreach ($this->excludedAttributes as $excludedAttr) {
            if ($this->hasAttribute($node, $excludedAttr)) {
                return null;
            }
        }

        // Uses an excluded trait (DataModel = property keys, not naming keys)
        if ($this->usesExcludedTrait($node)) {
            return null;
        }

        // Count public const string members
        $string_const_count = $this->countStringConstants($node);
        if ($string_const_count < $this->minConstants) {
            return null;
        }

        $className = (string) $node->name;

        if ($this->mode === 'auto') {
            return $this->addAttribute($node);
        }

        return $this->addTodoComment($node, $className, $string_const_count);
    }

    private function countStringConstants(Class_ $node): int
    {
        $count = 0;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            if (!$stmt->isPublic()) {
                continue;
            }

            // Check if typed as string
            if ($stmt->type instanceof Identifier && $stmt->type->name === 'string') {
                $count += count($stmt->consts);
            }
        }

        return $count;
    }

    private function usesExcludedTrait(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof TraitUse) {
                continue;
            }

            foreach ($stmt->traits as $trait) {
                $traitName = $this->getName($trait);
                if ($traitName !== null && in_array($traitName, $this->excludedTraits, true)) {
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

    private function addTodoComment(Class_ $node, string $className, int $count): Class_
    {
        $todoText = sprintf($this->message, $className, $count);
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
