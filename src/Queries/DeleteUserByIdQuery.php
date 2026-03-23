<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Framework\Attributes\DeletesFrom;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Queries\DbDelete;
use ZeroToProd\Thryds\Tables\User;

/**
 * @method static int delete(string $id)
 */
#[Infrastructure]
#[DeletesFrom(
    User::class,
    where: [User::id]
)]
readonly class DeleteUserByIdQuery
{
    use DbDelete;
}
