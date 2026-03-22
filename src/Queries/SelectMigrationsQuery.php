<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\SelectsFrom;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\Tables\Migration;

/**
 * @method static array<int, array<string, mixed>> allRows(?Database $Database = null)
 * @method static array<string, mixed>|null lastRow(?Database $Database = null)
 */
#[Infrastructure]
#[SelectsFrom(
    table: Migration::class,
    columns: [],
    where: [],
    order_by: Migration::id,
)]
readonly class SelectMigrationsQuery
{
    use DbRead;
}
