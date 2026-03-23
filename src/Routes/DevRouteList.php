<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\Guarded;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Controllers\OpcacheScriptsHandler;
use ZeroToProd\Framework\Controllers\OpcacheStatusHandler;
use ZeroToProd\Framework\Routes\Actions\StaticView;
use ZeroToProd\Framework\Routes\HttpMethod;
use ZeroToProd\Framework\Routes\RouteGuard;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Controllers\RouteManifestHandler;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::url_routes,
    addCase: <<<TEXT
    1. Add entry to thryds.yaml routes section with guard: devOnly.
    2. Run ./run sync:manifest.
    3. Implement controller logic (if controller route).
    4. Run ./run fix:all.
    TEXT
)]
#[Guarded(RouteGuard::devOnly)]
enum DevRouteList: string
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
        new StaticView(View::styleguide),
        'UI component and design token reference'
    )]
    case styleguide = '/_styleguide';

    #[Route(
        HttpMethod::GET,
        RouteManifestHandler::class,
        'Machine-readable manifest of all registered routes'
    )]
    case routes = '/_routes';
}
