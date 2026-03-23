<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\Guarded;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Routes\Actions\StaticView;
use ZeroToProd\Framework\Routes\HttpMethod;
use ZeroToProd\Framework\Routes\RouteGuard;
use ZeroToProd\Thryds\Blade\View;
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
        new StaticView(View::styleguide),
        'UI component and design token reference'
    )]
    case styleguide = '/_styleguide';
}
