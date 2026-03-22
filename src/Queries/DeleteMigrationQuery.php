<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\DeletesFrom;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\Tables\Migration;

/**
 * @method static int byColumn(string $column, mixed $value, ?Database $Database = null)
 */
#[Infrastructure]
#[DeletesFrom(
    table: Migration::class,
    where: [],
)]
readonly class DeleteMigrationQuery
{
    use DbDelete;
}
