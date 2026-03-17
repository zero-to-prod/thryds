<?php

declare(strict_types=1);

/**
 * Lint Blade templates for hardcoded route paths.
 *
 * Usage: docker compose exec php php /app/scripts/lint-blade-routes.php
 * Via Composer: ./run check:blade-routes
 *
 * Catches:
 *   - href="/about"           (hardcoded path in attribute)
 *   - action="/login"         (hardcoded path in attribute)
 *   - {{ '/about' }}          (string literal route in Blade echo)
 *   - {!! '/about' !!}        (string literal route in unescaped echo)
 *
 * Allows:
 *   - href="{{ Route::about->value }}"
 *   - href="#anchor"
 *   - href="https://..."
 *   - action="{{ Route::login->value }}"
 */

$template_dir = __DIR__ . '/../templates';
$pattern = $template_dir . '/**/*.blade.php';

$files = glob($pattern, GLOB_BRACE);

if ($files === false || $files === []) {
    $files = scanBladeFiles($template_dir);
}

$errors = [];

foreach ($files as $file) {
    $lines = file($file);
    if ($lines === false) {
        continue;
    }

    $relative = str_replace(
        search: realpath(path: __DIR__ . '/..') . '/',
        replace: '',
        subject: realpath(path: $file),
    );

    foreach ($lines as $i => $line) {
        $line_number = $i + 1;

        // href="/..." or action="/..." with a hardcoded path (not a Blade expression)
        if (preg_match('/\b(href|action)\s*=\s*"(\/[^"{]*)"/', $line, $m)) {
            $path = $m[2];

            // Allow fragment-only links
            if (str_starts_with(haystack: $path, needle: '/#')) {
                continue;
            }

            $errors[] = sprintf(
                '  %s:%d — hardcoded %s="%s"',
                $relative,
                $line_number,
                $m[1],
                $path,
            );
        }

        // String literal route inside {{ ... }} or {!! ... !!}
        if (preg_match('/\{\{[^}]*[\'"](\/[a-zA-Z_\-\/]+)[\'"]/', $line, $m)) {
            $errors[] = sprintf(
                '  %s:%d — string literal route \'%s\' in Blade echo',
                $relative,
                $line_number,
                $m[1],
            );
        }

        if (preg_match('/\{!![^!]*[\'"](\/[a-zA-Z_\-\/]+)[\'"]/', $line, $m)) {
            $errors[] = sprintf(
                '  %s:%d — string literal route \'%s\' in unescaped Blade echo',
                $relative,
                $line_number,
                $m[1],
            );
        }
    }
}

if ($errors === []) {
    echo "No hardcoded routes found in Blade templates.\n";
    exit(0);
}

echo "Hardcoded routes found in Blade templates:\n\n";
echo implode(separator: "\n", array: $errors) . "\n\n";
echo sprintf("Found %d violation(s). Use Route enum values instead.\n", count($errors));
echo "Example: href=\"{{ \\ZeroToProd\\Thryds\\Routes\\Route::about->value }}\"\n";
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
