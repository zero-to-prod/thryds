<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Routes\HttpMethod;

/**
 * Declares one HTTP operation on a Route enum case.
 * Apply multiple times to register more than one method on the same path.
 *
 * @example
 * #[RouteInfo('User authentication')]
 * #[RouteOperation(HttpMethod::GET,  'Render login form')]
 * #[RouteOperation(HttpMethod::POST, 'Handle login submission')]
 * case login = '/login';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
readonly class RouteOperation
{
    public function __construct(
        public HttpMethod $HttpMethod,
        public string $description,
    ) {}
}
