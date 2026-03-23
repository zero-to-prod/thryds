<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\InsertsInto;
use ZeroToProd\Framework\Attributes\PersistColumn;
use ZeroToProd\Framework\Queries\DbCreate;
use ZeroToProd\Framework\Queries\Persist;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Tables\User;

/**
 * @method static void create(RegisterRequest $RegisterRequest)
 * @method static void createMany(RegisterRequest ...$requests)
 */
#[Infrastructure]
#[InsertsInto(
    User::class,
    columns: [User::name, User::handle, User::email, User::password]
)]
#[PersistColumn(
    User::id,
    Persist::random_id
)]
#[PersistColumn(
    User::password,
    Persist::password_hash
)]
readonly class CreateUserQuery
{
    use DbCreate;
}
