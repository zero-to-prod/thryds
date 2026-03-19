<?php

declare(strict_types=1);

/**
 * Validates requirement coverage against acceptance-criteria in requirements.yaml.
 *
 * Fix 2: Orphan detection — flags test methods referencing removed criterion IDs.
 * Fix 4: Verification enforcement — asserts test files exist for testable requirements.
 * Fix 7: Style validation — criterion text must not use "should"/"must", "::", or ".php".
 * Authority validation — when present, asserts type/id/section/quote are all provided.
 * Links validation — known rel values; internal hrefs must point to existing files.
 *
 * Exit 0 if no violations. Exit 1 if violations found.
 * Output: JSON { ok: bool, violations: [{ id?, file?, line?, rule, message, fix }] }
 */

$base_dir = dirname(__DIR__);
$requirements_file = $base_dir . '/requirements.yaml';
$tests_dir = $base_dir . '/tests';

if (! file_exists($requirements_file)) {
    echo json_encode(
        value: [
            'ok'         => false,
            'violations' => [[
                'rule'    => 'missing-requirements-file',
                'message' => "requirements.yaml not found at {$requirements_file}",
                'fix'     => 'Create requirements.yaml in the project root',
            ]],
        ],
        flags: JSON_PRETTY_PRINT,
    ) . "\n";
    exit(1);
}

$requirements = parse_requirements($requirements_file);
$violations = [];

$testable = ['integration-test', 'unit-test'];
$test_subdir = ['integration-test' => 'Integration', 'unit-test' => 'Unit'];

$known_authority_types = ['rfc', 'w3c', 'ietf-draft', 'iso', 'ecma'];
$known_link_rels = ['adr', 'issue', 'pr', 'prior-art', 'discussion'];

// ── Authority validation ──────────────────────────────────────────────────────

foreach ($requirements as $req_id => $req) {
    $authority = $req['authority'];

    if ($authority === null) {
        continue;
    }

    if (! in_array($authority['type'], $known_authority_types, strict: true)) {
        $known = implode(', ', $known_authority_types);
        $violations[] = [
            'id'      => $req_id,
            'rule'    => 'invalid-authority-type',
            'message' => "authority.type '{$authority['type']}' is not a known value ({$known})",
            'fix'     => "Set authority.type to one of: {$known}",
        ];
    }

    if ($authority['id'] === '') {
        $violations[] = [
            'id'      => $req_id,
            'rule'    => 'empty-authority-id',
            'message' => 'authority.id is empty',
            'fix'     => 'Set authority.id to the standard document identifier',
        ];
    }

    if ($authority['section'] === '') {
        $violations[] = [
            'id'      => $req_id,
            'rule'    => 'missing-authority-section',
            'message' => 'authority.section is missing — required when authority is present',
            'fix'     => 'Add authority.section pointing to the relevant section number or name',
        ];
    }

    if (! $authority['has_quote']) {
        $violations[] = [
            'id'      => $req_id,
            'rule'    => 'missing-authority-quote',
            'message' => 'authority.quote is missing — required to trace criterion text back to normative language',
            'fix'     => 'Add authority.quote with the verbatim text from the standard',
        ];
    }
}

// ── Links validation ──────────────────────────────────────────────────────────

foreach ($requirements as $req_id => $req) {
    foreach ($req['links'] as $link) {
        if (! in_array($link['rel'], $known_link_rels, strict: true)) {
            $known = implode(', ', $known_link_rels);
            $violations[] = [
                'id'      => $req_id,
                'rule'    => 'invalid-link-rel',
                'message' => "link rel '{$link['rel']}' is not a known value ({$known})",
                'fix'     => "Set link rel to one of: {$known}",
            ];
        }

        if ($link['href'] === '') {
            $violations[] = [
                'id'      => $req_id,
                'rule'    => 'empty-link-href',
                'message' => "a link with rel '{$link['rel']}' has an empty href",
                'fix'     => 'Add a URL or relative path to the link href',
            ];

            continue;
        }

        // Internal hrefs (no scheme) must point to existing files
        if (! str_starts_with($link['href'], 'http')) {
            $abs = $base_dir . '/' . ltrim($link['href'], '/');
            if (! file_exists($abs)) {
                $violations[] = [
                    'id'      => $req_id,
                    'rule'    => 'missing-link-target',
                    'message' => "link href '{$link['href']}' not found",
                    'fix'     => "Create the file at {$abs} or update the href",
                ];
            }
        }
    }
}

