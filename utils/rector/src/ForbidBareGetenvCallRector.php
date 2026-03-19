<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ForbidBareGetenvCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $envClass = '';

    /** @var string[] */
    private array $functions = ['getenv'];

    private string $mode = 'warn';

    private string $message = "TODO: [ForbidBareGetenvCallRector] Use %s::%s instead of bare string '%s' in getenv().";

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function configure(array $configuration): void
    {
        $this->envClass = $configuration['envClass'] ?? '';
        $this->functions = $configuration['functions'] ?? ['getenv'];
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [ForbidBareGetenvCallRector] Use %s::%s instead of bare string '%s' in getenv().";
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace bare string keys in getenv() calls with class constants from the configured Env class',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$host = (string) getenv('DB_HOST');
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$host = (string) getenv(Env::DB_HOST);
CODE_SAMPLE,
                    [
                        'envClass' => 'App\\Env',
                        'functions' => ['getenv'],
                        'mode' => 'auto',
                    ],
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [Expression::class, Return_::class];
    }

    /**
     * @param Expression|Return_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $expr = $node->expr ?? null;
        if ($expr === null) {
            return null;
        }

        $matches = [];
        $this->traverseNodesWithCallable($expr, function (Node $subNode) use (&$matches): null {
            if (!$subNode instanceof FuncCall) {
                return null;
            }

            if (!$this->isNames($subNode, $this->functions)) {
                return null;
            }

            if (!isset($subNode->args[0])) {
                return null;
            }

            $firstArg = $subNode->args[0];
            if (!$firstArg instanceof \PhpParser\Node\Arg) {
                return null;
            }

            if (!$firstArg->value instanceof String_) {
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
        foreach ($matches as $funcCall) {
            $firstArg = $funcCall->args[0];
            if (!$firstArg instanceof \PhpParser\Node\Arg) {
                continue;
            }

            if (!$firstArg->value instanceof String_) {
                continue;
            }

            $constName = $firstArg->value->value;

            if ($this->envClass !== '') {
                $this->addConstantToClassFile($constName);
            }

            $firstArg->value = new ClassConstFetch(
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
     * @param FuncCall[] $matches
     * @param Expression|Return_ $node
     * @return Expression|Return_|null
     */
    private function addTodoComments(Node $node, array $matches): ?Node
    {
        $shortName = $this->envClass !== '' ? (str_contains($this->envClass, '\\') ? substr($this->envClass, strrpos($this->envClass, '\\') + 1) : $this->envClass) : 'Env';

        $marker = 'TODO: [ForbidBareGetenvCallRector]';

        $existingComments = $node->getComments();
        $changed = false;

        foreach ($matches as $funcCall) {
            $firstArg = $funcCall->args[0];
            if (!$firstArg instanceof \PhpParser\Node\Arg) {
                continue;
            }

            if (!$firstArg->value instanceof String_) {
                continue;
            }

            $constName = $firstArg->value->value;
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
