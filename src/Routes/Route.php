<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ReflectionAttribute;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\DevOnly;
use ZeroToProd\Thryds\Attributes\RendersView;
use ZeroToProd\Thryds\Attributes\RouteInfo;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Blade\View;
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
        'Marketing home page'
    )]
    #[RendersView(View::home)]
    case home = '/';

    #[RouteInfo('About')]
    #[RouteOperation(
        HttpMethod::GET,
        'Company and product information'
    )]
    #[RendersView(View::about)]
    case about = '/about';

    #[RouteInfo('Login')]
    #[RouteOperation(
        HttpMethod::GET,
        'User authentication form'
    )]
    #[RendersView(View::login)]
    case login = '/login';

    #[RouteInfo('Register')]
    #[RouteOperation(
        HttpMethod::GET,
        'New user registration form'
    )]
    #[RouteOperation(
        HttpMethod::POST,
        'Handle registration submission'
    )]
    #[RendersView(View::register)]
    case register = '/register';

    #[DevOnly]
    #[RouteInfo('OPcache status')]
    #[RouteOperation(
        HttpMethod::GET,
        'OPcache runtime statistics'
    )]
    case opcache_status = '/_opcache/status';

    #[DevOnly]
    #[RouteInfo('OPcache scripts')]
    #[RouteOperation(
        HttpMethod::GET,
        'Scripts loaded in OPcache'
    )]
    case opcache_scripts = '/_opcache/scripts';

    #[DevOnly]
    #[RouteInfo('Style guide')]
    #[RouteOperation(
        HttpMethod::GET,
        'UI component and design token reference'
    )]
    #[RendersView(View::styleguide)]
    case styleguide = '/_styleguide';

    #[DevOnly]
    #[RouteInfo('Routes')]
    #[RouteOperation(
        HttpMethod::GET,
        'Machine-readable manifest of all registered routes'
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

    /** @return string[] Parameter names extracted from {placeholders} in the route pattern. */
    public function params(): array
    {
        preg_match_all(pattern: '/\{(\w+)}/', subject: $this->value, matches: $matches);

        return $matches[1];
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
