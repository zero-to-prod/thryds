<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use Random\RandomException;
use ZeroToProd\Thryds\Tables\User;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string ', :' (used 4x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
/**
 * @method static bool create(string $name, string $handle, string $email, string $password)
 */
readonly class CreateUserQuery
{
    use DbCreate;

    /**
     * Check if a connection already exists where the requester already
     * initiated a request, or an accepted connection exists in either direction.
     *
     * @throws RandomException
     */
    public function handle(string $name, string $handle, string $email, string $password): void
    {
        db()->execute(
            'INSERT INTO ' . User::tableName() . ' (' . User::id . ', ' . User::name . ', ' . User::handle . ', ' . User::email . ', ' . User::password . ') VALUES (:' . User::id . ', :' . User::name . ', :' . User::handle . ', :' . User::email . ', :' . User::password . ')',
            [
                ':' . User::id => substr(bin2hex(random_bytes(13)), 0, 26),
                ':' . User::name => $name,
                ':' . User::handle => $handle,
                ':' . User::email => $email,
                ':' . User::password => password_hash($password, PASSWORD_BCRYPT),
            ],
        );
    }
}
