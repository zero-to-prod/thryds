<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Declares a class as the canonical owner of a domain concept.
 *
 * For enums, use #[ClosedSet] instead — it carries Domain and addCase in one attribute.
 * SourceOfTruth is for readonly classes whose public constants name keys (e.g. Env, DevFilter).
 *
 * AI agents use the `addCase` checklist to know the full set of changes required.
 * Usage sites are discovered dynamically by searching the codebase.
 *
 * @example #[SourceOfTruth(Concept::environment_variable_keys, addCase: '1. Add constant. 2. Add to compose.yaml.')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class SourceOfTruth
{
    /**
     * @param Concept $Concept  The concept this class owns.
     * @param string  $addCase Human-readable checklist for what to do when adding a new case/constant.
     *                         AI agents read this to know the full set of changes required.
     */
    public function __construct(
        public Concept $Concept,
        public string $addCase = '',
    ) {}
}
