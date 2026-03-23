<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Ensures every class carrying #[ViewModel] declares `public const string view_key`.
 * A missing constant produces null entries in the graph inventory output.
 */
final class RequireViewKeyConstantOnViewModelRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const VIEW_KEY_CONST = 'view_key';

    private string $viewModelAttribute = '';

    private string $mode = 'warn';

    private string $message = 'TODO: [RequireViewKeyConstantOnViewModelRector] %s is missing `public const string view_key`. Required for graph inventory.';

    public function configure(array $configuration): void
    {
        $this->viewModelAttribute = $configuration['viewModelAttribute'] ?? '';
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [RequireViewKeyConstantOnViewModelRector] %s is missing `public const string view_key`. Required for graph inventory.';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Require `public const string view_key` on any class carrying #[ViewModel]',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    public string $name;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    public const string view_key = 'UserViewModel';

    public string $name;
}
CODE_SAMPLE,
                    [
                        'viewModelAttribute' => 'ZeroToProd\\Thryds\\Attributes\\ViewModel',
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
        if (!$this->hasViewModelAttribute($node)) {
            return null;
        }

        if ($this->hasViewKeyConst($node)) {
            return null;
        }

        $shortName = $this->resolveShortName($node);
        if ($shortName === null) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addTodoComment($node, $shortName);
        }

        $constNode = $this->buildViewKeyConst($shortName);
        $insertIndex = $this->findInsertIndex($node);
        array_splice($node->stmts, $insertIndex, 0, [$constNode]);

        return $node;
    }

    private function hasViewModelAttribute(Class_ $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $this->getName($attr->name);
                if ($attrName === null) {
                    continue;
                }

                if ($this->matchesAttributeName($attrName)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function matchesAttributeName(string $attrName): bool
    {
        if ($this->viewModelAttribute === '') {
            return false;
        }

        if ($attrName === $this->viewModelAttribute) {
            return true;
        }

        $shortAttr = substr(strrchr($this->viewModelAttribute, '\\') ?: "\\{$this->viewModelAttribute}", 1);

        return $attrName === $shortAttr;
    }

    private function hasViewKeyConst(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if ($this->getName($const->name) === self::VIEW_KEY_CONST) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveShortName(Class_ $node): ?string
    {
        if ($node->name === null) {
            return null;
        }

        return $node->name->toString();
    }

    private function buildViewKeyConst(string $shortName): ClassConst
    {
        $const = new Const_(
            new Identifier(self::VIEW_KEY_CONST),
            new String_($shortName),
        );

        $classConst = new ClassConst([$const], Class_::MODIFIER_PUBLIC);
        $classConst->type = new Identifier('string');

        return $classConst;
    }

    private function findInsertIndex(Class_ $node): int
    {
        $lastTraitIndex = -1;
        foreach ($node->stmts as $index => $stmt) {
            if ($stmt instanceof TraitUse) {
                $lastTraitIndex = $index;
            }
        }

        return $lastTraitIndex + 1;
    }

    private function addTodoComment(Class_ $node, string $shortName): ?Node
    {
        $todoText = sprintf($this->message, $shortName);
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
