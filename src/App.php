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
use ReflectionClass;
use ReflectionNamedType;
use ZeroToProd\Thryds\Attributes\Bind;
use ZeroToProd\Thryds\Attributes\Requirement;
use ZeroToProd\Thryds\Blade\BladeDirectives;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Routes\RouteRegistrar;

readonly class App
{
    public function __construct(
        public Config $Config,
        #[Bind]
        public Blade $Blade,
        public CachedRouter $Router,
        #[Bind]
        public Database $Database,
    ) {}

    /** Reflects on own properties and registers those marked with #[Bind] as container instances. */
    public function registerBindings(): void
    {
        $Container = Container::getInstance();
        foreach (new ReflectionClass(self::class)->getProperties() as $Property) {
            if ($Property->getAttributes(Bind::class) === []) {
                continue;
            }
            $Type = $Property->getType();
            assert($Type instanceof ReflectionNamedType);
            $Container->instance($Type->getName(), instance: $this->{$Property->getName()});
        }
    }

    public static function bootBlade(Config $Config, Vite $Vite): Blade
    {
        $Container = new BladeContainer();
        Container::setInstance(container: $Container);
        // Bind Factory contract so ComponentTagCompiler can resolve aliases for <x-*> components.
        $Container->bind(Factory::class, fn() => $Container->get('view'));

        $Blade = new Blade(viewPaths: $Config->template_dir, cachePath: $Config->blade_cache_dir, container: $Container);

        $Container->instance(Vite::class, instance: $Vite);

        BladeDirectives::register($Blade, $Config, $Vite);
        Component::register($Blade);

        return $Blade;
    }

    #[Requirement('PERF-001')]
    public static function boot(string $base_dir, ?Config $Config = null, ?DatabaseConfig $DatabaseConfig = null): self
    {
        $Config ??= Config::from([
            Config::AppEnv => AppEnv::fromEnv(),
            Config::blade_cache_dir => $base_dir . '/var/cache/blade',
            Config::template_dir => $base_dir . '/templates',
        ]);
        $Blade = self::bootBlade($Config, new Vite($Config, baseDir: $base_dir, entry_css: [
            Vite::app_entry => [Vite::app_css],
        ]));

        // cacheEnabled is always false: FrankenPHP worker mode boots once and reuses the router
        // in-memory across all requests, so disk caching provides no benefit and breaks with
        // Blade captured in route handler closures (Blade contains non-serializable container bindings).
        $Database = new Database($DatabaseConfig ?? DatabaseConfig::fromEnv());

        $Router = new CachedRouter(
            builder: static function (Router $Router) use ($Config): Router {
                RouteRegistrar::register($Router, $Config);

                return $Router;
            },
            cache: new FileCache(cacheFilePath: $base_dir . '/var/cache/route.cache', ttl: 86400),
            cacheEnabled: false,
        );

        $App = new self($Config, $Blade, $Router, $Database);
        $App->registerBindings();

        return $App;
    }
}
