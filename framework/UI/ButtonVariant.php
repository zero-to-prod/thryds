<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\UI;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/** Visual intent variants for the button component. */
#[ClosedSet(
    Domain::button_variants,
    addCase: <<<TEXT
    1. Add enum case.
    2. Add conditional class in templates/components/button.blade.php.
    TEXT
)]
enum ButtonVariant: string
{
    case primary = 'primary';
    case danger = 'danger';
    case secondary = 'secondary';
}
