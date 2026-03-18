<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\UI;

use ZeroToProd\Thryds\Attributes\ClosedSet;

#[ClosedSet(Domain::button_variants, addCase: '1. Add enum case. 2. Add conditional class in templates/components/button.blade.php.')]
enum ButtonVariant: string
{
    case primary = 'primary';
    case danger = 'danger';
    case secondary = 'secondary';
}
