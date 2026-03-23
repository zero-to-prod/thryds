<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\InsertsInto;
use ZeroToProd\Framework\Attributes\PersistColumn;
use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\Tables\Migration;

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
