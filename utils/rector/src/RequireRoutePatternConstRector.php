<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireRoutePatternConstRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $classSuffix = 'Route';

    private string $constName = 'pattern';

    private string $mode = 'warn';

    private string $message = "TODO: Route class '%s' is missing a '%s' constant — define: public const string %s = '/...';";

    /** @var string[] */
    private array $excludedClasses = [];

    public function configure(array $configuration): void
    {
        if (isset($configuration['classSuffix'])) {
            $this->classSuffix = $configuration['classSuffix'];
        }

        if (isset($configuration['constName'])) {
            $this->constName = $configuration['constName'];
        }

        if (isset($configuration['excludedClasses'])) {
            $this->excludedClasses = $configuration['excludedClasses'];
        }

        if (isset($configuration['message'])) {
            $this->mode = $configuration['mode'] ?? 'warn';
            $this->message = $configuration['message'];
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment above Route classes that are missing a pattern constant',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class PostRoute
{
    public const string post = 'post';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: Route class 'PostRoute' is missing a 'pattern' constant — define: public const string pattern = '/...';
readonly class PostRoute
{
    public const string post = 'post';
}
CODE_SAMPLE,
                    ['classSuffix' => 'Route', 'constName' => 'pattern', 'excludedClasses' => []]
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
        if ($this->mode === 'auto') {
            return null;
        }

        if ($node->isAnonymous()) {
            return null;
        }

        $className = $this->getName($node);

        if ($className === null) {
            return null;
        }

        if (!str_ends_with($className, $this->classSuffix)) {
            return null;
        }

        if (in_array($className, $this->excludedClasses, true)) {
            return null;
        }

        if ($this->hasPatternConst($node)) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $todoComment = new Comment('// ' . sprintf($this->message, $className, $this->constName, $this->constName));

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    private function hasPatternConst(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if ($this->isName($const, $this->constName)) {
                    return true;
                }
            }
        }

        return false;
    }
}
