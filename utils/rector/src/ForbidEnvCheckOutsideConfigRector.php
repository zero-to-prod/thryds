<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidEnvCheckOutsideConfigRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md';

    /** @var string[] */
    private array $allowedClasses = ['AppEnv', 'Config'];

    /** @var string[] */
    private array $allowedNamespaceParts = ['AppEnv', 'Config'];

    /** @var string[] */
    private array $envFunctions = ['getenv', 'putenv'];

    /** @var string[] */
    private array $envSuperglobals = ['_ENV', '_SERVER'];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? $this->message;
        $this->allowedClasses = $configuration['allowedClasses'] ?? ['AppEnv', 'Config'];
        $this->allowedNamespaceParts = $configuration['allowedNamespaceParts'] ?? ['AppEnv', 'Config'];
        $this->envFunctions = $configuration['envFunctions'] ?? ['getenv', 'putenv'];
        $this->envSuperglobals = $configuration['envSuperglobals'] ?? ['_ENV', '_SERVER'];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag direct environment reads ($_ENV, $_SERVER, getenv, putenv) that appear outside the Config/AppEnv boundary',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$value = $_ENV['APP_KEY'];
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class.
$value = $_ENV['APP_KEY'];
CODE_SAMPLE,
                    [
                        'mode' => 'warn',
                        'message' => 'TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class.',
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

        if ($this->isInAllowedFile()) {
            return null;
        }

        if (!$this->containsEnvRead($node)) {
            return null;
        }

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

    private function isInAllowedFile(): bool
    {
        $filePath = $this->file->getFilePath();

        // Allow scripts/ directory
        if (str_contains($filePath, '/scripts/')) {
            return true;
        }

        // Allow application tests/ directory, but not the rector utils test path
        if (str_contains($filePath, '/tests/') && !str_contains($filePath, '/utils/rector/tests/')) {
            return true;
        }

        // Allow public/index.php bootstrap
        if (str_ends_with($filePath, '/public/index.php')) {
            return true;
        }

        // Allow classes named AppEnv or Config by filename (e.g. AppEnv.php, AppEnv.php.inc)
        $basename = basename($filePath);
        // Strip known double extensions (.php.inc) and single (.php)
        $stem = preg_replace('/\.(php\.inc|php)$/', '', $basename) ?? $basename;
        if (in_array($stem, $this->allowedClasses, true)) {
            return true;
        }

        // Allow any directory segment matching allowed namespace parts
        // e.g. src/Config/SomeClass.php or src/AppEnv/SubClass.php
        $parts = explode('/', $filePath);
        foreach ($parts as $part) {
            $dirPart = pathinfo($part, PATHINFO_FILENAME);
            if (in_array($dirPart, $this->allowedNamespaceParts, true)) {
                return true;
            }
        }

        return false;
    }

    private function containsEnvRead(Expression $node): bool
    {
        $found = false;

        $this->traverseNodesWithCallable([$node->expr], function (Node $inner) use (&$found): ?Node {
            if ($found) {
                return null;
            }

            // $_ENV['KEY'] or $_SERVER['KEY']
            if ($inner instanceof ArrayDimFetch && $inner->var instanceof Variable) {
                $varName = $inner->var->name;
                if (is_string($varName) && in_array($varName, $this->envSuperglobals, true)) {
                    $found = true;
                    return null;
                }
            }

            // getenv(...) or putenv(...)
            if ($inner instanceof FuncCall && $inner->name instanceof Name) {
                $funcName = $inner->name->toString();
                if (in_array($funcName, $this->envFunctions, true)) {
                    $found = true;
                    return null;
                }
            }

            return null;
        });

        return $found;
    }
}
