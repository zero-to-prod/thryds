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
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Adds `public const string view_key = 'ShortClassName';` to classes that carry
 * the #[ViewModel] attribute and use the DataModel trait.
 */
final class AddViewKeyConstantRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const VIEW_KEY_CONST = 'view_key';

    /** @var string[] */
    private array $dataModelTraits = [];

    private string $viewModelAttribute = '';

    private string $mode = 'auto';

    private string $message = '';

    public function configure(array $configuration): void
    {
        $this->dataModelTraits = $configuration['dataModelTraits'] ?? [];
        $this->viewModelAttribute = $configuration['viewModelAttribute'] ?? '';
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add view_key constant to ViewModel classes that use DataModel trait',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
use ZeroToProd\Thryds\Attributes\ViewModel;
use ZeroToProd\Thryds\Attributes\DataModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;

    public string $message;
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
use ZeroToProd\Thryds\Attributes\ViewModel;
use ZeroToProd\Thryds\Attributes\DataModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;

    public const string view_key = 'ErrorViewModel';

    public string $message;
}
CODE_SAMPLE,
                    [
                        'dataModelTraits' => ['ZeroToProd\\Thryds\\Attributes\\DataModel'],
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

        if (!$this->usesDataModelTrait($node)) {
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
            return $this->addMessageComment($node);
        }

        $constNode = $this->buildViewKeyConst($shortName);

        // Insert after the last use statement (trait use), or at the top of the class body
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

        // Match by short name (unqualified)
        $shortAttr = substr(strrchr($this->viewModelAttribute, '\\') ?: "\\{$this->viewModelAttribute}", 1);

        return $attrName === $shortAttr;
    }

    private function usesDataModelTrait(Class_ $node): bool
    {
        foreach ($node->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $traitName = $this->getName($trait);
                if ($traitName === null) {
                    continue;
                }

                foreach ($this->dataModelTraits as $dataModelTrait) {
                    if ($traitName === $dataModelTrait) {
                        return true;
                    }

                    $shortTrait = substr(strrchr($dataModelTrait, '\\') ?: "\\{$dataModelTrait}", 1);
                    if ($traitName === $shortTrait) {
                        return true;
                    }
                }
            }
        }

        return false;
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
            if ($stmt instanceof Node\Stmt\TraitUse) {
                $lastTraitIndex = $index;
            }
        }

        return $lastTraitIndex + 1;
    }

    private function addMessageComment(Node $node): ?Node
    {
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $this->message)) {
                return null;
            }
        }
        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $this->message));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
