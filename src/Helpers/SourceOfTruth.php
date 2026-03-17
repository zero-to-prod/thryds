<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Declares a class or enum as the canonical owner of a domain concept.
 *
 * AI agents use the `addCase` checklist to know the full set of changes required
 * when adding a new case or constant. Usage sites are discovered dynamically
 * by searching the codebase.
 *
 * @example #[SourceOfTruth(for: 'route paths', addCase: '1. Add enum case. 2. Register in WebRoutes.')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class SourceOfTruth
{
    /**
     * @param string $for     Human-readable name of the concept this class owns.
     * @param string $addCase Human-readable checklist for what to do when adding a new case/constant.
     *                        AI agents read this to know the full set of changes required.
     */
    public function __construct(
        public string $for,
        public string $addCase = '',
    ) {}
}
