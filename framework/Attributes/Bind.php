<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Declares that a promoted constructor property should be registered in the service container.
 *
 * Applied to readonly properties on the App class. At boot, properties carrying this
 * attribute are reflected and bound as container instances keyed by their declared type.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Bind {}
