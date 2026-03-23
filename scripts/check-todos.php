<?php

declare(strict_types=1);

/**
 * Fail if TODO/FIXME/HACK/XXX markers exist in src/ or tests/.
 *
 * Usage: ./run check:todos
 * Output: JSON { ok: bool, violations: [{ rule, file, message, fix }] }
 * Exit 0 if clean. Exit 1 if markers found.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config = Yaml::parseFile(__DIR__ . '/todos-config.yaml');
$projectRoot = dirname(__DIR__);

$markers = $config['markers'];
$markerGroup = implode('|', array_map('preg_quote', $markers));
$pattern = '/(?:\/\/|#|\/\*|\*|{{--)\s*(' . $markerGroup . ')[\s:]+(.*)$/i';

$extensions = $config['extensions'];
$extensionPattern = '/\.(' . implode('|', array_map(fn(string $ext): string => preg_quote($ext, '/'), $extensions)) . ')$/';

$directories = ['framework', 'src', 'tests'];

$violations = [];

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
                $message = trim($matches[2]);
                $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());

                $violations[] = [
                    'rule'    => 'no-todo',
                    'file'    => $relativePath,
                    'message' => sprintf('%s on line %d: %s', $marker, $lineNumber + 1, $message),
                    'fix'     => 'Resolve the TODO or remove the marker',
                ];
            }
        }
    }
}

usort($violations, fn(array $a, array $b): int => [$a['file'], $a['message']] <=> [$b['file'], $b['message']]);

$ok = $violations === [];

echo json_encode(['ok' => $ok, 'violations' => $violations], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

exit($ok ? 0 : 1);
