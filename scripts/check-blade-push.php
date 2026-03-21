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
 *
 * Exit 0 if no violations. Exit 1 if violations found.
 * Output: JSON { ok: bool, violations: [{ file, line, rule, message, fix }] }
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config         = Yaml::parseFile(__DIR__ . '/blade-config.yaml');
$template_dir   = __DIR__ . '/../' . $config['component_dir'];
$componentClass = $config['namespaces']['component'];

$violations = [];

foreach ($componentClass::cases() as $component) {
    $path = $template_dir . '/' . $component->value . '.blade.php';
    $relative = 'templates/components/' . $component->value . '.blade.php';

    if (!file_exists($path)) {
        $violations[] = [
            'file'    => $relative,
            'rule'    => 'missing-component-template',
            'message' => 'component template file not found',
            'fix'     => "Create {$relative} or remove Component::{$component->name}",
        ];

        continue;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line_number => $line) {
        if (preg_match('/@push\(/', $line) || preg_match('/@prepend\(/', $line)) {
            $directive = str_contains($line, '@push(') ? '@push' : '@prepend';
            $violations[] = [
                'file'    => $relative,
                'line'    => $line_number + 1,
                'rule'    => 'bare-push',
                'message' => "Component::{$component->name} uses bare {$directive}",
                'fix'     => "Use @pushOnce('stack', '{$component->value}') instead",
            ];
        }
    }
}

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);
