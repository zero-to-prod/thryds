<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Schema;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::sql_collations,
    addCase: <<<TEXT
    1. Add enum case.
    2. Verify MySQL/MariaDB support for the collation.
    TEXT
)]
/**
 * Closed set of supported MySQL/MariaDB collations. Ignored by PostgreSQL and SQLite.
 *
 * The backed string value is used directly in the COLLATE= clause of CREATE TABLE.
 */
enum Collation: string
{
    case utf8mb4_unicode_ci = 'utf8mb4_unicode_ci';
}
