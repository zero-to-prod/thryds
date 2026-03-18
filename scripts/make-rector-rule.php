<?php

declare(strict_types=1);

/**
 * Generate a custom Rector rule skeleton with test, config, and fixture.
 *
 * Usage: docker compose exec web php scripts/make-rector-rule.php <RuleName> [--mode=auto|warn] [--message="..."]
 * Via Composer: ./run generate:rector-rule -- <RuleName> [--mode=auto|warn] [--message="..."]
 *
 * Generates:
 *   utils/rector/src/<RuleName>.php
 *   utils/rector/tests/<RuleName>/<RuleName>Test.php
 *   utils/rector/tests/<RuleName>/config/configured_rule.php
 *   utils/rector/tests/<RuleName>/Fixture/example.php.inc
 *   utils/rector/docs/<RuleName>.md
 *
 * Appends import and registration to rector.php.
 */

$base_dir = realpath(path: __DIR__ . '/..');

// --- Parse arguments ---

$args = array_slice($argv, offset: 1);

if ($args === []) {
    echo "Usage: php scripts/make-rector-rule.php <RuleName> [--mode=auto|warn] [--message=\"...\"]\n";
    exit(1);
}

$rule_name = null;
$mode = 'auto';
$message = '';

foreach ($args as $arg) {
    if (str_starts_with(haystack: $arg, needle: '--mode=')) {
        $mode = substr(string: $arg, offset: 7);
    } elseif (str_starts_with(haystack: $arg, needle: '--message=')) {
        $message = substr(string: $arg, offset: 10);
    } elseif (!str_starts_with(haystack: $arg, needle: '--')) {
        $rule_name = $arg;
    }
}

// --- Validate ---

if ($rule_name === null) {
    echo "Error: Rule name is required.\n";
    exit(1);
}

if (!str_ends_with(haystack: $rule_name, needle: 'Rector')) {
    echo "Error: Rule name must end with 'Rector'.\n";
    exit(1);
}

if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $rule_name)) {
    echo "Error: Rule name must be PascalCase.\n";
    exit(1);
}

if (!in_array(needle: $mode, haystack: ['auto', 'warn'], strict: true)) {
    echo "Error: Mode must be 'auto' or 'warn'.\n";
    exit(1);
}

if ($mode === 'warn' && $message === '') {
    echo "Error: Warn-mode rules require a --message.\n";
    exit(1);
}

// Auto-append doc pointer to message if not already present
$doc_pointer = "See: utils/rector/docs/{$rule_name}.md";
if ($message !== '' && !str_contains(haystack: $message, needle: $doc_pointer)) {
    $message = rtrim(string: $message, characters: '.') . '. ' . $doc_pointer;
}

$rule_path = $base_dir . '/utils/rector/src/' . $rule_name . '.php';

if (file_exists(filename: $rule_path)) {
    echo "Error: Rule already exists at utils/rector/src/{$rule_name}.php\n";
    exit(1);
}

// --- Generate rule class ---

$message_default = $mode === 'warn' ? $message : '';

$rule_content = <<<PHP
<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class {$rule_name} extends AbstractRector implements ConfigurableRectorInterface
{
    private string \$mode = '{$mode}';

    private string \$message = '{$message_default}';

    public function configure(array \$configuration): void
    {
        \$this->mode = \$configuration['mode'] ?? '{$mode}';
        \$this->message = \$configuration['message'] ?? '{$message_default}';
    }

    public function getNodeTypes(): array
    {
        // TODO: return the AST node types this rule inspects
        return [];
    }

    public function refactor(Node \$node): ?Node
    {
        // TODO: implement transformation logic
        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('{$rule_name} description', [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
// TODO: code before transformation
CODE_SAMPLE,
                <<<'CODE_SAMPLE'
// TODO: code after transformation
CODE_SAMPLE,
                [
                    'mode' => '{$mode}',
                ],
            ),
        ]);
    }
}

PHP;

// --- Generate test class ---

