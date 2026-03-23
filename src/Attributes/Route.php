<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use Closure;
use Stringable;
use ZeroToProd\Thryds\Routes\Actions\Form;
use ZeroToProd\Thryds\Routes\Actions\StaticView;
use ZeroToProd\Thryds\Routes\Actions\Validated;
use ZeroToProd\Thryds\Routes\HttpMethod;

/**
 * Declares one HTTP operation on a Route enum case.
 * Apply multiple times to register more than one method on the same path.
 *
 * The $action parameter accepts strategy objects or callable references:
 * - new StaticView(View::home)                          — render a view
 * - new Form(View::register, controller: ..., ...)      — form with validation
 * - new Validated(controller: ..., request: ..., ...)    — validate then delegate
 * - InvokableController::class                          — class-string (invokable)
 * - [SomeController::class, 'method']                   — array callable
 * - SomeController::method(...)                         — first-class callable
 *
 * @example
 * #[RouteOperation(HttpMethod::GET, new StaticView(View::home), 'Marketing home page')]
 * #[RouteOperation(HttpMethod::GET, new Form(...), 'New user registration form')]
 * #[RouteOperation(HttpMethod::POST, new Validated(...))]
 * #[RouteOperation(HttpMethod::GET, OpcacheStatusHandler::class, 'OPcache runtime statistics')]
 *
 * @param StaticView|Form|Validated|class-string|array{class-string, string}|Closure $action
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
#[HopWeight(0)]
readonly class Route
{
    /** @param StaticView|Form|Validated|class-string|array{class-string, string}|Closure $action */
    public function __construct(
        public HttpMethod $HttpMethod,
        public StaticView|Form|Validated|string|array|Closure $action,
        public ?string $description,
    ) {}

    /** Short name describing the action type for manifests and diagnostics. */
    public function actionName(): string
    {
        return match (true) {
            $this->action instanceof Stringable => (string) $this->action,
            is_string($this->action)             => basename(str_replace('\\', '/', $this->action)),
            is_array($this->action)              => basename(str_replace('\\', '/', $this->action[0])) . '::' . $this->action[1],
            $this->action instanceof Closure     => 'Closure',
        };
    }
}
