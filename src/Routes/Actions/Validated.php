<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes\Actions;

use ZeroToProd\Thryds\Attributes\Infrastructure;

/** Validate the request body; re-render on error, delegate on success. */
#[Infrastructure]
final readonly class Validated
{
    /**
     * @param class-string $controller
     * @param class-string $request
     * @param class-string $view_model
     */
    public function __construct(
        public string $controller,
        public string $request,
        public string $view_model,
    ) {}
}
