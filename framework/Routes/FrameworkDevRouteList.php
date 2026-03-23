<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\Guarded;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Controllers\OpcacheScriptsHandler;
use ZeroToProd\Framework\Controllers\OpcacheStatusHandler;
use ZeroToProd\Framework\Controllers\RouteManifestHandler;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Dev-only routes provided by the framework for introspection and diagnostics.
 */
#[ClosedSet(
    Domain::framework_dev_routes,
    addCase: <<<TEXT
    1. Add enum case with #[Route] attribute.
    2. Implement handler in framework/Controllers/.
    3. Run ./run fix:all.
    TEXT
)]
#[Guarded(RouteGuard::devOnly)]
enum FrameworkDevRouteList: string
{
    #[Route(
        HttpMethod::GET,
        OpcacheStatusHandler::class,
        'OPcache runtime statistics'
    )]
    case opcache_status = '/_opcache/status';

    #[Route(
        HttpMethod::GET,
        OpcacheScriptsHandler::class,
        'Scripts loaded in OPcache'
    )]
    case opcache_scripts = '/_opcache/scripts';

    #[Route(
        HttpMethod::GET,
        RouteManifestHandler::class,
        'Machine-readable manifest of all registered routes'
    )]
    case routes = '/_routes';
}
