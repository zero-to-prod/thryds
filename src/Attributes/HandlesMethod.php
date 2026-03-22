<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Routes\HttpMethod;

/**
 * Declares which HTTP method a controller method handles.
 *
 * Makes the method-to-operation binding inspectable in the attribute graph
 * without relying on naming conventions.
 *
 * @example
 * #[HandlesMethod(HttpMethod::POST)]
 * public function post(RegisterRequest $request): ResponseInterface { ... }
 */
#[Attribute(Attribute::TARGET_METHOD)]
#[HopWeight(0)]
readonly class HandlesMethod
{
    public function __construct(public HttpMethod $HttpMethod) {}
}
