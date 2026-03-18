<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory;
use Jenssegers\Blade\Blade;
use Jenssegers\Blade\Container as BladeContainer;
use League\Route\Cache\FileCache;
use League\Route\Cache\Router as CachedRouter;
use League\Route\Router;
use ZeroToProd\Thryds\Blade\BladeDirectives;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Routes\RouteRegistrar;

readonly class App
{
    public function __construct(
        public Config $Config,
        public Blade $Blade,
        public CachedRouter $Router,
    ) {}

    public static function bootBlade(Config $Config, string $base_dir): Blade
    {
        $Container = new BladeContainer();
        Container::setInstance(container: $Container);
        $Blade = new Blade(viewPaths: $Config->template_dir, cachePath: $Config->blade_cache_dir, container: $Container);

        $Vite = new Vite($Config, baseDir: $base_dir, entry_css: [
            Vite::app_entry => [Vite::app_css],
        ]);
        $Container->instance(Vite::class, instance: $Vite);
        BladeDirectives::register($Blade, $Config, $Vite);

        // Bind Factory contract so ComponentTagCompiler can resolve aliases for <x-*> components.
        $Container->bind(Factory::class, fn() => $Container->get('view'));

        foreach (Component::cases() as $Component) {
            $Blade->compiler()->component($Component->viewName(), $Component->value);
        }

        return $Blade;
    }

    public static function boot(string $base_dir, ?Config $Config = null): self
    {
        $Config ??= Config::from([
            Config::AppEnv => $_SERVER[Env::APP_ENV] ?? $_ENV[Env::APP_ENV] ?? AppEnv::production->value,
            Config::blade_cache_dir => $base_dir . '/var/cache/blade',
            Config::template_dir => $base_dir . '/templates',
        ]);

        $Blade = self::bootBlade($Config, $base_dir);

        // cacheEnabled is always false: FrankenPHP worker mode boots once and reuses the router
        // in-memory across all requests, so disk caching provides no benefit and breaks with
        // Blade captured in route handler closures (Blade contains non-serializable container bindings).
        $Router = new CachedRouter(
            builder: static function (Router $Router) use ($Blade, $Config): Router {
                RouteRegistrar::register($Router, $Blade, $Config);

                return $Router;
            },
            cache: new FileCache(cacheFilePath: $base_dir . '/var/cache/route.cache', ttl: 86400),
            cacheEnabled: false,
        );

        return new self($Config, $Blade, $Router);
    }
}
