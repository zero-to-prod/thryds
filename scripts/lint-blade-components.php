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
 */

require __DIR__ . '/../vendor/autoload.php';

$template_dir = __DIR__ . '/../templates';

$files = scanBladeFiles($template_dir);

$tag_rules = [
    '/<button\b/' => 'Use <x-button> instead of raw <button>',
    '/<input\b/' => 'Use <x-input> instead of raw <input>',
    '/<div\s[^>]*role\s*=\s*["\']alert["\']/' => 'Use <x-alert> instead of <div role="alert">',
];

$errors = [];
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

        foreach ($tag_rules as $regex => $message) {
            if (preg_match($regex, $line)) {
                $errors[] = sprintf(
                    '  %s:%d — %s',
                    $relative,
                    $line_number,
                    $message,
                );
            }
        }
    }
}

if ($errors === []) {
    echo "No raw HTML component violations found in Blade templates.\n";
    exit(0);
}

echo "Raw HTML found in Blade templates:\n\n";
echo implode(separator: "\n", array: $errors) . "\n\n";
echo sprintf("Found %d violation(s). Use Blade components instead of raw HTML.\n", count($errors));
exit(1);

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