// ── enforced-by validation ────────────────────────────────────────────────────

foreach ($requirements as $req_id => $req) {
    foreach ($req['enforced-by'] as $rule) {
        $file = $base_dir . '/utils/rector/src/' . $rule . '.php';

        if (! file_exists($file)) {
            $violations[] = [
                'id'      => $req_id,
                'rule'    => 'missing-rector-rule',
                'message' => "enforced-by '{$rule}' not found at utils/rector/src/{$rule}.php",
                'fix'     => "Create the Rector rule or run: ./run generate:rector-rule -- {$rule}",
            ];
        }
    }
}

// ── Constraints validation ────────────────────────────────────────────────────

foreach ($requirements as $req_id => $req) {
    foreach ($req['constraints'] as $i => $constraint) {
        if (trim($constraint) === '') {
            $violations[] = [
                'id'      => $req_id,
                'rule'    => 'empty-constraint',
                'message' => "constraints[{$i}] is empty",
                'fix'     => 'Remove the empty entry or add constraint text',
            ];
        }
    }
}

// ── Fix 7: Criterion style validation ────────────────────────────────────────

foreach ($requirements as $req) {
    foreach ($req['acceptance-criteria'] as $criterion) {
        $id = $criterion['id'];
        $text = $criterion['text'];

        if ($text === '') {
            $violations[] = [
                'id'      => $id,
                'rule'    => 'empty-criterion-text',
                'message' => "criterion 'text' is empty",
                'fix'     => 'Write a present-tense declarative statement describing the expected behaviour',
            ];

            continue;
        }

        if (preg_match('/\bshould\b|\bmust\b/i', $text)) {
            $violations[] = [
                'id'      => $id,
                'rule'    => 'criterion-uses-modal',
                'message' => "criterion text uses 'should' or 'must'",
                'fix'     => 'Rewrite as a present-tense declarative (e.g. "The system returns 401" not "The system must return 401")',
            ];
        }

        if (str_contains($text, '::')) {
            $violations[] = [
                'id'      => $id,
                'rule'    => 'criterion-references-implementation',
                'message' => "criterion text references a class member ('{$id}' contains '::')",
                'fix'     => 'Describe behaviour, not implementation — omit class names and member references',
            ];
        }

        if (str_contains($text, '.php')) {
            $violations[] = [
                'id'      => $id,
                'rule'    => 'criterion-references-implementation',
                'message' => "criterion text references a file path ('{$id}' contains '.php')",
                'fix'     => 'Describe behaviour, not implementation — omit file path references',
            ];
        }
    }
}

// ── Collect all live criterion IDs ────────────────────────────────────────────

$live_criterion_ids = [];

foreach ($requirements as $req) {
    foreach ($req['acceptance-criteria'] as $criterion) {
        $live_criterion_ids[$criterion['id']] = true;
    }
}

// ── Fix 4: Test file existence + Fix 2: Method coverage ──────────────────────

foreach ($requirements as $req_id => $req) {
    $verification = $req['verification'];

    if (! in_array($verification, $testable, strict: true)) {
        continue;
    }

    $subdir = $test_subdir[$verification];
    $test_file_name = str_replace('-', '', $req_id) . 'Test.php';
    $test_file = "{$tests_dir}/{$subdir}/{$test_file_name}";

    if (! file_exists($test_file)) {
        $violations[] = [
            'id'      => $req_id,
            'file'    => "tests/{$subdir}/{$test_file_name}",
            'rule'    => 'missing-test-file',
            'message' => "expected tests/{$subdir}/{$test_file_name} not found (verification: {$verification})",
            'fix'     => "Create the test file or run: ./run generate:requirement -- {$req_id} --type=... --verification={$verification}",
        ];

        continue;
    }

    $source = (string) file_get_contents($test_file);

    foreach ($req['acceptance-criteria'] as $criterion) {
        // A criterion may override the requirement-level verification.
        $criterion_verification = $criterion['verification'] ?? $verification;

        if (! in_array($criterion_verification, $testable, strict: true)) {
            continue;
        }

        $expected_method = 'test_' . str_replace('-', '_', $criterion['id']);

        if (! str_contains($source, "function {$expected_method}(")) {
            $violations[] = [
                'id'      => $criterion['id'],
                'file'    => "tests/{$subdir}/{$test_file_name}",
                'rule'    => 'missing-test-method',
                'message' => "method '{$expected_method}' not found in tests/{$subdir}/{$test_file_name}",
                'fix'     => "Add public function {$expected_method}(): void to the test class",
            ];
        }
    }
}

