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
 * Exit code: 0 if all checks pass, 1 if any fail.
 */

$base_dir = dirname(__DIR__);
$requirements_file = $base_dir . '/requirements.yaml';
$tests_dir = $base_dir . '/tests';

if (! file_exists($requirements_file)) {
    echo "[FAIL] requirements.yaml not found at $requirements_file\n";
    exit(1);
}

$requirements = parse_requirements($requirements_file);
$errors = [];

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
        $errors[] = "$req_id: authority.type '{$authority['type']}' is not a known value ($known)";
    }

    if ($authority['id'] === '') {
        $errors[] = "$req_id: authority.id is empty";
    }

    if ($authority['section'] === '') {
        $errors[] = "$req_id: authority.section is missing — required when authority is present";
    }

    if (! $authority['has_quote']) {
        $errors[] = "$req_id: authority.quote is missing — required to trace criterion text back to normative language";
    }
}

// ── Links validation ──────────────────────────────────────────────────────────

foreach ($requirements as $req_id => $req) {
    foreach ($req['links'] as $link) {
        if (! in_array($link['rel'], $known_link_rels, strict: true)) {
            $known = implode(', ', $known_link_rels);
            $errors[] = "$req_id: link rel '{$link['rel']}' is not a known value ($known)";
        }

        if ($link['href'] === '') {
            $errors[] = "$req_id: a link with rel '{$link['rel']}' has an empty href";
            continue;
        }

        // Internal hrefs (no scheme) must point to existing files
        if (! str_starts_with($link['href'], 'http')) {
            $abs = $base_dir . '/' . ltrim($link['href'], '/');
            if (! file_exists($abs)) {
                $errors[] = "$req_id: link href '{$link['href']}' not found at $abs";
            }
        }
    }
}

// ── enforced-by validation ────────────────────────────────────────────────────

foreach ($requirements as $req_id => $req) {
    foreach ($req['enforced-by'] as $rule) {
        $file = $base_dir . '/utils/rector/src/' . $rule . '.php';

        if (! file_exists($file)) {
            $errors[] = "$req_id: enforced-by '$rule' not found at utils/rector/src/$rule.php";
        }
    }
}

// ── Constraints validation ────────────────────────────────────────────────────

foreach ($requirements as $req_id => $req) {
    foreach ($req['constraints'] as $i => $constraint) {
        if (trim($constraint) === '') {
            $errors[] = "$req_id: constraints[$i] is empty — remove the entry or add text";
        }
    }
}

// ── Fix 7: Criterion style validation ────────────────────────────────────────

foreach ($requirements as $req) {
    foreach ($req['acceptance-criteria'] as $criterion) {
        $id = $criterion['id'];
        $text = $criterion['text'];

        if ($text === '') {
            $errors[] = "$id: criterion 'text' is empty";
            continue;
        }

        if (preg_match('/\bshould\b|\bmust\b/i', $text)) {
            $errors[] = "$id: criterion text uses 'should' or 'must' — use present-tense declaratives";
        }

        if (str_contains($text, '::')) {
            $errors[] = "$id: criterion text references a class member ('::') — omit implementation details";
        }

        if (str_contains($text, '.php')) {
            $errors[] = "$id: criterion text references a file path ('.php') — omit implementation details";
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
        echo "  $req_id: skipped ($verification)\n";
        continue;
    }

    $subdir = $test_subdir[$verification];
    $test_file_name = str_replace('-', '', $req_id) . 'Test.php';
    $test_file = "$tests_dir/$subdir/$test_file_name";

    if (! file_exists($test_file)) {
        $errors[] = "$req_id: expected $subdir/$test_file_name not found (verification: $verification)";
        continue;
    }

    $source = (string) file_get_contents($test_file);

    foreach ($req['constraints'] as $constraint) {
        echo "  $req_id: [constraint] $constraint\n";
    }

    foreach ($req['acceptance-criteria'] as $criterion) {
        // A criterion may override the requirement-level verification.
        $criterion_verification = $criterion['verification'] ?? $verification;

        if (! in_array($criterion_verification, $testable, strict: true)) {
            echo "  {$criterion['id']}: skipped ($criterion_verification)\n";
            continue;
        }

        $expected_method = 'test_' . str_replace('-', '_', $criterion['id']);

        if (! str_contains($source, "function $expected_method(")) {
            $errors[] = "{$criterion['id']}: method '$expected_method' not found in $subdir/$test_file_name";
        }
    }
}

// ── Fix 2: Orphan detection ───────────────────────────────────────────────────

$test_files = array_merge(
    glob("$tests_dir/Integration/*.php") ?: [],
    glob("$tests_dir/Unit/*.php") ?: [],
);

foreach ($test_files as $test_file) {
    $source = (string) file_get_contents($test_file);
    preg_match_all('/public function (test_[A-Z]+_\d+_[a-z]+)\(/', $source, $matches);

    foreach ($matches[1] as $method) {
        // Convert test_TRACE_001_a -> TRACE-001-a
        if (preg_match('/^test_([A-Z]+)_(\d+)_([a-z]+)$/', $method, $parts)) {
            $criterion_id = "$parts[1]-$parts[2]-$parts[3]";

            if (! isset($live_criterion_ids[$criterion_id])) {
                $base = basename($test_file);
                $errors[] = "$base::$method: orphaned — criterion '$criterion_id' not found in requirements.yaml";
            }
        }
    }
}

// ── Report ────────────────────────────────────────────────────────────────────

if ($errors === []) {
    echo "check:requirements OK\n";
    exit(0);
}

foreach ($errors as $error) {
    echo "[FAIL] $error\n";
}

exit(1);

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
