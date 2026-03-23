<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use ZeroToProd\Framework\Attributes\KeyRegistry;
use ZeroToProd\Framework\Attributes\KeySource;

/**
 * Shared SQL fragment constants for attribute-driven query traits.
 */
#[KeyRegistry(
    KeySource::sql_fragments,
    superglobals: [],
    addKey: '1. Add constant. 2. Reference via Sql::CONSTANT_NAME in query traits.'
)]
final readonly class Sql
{
    public const string SELECT = 'SELECT ';

    public const string FROM = ' FROM ';

    public const string INSERT_INTO = 'INSERT INTO ';

    public const string UPDATE = 'UPDATE ';

    public const string SET = ' SET ';

    public const string DELETE_FROM = 'DELETE FROM ';

    public const string WHERE = ' WHERE ';

    public const string ORDER_BY = ' ORDER BY ';

    public const string CONJUNCTION = ' AND ';
}
