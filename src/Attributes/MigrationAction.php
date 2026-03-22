<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

/**
 * Contract for migration attributes that declare DDL operations.
 *
 * Implemented by #[CreateTable], #[AddColumn], and #[DropColumn].
 * The Migrator dispatches generically on any attribute implementing
 * this interface — adding a new DDL attribute type requires no changes
 * to the Migrator itself.
 */
#[Infrastructure]
interface MigrationAction
{
    public function upSql(): string;

    public function downSql(): string;
}
