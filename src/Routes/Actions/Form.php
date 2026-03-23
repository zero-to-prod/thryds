<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes\Actions;

use Stringable;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Blade\View;

/** Render a form view with an empty ViewModel. */
#[Infrastructure]
final readonly class Form implements Stringable
{
    /**
     * @param class-string $controller
     * @param class-string $request
     * @param class-string $view_model
     */
    public function __construct(
        public View $View,
        public string $controller,
        public string $request,
        public string $view_model,
    ) {}

    public function __toString(): string
    {
        return 'Form';
    }
}
