<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\DdlBuilder;

/**
 * Declares that a migration adds a column to an existing table.
 *
 * The Migrator reads this attribute to auto-generate ALTER TABLE ADD COLUMN DDL (up)
 * and ALTER TABLE DROP COLUMN DDL (down) from the target class's #[Column] attribute
 * on the named property. No imperative up()/down() methods are needed.
 *
 * The column definition is read from the $column property on the $table class,
 * which must carry a #[Column] attribute.
 *
 * @example
 * #[Migration(id: '0002', description: 'Add bio column to users')]
 * #[AddColumn(User::class, column: User::bio)]
 * final readonly class AddBioToUsers {}
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[MigrationAction]
readonly class AddColumn
{
    /**
     * @param class-string $table  Table class carrying #[Table] and #[Column] attributes.
     * @param string       $column Property name on the table class that carries the #[Column] attribute.
     */
    public function __construct(
        public string $table,
        public string $column,
    ) {}

    public function upSql(): string
    {
        return DdlBuilder::addColumnSql($this->table, $this->column, Connection::resolve($this->table)->driver());
    }

    public function downSql(): string
    {
        return DdlBuilder::dropColumnSql($this->table, $this->column, Connection::resolve($this->table)->driver());
    }
}
