<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares which controller class handles a route.
 *
 * Applied to Route enum cases to bind a route to its controller.
 * The router discovers controllers via this attribute, eliminating
 * filesystem scanning.
 *
 * @example
 * #[HandledBy(RegisterController::class)]
 * case register = '/register';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class HandledBy
{
    /** @param class-string $controller */
    public function __construct(public string $controller) {}
}
