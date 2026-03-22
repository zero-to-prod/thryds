<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\UpdatesIn;
use ZeroToProd\Thryds\Tables\User;

/**
 * @method static int update(object $request, string $id)
 */
#[Infrastructure]
#[UpdatesIn(
    User::class,
    columns: [User::name, User::handle, User::email],
    where: [User::id]
)]
readonly class UpdateUserQuery
{
    use DbUpdate;
}
