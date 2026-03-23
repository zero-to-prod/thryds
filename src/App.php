<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Cache\FileCache;
use League\Route\Cache\Router as CachedRouter;
use League\Route\Router;
use ReflectionClass;
use ReflectionNamedType;
use Tempest\Blade\Blade;
use Tempest\Blade\Container as BladeContainer;
use ZeroToProd\Thryds\Attributes\Bind;
use ZeroToProd\Thryds\Attributes\Requirement;
use ZeroToProd\Thryds\Blade\BladeDirectives;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Routes\RouteRegistrar;

readonly class App
{
    /** @var list<array{string, string}> Property name and type name pairs for container-bound properties. */
    private array $bindings;

    public function __construct(
        #[Bind]
        public Config $Config,
        #[Bind]
        public Blade $Blade,
        public CompilerEngine $CompilerEngine,
        public SapiEmitter $SapiEmitter,
        public CachedRouter $Router,
        #[Bind]
        public Database $Database,
        public ExceptionHandler $ExceptionHandler,
    ) {
        $bindings = [];
        foreach (new ReflectionClass(self::class)->getProperties() as $Property) {
            if ($Property->getAttributes(Bind::class) === []) {
                continue;
            }
            $Type = $Property->getType();
            assert($Type instanceof ReflectionNamedType);
            $bindings[] = [$Property->getName(), $Type->getName()];
        }
        $this->bindings = $bindings;
    }

    /** Registers properties marked with the container binding attribute as container instances using pre-resolved metadata. */
    public function registerBindings(): void
    {
        $Container = Container::getInstance();
        foreach ($this->bindings as [$propertyName, $typeName]) {
            $Container->instance(abstract: $typeName, instance: $this->{$propertyName});
        }
    }

    public static function bootBlade(Config $Config, Vite $Vite): Blade
    {
        $Container = new BladeContainer();
        Container::setInstance(container: $Container);
        // Bind Factory contract so ComponentTagCompiler can resolve aliases for <x-*> components.
        $Container->bind(Factory::class, fn() => $Container->get('view'));

        $Blade = new Blade(viewPaths: $Config->template_dir, cachePath: $Config->blade_cache_dir, container: $Container);

        $Container->alias('view.engine.resolver', EngineResolver::class);
        $Container->instance(Vite::class, instance: $Vite);

        BladeDirectives::register($Blade, $Config, $Vite);
        Component::register($Blade);

        return $Blade;
    }

    #[Requirement('PERF-001')]
    public static function boot(string $base_dir, ?Config $Config = null): self
    {
        $Config ??= Config::fromEnv($base_dir);
        $Blade = self::bootBlade($Config, new Vite($Config, baseDir: $base_dir, entry_css: [
            Vite::app_entry => [Vite::app_css],
        ]));

        // cacheEnabled is always false: FrankenPHP worker mode boots once and reuses the router
        // in-memory across all requests, so disk caching provides no benefit and breaks with
        // Blade captured in route handler closures (Blade contains non-serializable container bindings).
        $Database = new Database($Config->DatabaseConfig);

        $Router = new CachedRouter(
            builder: static function (Router $Router) use ($Config): Router {
                RouteRegistrar::register($Router, $Config);

                return $Router;
            },
            cache: new FileCache(cacheFilePath: $base_dir . '/var/cache/route.cache', ttl: 86400),
            cacheEnabled: false,
        );

        $Engine = Container::getInstance()->make(EngineResolver::class)->resolve('blade');
        assert($Engine instanceof CompilerEngine);

        $SapiEmitter = new SapiEmitter();
        $App = new self($Config, $Blade, CompilerEngine: $Engine, SapiEmitter: $SapiEmitter, Router: $Router, Database: $Database, ExceptionHandler: new ExceptionHandler($Config, EmitterInterface: $SapiEmitter));
        $App->registerBindings();

        return $App;
    }
}
