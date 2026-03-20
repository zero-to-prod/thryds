<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\UI;

use ZeroToProd\Thryds\Attributes\ClosedSet;

/** Prop names accepted by Blade components via #[Prop]. */
#[ClosedSet(
    Domain::component_props,
    addCase: <<<TEXT
    1. Add enum case.
    2. Use it in a #[Prop] attribute on the relevant Component case.
    TEXT
)]
enum Props: string
{
    case variant = 'variant';
    case size = 'size';
    case type = 'type';
    case label = 'label';
    case error = 'error';
}
