<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Marks a backed enum as a closed set of allowed values in a specific domain.
 *
 * @example
 * #[ClosedSet(Domain: Domain::http_methods)]
 * enum HTTP_METHOD: string
 * {
 *     case GET = 'GET';
 *     case POST = 'POST';
 * }
 *
 * @example
 * #[ClosedSet(Domain: Domain::application_environment, addCase: '1. Add enum case. 2. Handle in Config::__construct().')]
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
        public string $addCase = '',
    ) {}
}
