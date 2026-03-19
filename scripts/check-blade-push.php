<?php

declare(strict_types=1);

/**
 * Lint component templates for bare @push / @prepend directives.
 *
 * Component templates must use @pushOnce to avoid duplicate script/style
 * injection when a component is rendered multiple times on a page.
 *
 * Usage: docker compose exec web php /app/scripts/check-blade-push.php
 * Via Composer: ./run check:blade-push
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Blade\Component;

$template_dir = __DIR__ . '/../templates/components';

$failures = [];
$passes = [];

foreach (Component::cases() as $component) {
    $path = $template_dir . '/' . $component->value . '.blade.php';

    if (!file_exists($path)) {
        $failures[] = sprintf('Missing component template: %s', $path);
        continue;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    $violations = [];

    foreach ($lines as $line_number => $line) {
        if (preg_match('/@push\(/', $line) || preg_match('/@prepend\(/', $line)) {
            $violations[] = sprintf('line %d: %s', $line_number + 1, trim($line));
        }
    }

    if ($violations !== []) {
        foreach ($violations as $violation) {
            $failures[] = sprintf(
                'Component::%s uses bare @push or @prepend (%s). Use @pushOnce(\'stack\', \'%s\') instead.',
                $component->name,
                $violation,
                $component->value,
            );
        }
    } else {
        $passes[] = sprintf('Component::%s has no bare @push or @prepend', $component->name);
    }
}

foreach ($failures as $f) {
    echo "  [FAIL] $f\n";
}
foreach ($passes as $p) {
    echo "  [ OK ] $p\n";
}

$total = count($failures) + count($passes);
echo sprintf("\nResult: %d checks — %d failed, %d passed\n", $total, count($failures), count($passes));

if ($failures !== []) {
    echo "Verdict: Component @push directives are NOT correct\n\n";
    exit(1);
}

echo "Verdict: Component @push directives are correct\n\n";
exit(0);