$test_content = <<<PHP
<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\\{$rule_name};

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class {$rule_name}Test extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string \$filePath): void
    {
        \$this->doTestFile(\$filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}

PHP;

// --- Generate test config ---

$config_content = <<<PHP
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\\{$rule_name};

return static function (RectorConfig \$rectorConfig): void {
    \$rectorConfig->ruleWithConfiguration({$rule_name}::class, [
        'mode' => '{$mode}',
    ]);
};

PHP;

// --- Generate fixture ---

if ($mode === 'warn') {
    $fixture_content = <<<'FIXTURE'
<?php
// TODO: code that triggers the warning
?>
-----
<?php
// MESSAGE_PLACEHOLDER
// TODO: code that triggers the warning
?>

FIXTURE;
    $fixture_content = str_replace(
        search: 'MESSAGE_PLACEHOLDER',
        replace: $message,
        subject: $fixture_content,
    );
} else {
    $fixture_content = <<<'FIXTURE'
<?php
// TODO: code before transformation
?>
-----
<?php
// TODO: code after transformation
?>

FIXTURE;
}

// --- Generate doc skeleton ---

$mode_label = $mode === 'warn' ? '`warn`' : '`auto` or `warn` (configurable)';
$autofix_label = $mode === 'auto' ? 'Yes' : 'No';
$message_display = $message !== '' ? $message : 'TODO: add message';

$warn_section = $mode === 'warn' ? <<<MD

### In `warn` mode

```
// {$message_display}
```
MD : '';

$resolution_section = $mode === 'warn' ? <<<MD

## Resolution

When you see the TODO comment from this rule:

1. TODO: step one
2. TODO: step two
MD : '';

$doc_content = <<<MD
# {$rule_name}

TODO: One-sentence description of what the rule enforces.

**Category:** TODO
**Mode:** {$mode_label}
**Auto-fix:** {$autofix_label}

## Rationale

TODO: Why this rule exists. The principle or project convention it enforces.

## What It Detects

TODO: The code pattern(s) that trigger this rule.

## Transformation

### In `auto` mode

TODO: Describe exactly what change is made to the code. (Remove this section if auto is a no-op.)
{$warn_section}

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'{$mode}'` | `'auto'` to transform, `'warn'` to add a TODO comment |

## Example

### Before

```php
// TODO: add example from test fixture
```

### After

```php
// TODO: add example from test fixture
```
{$resolution_section}

## Related Rules

None yet.
MD;

// --- Write files ---

$test_dir = $base_dir . '/utils/rector/tests/' . $rule_name;
$config_dir = $test_dir . '/config';
$fixture_dir = $test_dir . '/Fixture';

$dirs = [$config_dir, $fixture_dir];
foreach ($dirs as $dir) {
    if (!is_dir(filename: $dir)) {
        mkdir(directory: $dir, permissions: 0755, recursive: true);
    }
}

$docs_dir = $base_dir . '/utils/rector/docs';

$files = [
    $rule_path => $rule_content,
    $test_dir . '/' . $rule_name . 'Test.php' => $test_content,
    $config_dir . '/configured_rule.php' => $config_content,
    $fixture_dir . '/example.php.inc' => $fixture_content,
    $docs_dir . '/' . $rule_name . '.md' => $doc_content,
];

foreach ($files as $path => $content) {
    file_put_contents(filename: $path, data: $content);
    $relative = str_replace(
        search: $base_dir . '/',
        replace: '',
        subject: $path,
    );
    echo "  Created {$relative}\n";
}

// --- Append to rector.php ---

$rector_path = $base_dir . '/rector.php';
$rector_content = file_get_contents(filename: $rector_path);

if ($rector_content === false) {
    echo "\nWarning: Could not read rector.php. Add the import and registration manually.\n";
    exit(0);
}

// Add import: find the last "use Utils\Rector\Rector\" line and insert after it
$import_line = "use Utils\\Rector\\Rector\\{$rule_name};";

if (str_contains(haystack: $rector_content, needle: $import_line)) {
    echo "\n  Import already exists in rector.php\n";
} else {
    $last_import_pos = strrpos(haystack: $rector_content, needle: 'use Utils\\Rector\\Rector\\');
    if ($last_import_pos !== false) {
        $end_of_line = strpos(haystack: $rector_content, needle: "\n", offset: $last_import_pos);
        if ($end_of_line !== false) {
            $rector_content = substr(string: $rector_content, offset: 0, length: $end_of_line + 1)
                . $import_line . "\n"
                . substr(string: $rector_content, offset: $end_of_line + 1);
        }
    }
}

// Add registration: insert before the closing "};'
$message_line = $mode === 'warn'
    ? "\n        'message' => '{$message}',"
    : '';

$registration = <<<PHP
    \$rectorConfig->ruleWithConfiguration({$rule_name}::class, [
        'mode' => '{$mode}',{$message_line}
    ]);
PHP;

$closing_pos = strrpos(haystack: $rector_content, needle: '};');
if ($closing_pos !== false) {
    $rector_content = substr(string: $rector_content, offset: 0, length: $closing_pos)
        . "\n" . $registration . "\n" . substr(string: $rector_content, offset: $closing_pos);
}

file_put_contents(filename: $rector_path, data: $rector_content);
echo "  Updated rector.php\n";

echo "\nDone. Next steps:\n";
echo "  1. Fill in getNodeTypes() and refactor() in utils/rector/src/{$rule_name}.php\n";
echo "  2. Replace fixture TODOs in utils/rector/tests/{$rule_name}/Fixture/example.php.inc\n";
echo "  3. Fill in utils/rector/docs/{$rule_name}.md (rationale, examples from fixtures, resolution steps)\n";
echo "  4. Run: ./run test:rector\n";
