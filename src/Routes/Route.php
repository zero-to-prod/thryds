<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet]
enum Route: string
{
    case home = '/';
    case about = '/about';
    case opcache_status = '/_opcache/status';
    case opcache_scripts = '/_opcache/scripts';

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
