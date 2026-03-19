<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\DevOnly;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::url_routes,
    addCase: <<<TEXT
    1. Add enum case. 
    2. If the route is a simple read-only view: add a matching View case with the same name — RouteRegistrar::register() auto-registers it via View::tryFrom(\$Route->name). If the route needs stateful or complex logic: add an explicit \$Router->map() call in RouteRegistrar::register() instead. 
    3. Create controller (if needed) + template. 
    4. Add integration test. 
    5. Add template render in generate-preload.php.
    TEXT
)]
enum Route: string
{
    case home = '/';
    case about = '/about';
    case login = '/login';
    case register = '/register';
    #[DevOnly]
    case opcache_status = '/_opcache/status';
    #[DevOnly]
    case opcache_scripts = '/_opcache/scripts';
    #[DevOnly]
    case styleguide = '/_styleguide';
    #[DevOnly]
    case routes = '/_routes';

    public function isDevOnly(): bool
    {
        return !empty(new ReflectionEnumUnitCase(self::class, $this->name)->getAttributes(DevOnly::class));
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
