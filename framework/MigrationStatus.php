<?php

declare(strict_types=1);

namespace ZeroToProd\Framework;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::migration_statuses,
    addCase: 'Add enum case. Then handle in Migrator::status().',
)]
enum MigrationStatus: string
{
    case pending  = 'pending';
    case applied  = 'applied';
    case modified = 'modified';
}
