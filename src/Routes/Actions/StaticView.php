<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes\Actions;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Blade\View;

/** Render a Blade view with no controller. */
#[Infrastructure]
final readonly class StaticView
{
    public function __construct(
        public View $View,
    ) {}
}
