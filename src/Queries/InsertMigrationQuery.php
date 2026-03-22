<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\InsertsInto;
use ZeroToProd\Thryds\Attributes\PersistColumn;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\Tables\Migration;

/**
 * @method static void create(object $request, ?Database $Database = null)
 */
#[Infrastructure]
#[InsertsInto(
    Migration::class,
    columns: [Migration::id, Migration::description, Migration::checksum],
)]
#[PersistColumn(
    Migration::applied_at,
    Persist::now,
)]
readonly class InsertMigrationQuery
{
    use DbCreate;
}
