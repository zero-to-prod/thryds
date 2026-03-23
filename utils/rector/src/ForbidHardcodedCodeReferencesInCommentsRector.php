<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\FileNode;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Flags code references in comments that are not reachable by AST refactoring,
 * ensuring comments stay evergreen across renames and restructures.
 */
final class ForbidHardcodedCodeReferencesInCommentsRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'warn';

    private string $message = "TODO: [ForbidHardcodedCodeReferencesInCommentsRector] '%s' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md";

    /** Attribute syntax: #[Name or #[Name( */
    private const string ATTRIBUTE_PATTERN = '/#\[([A-Z][A-Za-z0-9_\\\\]*)/';

    /** Static member access: Name::member, Name::class, Name::CONST */
    private const string STATIC_MEMBER_PATTERN = '/\b([A-Z][A-Za-z0-9_\\\\]*::[a-zA-Z_\$][a-zA-Z0-9_]*)/';

    /** Arrow method call: ->method( */
    private const string ARROW_CALL_PATTERN = '/(->[a-zA-Z_][a-zA-Z0-9_]*\s*\()/';

    /** Fully-qualified class name: \Namespace\ClassName */
    private const string FQCN_PATTERN = '/(\\\\[A-Z][A-Za-z0-9_]*(?:\\\\[A-Za-z][A-Za-z0-9_]*)+)/';

    /** Tags whose entire line is AST-reachable — skip completely. */
    private const SKIP_TAGS = [
        'see', 'link', 'template', 'extends', 'implements',
        'method', 'property', 'property-read', 'property-write', 'mixin',
        'codeCoverageIgnore', 'codeCoverageIgnoreStart', 'codeCoverageIgnoreEnd',
        'inheritDoc', 'inheritdoc',
    ];

    /** Tags whose type portion is AST-reachable — only check the description. */
    private const TYPED_TAGS = ['param', 'return', 'var', 'throws'];

    /** Prefixes for static analysis annotations — skip entirely. */
    private const ANALYSIS_PREFIXES = ['phpstan-', 'psalm-'];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'warn';
        $this->message = $configuration['message'] ?? "TODO: [ForbidHardcodedCodeReferencesInCommentsRector] '%s' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md";
    }

    public function getNodeTypes(): array
    {
        return [FileNode::class];
    }

    /** @param FileNode $node */
    public function refactor(Node $node): ?Node
    {
        $changed = false;

        $this->traverseNodesWithCallable($node->stmts, function (Node $inner) use (&$changed): null {
            $comments = $inner->getComments();
            if ($comments === []) {
                return null;
            }

            foreach ($comments as $comment) {
                $proseLines = $this->extractProseLines($comment->getText());

                foreach ($proseLines as $line) {
                    $match = $this->findCodeReference($line);
                    if ($match !== null) {
                        if ($this->addTodoComment($inner, $match)) {
                            $changed = true;
                        }

                        return null;
                    }
                }
            }

            return null;
        });

        return $changed ? $node : null;
    }

    /** @return list<string> Lines of comment text that are NOT in AST-reachable positions. */
    private function extractProseLines(string $commentText): array
    {
        $lines = explode("\n", $commentText);
        $result = [];

        foreach ($lines as $line) {
            $cleaned = $this->cleanCommentLine($line);
            if ($cleaned === '') {
                continue;
            }

            // Skip Rector-generated TODO comments.
            if (str_contains($cleaned, 'TODO: [')) {
                continue;
            }

            // Handle @tag lines.
            if (preg_match('/^@(\S+)/', $cleaned, $tagMatch)) {
                $tag = $tagMatch[1];

                if (in_array($tag, self::SKIP_TAGS, true)) {
                    continue;
                }

                foreach (self::ANALYSIS_PREFIXES as $prefix) {
                    if (str_starts_with($tag, $prefix)) {
                        continue 2;
                    }
                }

                foreach (self::TYPED_TAGS as $typedTag) {
                    if ($tag === $typedTag) {
                        $cleaned = $this->stripTypeFromTagLine($cleaned, $typedTag);
                        break;
                    }
                }
            }

            // Strip inline {@see ...} and {@link ...} tags — these are AST-reachable.
            $cleaned = (string) preg_replace('/\{@(?:see|link)\s+[^}]+\}/', '', $cleaned);

            if (trim($cleaned) !== '') {
                $result[] = $cleaned;
            }
        }

        return $result;
    }

    private function cleanCommentLine(string $line): string
    {
        $line = trim($line);

        if (str_starts_with($line, '//')) {
            return trim(substr($line, 2));
        }

        if (str_starts_with($line, '/**')) {
            $line = trim(substr($line, 3));
        } elseif (str_starts_with($line, '/*')) {
            $line = trim(substr($line, 2));
        }

        if (str_ends_with($line, '*/')) {
            $line = trim(substr($line, 0, -2));
        }

        if (str_starts_with($line, '* ')) {
            return trim(substr($line, 2));
        }

        if ($line === '*') {
            return '';
        }

        return trim($line);
    }

    /** Strip the AST-reachable type portion from a typed tag line, returning only the description. */
    private function stripTypeFromTagLine(string $cleaned, string $tag): string
    {
        $rest = trim(substr($cleaned, strlen('@' . $tag)));

        $rest = $this->skipTypeExpression($rest);

        if ($tag === 'param' && preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*\s*/', $rest, $m)) {
            $rest = substr($rest, strlen($m[0]));
        }

        return trim($rest);
    }

    /** Advance past a PHP type expression (handles generics, unions, intersections, nullable). */
    private function skipTypeExpression(string $text): string
    {
        $text = ltrim($text);
        $depth = 0;
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];

            if ($char === '<' || $char === '(' || $char === '{') {
                $depth++;
            } elseif ($char === '>' || $char === ')' || $char === '}') {
                $depth--;
            } elseif ($depth === 0 && $char === ' ') {
                return substr($text, $i + 1);
            }
        }

        return '';
    }

    private function findCodeReference(string $text): ?string
    {
        if (preg_match(self::ATTRIBUTE_PATTERN, $text, $m)) {
            return '#[' . $m[1] . ']';
        }

        if (preg_match(self::STATIC_MEMBER_PATTERN, $text, $m)) {
            return $m[1];
        }

        if (preg_match(self::ARROW_CALL_PATTERN, $text, $m)) {
            return trim($m[1]);
        }

        if (preg_match(self::FQCN_PATTERN, $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function addTodoComment(Node $node, string $reference): bool
    {
        $todoText = sprintf($this->message, $reference);
        $marker = '[ForbidHardcodedCodeReferencesInCommentsRector]';

        $comments = $node->getComments();
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return false;
            }
        }

        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return true;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Flag code references in comments that are not reachable by AST refactoring tools, keeping comments evergreen',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class Router
{
    /** Dispatch to the handler declared on the #[RouteOperation]. */
    public function dispatch(): void {}
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
class Router
{
    // TODO: [ForbidHardcodedCodeReferencesInCommentsRector] '#[RouteOperation]' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md
    /** Dispatch to the handler declared on the #[RouteOperation]. */
    public function dispatch(): void {}
}
CODE_SAMPLE,
                    ['mode' => 'warn'],
                ),
            ]
        );
    }
}
