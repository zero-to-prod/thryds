<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the human-readable description of a Route enum case (the path/resource level).
 * Pair with one or more #[RouteOperation] attributes to declare the supported HTTP methods.
 *
 * @example
 * #[RouteInfo('User authentication')]
 * #[RouteOperation(HttpMethod::GET,  'Render login form')]
 * #[RouteOperation(HttpMethod::POST, 'Handle login submission')]
 * case login = '/login';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class RouteInfo
{
    public function __construct(
        public string $description,
    ) {}
}
