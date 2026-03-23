<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use LogicException;
use ReflectionAttribute;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\DevOnly;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Attributes\RouteParam;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Controllers\OpcacheScriptsHandler;
use ZeroToProd\Thryds\Controllers\OpcacheStatusHandler;
use ZeroToProd\Thryds\Controllers\RegisterController;
use ZeroToProd\Thryds\Controllers\RouteManifestHandler;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::url_routes,
    addCase: <<<TEXT
    1. Add entry to thryds.yaml routes section.
    2. Run ./run sync:manifest.
    3. Implement controller logic (if controller route).
    4. Run ./run fix:all.
    TEXT
)]
enum Route: string
{
    #[RouteOperation(
        HttpMethod::GET,
        'Marketing home page',
        HandlerStrategy::static_view,
        info: 'Home',
        controller: null,
        View: View::home,
    )]
    case home = '/';

    #[RouteOperation(
        HttpMethod::GET,
        'Company and product information',
        HandlerStrategy::static_view,
        info: 'About',
        controller: null,
        View: View::about,
    )]
    case about = '/about';

    #[RouteOperation(
        HttpMethod::GET,
        'User authentication form',
        HandlerStrategy::static_view,
        info: 'Login',
        controller: null,
        View: View::login,
    )]
    case login = '/login';

    #[RouteOperation(
        HttpMethod::GET,
        'New user registration form',
        HandlerStrategy::form,
        info: 'Register',
        controller: RegisterController::class,
        View: View::register,
    )]
    #[RouteOperation(
        HttpMethod::POST,
        'Handle registration submission',
        HandlerStrategy::validated,
        info: null,
        controller: null,
        View: null,
    )]
    case register = '/register';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        'OPcache runtime statistics',
        HandlerStrategy::controller,
        info: 'OPcache status',
        controller: OpcacheStatusHandler::class,
        View: null,
    )]
    case opcache_status = '/_opcache/status';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        'Scripts loaded in OPcache',
        HandlerStrategy::controller,
        info: 'OPcache scripts',
        controller: OpcacheScriptsHandler::class,
        View: null,
    )]
    case opcache_scripts = '/_opcache/scripts';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        'UI component and design token reference',
        HandlerStrategy::static_view,
        info: 'Style guide',
        controller: null,
        View: View::styleguide,
    )]
    case styleguide = '/_styleguide';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        'Machine-readable manifest of all registered routes',
        HandlerStrategy::controller,
        info: 'Routes',
        controller: RouteManifestHandler::class,
        View: null,
    )]
    case routes = '/_routes';

    public function isDevOnly(): bool
    {
        /** @var array<string, bool> $cache */
        static $cache = [];

        return $cache[$this->name] ??= !empty(
            new ReflectionEnumUnitCase(self::class, $this->name)->getAttributes(DevOnly::class)
        );
    }

    /** Returns the route-level description from the first #[RouteOperation] with a non-null info. */
    public function description(): string
    {
        /** @var array<string, string> $cache */
        static $cache = [];

        return $cache[$this->name] ??= (function (): string {
            foreach ($this->operations() as $op) {
                if ($op->info !== null) {
                    return $op->info;
                }
            }
            throw new LogicException("Route::{$this->name} has no #[RouteOperation] with an info parameter.");
        })();
    }

    /** @return RouteOperation[] HTTP operations declared on this route via #[RouteOperation]. */
    public function operations(): array
    {
        /** @var array<string, RouteOperation[]> $cache */
        static $cache = [];

        return $cache[$this->name] ??= array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): RouteOperation => $ReflectionAttribute->newInstance(),
            new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(RouteOperation::class),
        );
    }

    /** Returns the View from the first #[RouteOperation] with a non-null View, or null. */
    public function rendersView(): ?View
    {
        /** @var array<string, ?View> $cache */
        static $cache = [];

        if (!array_key_exists($this->name, array: $cache)) {
            $cache[$this->name] = null;
            foreach ($this->operations() as $op) {
                if ($op->View !== null) {
                    $cache[$this->name] = $op->View;
                    break;
                }
            }
        }

        return $cache[$this->name];
    }

    /** Returns the controller from the first #[RouteOperation] with a non-null controller, or null. */
    public function controller(): ?object
    {
        /** @var array<string, ?object> $cache */
        static $cache = [];

        if (!array_key_exists($this->name, array: $cache)) {
            $cache[$this->name] = null;
            foreach ($this->operations() as $op) {
                if ($op->controller !== null) {
                    $cache[$this->name] = new ($op->controller)();
                    break;
                }
            }
        }

        return $cache[$this->name];
    }

    /** @return string[] Parameter names declared via #[RouteParam] on this route case. */
    public function params(): array
    {
        /** @var array<string, string[]> $cache */
        static $cache = [];

        return $cache[$this->name] ??= array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): string => $ReflectionAttribute->newInstance()->name,
            new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(RouteParam::class),
        );
    }

    /**
     * @param array<string, string> $params
     * @param array<string, string> $query
     */
    public function with(array $params = [], array $query = []): RouteUrl
    {
        return new RouteUrl(Route: $this, params: $params, query: $query);
    }
}
