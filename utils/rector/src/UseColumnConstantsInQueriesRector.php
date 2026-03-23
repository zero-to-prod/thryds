<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use ReflectionClass;
use ReflectionClassConstant;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use ZeroToProd\Framework\Attributes\Table;

/**
 * Replaces magic string column names and table names in Database query calls
 * with Table class constants and ::tableName() references.
 *
 * Transforms parameter array keys (':col' → ':' . Table::col) and SQL string
 * fragments (bare table/column names → class constant references).
 */
final class UseColumnConstantsInQueriesRector extends AbstractRector implements ConfigurableRectorInterface
{
    private string $mode = 'auto';

    /** @var array<string, array{class: string, columns: array<string, string>}> table_name => info */
    private array $tableMap = [];

    private const array DATABASE_METHODS = ['execute', 'all', 'one', 'scalar', 'insert'];

    public function configure(array $configuration): void
    {
        $this->mode = $configuration['mode'] ?? 'auto';

        foreach ($configuration['tableClasses'] ?? [] as $tableClass) {
            $this->registerTableClass($tableClass);
        }
    }

    private function registerTableClass(string $className): void
    {
        $ref = new ReflectionClass($className);

        $tableAttrs = $ref->getAttributes(Table::class);
        if ($tableAttrs === []) {
            return;
        }

        $tableName = $tableAttrs[0]->newInstance()->TableName->value;

        $columns = [];
        foreach ($ref->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC) as $const) {
            if ($const->getType()?->getName() === 'string') {
                $columns[$const->getValue()] = $const->getName();
            }
        }

        $this->tableMap[$tableName] = [
            'class' => $className,
            'columns' => $columns,
        ];
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($this->mode !== 'auto') {
            return null;
        }

        if (!$node instanceof MethodCall) {
            return null;
        }

        $methodName = $node->name instanceof Identifier ? $node->name->name : null;
        if ($methodName === null || !in_array($methodName, self::DATABASE_METHODS, true)) {
            return null;
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return null;
        }

        $sqlArg = $node->args[0]->value;
        if (!$sqlArg instanceof String_) {
            return null;
        }

        // Only transform DML statements — DDL (CREATE, ALTER, DROP) has column names
        // embedded in definitions and comments where replacement causes false positives
        if (!preg_match('/^\s*(INSERT|SELECT|UPDATE|DELETE)\b/i', $sqlArg->value)) {
            return null;
        }

        $tableInfo = $this->resolveTable($sqlArg->value);
        if ($tableInfo === null) {
            return null;
        }

        $changed = false;

        $newSqlExpr = $this->transformSql($sqlArg->value, $tableInfo);
        if ($newSqlExpr !== null) {
            $node->args[0]->value = $newSqlExpr;
            $changed = true;
        }

        if (isset($node->args[1]) && $node->args[1] instanceof Arg && $node->args[1]->value instanceof Array_) {
            if ($this->transformParams($node->args[1]->value, $tableInfo)) {
                $changed = true;
            }
        }

