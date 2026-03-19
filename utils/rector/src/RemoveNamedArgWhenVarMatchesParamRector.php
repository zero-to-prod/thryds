<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RemoveNamedArgWhenVarMatchesParamRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    private string $message = '';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? '';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove named argument when the variable name already matches the parameter name', [
            new CodeSample(
                <<<'CODE_SAMPLE'
RouteRegistrar::register(Router: $Router, Blade: $Blade);
$Router->dispatch(request: $Request);
new HtmlResponse(html: $html);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
WebRoutes::register($Router, $Blade);
$Router->dispatch($Request);
new HtmlResponse($html);
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class, FuncCall::class, New_::class, Attribute::class];
    }

    /**
     * @param MethodCall|StaticCall|FuncCall|New_|Attribute $node
     */
    public function refactor(Node $node): ?Node
    {
        // First pass: identify which args are candidates for name removal
        $removable = [];
        foreach ($node->args as $position => $arg) {
            if ($arg->name === null) {
                continue;
            }

            if ($arg->value instanceof Variable) {
                $matchName = $this->getName($arg->value);
            } elseif ($arg->value instanceof ClassConstFetch && $arg->value->class instanceof Name) {
                $matchName = $arg->value->class->getLast();
            } else {
                continue;
            }

            if ($matchName === null) {
                continue;
            }

            if ($arg->name->name === $matchName) {
                $removable[$position] = true;
            }
        }

        if ($removable === []) {
            return null;
        }

        // Second pass: only remove names where it won't create positional-after-named
        $hasChanged = false;
        foreach ($node->args as $position => $arg) {
            if (! isset($removable[$position])) {
                continue;
            }

            // Check if any preceding arg will remain named after all removals
            $precededByNamed = false;
            for ($i = 0; $i < $position; $i++) {
                $preceding = $node->args[$i];
                if ($preceding->name !== null && ! isset($removable[$i])) {
                    $precededByNamed = true;
                    break;
                }
            }

            if ($precededByNamed) {
                continue;
            }

            $arg->name = null;
            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addMessageComment($node);
        }

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
}
