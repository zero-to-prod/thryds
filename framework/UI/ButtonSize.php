<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\UI;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/** Size scale for the button component (sm, md, lg). */
#[ClosedSet(
    Domain::button_sizes,
    addCase: <<<TEXT
    1. Add enum case.
    2. Add conditional class in templates/components/button.blade.php.
TEXT
)]
enum ButtonSize: string
{
    case sm = 'sm';
    case md = 'md';
    case lg = 'lg';
}