        return $changed ? $node : null;
    }

    /** @return array{class: string, columns: array<string, string>, tableName: string}|null */
    private function resolveTable(string $sql): ?array
    {
        foreach ($this->tableMap as $tableName => $info) {
            if (preg_match('/\b' . preg_quote($tableName, '/') . '\b/', $sql)) {
                return [...$info, 'tableName' => $tableName];
            }
        }

        return null;
    }

    private function transformSql(string $sql, array $tableInfo): ?Node\Expr
    {
        $tableName = $tableInfo['tableName'];
        $className = $tableInfo['class'];
        $columns = $tableInfo['columns'];

        // Sort column names longest-first so regex prefers longer matches
        $sortedColValues = array_keys($columns);
        usort($sortedColValues, static fn(string $a, string $b): int => strlen($b) - strlen($a));

        $quotedTable = preg_quote($tableName, '/');
        $quotedCols = array_map(static fn(string $c): string => preg_quote($c, '/'), $sortedColValues);
        $colAlternation = implode('|', $quotedCols);

        // Match: `table` or table | :column | bare column (all with word boundaries)
        $pattern = '/`?' . $quotedTable . '`?|:(' . $colAlternation . ')\\b|\\b(' . $colAlternation . ')\\b/';

        preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        if ($matches === []) {
            return null;
        }

        $segments = [];
        $lastPos = 0;
        $changed = false;

        foreach ($matches as $match) {
            $fullMatch = $match[0][0];
            $offset = $match[0][1];

            if ($offset > $lastPos) {
                $segments[] = new String_(substr($sql, $lastPos, $offset - $lastPos));
            }

            if (str_contains($fullMatch, $tableName) && ($fullMatch === $tableName || $fullMatch === '`' . $tableName . '`')) {
                // Table name → TableClass::tableName()
                $segments[] = new StaticCall(
                    new FullyQualified($className),
                    new Identifier('tableName')
                );
                $changed = true;
            } elseif (str_starts_with($fullMatch, ':')) {
                // Param placeholder :col → keep ':' in preceding string, emit ClassConstFetch
                $colValue = substr($fullMatch, 1);
                if (isset($columns[$colValue])) {
                    // Append ':' to the last string segment or create a new one
                    $lastIdx = count($segments) - 1;
                    if ($lastIdx >= 0 && $segments[$lastIdx] instanceof String_) {
                        $segments[$lastIdx] = new String_($segments[$lastIdx]->value . ':');
                    } else {
                        $segments[] = new String_(':');
                    }
                    $segments[] = new ClassConstFetch(
                        new FullyQualified($className),
                        new Identifier($columns[$colValue])
                    );
                    $changed = true;
                } else {
                    $segments[] = new String_($fullMatch);
                }
            } elseif (isset($columns[$fullMatch])) {
                // Bare column name → TableClass::col
                $segments[] = new ClassConstFetch(
                    new FullyQualified($className),
                    new Identifier($columns[$fullMatch])
                );
                $changed = true;
            } else {
                $segments[] = new String_($fullMatch);
            }

            $lastPos = $offset + strlen($fullMatch);
        }

        if (!$changed) {
            return null;
        }

        if ($lastPos < strlen($sql)) {
            $segments[] = new String_(substr($sql, $lastPos));
        }

        // Filter empty string segments
        $segments = array_values(array_filter($segments, static fn(Node\Expr $s): bool => !($s instanceof String_ && $s->value === '')));

        if ($segments === []) {
            return null;
        }

        if (count($segments) === 1) {
            return $segments[0];
        }

        // Build left-associative Concat chain
        $expr = $segments[0];
        for ($i = 1, $count = count($segments); $i < $count; $i++) {
            $expr = new Concat($expr, $segments[$i]);
        }

        return $expr;
    }

    private function transformParams(Array_ $array, array $tableInfo): bool
    {
        $columns = $tableInfo['columns'];
        $className = $tableInfo['class'];
        $changed = false;

        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem || !$item->key instanceof String_) {
                continue;
            }

            $key = $item->key->value;
            if (!str_starts_with($key, ':')) {
                continue;
            }

            $colName = substr($key, 1);
            if (!isset($columns[$colName])) {
                continue;
            }

            $item->key = new Concat(
                new String_(':'),
                new ClassConstFetch(
                    new FullyQualified($className),
                    new Identifier($columns[$colName])
                )
            );

            // Remove ForbidMagicStringArrayKeyRector TODO comments
            $comments = $item->getComments();
            $filtered = array_values(array_filter(
                $comments,
                static fn($c): bool => !str_contains($c->getText(), 'ForbidMagicStringArrayKeyRector')
            ));
            if (count($filtered) !== count($comments)) {
                $item->setAttribute('comments', $filtered);
            }

            $changed = true;
        }

        return $changed;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace magic string column/table names in Database query calls with Table class constants and ::tableName()',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$this->Database->execute(
    'INSERT INTO users (id, email) VALUES (:id, :email)',
    [':id' => $id, ':email' => $email],
);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$this->Database->execute(
    'INSERT INTO ' . User::tableName() . ' (' . User::id . ', ' . User::email . ') VALUES (:' . User::id . ', :' . User::email . ')',
    [':' . User::id => $id, ':' . User::email => $email],
);
CODE_SAMPLE,
                    [
                        'mode' => 'auto',
                        'tableClasses' => ['ZeroToProd\\Thryds\\Tables\\User'],
                    ],
                ),
            ]
        );
    }
}
