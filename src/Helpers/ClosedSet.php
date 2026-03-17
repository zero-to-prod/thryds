<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Marks a backed enum as a closed set of allowed values in a specific domain.
 *
 * @example
 * #[ClosedSet(domain: 'HTTP methods', used_in: [[WebRoutes::class, 'register']])]
 * enum HTTP_METHOD: string
 * {
 *     case GET = 'GET';
 *     case POST = 'POST';
 * }
 *
 * @example
 * #[ClosedSet(domain: 'application environment', used_in: [[Config::class, '__construct'], [App::class, 'boot']])]
 * enum AppEnv: string
 * {
 *     case production = 'production';
 *     case development = 'development';
 * }
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ClosedSet
{
    /** @param list<array{class: class-string, method: string}> $used_in Each entry is [Class::class, 'method'] — AST-refactorable. */
    public function __construct(
        public string $domain,
        public array $used_in = [],
    ) {}
}
