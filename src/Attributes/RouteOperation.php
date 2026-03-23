<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Routes\HandlerStrategy;
use ZeroToProd\Thryds\Routes\HttpMethod;

/**
 * Declares one HTTP operation on a Route enum case.
 * Apply multiple times to register more than one method on the same path.
 *
 * Resource-level properties (info, controller, View) need only appear on one
 * operation per route case — the resolver takes the first non-null value.
 *
 * @example
 * #[RouteOperation(HttpMethod::GET,  'Render login form',        HandlerStrategy::form, info: 'Login', controller: LoginController::class, View: View::login)]
 * #[RouteOperation(HttpMethod::POST, 'Handle login submission',  HandlerStrategy::validated, info: null, controller: null, View: null)]
 * case login = '/login';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
#[HopWeight(0)]
readonly class RouteOperation
{
    /** @param class-string|null $controller */
    public function __construct(
        public HttpMethod $HttpMethod,
        public string $description,
        public HandlerStrategy $HandlerStrategy,
        public ?string $info,
        public ?string $controller,
        public ?View $View,
    ) {}
}
