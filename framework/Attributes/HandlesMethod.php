<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use ZeroToProd\Framework\Routes\HttpMethod;

/**
 * Declares which HTTP method a controller method handles.
 *
 * Makes the method-to-operation binding inspectable in the attribute graph
 * without relying on naming conventions.
 */
#[Attribute(Attribute::TARGET_METHOD)]
#[HopWeight(0)]
readonly class HandlesMethod
{
    public function __construct(public HttpMethod $HttpMethod) {}
}
