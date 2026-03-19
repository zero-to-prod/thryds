<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ReflectionAttribute;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\DevOnly;
use ZeroToProd\Thryds\Attributes\RouteInfo;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::url_routes,
    addCase: <<<TEXT
    1. Add enum case with #[RouteInfo('<route description>')] and one or more #[RouteOperation(HttpMethod::<METHOD>, '<operation description>')] attributes.
    2. If the route is a simple read-only view: add a matching View case with the same name — RouteRegistrar::register() auto-registers it via View::tryFrom(\$Route->name). If the route needs stateful or complex logic: add an explicit \$Router->map() call in RouteRegistrar::register() instead.
    3. Create controller (if needed) + template.
    4. Add integration test.
    5. Add template render in generate-preload.php.
    TEXT
)]
enum Route: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(
        HttpMethod::GET,
        'Marketing home page'
    )]
    case home = '/';

    #[RouteInfo('About')]
    #[RouteOperation(
        HttpMethod::GET,
        'Company and product information'
    )]
    case about = '/about';

    #[RouteInfo('Login')]
    #[RouteOperation(
        HttpMethod::GET,
        'User authentication form'
    )]
    case login = '/login';

    #[RouteInfo('Register')]
    #[RouteOperation(
        HttpMethod::GET,
        'New user registration form'
    )]
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
        return !empty(new ReflectionEnumUnitCase(self::class, $this->name)->getAttributes(DevOnly::class));
    }

    /** Returns the route-level description declared via #[RouteInfo]. */
    public function description(): string
    {
        return new ReflectionEnumUnitCase(self::class, $this->name)
            ->getAttributes(RouteInfo::class)[0]
            ->newInstance()
            ->description;
    }

    /** @return RouteOperation[] HTTP operations declared on this route via #[RouteOperation]. */
    public function operations(): array
    {
        return array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): RouteOperation => $ReflectionAttribute->newInstance(),
            new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(RouteOperation::class),
        );
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
