<?php

declare(strict_types=1);

/**
 * Pre-compiles all Blade templates to the cache directory.
 *
 * Renders every View enum case (with stub data) so that all templates —
 * including components referenced transitively — are compiled and written
 * to var/cache/blade before the first real request.
 *
 * Usage: docker compose exec web php scripts/cache-views.php
 * Via Composer: ./run cache:views
 * Build: called by generate-preload.php during `docker build`
 */

require __DIR__ . '/../vendor/autoload.php';

use Jenssegers\Blade\Blade;
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Helpers\View;

/**
 * Compiles all Blade templates by rendering every View case with stub data.
 * All components used transitively are compiled as a side effect.
 */
function compileAllTemplates(Blade $Blade): void
{
    foreach (View::cases() as $view) {
        $Blade->make(view: $view->value, data: $view->stubData())->render();
    }
}

// Only execute when run directly (not when required by generate-preload.php)
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $base_dir = dirname(__DIR__);

    $Config = Config::from([
        Config::AppEnv => AppEnv::production->value,
        Config::blade_cache_dir => $base_dir . '/var/cache/blade',
        Config::template_dir => $base_dir . '/templates',
    ]);

    if (!is_dir($Config->blade_cache_dir)) {
        mkdir($Config->blade_cache_dir, 0o755, true);
    }

    $Blade = App::bootBlade($Config, $base_dir);

    echo "Compiling templates...\n";
    compileAllTemplates($Blade);

    $count = count(glob($Config->blade_cache_dir . '/*.php') ?: []);
    echo sprintf("Compiled %d template cache files to %s\n", $count, $Config->blade_cache_dir);
}
