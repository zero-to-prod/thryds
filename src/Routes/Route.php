<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ReflectionAttribute;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\DevOnly;
use ZeroToProd\Thryds\Attributes\HandledBy;
use ZeroToProd\Thryds\Attributes\RendersView;
use ZeroToProd\Thryds\Attributes\RouteInfo;
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
    #[RouteInfo('Home')]
    #[RouteOperation(
        HttpMethod::GET,
        'Marketing home page',
        HandlerStrategy::static_view
    )]
    #[RendersView(View::home)]
    case home = '/';

    #[RouteInfo('About')]
    #[RouteOperation(
        HttpMethod::GET,
        'Company and product information',
        HandlerStrategy::static_view
    )]
    #[RendersView(View::about)]
    case about = '/about';

    #[RouteInfo('Login')]
    #[RouteOperation(
        HttpMethod::GET,
        'User authentication form',
        HandlerStrategy::static_view
    )]
    #[RendersView(View::login)]
    case login = '/login';

    #[RouteInfo('Register')]
    #[RouteOperation(
        HttpMethod::GET,
        'New user registration form',
        HandlerStrategy::form
    )]
    #[RouteOperation(
        HttpMethod::POST,
        'Handle registration submission',
        HandlerStrategy::validated
    )]
    #[HandledBy(RegisterController::class)]
    #[RendersView(View::register)]
    case register = '/register';

    #[DevOnly]
    #[RouteInfo('OPcache status')]
    #[RouteOperation(
        HttpMethod::GET,
        'OPcache runtime statistics',
        HandlerStrategy::controller
    )]
    #[HandledBy(OpcacheStatusHandler::class)]
    case opcache_status = '/_opcache/status';

    #[DevOnly]
    #[RouteInfo('OPcache scripts')]
    #[RouteOperation(
        HttpMethod::GET,
        'Scripts loaded in OPcache',
        HandlerStrategy::controller
    )]
    #[HandledBy(OpcacheScriptsHandler::class)]
    case opcache_scripts = '/_opcache/scripts';

    #[DevOnly]
    #[RouteInfo('Style guide')]
    #[RouteOperation(
        HttpMethod::GET,
        'UI component and design token reference',
        HandlerStrategy::static_view
    )]
    #[RendersView(View::styleguide)]
    case styleguide = '/_styleguide';

    #[DevOnly]
    #[RouteInfo('Routes')]
    #[RouteOperation(
        HttpMethod::GET,
        'Machine-readable manifest of all registered routes',
        HandlerStrategy::controller
    )]
    #[HandledBy(RouteManifestHandler::class)]
    case routes = '/_routes';

    public function isDevOnly(): bool
    {
        /** @var array<string, bool> $cache */
        static $cache = [];

        return $cache[$this->name] ??= !empty(
            new ReflectionEnumUnitCase(self::class, $this->name)->getAttributes(DevOnly::class)
        );
    }

    /** Returns the route-level description declared via #[RouteInfo]. */
    public function description(): string
    {
        /** @var array<string, string> $cache */
        static $cache = [];

        return $cache[$this->name] ??= new ReflectionEnumUnitCase(self::class, $this->name)
            ->getAttributes(RouteInfo::class)[0]
            ->newInstance()
            ->description;
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

    /** Returns the view declared via #[RendersView], or null if this route has no view. */
    public function rendersView(): ?View
    {
        /** @var array<string, ?View> $cache */
        static $cache = [];

        if (!array_key_exists($this->name, array: $cache)) {
            $attrs = new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(RendersView::class);
            $cache[$this->name] = $attrs !== [] ? $attrs[0]->newInstance()->View : null;
        }

        return $cache[$this->name];
    }

    /** Returns the controller class declared via #[HandledBy], or null if this route has no controller. */
    public function controller(): ?object
    {
        /** @var array<string, ?object> $cache */
        static $cache = [];

        if (!array_key_exists($this->name, array: $cache)) {
            $attrs = new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(HandledBy::class);
            $cache[$this->name] = $attrs !== [] ? new ($attrs[0]->newInstance()->controller)() : null;
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
