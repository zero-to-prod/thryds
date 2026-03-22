<?php

declare(strict_types=1);

/**
 * Generate a requirement stub: a YAML block in requirements.yaml and (when
 * applicable) a pre-named test class so no method names need to be invented.
 *
 * Usage: docker compose exec web php scripts/generate-requirement.php <ID> --type=... --verification=... [--title="..."]
 * Via Composer: ./run generate:requirement -- <ID> --type=... --verification=... [--title="..."]
 *
 * Verification values: integration-test, unit-test, rector-rule, architecture, manual
 *
 * Generates:
 *   Appends a block to requirements.yaml
 *   tests/Integration/<IDnodash>Test.php   (verification=integration-test)
 *   tests/Unit/<IDnodash>Test.php          (verification=unit-test)
 */

$base_dir = (string) realpath(__DIR__ . '/..');

// ── Parse arguments ───────────────────────────────────────────────────────────

$args = array_slice($argv, offset: 1);

if ($args === []) {
    echo "Usage: php scripts/generate-requirement.php <ID> --type=functional|non-functional --verification=<value> [--title=\"...\"]\n";
    echo "Verification: integration-test, unit-test, rector-rule, architecture, manual\n";
    exit(1);
}

$id = null;
$type = null;
$verification = null;
$title = null;

foreach ($args as $arg) {
    if (str_starts_with(haystack: $arg, needle: '--type=')) {
        $type = substr(string: $arg, offset: 7);
    } elseif (str_starts_with(haystack: $arg, needle: '--verification=')) {
        $verification = substr(string: $arg, offset: 15);
    } elseif (str_starts_with(haystack: $arg, needle: '--title=')) {
        $title = substr(string: $arg, offset: 8);
    } elseif (! str_starts_with(haystack: $arg, needle: '--')) {
        $id = strtoupper(string: $arg);
    }
}

// ── Load config ──────────────────────────────────────────────────────────────

require_once $base_dir . '/vendor/autoload.php';

$config               = \Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/requirements-config.yaml');
$valid_verifications  = $config['all_verifications'];
$testable_verifications = $config['testable_verifications'];
$test_namespace_map   = $config['test_namespaces'];

// ── Validate ──────────────────────────────────────────────────────────────────

$valid_types = ['functional', 'non-functional'];

if ($id === null) {
    echo "Error: Requirement ID is required (e.g. AUTH-001).\n";
    exit(1);
}

if (! preg_match('/^[A-Z]+-\d+$/', $id)) {
    echo "Error: ID must match <DOMAIN>-<NUMBER> (e.g. AUTH-001, PERF-002).\n";
    exit(1);
}

if ($type === null) {
    echo 'Error: --type is required. Values: ' . implode(', ', $valid_types) . "\n";
    exit(1);
}

if (! in_array(needle: $type, haystack: $valid_types, strict: true)) {
    echo 'Error: --type must be one of: ' . implode(', ', $valid_types) . "\n";
    exit(1);
}

if ($verification === null) {
    echo 'Error: --verification is required. Values: ' . implode(', ', $valid_verifications) . "\n";
    exit(1);
}

if (! in_array(needle: $verification, haystack: $valid_verifications, strict: true)) {
    echo 'Error: --verification must be one of: ' . implode(', ', $valid_verifications) . "\n";
    exit(1);
}

// ── Check for duplicate ID ────────────────────────────────────────────────────

$requirements_file = $base_dir . '/requirements.yaml';

/** @var array<string, mixed> $existing */
$existing = \Symfony\Component\Yaml\Yaml::parseFile($requirements_file);

if (isset($existing[$id])) {
    echo "Error: '$id' already exists in requirements.yaml.\n";
    exit(1);
}

// ── Derive names ──────────────────────────────────────────────────────────────

$criterion_id = $id . '-a';
$method_name = 'test_' . str_replace(search: '-', replace: '_', subject: $criterion_id);
$test_class = str_replace(search: '-', replace: '', subject: $id) . 'Test';
$title ??= 'TODO: one-line title';

// ── Build YAML block ──────────────────────────────────────────────────────────

$yaml_block = <<<YAML

{$id}:
  type: {$type}
  title: {$title}
  description: >
    TODO
  acceptance-criteria:
    - id: {$criterion_id}
      text: TODO
  verification: {$verification}
YAML;

// ── Build test stub ───────────────────────────────────────────────────────────

$test_file = null;
$test_content = null;

if (isset($testable_verifications[$verification])) {
    $subdir = $testable_verifications[$verification];
    $test_file = $base_dir . '/tests/' . $subdir . '/' . $test_class . '.php';

    if (file_exists(filename: $test_file)) {
        echo "Error: Test file already exists at tests/$subdir/$test_class.php\n";
        exit(1);
    }

    $ns_config = $test_namespace_map[$verification];
    $namespace = $ns_config['namespace'];
    $extends   = $ns_config['extends'];
    $extra_use = $ns_config['extra_use'] !== '' ? "\n" . $ns_config['extra_use'] : '';

    $test_content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};
{$extra_use}
use PHPUnit\\Framework\\Attributes\\Test;

final class {$test_class} extends {$extends}
{
    #[Test]
    // Criterion: {$criterion_id} — TODO
    public function {$method_name}(): void
    {
        \$this->markTestIncomplete('Implement acceptance criterion {$criterion_id}');
    }
}
PHP;
}

// ── Write files ───────────────────────────────────────────────────────────────

file_put_contents(filename: $requirements_file, data: $yaml_block . "\n", flags: FILE_APPEND);

$updated = ['requirements.yaml'];
$created = [];

if ($test_file !== null && $test_content !== null) {
    file_put_contents(filename: $test_file, data: $test_content . "\n");
    $created[] = str_replace(search: $base_dir . '/', replace: '', subject: $test_file);
}

// ── Next steps ────────────────────────────────────────────────────────────────

$next_steps = [
    ['action' => "Fill in description and acceptance criteria for {$id} in requirements.yaml — use present-tense declaratives"],
    ['action' => "Add further criteria ({$criterion_id}, then {$id}-b, {$id}-c, ...) as needed — one criterion per assertion"],
];

if ($test_file !== null) {
    $relative = str_replace(search: $base_dir . '/', replace: '', subject: $test_file);
    $next_steps[] = ['action' => "Implement {$method_name}() in {$relative} and add a method per additional criterion"];
    $next_steps[] = ['action' => 'Verify coverage and run tests', 'command' => './run check:requirements && ./run test'];
} else {
    $next_steps[] = ['action' => "verification: {$verification} — no test file generated (see docs/acceptance-criteria.md)"];
    $next_steps[] = ['action' => 'Verify coverage', 'command' => './run check:requirements'];
}

echo json_encode(
    value: [
        'created'    => $created,
        'updated'    => $updated,
        'next_steps' => $next_steps,
    ],
    flags: JSON_PRETTY_PRINT,
) . "\n";
