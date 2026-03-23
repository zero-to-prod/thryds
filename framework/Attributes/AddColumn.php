<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use ZeroToProd\Framework\Schema\DdlBuilder;

/**
 * Declares that a migration adds a column to an existing table.
 *
 * The Migrator reads this attribute to auto-generate ALTER TABLE ADD COLUMN DDL (up)
 * and ALTER TABLE DROP COLUMN DDL (down) from the column definition attribute
 * on the named property. No imperative up()/down() methods are needed.
 *
 * The column definition is read from the $column property on the $table class,
 * which must carry a column definition attribute.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[MigrationAction]
readonly class AddColumn
{
    /**
     * @param class-string $table  Table class carrying table declaration and column definition attributes.
     * @param string       $column Property name on the table class that carries the column definition attribute.
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
