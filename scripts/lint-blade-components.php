<?php

declare(strict_types=1);

/**
 * Lint Blade templates for raw HTML tags that should use Blade components.
 *
 * Usage: docker compose exec web php /app/scripts/lint-blade-components.php
 * Via Composer: ./run check:blade-components
 *
 * Allows:
 *   - Component templates themselves (templates/components/*.blade.php)
 *   - Lines inside @php / @endphp blocks
 *
 * Exit 0 if no violations. Exit 1 if violations found.
 * Output: JSON { ok: bool, violations: [{ file, line, rule, message, fix }] }
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config       = Yaml::parseFile(__DIR__ . '/blade-config.yaml');
$template_dir = __DIR__ . '/../' . $config['template_dir'];

$files = scanBladeFiles($template_dir);

$tag_rules = [];
foreach ($config['tag_rules'] as $entry) {
    $tag_rules[$entry['pattern']] = [
        'rule'    => $entry['rule'],
        'message' => $entry['message'],
        'fix'     => $entry['fix'],
    ];
}

$violations = [];
$base_path = realpath(path: __DIR__ . '/..');

foreach ($files as $file) {
    $real_path = realpath(path: $file);

    // Skip component templates — they legitimately contain the raw HTML they wrap
    if (str_contains(haystack: $real_path, needle: '/components/')) {
        continue;
    }

    $lines = file($file);
    if ($lines === false) {
        continue;
    }

    $relative = str_replace(
        search: $base_path . '/',
        replace: '',
        subject: $real_path,
    );

    $in_php_block = false;

    foreach ($lines as $i => $line) {
        $line_number = $i + 1;
        $trimmed = trim($line);

        // Track @php / @endphp blocks
        if (str_starts_with(haystack: $trimmed, needle: '@php')) {
            $in_php_block = true;
        }
        if (str_contains(haystack: $trimmed, needle: '@endphp')) {
            $in_php_block = false;

            continue;
        }
        if ($in_php_block) {
            continue;
        }

        foreach ($tag_rules as $regex => $info) {
            if (preg_match($regex, $line)) {
                $violations[] = [
                    'file'    => $relative,
                    'line'    => $line_number,
                    'rule'    => $info['rule'],
                    'message' => $info['message'],
                    'fix'     => $info['fix'],
                ];
            }
        }
    }
}

echo json_encode(
    value: ['ok' => $violations === [], 'violations' => $violations],
    flags: JSON_PRETTY_PRINT,
) . "\n";

exit($violations === [] ? 0 : 1);

function scanBladeFiles(string $dir): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && str_ends_with(haystack: $file->getFilename(), needle: '.blade.php')) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}
