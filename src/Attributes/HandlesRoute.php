<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Routes\Route;

// TODO: [RequireRoutePatternConstRector] Constants name things — route class 'ZeroToProd\Thryds\Attributes\HandlesRoute' is missing a 'pattern' constant. Define: public const string pattern = '/...'.
/**
 * Declares which route a controller handles.
 *
 * The router discovers controllers via this attribute at boot time,
 * eliminating manual wiring in the route registrar.
 *
 * @example
 * #[HandlesRoute(Route::register)]
 * class RegisterController { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class HandlesRoute
{
    public function __construct(public Route $Route) {}
}
