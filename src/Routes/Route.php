<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

enum Route: string
{
    case home = '/';
    case about = '/about';
    case opcache_status = '/_opcache/status';
    case opcache_scripts = '/_opcache/scripts';

    public function with(array $params = [], array $query = []): RenderedRoute
    {
        return new RenderedRoute(Route: $this, params: $params, query: $query);
    }
}
