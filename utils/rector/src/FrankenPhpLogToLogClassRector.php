<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Int_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class FrankenPhpLogToLogClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $functions = ['frankenphp_log'];

    private string $logClass = 'ZeroToProd\\Thryds\\Log';

    /** @var array<int, string> */
    private const LEVEL_METHOD_MAP = [
        -4 => 'debug',
        0 => 'info',
        4 => 'warn',
        8 => 'error',
    ];

    public function configure(array $configuration): void
    {
        $this->functions = $configuration['functions'] ?? $this->functions;
        $this->logClass = $configuration['logClass'] ?? $this->logClass;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace frankenphp_log() calls with Log::method() calls', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
frankenphp_log('message', 4, ['key' => 'value']);
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
\ZeroToProd\Thryds\Log::warn('message', ['key' => 'value']);
CODE_SAMPLE,
                [
                    'functions' => ['frankenphp_log'],
                    'logClass' => 'ZeroToProd\\Thryds\\Log',
                ]
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isNames($node, $this->functions)) {
            return null;
        }

        $args = $node->getArgs();

        if ($args === []) {
            return null;
        }

        $funcName = $this->getName($node);
        $method = $this->resolveMethod($funcName, $args);

        if ($method === null) {
            return null;
        }

        $newArgs = [new Arg($args[0]->value)];

        if ($funcName !== 'error_log' && isset($args[2])) {
            $newArgs[] = new Arg($args[2]->value);
        }

        return new StaticCall(
            new FullyQualified($this->logClass),
            new Identifier($method),
            $newArgs
        );
    }

    /**
     * @param Arg[] $args
     */
    private function resolveMethod(string $funcName, array $args): ?string
    {
        if ($funcName === 'error_log') {
            return 'error';
        }

        if (! isset($args[1])) {
            return 'info';
        }

        $levelArg = $args[1]->value;

        if ($levelArg instanceof UnaryMinus && $levelArg->expr instanceof Int_) {
            $value = -$levelArg->expr->value;
        } elseif ($levelArg instanceof Int_) {
            $value = $levelArg->value;
        } else {
            return null;
        }

        return self::LEVEL_METHOD_MAP[$value] ?? null;
    }
}
