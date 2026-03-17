<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Helpers\ClosedSet;
use ZeroToProd\Thryds\Helpers\SourceOfTruth;
use ZeroToProd\Thryds\Tests\Integration\IntegrationTestCase;

#[SourceOfTruth(
    for: 'route paths',
    consumers: [
        WebRoutes::class,
        IntegrationTestCase::class,
        'templates/*.blade.php',
        'scripts/opcache-audit.php',
        'scripts/generate-preload.php',
    ],
    addCase: '1. Add enum case. 2. Register in WebRoutes::register(). 3. Create controller + template. 4. Add integration test. 5. Add template render in generate-preload.php.',
)]
#[ClosedSet(domain: 'URL routes', used_in: [[WebRoutes::class, 'register'], [RenderedRoute::class, '__construct']])]
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
