<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares arbitrary SQL for a migration's forward and rollback operations.
 *
 * Use when no structured action attribute (#[CreateTable], #[AddColumn], #[DropColumn])
 * fits — e.g. DML, data backfills, index changes, or multi-step DDL.
 *
 * Both $up and $down are required to enforce reversibility at the attribute level.
 *
 * @example
 * #[Migration(id: '0003', description: 'Seed default roles')]
 * #[RawSql(
 *     up: "INSERT INTO roles (name) VALUES ('admin'), ('user')",
 *     down: "DELETE FROM roles WHERE name IN ('admin', 'user')",
 * )]
 * final readonly class SeedDefaultRoles {}
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class RawSql implements MigrationAction
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
