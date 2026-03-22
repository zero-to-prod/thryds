<?php

declare(strict_types=1);

/**
 * Scan source directories for TODO/FIXME/HACK/XXX markers and report them.
 *
 * Usage: docker compose exec web php scripts/list-todos.php
 * Via Composer: ./run list:todos
 *
 * Options:
 *   --format=json|table    Output format (default: table)
 *   --marker=TODO          Filter to a specific marker (case-insensitive)
 *   --dir=src              Filter to a specific directory
 *
 * Exit 0 on success.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config = Yaml::parseFile(__DIR__ . '/todos-config.yaml');
$projectRoot = dirname(__DIR__);

$format = 'table';
$filterMarker = null;
$filterDir = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, strlen('--format='));
    } elseif (str_starts_with($arg, '--marker=')) {
        $filterMarker = strtoupper(substr($arg, strlen('--marker=')));
    } elseif (str_starts_with($arg, '--dir=')) {
        $filterDir = substr($arg, strlen('--dir='));
    }
}

$markers = $config['markers'];
$markerGroup = implode('|', array_map('preg_quote', $markers));
$pattern = '/(?:\/\/|#|\/\*|\*|{{--)\s*(' . $markerGroup . ')[\s:]+(.*)$/i';

$extensions = $config['extensions'];
$extensionPattern = '/\.(' . implode('|', array_map(fn(string $ext): string => preg_quote($ext, '/'), $extensions)) . ')$/';

$directories = $config['scan_directories'];
if ($filterDir !== null) {
    $directories = array_filter($directories, fn(string $dir): bool => $dir === $filterDir);
}

$todos = [];

foreach ($directories as $dir) {
    $fullPath = $projectRoot . '/' . $dir;
    if (!is_dir($fullPath)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || !preg_match($extensionPattern, $file->getFilename())) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        if ($contents === false) {
            continue;
        }

        $lines = explode("\n", $contents);
        foreach ($lines as $lineNumber => $line) {
            if (preg_match($pattern, $line, $matches)) {
                $marker = strtoupper($matches[1]);

                if ($filterMarker !== null && $marker !== $filterMarker) {
                    continue;
                }

                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
                $message = trim($matches[2]);

                $todos[] = [
                    'file'    => $relativePath,
                    'line'    => $lineNumber + 1,
                    'marker'  => $marker,
                    'message' => $message,
                ];
            }
        }
    }
}

usort($todos, fn(array $a, array $b): int => [$a['file'], $a['line']] <=> [$b['file'], $b['line']]);

if ($format === 'json') {
    echo json_encode([
        'total' => count($todos),
        'todos' => $todos,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    if ($todos === []) {
        fwrite(STDERR, "No TODOs found.\n");
    } else {
        fwrite(STDERR, sprintf("Found %d TODO(s):\n\n", count($todos)));
        foreach ($todos as $todo) {
            fprintf(STDERR, "  %s:%d [%s] %s\n", $todo['file'], $todo['line'], $todo['marker'], $todo['message']);
        }
        fwrite(STDERR, "\n");
    }
}
