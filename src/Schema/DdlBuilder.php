<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Schema;

use BackedEnum;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;
use RuntimeException;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\ForeignKey;
use ZeroToProd\Thryds\Attributes\Index;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\OnDelete;
use ZeroToProd\Thryds\Attributes\OnUpdate;
use ZeroToProd\Thryds\Attributes\PrimaryKey;
use ZeroToProd\Thryds\Attributes\Table;

/**
 * Generates DDL statements from Table class attributes.
 *
 * Reads table, column, primary key, index, foreign key, on-delete,
 * and on-update attributes via reflection to produce CREATE TABLE and DROP TABLE
 * SQL without hand-written DDL.
 */
#[Infrastructure]
final readonly class DdlBuilder
{
    private const string ALTER_TABLE = 'ALTER TABLE ';

    private const string INDENT = '    ';

    private const string DEFAULT = 'DEFAULT ';
    /**
     * Generates a CREATE TABLE statement from a Table class's attributes.
     *
     * @param class-string $class A class carrying table, column, primary key, and optionally index definition attributes.
     */
    public static function createTableSql(string $class, Driver $Driver): string
    {
        $ReflectionClass = new ReflectionClass(objectOrClass: $class);
        $Table = $ReflectionClass->getAttributes(Table::class)[0]->newInstance();
        $table_name = $Table->TableName->value;

        $php_cols = self::reflectColumns($ReflectionClass);

        $col_lines = [];

        foreach ($php_cols as $prop_name => $col) {
            $col_lines[] = self::INDENT . self::columnDdl(name: $prop_name, Column: $col, Driver: $Driver);
        }

        $pk_columns = self::reflectPrimaryKey($ReflectionClass);

        if ($pk_columns !== []) {
            $col_lines[] = self::INDENT . 'PRIMARY KEY (' . implode(', ', array_map(static fn(string $c): string => $Driver->quote(identifier: $c), $pk_columns)) . ')';
        }

        foreach ($ReflectionClass->getAttributes(Index::class) as $idx_attr) {
            $Index = $idx_attr->newInstance();
            $col_lines[] = self::INDENT . ($Index->unique ? 'UNIQUE ' : '') . 'KEY ' . $Driver->quote($Index->name !== '' ? $Index->name : 'idx_' . $table_name . '_' . implode('_', $Index->columns)) . ' (' . implode(', ', array_map(static fn(string $c): string => $Driver->quote(identifier: $c), $Index->columns)) . ')';
        }

        foreach (self::reflectForeignKeys($ReflectionClass, $table_name, $Driver) as $fk_clause) {
            $col_lines[] = self::INDENT . $fk_clause;
        }

        foreach ($php_cols as $prop_name => $col) {
            if ($col->values !== null && $col->values !== []) {
                $check = $Driver->enumConstraint(column: $prop_name, values: array_values($col->values));
                if ($check !== null) {
                    $col_lines[] = self::INDENT . $check;
                }
            }
        }

        return 'CREATE TABLE IF NOT EXISTS ' . $Driver->quote(identifier: $table_name) . " (\n"
            . implode(",\n", array: $col_lines) . "\n"
            . ')' . $Driver->tableOptions($Table->Engine, $Table->Charset, $Table->Collation);
    }

    /**
     * Generates a DROP TABLE IF EXISTS statement from a Table class's attributes.
     *
     * @param class-string $class A class carrying a table definition attribute.
     */
    public static function dropTableSql(string $class, Driver $Driver): string
    {
        return 'DROP TABLE IF EXISTS ' . $Driver->quote(new ReflectionClass(objectOrClass: $class)->getAttributes(Table::class)[0]->newInstance()->TableName->value);
    }

    public static function columnDdl(string $name, Column $Column, Driver $Driver): string
    {
        $parts = [$Driver->quote(identifier: $name), $Driver->typeSql($Column)];
        $parts[] = $Column->nullable ? 'NULL' : 'NOT NULL';

        if ($Column->auto_increment && $Driver === Driver::mysql) {
            $parts[] = $Driver->autoIncrementSql();
        }

        if ($Column->default !== null) {
            if ($Column->default === Column::CURRENT_TIMESTAMP) {
                $parts[] = self::DEFAULT . 'CURRENT_TIMESTAMP';
            } elseif (is_bool($Column->default)) {
                $parts[] = self::DEFAULT . ($Column->default ? '1' : '0');
            } elseif (is_int($Column->default) || is_float($Column->default)) {
                $parts[] = self::DEFAULT . $Column->default;
            } else {
                $parts[] = self::DEFAULT . "'" . addslashes((string) $Column->default) . "'";
            }
        }

        if ($Column->comment !== '' && $Driver === Driver::mysql) {
            $parts[] = "COMMENT '" . addslashes($Column->comment) . "'";
        }

        return implode(' ', array: $parts);
    }

    /**
     * Reflects column definition attributes from a Table class's public properties.
     *
     * @param ReflectionClass<object> $ReflectionClass
     * @return array<string, Column>
     */
    public static function reflectColumns(ReflectionClass $ReflectionClass): array
    {
        $cols = [];

        foreach ($ReflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $col_attrs = $prop->getAttributes(Column::class);

            if ($col_attrs === []) {
                continue;
            }

            $cols[$prop->getName()] = $col_attrs[0]->newInstance();
        }

        return $cols;
    }

    /**
     * Reflects the primary key columns from a Table class.
     *
     * @param ReflectionClass<object> $ReflectionClass
     * @return list<string>
     */
    public static function reflectPrimaryKey(ReflectionClass $ReflectionClass): array
    {
        $class_pks = $ReflectionClass->getAttributes(PrimaryKey::class);

        if ($class_pks !== [] && $class_pks[0]->newInstance()->columns !== []) {
            return array_values($class_pks[0]->newInstance()->columns);
        }

        $columns = [];

        foreach ($ReflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->getAttributes(PrimaryKey::class) !== []) {
                $columns[] = $prop->getName();
            }
        }

        return $columns;
    }

    /**
     * Generates an ALTER TABLE ADD COLUMN statement from a Table class property's column definition attribute.
     *
     * @param class-string $class  A class carrying table and column definition attributes.
     * @param string       $column Property name on the class that carries the column definition attribute.
     */
    public static function addColumnSql(string $class, string $column, Driver $Driver): string
    {
        $ReflectionClass = new ReflectionClass(objectOrClass: $class);

        return self::ALTER_TABLE . $Driver->quote($ReflectionClass->getAttributes(Table::class)[0]->newInstance()->TableName->value) . ' ADD COLUMN ' . self::columnDdl(name: $column, Column: self::reflectColumn($ReflectionClass, $column), Driver: $Driver);
    }

    /**
     * Generates an ALTER TABLE DROP COLUMN statement from a Table class property name.
     *
     * @param class-string $class  A class carrying a table definition attribute.
     * @param string       $column Column name to drop.
     */
    public static function dropColumnSql(string $class, string $column, Driver $Driver): string
    {
        if ($Driver === Driver::sqlite) {
            throw new RuntimeException('SQLite does not support DROP COLUMN. Recreate the table instead.');
        }

        return self::ALTER_TABLE . $Driver->quote(new ReflectionClass(objectOrClass: $class)->getAttributes(Table::class)[0]->newInstance()->TableName->value) . ' DROP COLUMN ' . $Driver->quote(identifier: $column);
    }

    /**
     * Reflects a single column definition attribute from a named property on a Table class.
     *
     * @param ReflectionClass<object> $ReflectionClass
     */
    public static function reflectColumn(ReflectionClass $ReflectionClass, string $column): Column
    {
        $col_attrs = $ReflectionClass->getProperty(name: $column)->getAttributes(Column::class);

        if ($col_attrs === []) {
            throw new RuntimeException(
                "Property '$column' on {$ReflectionClass->getName()} does not have a #[Column] attribute."
            );
        }

        return $col_attrs[0]->newInstance();
    }

    /**
     * Reflects foreign key, on-delete, and on-update attributes from a Table class's
     * constants and returns CONSTRAINT ... FOREIGN KEY DDL clauses.
     *
     * @param ReflectionClass<object> $ReflectionClass
     * @return list<string>
     */
    public static function reflectForeignKeys(ReflectionClass $ReflectionClass, string $table_name, Driver $Driver): array
    {
        $clauses = [];

        foreach ($ReflectionClass->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC) as $const) {
            $fk_attrs = $const->getAttributes(ForeignKey::class);

            if ($fk_attrs === []) {
                continue;
            }

            /** @var ForeignKey $ForeignKey */
            $ForeignKey = $fk_attrs[0]->newInstance();
            $raw = $const->getValue();
            $column = $raw instanceof BackedEnum ? (string) $raw->value : (is_string(value: $raw) ? $raw : '');
            $target_table = new ReflectionClass($ForeignKey->BackedEnum)
                ->getAttributes(Table::class)[0]
                ->newInstance()
                ->TableName->value;
            $target_column = (string) $ForeignKey->BackedEnum->value;

            $on_delete_attrs = $const->getAttributes(OnDelete::class);
            $on_update_attrs = $const->getAttributes(OnUpdate::class);

            $clauses[] = 'CONSTRAINT ' . $Driver->quote($ForeignKey->name !== '' ? $ForeignKey->name : 'fk_' . $table_name . '_' . $column . '_' . $target_table)
                . ' FOREIGN KEY (' . $Driver->quote(identifier: $column) . ')'
                . ' REFERENCES ' . $Driver->quote(identifier: $target_table) . ' (' . $Driver->quote(identifier: $target_column) . ')'
                . ' ON DELETE ' . ($on_delete_attrs !== [] ? $on_delete_attrs[0]->newInstance()->ReferentialAction : ReferentialAction::RESTRICT)->value
                . ' ON UPDATE ' . ($on_update_attrs !== [] ? $on_update_attrs[0]->newInstance()->ReferentialAction : ReferentialAction::RESTRICT)->value;
        }

        return $clauses;
    }

}
