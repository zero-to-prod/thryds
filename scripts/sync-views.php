<?php

declare(strict_types=1);

/**
 * Pre-compiles all Blade templates to the cache directory.
 *
 * Renders every View enum case (with stub data) so that all templates —
 * including components referenced transitively — are compiled and written
 * to var/cache/blade before the first real request.
 *
 * Usage: docker compose exec web php scripts/sync-views.php
 * Via Composer: ./run sync:views
 * Build: called by sync-preload.php during `docker build`
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Tempest\Blade\Blade;
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\ConfigKey;

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

// Only execute when run directly (not when required by sync-preload.php)
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $base_dir = dirname(__DIR__);

    $bladeConfig = Yaml::parseFile(__DIR__ . '/blade-config.yaml');

    $Config = Config::from([
        ConfigKey::AppEnv->value => AppEnv::production->value,
        ConfigKey::blade_cache_dir->value => $base_dir . '/' . $bladeConfig['cache_dir'],
        ConfigKey::template_dir->value => $base_dir . '/' . $bladeConfig['template_dir'],
    ]);

    if (!is_dir($Config->blade_cache_dir)) {
        mkdir($Config->blade_cache_dir, 0o755, true);
    }

    $Vite = new Vite($Config, baseDir: $base_dir, entry_css: $bladeConfig['vite']['entry_css']);
    $Blade = App::bootBlade($Config, $Vite);

    echo "Compiling templates...\n";
    compileAllTemplates($Blade);

    $count = count(glob($Config->blade_cache_dir . '/*.php') ?: []);
    echo sprintf("Compiled %d template cache files to %s\n", $count, $Config->blade_cache_dir);
}
