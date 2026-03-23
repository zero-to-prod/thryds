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
 * - a static view strategy object                       — render a view
 * - a form strategy object with a view and controller   — form with validation
 * - a validated strategy object with controller/request  — validate then delegate
 * - a class-string for an invokable controller          — invokable dispatch
 * - an array callable (class-string + method name)      — array callable
 * - a first-class callable                              — first-class callable
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
