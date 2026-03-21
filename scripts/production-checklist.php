<?php

declare(strict_types=1);

/**
 * Production readiness checklist.
 *
 * Runs all verification scripts and reports a combined pass/fail result.
 *
 * Usage: docker compose exec web php /app/scripts/production-checklist.php
 * Via Composer: ./run prod:check
 *
 * Checks are defined in production-config.yaml. Command-based checks run as
 * sub-processes; inline checks (template-cache, component-push) use reflection
 * on the classes declared in the config.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$base_dir      = dirname(__DIR__);
$config        = Yaml::parseFile(__DIR__ . '/production-config.yaml');
$overall_exit  = 0;

$appClass       = $config['namespaces']['app'];
$viewClass      = $config['namespaces']['view'];
$componentClass = $config['namespaces']['component'];
$viteClass      = $config['namespaces']['vite'];
$configClass    = $config['namespaces']['config'];
$configKeyClass = $config['namespaces']['config_key'];
$appEnvClass    = $config['namespaces']['app_env'];

$template_dir  = $base_dir . '/' . $config['template_dir'];
$component_dir = $base_dir . '/' . $config['component_dir'];
$cache_dir     = $base_dir . '/' . $config['cache_dir'];

echo "\n╔══════════════════════════════════════╗\n";
echo "║     Production Readiness Checklist   ║\n";
echo "╚══════════════════════════════════════╝\n";

// ── Run checks ───────────────────────────────────────────────────

$check_results = [];

foreach ($config['checks'] as $i => $check) {
    $num   = $i + 1;
    $total = count($config['checks']);
    $name  = $check['name'];

    echo sprintf("\n┌─ %d/%d %s ─────────────────────\n", $num, $total, $name);

    if (isset($check['command'])) {
        $exit_code = runScript('php ' . escapeshellarg($base_dir . '/' . preg_replace('/^php\s+/', '', $check['command'])));
    } elseif (($check['type'] ?? '') === 'template-cache') {
        $exit_code = verifyTemplateCache($base_dir, $cache_dir, $template_dir, $appClass, $viewClass, $componentClass, $viteClass, $configClass, $configKeyClass, $appEnvClass);
    } elseif (($check['type'] ?? '') === 'component-push') {
        $exit_code = verifyComponentPushDirectives($component_dir, $componentClass);
    } else {
        $exit_code = 1;
    }

    $check_results[] = [$name, $exit_code];

    if ($exit_code !== 0) {
        $overall_exit = 1;
    }
}

echo "┌─ Summary ─────────────────────────────\n\n";

$failed = 0;
foreach ($check_results as [$name, $exit_code]) {
    $status = $exit_code === 0 ? '[ OK ]' : '[FAIL]';
    if ($exit_code !== 0) {
        $failed++;
    }
    echo "  $status $name\n";
}

echo sprintf("\nResult: %d/%d checks passed\n", count($check_results) - $failed, count($check_results));

if ($overall_exit !== 0) {
    echo "Verdict: NOT production ready\n\n";
} else {
    echo "Verdict: Production ready\n\n";
}

exit($overall_exit);

// ─────────────────────────────────────────────────────────────────

function runScript(string $command): int
{
    passthru($command, $exit_code);

    return $exit_code;
}

function verifyComponentPushDirectives(string $component_dir, string $componentClass): int
{
    $failures = [];
    $passes = [];

    echo "\n=== Component @push Directive Verification ===\n\n";

    foreach ($componentClass::cases() as $component) {
        $path = $component_dir . '/' . $component->value . '.blade.php';

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
                $failures[] = sprintf('Component::%s uses bare @push or @prepend (%s). Use @pushOnce(\'stack\', \'%s\') instead.', $component->name, $violation, $component->value);
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
    echo "\n";

    $total = count($failures) + count($passes);
    echo sprintf("Result: %d checks — %d failed, %d passed\n", $total, count($failures), count($passes));

    if ($failures !== []) {
        echo "Verdict: Component @push directives are NOT production ready\n\n";

        return 1;
    }

    echo "Verdict: Component @push directives are production ready\n\n";

    return 0;
}

function verifyTemplateCache(
    string $base_dir,
    string $cache_dir,
    string $template_dir,
    string $appClass,
    string $viewClass,
    string $componentClass,
    string $viteClass,
    string $configClass,
    string $configKeyClass,
    string $appEnvClass,
): int {
    $failures = [];
    $passes = [];

    echo "\n=== Template Cache Verification ===\n\n";

    // 1. Populate the real cache via cache-views.php
    passthru('php ' . escapeshellarg($base_dir . '/scripts/cache-views.php'), $populate_exit);
    if ($populate_exit !== 0) {
        echo "  [FAIL] cache-views.php failed\n\n";

        return 1;
    }

    // 2. Verify the real cache has files for all views and components
    $cached_files = glob($cache_dir . '/*.php') ?: [];
    $expected_min = count($viewClass::cases()) + count($componentClass::cases());

    if (count($cached_files) >= $expected_min) {
        $passes[] = sprintf('%d compiled files in %s (expected ≥ %d)', count($cached_files), $cache_dir, $expected_min);
    } else {
        $failures[] = sprintf('%d compiled files found in %s, expected ≥ %d', count($cached_files), $cache_dir, $expected_min);
    }

    // 3. Verify second render reuses cache (no recompilation)
    $mtimes = [];
    foreach ($cached_files as $file) {
        $mtimes[$file] = filemtime($file);
    }

    sleep(1);

    $Config = $configClass::from([
        $configKeyClass::AppEnv->value => $appEnvClass::production->value,
        $configKeyClass::blade_cache_dir->value => $cache_dir,
        $configKeyClass::template_dir->value => $template_dir,
    ]);
    $Vite = new $viteClass($Config, baseDir: $base_dir, entry_css: [
        $viteClass::app_entry => [$viteClass::app_css],
    ]);
    $Blade = $appClass::bootBlade($Config, $Vite);

    foreach ($viewClass::cases() as $view) {
        $Blade->make($view->value, $view->stubData())->render();
    }

    $recompiled = 0;
    foreach ($cached_files as $file) {
        if (filemtime($file) !== $mtimes[$file]) {
            $recompiled++;
        }
    }

    if ($recompiled > 0) {
        $failures[] = sprintf('%d files recompiled on second render (expected 0)', $recompiled);
    } else {
        $passes[] = 'Second render reused cached templates (0 recompilations)';
    }

    // Report
    foreach ($failures as $f) {
        echo "  [FAIL] $f\n";
    }
    foreach ($passes as $p) {
        echo "  [ OK ] $p\n";
    }
    echo "\n";

    $total = count($failures) + count($passes);
    echo sprintf("Result: %d checks — %d failed, %d passed\n", $total, count($failures), count($passes));

    if ($failures !== []) {
        echo "Verdict: Template cache is NOT production ready\n\n";

        return 1;
    }

    echo "Verdict: Template cache is production ready\n\n";

    return 0;
}
