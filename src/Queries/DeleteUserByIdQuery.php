<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\DeletesFrom;
use ZeroToProd\Thryds\Attributes\Infrastructure;
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
