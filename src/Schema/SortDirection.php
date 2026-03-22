<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Schema;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::sort_directions,
    addCase: 'Add enum case. Verify MySQL support for the sort direction.'
)]
/**
 * Closed set of SQL sort directions.
 *
 * The backed string value is used directly in the ORDER BY clause.
 */
enum SortDirection: string
{
    case ASC  = 'ASC';
    case DESC = 'DESC';
}
