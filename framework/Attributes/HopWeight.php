<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Classifies an edge-producing attribute as structural or semantic.
 *
 * Structural edges (weight 0) are organizational — traversing them
 * does not require understanding the target to understand the source.
 * Semantic edges (weight 1) are behavioral — the source's meaning
 * depends on the target's implementation.
 *
 * Used by check:graph to enforce the 1-hop rule: no path through
 * consecutive semantic edges may exceed the configured maximum.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class HopWeight
{
    public function __construct(public int $weight) {}
}
