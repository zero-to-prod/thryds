<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::database_table_names,
    addCase: <<<TEXT
    1. Add enum case. 
    2. Add corresponding table entry to thryds.yaml. 
    3. Run ./run sync:manifest.
    TEXT
)]
enum TableName: string
{
    case migrations = 'migrations';
    case users = 'users';
}
