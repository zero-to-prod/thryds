<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RemoveNamedArgWhenVarMatchesParamRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove named argument when the variable name already matches the parameter name', [
            new CodeSample(
                <<<'CODE_SAMPLE'
WebRoutes::register(Router: $Router, Blade: $Blade);
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
        return [MethodCall::class, StaticCall::class, FuncCall::class, New_::class];
    }

    /**
     * @param MethodCall|StaticCall|FuncCall|New_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->args as $arg) {
            // Skip positional args (no name)
            if ($arg->name === null) {
                continue;
            }

            // Skip non-variable expressions
            if (! $arg->value instanceof Variable) {
                continue;
            }

            $varName = $this->getName($arg->value);
            if ($varName === null) {
                continue;
            }

            $argName = $arg->name->name;

            // Remove the named arg only when the names match exactly
            if ($argName !== $varName) {
                continue;
            }

            $arg->name = null;
            $hasChanged = true;
        }

        if (! $hasChanged) {
            return null;
        }

        return $node;
    }
}
