<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\UI;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/** Visual intent variants for the alert component. */
#[ClosedSet(
    Domain::alert_variants,
    addCase: <<<TEXT
    1. Add enum case.
    2. Add conditional class in templates/components/alert.blade.php.
TEXT
)]
enum AlertVariant: string
{
    case info = 'info';
    case danger = 'danger';
    case success = 'success';
}
