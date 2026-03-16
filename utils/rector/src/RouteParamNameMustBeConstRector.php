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

final class RouteParamNameMustBeConstRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $classSuffix = 'Route';

    private string $constName = 'pattern';

    private string $mode = 'auto';

    private string $message = '';

    public function configure(array $configuration): void
    {
        if (isset($configuration['classSuffix'])) {
            $this->classSuffix = $configuration['classSuffix'];
        }

        if (isset($configuration['constName'])) {
            $this->constName = $configuration['constName'];
        }

        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a public const string for each {param} placeholder found in a Route class pattern constant',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
    public const string post = 'post';
    public const string comment = 'comment';
}
CODE_SAMPLE,
                    ['classSuffix' => 'Route', 'constName' => 'pattern']
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
        $className = $this->getName($node);
        if ($className === null) {
            return null;
        }

        if (!str_ends_with($className, $this->classSuffix)) {
            return null;
        }

        $patternValue = $this->findPatternConstValue($node);
        if ($patternValue === null) {
            return null;
        }

        $params = $this->extractParams($patternValue);
        if ($params === []) {
            return null;
        }

        $missingParams = $this->findMissingParams($node, $params);
        if ($missingParams === []) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

        $this->insertParamConsts($node, $missingParams);

        return $node;
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

    private function findPatternConstValue(Class_ $node): ?string
    {
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                if ($this->getName($const) !== $this->constName) {
                    continue;
                }

                if ($const->value instanceof String_) {
                    return $const->value->value;
                }
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function extractParams(string $pattern): array
    {
        preg_match_all('/\{(\w+)\}/', $pattern, $matches);

        return $matches[1];
    }

    /**
     * @param string[] $params
     * @return string[]
     */
    private function findMissingParams(Class_ $node, array $params): array
    {
        $existingConstNames = [];

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof ClassConst) {
                continue;
            }

            foreach ($stmt->consts as $const) {
                $existingConstNames[] = $this->getName($const);
            }
        }

        return array_values(array_filter(
            $params,
            static fn(string $param): bool => !in_array($param, $existingConstNames, true),
        ));
    }

    /**
     * @param string[] $params
     */
    private function insertParamConsts(Class_ $node, array $params): void
    {
        $lastConstIndex = null;

        foreach ($node->stmts as $index => $stmt) {
            if ($stmt instanceof ClassConst) {
                $lastConstIndex = $index;
            }
        }

        $insertLine = $lastConstIndex !== null
            ? ($node->stmts[$lastConstIndex]->getEndLine())
            : $node->getStartLine();

        $newConsts = [];
        foreach ($params as $paramName) {
            $const = new Const_($paramName, new String_($paramName));
            $const->setAttribute('startLine', $insertLine);
            $const->setAttribute('endLine', $insertLine);

            $classConst = new ClassConst(
                [$const],
                Class_::MODIFIER_PUBLIC,
            );
            $classConst->type = new Identifier('string');
            $classConst->setAttribute('startLine', $insertLine);
            $classConst->setAttribute('endLine', $insertLine);
            $newConsts[] = $classConst;
        }

        if ($lastConstIndex !== null) {
            array_splice($node->stmts, $lastConstIndex + 1, 0, $newConsts);
        } else {
            $node->stmts = array_merge($newConsts, $node->stmts);
        }
    }
}
