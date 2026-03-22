<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Schema;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::sql_storage_engines,
    addCase: <<<TEXT
    1. Add enum case.
    2. Verify MySQL/MariaDB support for the engine.
    TEXT
)]
/**
 * Closed set of supported MySQL/MariaDB storage engines. Ignored by PostgreSQL and SQLite.
 *
 * The backed string value is used directly in the ENGINE= clause of CREATE TABLE.
 */
enum Engine: string
{
    case InnoDB = 'InnoDB';
    case MyISAM = 'MyISAM';
    case MEMORY = 'MEMORY';
    case ARCHIVE = 'ARCHIVE';
    case CSV     = 'CSV';
}
