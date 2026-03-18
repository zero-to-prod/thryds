<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Marks a backed enum as a closed set of allowed values in a specific domain.
 *
 * This is the single annotation for enums. For readonly classes, use #[SourceOfTruth] instead.
 *
 * @example
 * #[ClosedSet(Domain::http_methods, addCase: 'Add enum case. No other changes needed.')]
 * enum HTTP_METHOD: string
 * {
 *     case GET = 'GET';
 *     case POST = 'POST';
 * }
 *
 * @example
 * #[ClosedSet(Domain::application_environment, addCase: '1. Add enum case. 2. Handle in Config::__construct().')]
 * enum AppEnv: string
 * {
 *     case production = 'production';
 *     case development = 'development';
 * }
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ClosedSet
{
    /**
     * @param Domain $Domain  The value domain this enum constrains.
     * @param string $addCase Human-readable checklist for what to do when adding a new enum case.
     */
    public function __construct(
        public Domain $Domain,
        public string $addCase,
    ) {}
}