// ── Fix 2: Orphan detection ───────────────────────────────────────────────────

$test_files = array_merge(
    glob("{$tests_dir}/Integration/*.php") ?: [],
    glob("{$tests_dir}/Unit/*.php") ?: [],
);

foreach ($test_files as $test_file) {
    $source = (string) file_get_contents($test_file);
    preg_match_all('/public function (test_[A-Z]+_\d+_[a-z]+)\(/', $source, $matches);

    foreach ($matches[1] as $method) {
        // Convert test_TRACE_001_a -> TRACE-001-a
        if (preg_match('/^test_([A-Z]+)_(\d+)_([a-z]+)$/', $method, $parts)) {
            $criterion_id = "{$parts[1]}-{$parts[2]}-{$parts[3]}";

            if (! isset($live_criterion_ids[$criterion_id])) {
                $base = basename($test_file);
                $violations[] = [
                    'file'    => 'tests/' . (str_contains($test_file, '/Integration/') ? 'Integration' : 'Unit') . "/{$base}",
                    'rule'    => 'orphaned-test-method',
                    'message' => "{$base}::{$method} references criterion '{$criterion_id}' not found in requirements.yaml",
                    'fix'     => "Add criterion '{$criterion_id}' to requirements.yaml or rename/remove the test method",
                ];
            }
        }
    }
}

// ── Report ────────────────────────────────────────────────────────────────────

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);

// ── Parser ────────────────────────────────────────────────────────────────────

/**
 * @return array<string, array{
 *   verification: string,
 *   constraints: list<string>,
 *   authority: array{type: string, id: string, section: string, has_quote: bool}|null,
 *   links: list<array{rel: string, href: string}>,
 *   acceptance-criteria: list<array{id: string, text: string, verification: string|null}>
 * }>
 */
function parse_requirements(string $file): array
{
    require_once dirname(__DIR__) . '/vendor/autoload.php';

    /** @var array<string, mixed> $raw */
    $raw = \Symfony\Component\Yaml\Yaml::parseFile($file);

    $requirements = [];

    foreach ($raw as $req_id => $data) {
        if (! is_array($data)) {
            continue;
        }

        $authority = null;

        if (isset($data['authority']) && is_array($data['authority'])) {
            $a = $data['authority'];
            $authority = [
                'type'      => (string) ($a['type'] ?? ''),
                'id'        => (string) ($a['id'] ?? ''),
                'section'   => (string) ($a['section'] ?? ''),
                'has_quote' => isset($a['quote']),
            ];
        }

        $links = [];

        foreach ((array) ($data['links'] ?? []) as $link) {
            if (! is_array($link)) {
                continue;
            }
            $links[] = [
                'rel'  => (string) ($link['rel'] ?? ''),
                'href' => (string) ($link['href'] ?? ''),
            ];
        }

        $criteria = [];

        foreach ((array) ($data['acceptance-criteria'] ?? []) as $criterion) {
            if (! is_array($criterion)) {
                continue;
            }
            $criteria[] = [
                'id'           => (string) ($criterion['id'] ?? ''),
                'text'         => trim((string) ($criterion['text'] ?? '')),
                'verification' => isset($criterion['verification']) ? (string) $criterion['verification'] : null,
            ];
        }

        $enforced_by = [];

        foreach ((array) ($data['enforced-by'] ?? []) as $rule) {
            $enforced_by[] = (string) $rule;
        }

        $constraints = [];

        foreach ((array) ($data['constraints'] ?? []) as $constraint) {
            $constraints[] = (string) $constraint;
        }

        $requirements[(string) $req_id] = [
            'verification'        => (string) ($data['verification'] ?? ''),
            'constraints'         => $constraints,
            'authority'           => $authority,
            'links'               => $links,
            'enforced-by'         => $enforced_by,
            'acceptance-criteria' => $criteria,
        ];
    }

    return $requirements;
}
