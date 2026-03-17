<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RequireFragmentIfForBladeRenderRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = "TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page. For htmx partial requests, use ->fragmentIf(\$request->hasHeader(Header::hx_request), 'body') instead.";

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag ->make()->render() chains that should use ->fragmentIf() for htmx partial responses',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$Blade->make(view: 'home')->render();
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page. For htmx partial requests, use ->fragmentIf($request->hasHeader(Header::hx_request), 'body') instead.
$Blade->make(view: 'home')->render();
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Expression::class];
    }

    /**
     * @param Expression $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if (!$node->expr instanceof MethodCall) {
            return null;
        }

        $renderCall = $node->expr;

        if (!$this->isName($renderCall->name, 'render')) {
            return null;
        }

        if (!$renderCall->var instanceof MethodCall) {
            return null;
        }

        if (!$this->isName($renderCall->var->name, 'make')) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $this->message));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
