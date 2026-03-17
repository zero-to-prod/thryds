<?php

declare(strict_types=1);

/**
 * Lint Blade template files for coverage by View and Component enums.
 *
 * Usage: docker compose exec web php /app/scripts/lint-blade-templates.php
 * Via Composer: ./run check:blade-templates
 *
 * Checks:
 *   - Every templates/*.blade.php (excluding known layouts) maps to a View enum case.
 *   - Every templates/components/*.blade.php maps to a Component enum case.
 *
 * Unregistered templates are never pre-compiled at build time and will
 * be compiled on the first request that renders them, which defeats
 * the view:cache optimization.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Helpers\Component;
use ZeroToProd\Thryds\Helpers\View;

$template_dir = __DIR__ . '/../templates';
$errors = [];

// Layouts are shared parent templates extended via @extends — not routable views.
$known_layouts = ['base'];

// ── Views ────────────────────────────────────────────────────────────────────

$view_values = array_map(static fn(View $v) => $v->value, View::cases());

foreach (glob($template_dir . '/*.blade.php') ?: [] as $file) {
    $stem = basename($file, '.blade.php');

    if (in_array($stem, $known_layouts, true)) {
        continue;
    }

    if (!in_array($stem, $view_values, true)) {
        $errors[] = sprintf(
            '  templates/%s.blade.php — no matching View enum case (expected View::%s)',
            $stem,
            $stem,
        );
    }
}

// ── Components ───────────────────────────────────────────────────────────────

$component_values = array_map(static fn(Component $c) => $c->value, Component::cases());

foreach (glob($template_dir . '/components/*.blade.php') ?: [] as $file) {
    $stem = basename($file, '.blade.php');

    if (!in_array($stem, $component_values, true)) {
        $errors[] = sprintf(
            '  templates/components/%s.blade.php — no matching Component enum case (expected Component::%s)',
            $stem,
            str_replace('-', '_', $stem),
        );
    }
}

// ── Result ───────────────────────────────────────────────────────────────────

if ($errors === []) {
    echo "All Blade templates are registered in View or Component enums.\n";
    exit(0);
}

echo "Unregistered Blade templates found:\n\n";
echo implode("\n", $errors) . "\n\n";
echo sprintf(
    "Found %d unregistered template(s). Add enum cases or delete the files.\n",
    count($errors),
);
exit(1);
