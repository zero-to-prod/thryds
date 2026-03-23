<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes\Actions;

use Stringable;
use ZeroToProd\Framework\Attributes\Infrastructure;

/** Validate the request body; re-render on error, delegate on success. */
#[Infrastructure]
final readonly class Validated implements Stringable
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

    public function __toString(): string
    {
        return 'Validated';
    }
}
