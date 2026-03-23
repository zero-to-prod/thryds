<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes\Actions;

use Stringable;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Blade\View;

/** Render a Blade view with no controller. */
#[Infrastructure]
final readonly class StaticView implements Stringable
{
    public function __construct(
        public View $View,
    ) {}

    public function __toString(): string
    {
        return 'StaticView';
    }
}
