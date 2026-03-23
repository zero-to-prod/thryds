<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Marks a class as application infrastructure — plumbing that supports the domain layer
 * but does not itself carry domain-specific attributes.
 *
 * Classes carrying this attribute appear in the attribute graph as nodes,
 * satisfying the discovery rule without forcing a domain attribute onto infrastructure code.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Infrastructure {}
