<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;

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
    public const string CONJUNCTION = ' AND ';
}
