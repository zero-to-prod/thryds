<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the zero-value default used when preload-rendering a view with stub data.
 *
 * Applied to ViewModel properties. Read by {@see \ZeroToProd\Thryds\Blade\View::stubData()}
 * so stub values are visible in the attribute graph without runtime type introspection.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class StubValue
{
    public function __construct(
        public mixed $value,
    ) {}
}
