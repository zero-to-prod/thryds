<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\ClosedSet;
use ZeroToProd\Thryds\Helpers\Domain;

#[ClosedSet(Domain::dev_path_groups, addCase: 'Add enum case. Then use it in a #[Group] attribute on DevPath cases.')]
/**
 * Groups for dev-only path filters.
 */
enum DevPathGroup: string
{
    case vendor = 'vendor';
    case excluded_dir = 'excluded_dir';
}
