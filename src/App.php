<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Illuminate\Container\Container;
use Jenssegers\Blade\Blade;
use Jenssegers\Blade\Container as BladeContainer;
use League\Route\Cache\FileCache;
use League\Route\Cache\Router as CachedRouter;
use League\Route\Router;
use ZeroToProd\Thryds\Helpers\Vite;
use ZeroToProd\Thryds\Routes\WebRoutes;

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

        $Blade->if(AppEnv::production->value, fn(): bool => $Config->AppEnv === AppEnv::production);
        $Blade->if('env', fn(string ...$environments): bool => in_array($Config->AppEnv->value, haystack: $environments, strict: true));

        $Vite = new Vite($Config, baseDir: $base_dir, entry_css: [
            Vite::app_entry => [Vite::app_css],
        ]);
        $Container->instance(Vite::class, instance: $Vite);
        $Blade->directive('vite', static fn(): string => $Vite->directivePhp(Vite::app_entry));
        $Blade->directive('htmx', static fn(): string => $Vite->directivePhp(Vite::htmx_entry));

        return $Blade;
    }

    public static function boot(string $base_dir, ?Config $Config = null): self
    {
        $Config ??= Config::from([
            Config::AppEnv => $_SERVER[Server::APP_ENV] ?? $_ENV[Env::APP_ENV] ?? AppEnv::production->value,
            Config::blade_cache_dir => $base_dir . '/var/cache/blade',
            Config::template_dir => $base_dir . '/templates',
        ]);

        $Blade = self::bootBlade($Config, $base_dir);

        $Router = new CachedRouter(
            builder: static function (Router $Router) use ($Blade): Router {
                WebRoutes::register($Router, $Blade);

                return $Router;
            },
            cache: new FileCache(cacheFilePath: $base_dir . '/var/cache/route.cache', ttl: 86400),
            cacheEnabled: $Config->isProduction(),
        );

        return new self($Config, $Blade, $Router);
    }
}
