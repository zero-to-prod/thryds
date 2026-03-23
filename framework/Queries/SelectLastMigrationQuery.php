<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\SelectsFrom;
use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\Schema\SortDirection;
use ZeroToProd\Framework\Tables\Migration;

/**
 * @method static array<string, mixed>|null oneRow(?Database $Database = null)
 */
#[Infrastructure]
#[SelectsFrom(
    table: Migration::class,
    columns: [],
    where: [],
    order_by: Migration::id,
    SortDirection: SortDirection::DESC,
    limit: 1,
    offset: null,
)]
readonly class SelectLastMigrationQuery
{
    use DbRead;
}
