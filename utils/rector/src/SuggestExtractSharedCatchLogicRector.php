<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeFinder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class SuggestExtractSharedCatchLogicRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s) — extract shared logic to a single declarative source. See: utils/rector/docs/SuggestExtractSharedCatchLogicRector.md';

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s) — extract shared logic to a single declarative source. See: utils/rector/docs/SuggestExtractSharedCatchLogicRector.md';
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add a TODO comment when multiple catch blocks instantiate the same classes, suggesting extraction of shared logic',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
try {
    run();
} catch (FooException $e) {
    new Emitter()->emit(new Response($e->getMessage()));
} catch (Throwable $e) {
    new Emitter()->emit(new Response('error'));
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (Emitter, Response) — extract shared logic to a single declarative source. See: utils/rector/docs/SuggestExtractSharedCatchLogicRector.md
try {
    run();
} catch (FooException $e) {
    new Emitter()->emit(new Response($e->getMessage()));
} catch (Throwable $e) {
    new Emitter()->emit(new Response('error'));
}
CODE_SAMPLE,
                    [
                        'message' => 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s) — extract shared logic to a single declarative source. See: utils/rector/docs/SuggestExtractSharedCatchLogicRector.md',
                    ]
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [TryCatch::class];
    }

    /**
     * @param TryCatch $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->mode === 'auto') {
            return null;
        }

        if (count($node->catches) < 2) {
            return null;
        }

        $marker = strstr($this->message, '%', true) ?: $this->message;
        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return null;
            }
        }

        $classesPerCatch = [];
        $NodeFinder = new NodeFinder();
        foreach ($node->catches as $i => $catch) {
            $classesPerCatch[$i] = [];
            $newNodes = $NodeFinder->findInstanceOf($catch->stmts, New_::class);
            foreach ($newNodes as $newNode) {
                $className = $this->getName($newNode->class);
                if ($className !== null) {
                    $classesPerCatch[$i][] = $className;
                }
            }
        }

        $catchBlockCount = [];
        foreach ($classesPerCatch as $classes) {
            foreach (array_unique($classes) as $class) {
                $catchBlockCount[$class] = ($catchBlockCount[$class] ?? 0) + 1;
            }
        }

        $sharedClasses = array_keys(array_filter($catchBlockCount, static fn(int $count): bool => $count >= 2));

        if ($sharedClasses === []) {
            return null;
        }

        $shortNames = array_map(static fn(string $fqcn): string => substr(strrchr($fqcn, '\\') ?: "\\{$fqcn}", 1), $sharedClasses);
        $sharedList = implode(', ', $shortNames);
        $todoComment = new Comment('// ' . sprintf($this->message, $sharedList));

        $existingComments = $node->getComments();
        array_unshift($existingComments, $todoComment);
        $node->setAttribute('comments', $existingComments);

        return $node;
    }
}
