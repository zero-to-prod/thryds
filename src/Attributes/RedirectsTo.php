<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Routes\Route;

/**
 * Declares the route a controller redirects to on success.
 * Apply multiple times when a controller may redirect to different routes.
 *
 * @example
 * #[RedirectsTo(Route::home)]
 * class RegisterController { ... }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class RedirectsTo
{
    public function __construct(public Route $Route) {}
}
