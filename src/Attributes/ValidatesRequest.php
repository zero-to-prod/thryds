<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares that a controller validates the given request class on POST.
 *
 * Infrastructure intercepts the POST handler, creates the request from
 * the parsed body, validates it, and re-renders the view with errors
 * using the declared ViewModel, or delegates to the controller with
 * the validated request.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(1)]
readonly class ValidatesRequest
{
    /**
     * @param class-string $request
     * @param class-string $view_model ViewModel that receives validation errors on failure.
     */
    public function __construct(
        public string $request,
        public string $view_model,
    ) {}
}
