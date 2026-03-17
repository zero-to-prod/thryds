<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidBareServerEnvKeyRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $envClass = '';

    /** @var string[] */
    private array $superglobals = ['_SERVER', '_ENV'];

    private string $mode = 'warn';

    private string $message = "TODO: [ForbidBareServerEnvKeyRector] Use %s::%s instead of bare string '%s'.";

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->envClass = $configuration['envClass'] ?? '';
        $this->superglobals = $configuration['superglobals'] ?? ['_SERVER', '_ENV'];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [ForbidBareServerEnvKeyRector] Use %s::%s instead of bare string '%s'.";
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace bare string keys in $_SERVER/$_ENV with class constants from the configured Env class',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$max = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$max = (int) ($_SERVER[Env::MAX_REQUESTS] ?? 0);
CODE_SAMPLE,
                    [
                        'envClass' => 'App\\Env',
                        'superglobals' => ['_SERVER', '_ENV'],
                        'mode' => 'auto',
                    ],
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
        $matches = [];
        $this->traverseNodesWithCallable($node->expr, function (Node $subNode) use (&$matches): null {
            if (!$subNode instanceof ArrayDimFetch) {
                return null;
            }

            if (!$subNode->var instanceof Variable) {
                return null;
            }

            $varName = $subNode->var->name;
            if (!is_string($varName) || !in_array($varName, $this->superglobals, true)) {
                return null;
            }

            if (!$subNode->dim instanceof String_) {
                return null;
            }

            $matches[] = $subNode;

            return null;
        });

        if ($matches === []) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addTodoComments($node, $matches);
        }

        $changed = false;
        foreach ($matches as $arrayDimFetch) {
            $constName = $arrayDimFetch->dim->value;

            if ($this->envClass !== '') {
                $this->addConstantToClassFile($constName);
            }

            $arrayDimFetch->dim = new ClassConstFetch(
                new FullyQualified($this->envClass),
                new Identifier($constName)
            );
            $changed = true;
        }

        if (!$changed) {
            return null;
        }

        return $node;
    }

    /**
     * @param ArrayDimFetch[] $matches
     */
    private function addTodoComments(Expression $node, array $matches): ?Expression
    {
        $shortName = $this->envClass !== '' ? (str_contains($this->envClass, '\\') ? substr($this->envClass, strrpos($this->envClass, '\\') + 1) : $this->envClass) : 'Env';

        $marker = 'TODO: [ForbidBareServerEnvKeyRector]';

        $existingComments = $node->getComments();
        $changed = false;

        foreach ($matches as $arrayDimFetch) {
            $constName = $arrayDimFetch->dim->value;
            $todoText = sprintf($this->message, $shortName, $constName, $constName);

            $alreadyPresent = false;
            foreach ($existingComments as $comment) {
                if (str_contains($comment->getText(), $marker) && str_contains($comment->getText(), $constName)) {
                    $alreadyPresent = true;
                    break;
                }
            }

            if ($alreadyPresent) {
                continue;
            }

            array_unshift($existingComments, new Comment('// ' . $todoText));
            $changed = true;
        }

        if (!$changed) {
            return null;
        }

        $node->setAttribute('comments', $existingComments);

        return $node;
    }

    private function addConstantToClassFile(string $constName): void
    {
        if (!$this->reflectionProvider->hasClass($this->envClass)) {
            return;
        }

        $classReflection = $this->reflectionProvider->getClass($this->envClass);

        if ($classReflection->hasConstant($constName)) {
            return;
        }

        $fileName = $classReflection->getFileName();
        if ($fileName === null || $fileName === false) {
            return;
        }

        $content = file_get_contents($fileName);
        if ($content === false) {
            return;
        }

        $constLine = "    public const string {$constName} = '{$constName}';";

        if (str_contains($content, $constLine)) {
            return;
        }

        $lastBrace = strrpos($content, '}');
        if ($lastBrace === false) {
            return;
        }

        $content = substr($content, 0, $lastBrace) . $constLine . "\n" . substr($content, $lastBrace);

        file_put_contents($fileName, $content);
    }
}
