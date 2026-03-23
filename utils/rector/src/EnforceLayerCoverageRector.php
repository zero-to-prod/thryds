<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class EnforceLayerCoverageRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $layerEnum = 'Layer';

    private string $segmentAttribute = 'Segment';

    /** @var list<string> */
    private array $srcDirs = ['src'];

    private string $mode = 'warn';

    private string $message = 'TODO: [EnforceLayerCoverageRector] Namespace segment "%s" has no corresponding Layer enum case — add one to ensure attribute graph visibility.';

    public function configure(array $configuration): void
    {
        $this->layerEnum = $configuration['layerEnum'] ?? $this->layerEnum;
        $this->segmentAttribute = $configuration['segmentAttribute'] ?? $this->segmentAttribute;
        $srcDir = $configuration['srcDir'] ?? null;
        if (is_array($srcDir)) {
            $this->srcDirs = $srcDir;
        } elseif (is_string($srcDir)) {
            $this->srcDirs = str_contains($srcDir, ',') ? explode(',', $srcDir) : [$srcDir];
        }
        $this->mode = $configuration['mode'] ?? $this->mode;
        $this->message = $configuration['message'] ?? $this->message;
    }

    public function getNodeTypes(): array
    {
        return [Enum_::class];
    }

    /**
     * @param Enum_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ((string) $node->name !== $this->shortName($this->layerEnum)) {
            return null;
        }

        $coveredSegments = $this->extractCoveredSegments($node);
        $actualSegments = $this->discoverNamespaceSegments();

        $uncovered = array_diff($actualSegments, $coveredSegments);

        if ($uncovered === []) {
            return null;
        }

        return $this->addTodoComments($node, $uncovered);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Every first-level namespace directory under src/ must have a corresponding Layer enum case for attribute graph visibility.',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
enum Layer: string
{
    case controllers = 'controllers';
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
// TODO: [EnforceLayerCoverageRector] Namespace segment "Queries" has no corresponding Layer enum case — add one to ensure attribute graph visibility.
enum Layer: string
{
    case controllers = 'controllers';
}
CODE_SAMPLE,
                    [
                        'layerEnum' => 'Layer',
                        'segmentAttribute' => 'Segment',
                        'srcDir' => 'src',
                        'mode' => 'warn',
                    ]
                ),
            ]
        );
    }

    /**
     * Builds the set of namespace segments covered by existing enum cases.
     *
     * Each case covers either the segment declared by #[Segment], or the PascalCase of the case name.
     *
     * @return list<string>
     */
    private function extractCoveredSegments(Enum_ $node): array
    {
        $segments = [];

        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof EnumCase) {
                continue;
            }

            $segment = $this->resolveSegment($stmt);
            $segments[] = $segment;
        }

        return $segments;
    }

    /**
     * Resolves the namespace segment a case covers: #[Segment] value if present, otherwise PascalCase of the case name.
     */
    private function resolveSegment(EnumCase $case): string
    {
        $shortAttr = $this->shortName($this->segmentAttribute);

        foreach ($case->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->getName($attr->name);
                $nameShort = $this->shortName($name ?? '');
                if ($name === $this->segmentAttribute || $name === $shortAttr || $nameShort === $shortAttr) {
                    $arg = $attr->args[0] ?? null;
                    if ($arg !== null && $arg->value instanceof Node\Scalar\String_) {
                        return $arg->value->value;
                    }
                }
            }
        }

        return ucfirst((string) $case->name);
    }

    /**
     * Discovers first-level subdirectories under each srcDir that contain PHP files.
     *
     * @return list<string>
     */
    private function discoverNamespaceSegments(): array
    {
        $segments = [];

        foreach ($this->srcDirs as $srcDir) {
            $srcPath = $this->resolveSrcPath($srcDir);

            if (! is_dir($srcPath)) {
                continue;
            }

            /** @var \DirectoryIterator $entry */
            foreach (new \DirectoryIterator($srcPath) as $entry) {
                if ($entry->isDot() || ! $entry->isDir()) {
                    continue;
                }

                $segments[] = $entry->getFilename();
            }
        }

        $segments = array_unique($segments);
        sort($segments);

        return $segments;
    }

    /**
     * Resolves a single srcDir to an absolute path using the processed file's location as anchor.
     */
    private function resolveSrcPath(string $srcDir): string
    {
        if (str_starts_with($srcDir, '/')) {
            return $srcDir;
        }

        // Walk up from the file being processed to find the project root containing srcDir.
        $filePath = $this->file->getFilePath();
        $dir = dirname($filePath);

        while ($dir !== '/' && $dir !== '.') {
            $candidate = $dir . '/' . $srcDir;
            if (is_dir($candidate)) {
                return $candidate;
            }
            $dir = dirname($dir);
        }

        return $srcDir;
    }

    private function addTodoComments(Enum_ $node, array $uncovered): Enum_
    {
        $marker = 'EnforceLayerCoverageRector';
        $comments = $node->getComments();

        // Remove any stale TODO comments from this rule (segments may have been resolved since last run).
        $comments = array_values(array_filter(
            $comments,
            static fn(Comment $c): bool => ! str_contains($c->getText(), $marker),
        ));

        // Add one comment per uncovered segment, in sorted order.
        sort($uncovered);
        $newComments = [];
        foreach ($uncovered as $segment) {
            $newComments[] = new Comment('// ' . sprintf($this->message, $segment));
        }

        $node->setAttribute('comments', [...$newComments, ...$comments]);

        return $node;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
