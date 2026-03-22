<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the resolver class for a persistence hook enum case.
 *
 * The resolver class must carry #[PersistResolver]. At runtime, the
 * Persist enum reads this attribute via reflection and delegates to the
 * resolver — no match statement or case-by-case dispatch needed.
 *
 * @example
 * #[ResolvesTo(RandomIdResolver::class)]
 * case random_id = 'random_id';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
#[HopWeight(1)]
readonly class ResolvesTo
{
    public function __construct(
        /** @var class-string */
        public string $resolver,
    ) {}

    public function newResolver(): object
    {
        return new $this->resolver();
    }
}
