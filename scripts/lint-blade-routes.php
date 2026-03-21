<?php

declare(strict_types=1);

/**
 * Lint Blade templates for hardcoded route paths.
 *
 * Usage: docker compose exec web php /app/scripts/lint-blade-routes.php
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
 *
 * Exit 0 if no violations. Exit 1 if violations found.
 * Output: JSON { ok: bool, violations: [{ file, line, rule, message, fix }] }
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config       = Yaml::parseFile(__DIR__ . '/blade-config.yaml');
$template_dir = __DIR__ . '/../' . $config['template_dir'];
$pattern      = $template_dir . '/**/*.blade.php';

$files = glob($pattern, GLOB_BRACE);

if ($files === false || $files === []) {
    $files = scanBladeFiles($template_dir);
}

$violations = [];

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

            $violations[] = [
                'file'    => $relative,
                'line'    => $line_number,
                'rule'    => 'hardcoded-route',
                'message' => "hardcoded {$m[1]}=\"{$path}\"",
                'fix'     => "Use a Route enum value: {$m[1]}=\"{{ Route::name->value }}\"",
            ];
        }

        // String literal route inside {{ ... }}
        if (preg_match('/\{\{[^}]*[\'"](\/[a-zA-Z_\-\/]+)[\'"]/', $line, $m)) {
            $violations[] = [
                'file'    => $relative,
                'line'    => $line_number,
                'rule'    => 'hardcoded-route',
                'message' => "string literal route '{$m[1]}' in Blade echo",
                'fix'     => 'Use a Route enum value instead of a string literal',
            ];
        }

        // String literal route inside {!! ... !!}
        if (preg_match('/\{!![^!]*[\'"](\/[a-zA-Z_\-\/]+)[\'"]/', $line, $m)) {
            $violations[] = [
                'file'    => $relative,
                'line'    => $line_number,
                'rule'    => 'hardcoded-route',
                'message' => "string literal route '{$m[1]}' in unescaped Blade echo",
                'fix'     => 'Use a Route enum value instead of a string literal',
            ];
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
