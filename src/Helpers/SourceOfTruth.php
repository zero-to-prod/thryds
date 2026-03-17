<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Declares a class or enum as the canonical owner of a domain concept.
 *
 * Lists the files/classes that consume this data. Rector rules verify that
 * consumers actually reference the source class. AI agents use the consumers
 * list as a dependency map when making changes.
 *
 * @example #[SourceOfTruth(for: 'route paths', consumers: [WebRoutes::class, 'templates/*.blade.php'])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class SourceOfTruth
{
    /**
     * @param string   $for        Human-readable name of the concept this class owns.
     * @param string[] $consumers  Where this class's data is consumed. Each entry is either:
     *                             - A class FQN (verified by Rector: must import the source class)
     *                             - A file glob pattern (verified by Rector: at least one match must reference the source)
     * @param string   $addCase    Human-readable checklist for what to do when adding a new case/constant.
     *                             AI agents read this to know the full set of changes required.
     */
    public function __construct(
        public string $for,
        public array $consumers = [],
        public string $addCase = '',
    ) {}
}
