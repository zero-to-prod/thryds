<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\SelectsFrom;
use ZeroToProd\Thryds\Schema\SortDirection;
use ZeroToProd\Thryds\Tables\User;

/**
 * @method static array<string, mixed>|null one(string $id)
 * @method static array<int, array<string, mixed>> all()
 */
#[Infrastructure]
#[SelectsFrom(
    User::class,
    columns: [User::id, User::name, User::handle, User::email, User::email_verified_at, User::created_at, User::updated_at],
    where: [User::id],
    order_by: '',
    SortDirection: SortDirection::ASC,
)]
readonly class FindUserByIdQuery
{
    use DbRead;
}
