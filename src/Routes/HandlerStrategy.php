<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Declares how a route operation is dispatched at runtime.
 *
 * Each #[RouteOperation] carries a HandlerStrategy so the attribute graph
 * exposes dispatch behavior without reading infrastructure code.
 */
#[ClosedSet(
    Domain::handler_strategies,
    addCase: 'Add enum case. Then handle it in RouteRegistrar::handler() match expression.'
)]
enum HandlerStrategy: string
{
    /** Render a Blade view with no controller. */
    case static_view = 'static_view';

    /** Delegate directly to the controller. */
    case controller = 'controller';

    /** Render a form view with an empty ViewModel. */
    case form = 'form';

    /** Validate the request body; re-render on error, delegate on success. */
    case validated = 'validated';
}
