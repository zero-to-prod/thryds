<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\SelectsFrom;
use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\Schema\SortDirection;
use ZeroToProd\Framework\Tables\Migration;

/**
 * @method static array<int, array<string, mixed>> allRows(?Database $Database = null)
 */
#[Infrastructure]
#[SelectsFrom(
    table: Migration::class,
    columns: [],
    where: [],
    order_by: Migration::id,
    SortDirection: SortDirection::ASC,
    limit: null,
    offset: null,
)]
readonly class SelectMigrationsQuery
{
    use DbRead;
}
