<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

#[ClosedSet(Domain::alert_variants, addCase: '1. Add enum case. 2. Add conditional class in templates/components/alert.blade.php.')]
enum AlertVariant: string
{
    case info = 'info';
    case danger = 'danger';
    case success = 'success';
}
