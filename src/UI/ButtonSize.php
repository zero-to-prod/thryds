<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\UI;

use ZeroToProd\Thryds\Attributes\ClosedSet;

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
