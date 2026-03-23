<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use ZeroToProd\Framework\Attributes\DeletesFrom;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\Tables\Migration;

/**
 * @method static int delete(string $id, ?Database $Database = null)
 */
#[Infrastructure]
#[DeletesFrom(
    table: Migration::class,
    where: [Migration::id],
)]
readonly class DeleteMigrationQuery
{
    use DbDelete;
}
