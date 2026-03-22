<?php

declare(strict_types=1);

/**
 * Lint Blade template files for coverage by View and Component enums.
 *
 * Usage: docker compose exec web php /app/scripts/check-blade-templates.php
 * Via Composer: ./run check:blade-templates
 *
 * Checks:
 *   - Every templates/*.blade.php (excluding known layouts) maps to a View enum case.
 *   - Every templates/components/*.blade.php maps to a Component enum case.
 *
 * Unregistered templates are never pre-compiled at build time and will
 * be compiled on the first request that renders them, which defeats
 * the view:cache optimization.
 *
 * Exit 0 if no violations. Exit 1 if violations found.
 * Output: JSON { ok: bool, violations: [{ file, rule, message, fix }] }
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config         = Yaml::parseFile(__DIR__ . '/blade-config.yaml');
$template_dir   = __DIR__ . '/../' . $config['template_dir'];
$known_layouts  = $config['known_layouts'];
$viewClass      = $config['namespaces']['view'];
$componentClass = $config['namespaces']['component'];

$violations = [];

// ── Views ────────────────────────────────────────────────────────────────────

$view_values = array_map(static fn($v) => $v->value, $viewClass::cases());

foreach (glob($template_dir . '/*.blade.php') ?: [] as $file) {
    $stem = basename($file, '.blade.php');

    if (in_array($stem, $known_layouts, true)) {
        continue;
    }

    if (!in_array($stem, $view_values, true)) {
        $violations[] = [
            'file'    => "templates/{$stem}.blade.php",
            'rule'    => 'unregistered-view',
            'message' => "no matching View enum case for '{$stem}'",
            'fix'     => "Add View::{$stem} to src/Blade/View.php or delete the file",
        ];
    }
}

// ── Components ───────────────────────────────────────────────────────────────

$component_values = array_map(static fn($c) => $c->value, $componentClass::cases());

foreach (glob($template_dir . '/components/*.blade.php') ?: [] as $file) {
    $stem = basename($file, '.blade.php');

    if (!in_array($stem, $component_values, true)) {
        $enum_case = str_replace('-', '_', $stem);
        $violations[] = [
            'file'    => "templates/components/{$stem}.blade.php",
            'rule'    => 'unregistered-component',
            'message' => "no matching Component enum case for '{$stem}'",
            'fix'     => "Add Component::{$enum_case} to src/Blade/Component.php or delete the file",
        ];
    }
}

// ── Result ───────────────────────────────────────────────────────────────────

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
