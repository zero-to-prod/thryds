<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Validates that every ID in #[Requirement('X')] exists in requirements.yaml.
 *
 * @see docs/requirement-tracing.md
 */
final class ValidateRequirementIdsRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $requirementsFile = '';

    /** @var array<string, true> */
    private array $validIds = [];

    private string $message = '';

    public function configure(array $configuration): void
    {
        $this->requirementsFile = $configuration['requirements_file'] ?? '';
        $this->message = $configuration['message'] ?? "TODO: [ValidateRequirementIdsRector] Requirement ID '%s' not found in requirements.yaml";
        $this->loadRequirements();
    }

    private function loadRequirements(): void
    {
        if ($this->requirementsFile === '') {
            throw new \InvalidArgumentException(
                'ValidateRequirementIdsRector requires an explicit requirements_file path in rector.php. '
                . "Example: ['requirements_file' => __DIR__ . '/requirements.yaml']"
            );
        }

        if (! file_exists($this->requirementsFile)) {
            throw new \InvalidArgumentException(
                "ValidateRequirementIdsRector: requirements_file not found at '{$this->requirementsFile}'"
            );
        }

        $content = (string) file_get_contents($this->requirementsFile);
        preg_match_all('/^([A-Z]+-\d+):/m', $content, $matches);
        $this->validIds = array_flip($matches[1]);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            "Validates that every ID in #[Requirement('X')] exists in requirements.yaml",
            [
                new CodeSample(
                    "#[Requirement('INVALID-999')]\nclass SomeClass {}",
                    "// TODO: Requirement ID 'INVALID-999' not found in requirements.yaml\n#[Requirement('INVALID-999')]\nclass SomeClass {}",
                ),
            ]
        );
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [Class_::class, ClassMethod::class];
    }

    /** @param Class_|ClassMethod $node */
    public function refactor(Node $node): int|Node|null
    {
        $invalid = [];

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $attr->name->toString();

                if ($name !== 'Requirement' && $name !== 'ZeroToProd\\Thryds\\Attributes\\Requirement') {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    if (! $arg->value instanceof String_) {
                        continue;
                    }

                    $id = $arg->value->value;

                    if (! isset($this->validIds[$id])) {
                        $invalid[] = $id;
                    }
                }
            }
        }

        if ($invalid === []) {
            return null;
        }

        $comments = $node->getComments();

        foreach ($invalid as $id) {
            array_unshift($comments, new Comment('// ' . sprintf($this->message, $id)));
        }

        $node->setAttribute('comments', $comments);

        return $node;
    }
}
