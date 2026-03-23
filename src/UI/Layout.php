<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\UI;

use ZeroToProd\Framework\Attributes\ClosedSet;

/** Available layout templates for views. */
#[ClosedSet(
    Domain::layouts,
    addCase: <<<TEXT
    1. Add enum case.
    2. Create templates/{value}.blade.php layout template.
    TEXT
)]
enum Layout: string
{
    case base = 'base';
}
