<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares a URL parameter on a Route enum case.
 * Apply once per {placeholder} in the route path so the parameter
 * schema is visible in the attribute graph without parsing the path string.
 *
 * @example
 * #[RouteParam('user_id', 'ULID of the target user')]
 * case profile = '/users/{user_id}';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
readonly class RouteParam
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}
}
