<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PhpParser\Parser\RectorParser;
use Rector\Rector\AbstractRector;
use Rector\Util\Reflection\PrivatesAccessor;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class VerticalAttributeArgsRector extends AbstractRector implements ConfigurableRectorInterface
{
    private int $minArgs = 2;

    private string $mode = 'auto';

    private string $message = 'TODO: [VerticalAttributeArgsRector] Attribute %s has multiple args — use vertical formatting.';

    /** @var AttributeGroup[] */
    private array $pendingAttrGroups = [];

    public function __construct(
        private readonly RectorParser $rectorParser,
        private readonly PrivatesAccessor $privatesAccessor,
    ) {}

    public function configure(array $configuration): void
    {
        $this->minArgs = $configuration['minArgs'] ?? 2;
        $this->mode = $configuration['mode'] ?? 'auto';
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Format attribute arguments vertically when there are 2 or more args on the same line',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
#[ClosedSet(Domain::foo, addCase: 'bar')]
enum Foo: string {}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
#[ClosedSet(
    Domain::foo,
    addCase: 'bar'
)]
enum Foo: string {}
CODE_SAMPLE,
                    ['minArgs' => 2, 'mode' => 'auto']
                ),
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [AttributeGroup::class];
    }

    /**
     * @param AttributeGroup $node
     */
    public function refactor(Node $node): ?Node
    {
        $needsReformat = false;

        foreach ($node->attrs as $attr) {
            if (count($attr->args) < $this->minArgs) {
                continue;
            }

            if ($this->isAlreadyMultiline($attr)) {
                continue;
            }

            $needsReformat = true;
            break;
        }

        if (!$needsReformat) {
            return null;
        }

        if ($this->mode !== 'auto') {
            return $this->addTodoComment($node);
        }

        $this->pendingAttrGroups[] = $node;

        return null;
    }

    public function afterTraverse(array $nodes): ?array
    {
        if ($this->pendingAttrGroups === []) {
            return null;
        }

        $content = $this->file->getFileContent();
        $oldTokens = $this->file->getOldTokens();

        // Process in reverse order to preserve character positions
        $reversed = array_reverse($this->pendingAttrGroups);
        $this->pendingAttrGroups = [];

        foreach ($reversed as $attrGroup) {
            $content = $this->reformatAttrGroup($attrGroup, $content, $oldTokens);
        }

        // Re-parse the modified content
        $stmtsAndTokens = $this->rectorParser->parseFileContentToStmtsAndTokens($content);
        $newStmts = $stmtsAndTokens->getStmts();
        $newTokens = $stmtsAndTokens->getTokens();

        // Set origNode = self on all nodes so the format-preserving printer
        // treats everything as TYPE_KEEP and uses the new multiline tokens
        $this->setOrigNodeToSelf($newStmts);

        // Update File internals — hydrateStmtsAndTokens() cannot be called twice,
        // so we bypass its guard via PrivatesAccessor
        $this->privatesAccessor->setPrivateProperty($this->file, 'oldStmts', $newStmts);
        $this->privatesAccessor->setPrivateProperty($this->file, 'newStmts', $newStmts);
        $this->privatesAccessor->setPrivateProperty($this->file, 'oldTokens', $newTokens);

        return $newStmts;
    }

    private function isAlreadyMultiline(Attribute $attr): bool
    {
        if ($attr->args === []) {
            return true;
        }

        return $attr->args[0]->getStartLine() > $attr->name->getEndLine();
    }

    /**
     * @param array<int, \PhpParser\Token> $oldTokens
     */
    private function reformatAttrGroup(AttributeGroup $attrGroup, string $content, array $oldTokens): string
    {
        $startToken = $oldTokens[$attrGroup->getStartTokenPos()];
        $endToken = $oldTokens[$attrGroup->getEndTokenPos()];
        $attrGroupStartChar = $startToken->pos;
        $attrGroupEndChar = $endToken->pos + strlen($endToken->text);

        // Determine indentation of the line that contains #[
        $lineStart = strrpos(substr($content, 0, $attrGroupStartChar), "\n");
        $lineStart = ($lineStart === false) ? 0 : $lineStart + 1;
        $indent = '';
        for ($i = $lineStart; $i < $attrGroupStartChar; $i++) {
            $char = $content[$i];
            if ($char === ' ' || $char === "\t") {
                $indent .= $char;
            } else {
                break;
            }
        }

        $argIndent = $indent . '    ';

        $newAttrGroupText = $this->buildAttrGroupText($attrGroup, $content, $oldTokens, $indent, $argIndent);

        return substr($content, 0, $attrGroupStartChar) . $newAttrGroupText . substr($content, $attrGroupEndChar);
    }

    /**
     * @param array<int, \PhpParser\Token> $oldTokens
     */
    private function buildAttrGroupText(
        AttributeGroup $attrGroup,
        string $content,
        array $oldTokens,
        string $indent,
        string $argIndent,
    ): string {
        $attrTexts = [];

        foreach ($attrGroup->attrs as $attr) {
            if (count($attr->args) < $this->minArgs || $this->isAlreadyMultiline($attr)) {
                // Keep attribute as-is
                $startChar = $oldTokens[$attr->getStartTokenPos()]->pos;
                $endToken = $oldTokens[$attr->getEndTokenPos()];
                $endChar = $endToken->pos + strlen($endToken->text);
                $attrTexts[] = substr($content, $startChar, $endChar - $startChar);
                continue;
            }

            // Get attribute name text
            $nameStartChar = $oldTokens[$attr->name->getStartTokenPos()]->pos;
            $nameEndToken = $oldTokens[$attr->name->getEndTokenPos()];
            $nameEndChar = $nameEndToken->pos + strlen($nameEndToken->text);
            $attrName = substr($content, $nameStartChar, $nameEndChar - $nameStartChar);

            // Extract each arg's text from original file content
            $argLines = [];
            foreach ($attr->args as $arg) {
                $argStartChar = $oldTokens[$arg->getStartTokenPos()]->pos;
                $argEndToken = $oldTokens[$arg->getEndTokenPos()];
                $argEndChar = $argEndToken->pos + strlen($argEndToken->text);
                $argText = substr($content, $argStartChar, $argEndChar - $argStartChar);
                $argText = $this->adjustHeredocClosingLabel($argText, $argIndent);
                $argLines[] = $argIndent . $argText;
            }

            $attrTexts[] = $attrName . "(\n" . implode(",\n", $argLines) . "\n" . $indent . ')';
        }

        return '#[' . implode(', ', $attrTexts) . ']';
    }

    /**
     * When a heredoc closing label has less indentation than the target,
     * update it to use $targetIndent so the label aligns with the arg block.
     */
    private function adjustHeredocClosingLabel(string $argText, string $targetIndent): string
    {
        // Match heredoc: <<<LABEL ... \n{currentIndent}LABEL at end of string
        if (!preg_match('/<<<(\w+)\n/', $argText, $labelMatch)) {
            return $argText;
        }

        $label = $labelMatch[1];
        $escapedLabel = preg_quote($label, '/');

        // Match the closing label line at the very end of argText
        if (!preg_match('/\n(\s*)' . $escapedLabel . '$/', $argText, $closingMatch)) {
            return $argText;
        }

        $currentIndent = $closingMatch[1];

        if ($currentIndent === $targetIndent) {
            return $argText;
        }

        // Replace current closing-label indentation with target indentation
        return preg_replace(
            '/\n' . preg_quote($currentIndent, '/') . $escapedLabel . '$/',
            "\n" . $targetIndent . $label,
            $argText
        ) ?? $argText;
    }

    /**
     * @param Node[] $nodes
     */
    private function setOrigNodeToSelf(array $nodes): void
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class extends NodeVisitorAbstract {
            public function enterNode(Node $node): Node
            {
                $node->setAttribute(AttributeKey::ORIGINAL_NODE, $node);

                return $node;
            }
        });
        $traverser->traverse($nodes);
    }

    private function addTodoComment(AttributeGroup $node): AttributeGroup
    {
        $attrNames = array_map(
            fn(Attribute $attr): string => (string) $attr->name,
            $node->attrs
        );
        $todoText = sprintf($this->message, implode(', ', $attrNames));
        $marker = strstr($this->message, '%', true) ?: $this->message;

        foreach ($node->getComments() as $comment) {
            if (str_contains($comment->getText(), $marker)) {
                return $node;
            }
        }

        $comments = $node->getComments();
        array_unshift($comments, new Comment('// ' . $todoText));
        $node->setAttribute('comments', $comments);

        return $node;
    }
}
