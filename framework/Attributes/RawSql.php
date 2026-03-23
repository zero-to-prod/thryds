<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Declares arbitrary SQL for a migration's forward and rollback operations.
 *
 * Use when no structured migration action attribute
 * fits — e.g. DML, data backfills, index changes, or multi-step DDL.
 *
 * Both $up and $down are required to enforce reversibility at the attribute level.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[MigrationAction]
readonly class RawSql
{
    public function __construct(
        public string $up,
        public string $down,
    ) {}

    public function upSql(): string
    {
        return $this->up;
    }

    public function downSql(): string
    {
        return $this->down;
    }
}
