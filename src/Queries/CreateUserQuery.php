<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\InsertsInto;
use ZeroToProd\Thryds\Attributes\PersistColumn;
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
