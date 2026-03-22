<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\DdlBuilder;

/**
 * Declares that a migration creates a table defined by the referenced Table class.
 *
 * The Migrator reads this attribute to auto-generate CREATE TABLE DDL (up)
 * and DROP TABLE DDL (down) from the target class's #[Table], #[Column],
 * #[PrimaryKey], and #[Index] attributes. No imperative up()/down() methods needed.
 *
 * @example
 * #[Migration(id: '0001', description: 'Create Users Table')]
 * #[CreateTable(User::class)]
 * final readonly class CreateUsersTable {}
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[MigrationAction]
readonly class CreateTable
{
    /** @param class-string $table Table class carrying #[Table], #[Column], #[PrimaryKey], and #[Index] attributes. */
    public function __construct(
        public string $table,
    ) {}

    public function upSql(): string
    {
        return DdlBuilder::createTableSql($this->table, Connection::resolve($this->table)->driver());
    }

    public function downSql(): string
    {
        return DdlBuilder::dropTableSql($this->table, Connection::resolve($this->table)->driver());
    }
}
