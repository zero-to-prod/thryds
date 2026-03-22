<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\SelectsFrom;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\Schema\SortDirection;
use ZeroToProd\Thryds\Tables\Migration;

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
