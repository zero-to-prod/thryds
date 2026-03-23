<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Schema;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::sql_charsets,
    addCase: <<<TEXT
    1. Add enum case.
    2. Verify MySQL/MariaDB support for the charset.
    TEXT
)]
/**
 * Closed set of supported MySQL/MariaDB character sets. Ignored by PostgreSQL and SQLite.
 *
 * The backed string value is used directly in the DEFAULT CHARSET= clause of CREATE TABLE.
 */
enum Charset: string
{
    case utf8mb4 = 'utf8mb4';
}
