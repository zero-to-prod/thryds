<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Helpers\ClosedSet;
use ZeroToProd\Thryds\Helpers\Domain;

#[ClosedSet(
    Domain::url_routes,
    addCase: '1. Add enum case. 2. Register in WebRoutes::register() (inline closure for read-only views, controller class for stateful or complex logic). 3. Create controller + template. 4. Add integration test. 5. Add template render in generate-preload.php.',
)]
enum Route: string
{
    case home = '/';
    case about = '/about';
    case opcache_status = '/_opcache/status';
    case login = '/login';
    case opcache_scripts = '/_opcache/scripts';
    case styleguide = '/_styleguide';

    /** @return string[] Parameter names extracted from {placeholders} in the route pattern. */
    public function params(): array
    {
        preg_match_all(pattern: '/\{(\w+)\}/', subject: $this->value, matches: $matches);

        return $matches[1];
    }

    public function with(array $params = [], array $query = []): RenderedRoute
    {
        return new RenderedRoute(Route: $this, params: $params, query: $query);
    }
}
