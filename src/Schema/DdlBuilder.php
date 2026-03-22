<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Schema;

use ReflectionClass;
use ReflectionProperty;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\Index;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\PrimaryKey;
use ZeroToProd\Thryds\Attributes\Table;

/**
 * Generates DDL statements from Table class attributes.
 *
 * Reads #[Table], #[Column], #[PrimaryKey], and #[Index] attributes via reflection
 * to produce CREATE TABLE and DROP TABLE SQL without hand-written DDL.
 */
#[Infrastructure]
final readonly class DdlBuilder
{
    private const string INDENT = '    ';

    private const string UNSIGNED = ' UNSIGNED';

    private const string DEFAULT = 'DEFAULT ';
    /**
     * Generates a CREATE TABLE statement from a Table class's attributes.
     *
     * @param class-string $class A class carrying #[Table], #[Column], #[PrimaryKey], and optionally #[Index] attributes.
     */
    public static function createTableSql(string $class): string
    {
        $ReflectionClass = new ReflectionClass(objectOrClass: $class);
        $Table = $ReflectionClass->getAttributes(Table::class)[0]->newInstance();
        $table_name = $Table->TableName->value;

        $php_cols = self::reflectColumns($ReflectionClass);

        $col_lines = [];

        foreach ($php_cols as $prop_name => $col) {
            $col_lines[] = self::INDENT . self::columnDdl(name: $prop_name, Column: $col);
        }

        $pk_columns = self::reflectPrimaryKey($ReflectionClass);

        if ($pk_columns !== []) {
            $col_lines[] = self::INDENT . 'PRIMARY KEY (' . implode(', ', array_map(static fn(string $c): string => '`' . $c . '`', $pk_columns)) . ')';
        }

        foreach ($ReflectionClass->getAttributes(Index::class) as $idx_attr) {
            $Index = $idx_attr->newInstance();
            $col_lines[] = self::INDENT . ($Index->unique ? 'UNIQUE ' : '') . 'KEY `' . ($Index->name !== '' ? $Index->name : 'idx_' . $table_name . '_' . implode('_', $Index->columns)) . '` (' . implode(', ', array_map(static fn(string $c): string => '`' . $c . '`', $Index->columns)) . ')';
        }

        return 'CREATE TABLE IF NOT EXISTS `' . $table_name . "` (\n"
            . implode(",\n", array: $col_lines) . "\n"
            . ') ENGINE=' . $Table->Engine->value
            . ' DEFAULT CHARSET=' . $Table->Charset->value
            . ' COLLATE=' . $Table->Collation->value;
    }

    /**
     * Generates a DROP TABLE IF EXISTS statement from a Table class's attributes.
     *
     * @param class-string $class A class carrying a #[Table] attribute.
     */
    public static function dropTableSql(string $class): string
    {
        return 'DROP TABLE IF EXISTS `' . new ReflectionClass(objectOrClass: $class)->getAttributes(Table::class)[0]->newInstance()->TableName->value . '`';
    }

    public static function columnDdl(string $name, Column $Column): string
    {
        $parts = ['`' . $name . '`', self::columnTypeSql($Column)];
        $parts[] = $Column->nullable ? 'NULL' : 'NOT NULL';

        if ($Column->auto_increment) {
            $parts[] = 'AUTO_INCREMENT';
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

        if ($Column->comment !== '') {
            $parts[] = "COMMENT '" . addslashes($Column->comment) . "'";
        }

        return implode(' ', array: $parts);
    }

    public static function columnTypeSql(Column $Column): string
    {
        return match ($Column->DataType) {
            DataType::VARCHAR    => 'VARCHAR(' . $Column->length . ')',
            DataType::CHAR       => 'CHAR(' . $Column->length . ')',
            DataType::BIGINT     => 'BIGINT' . ($Column->unsigned ? self::UNSIGNED : ''),
            DataType::INT        => 'INT' . ($Column->unsigned ? self::UNSIGNED : ''),
            DataType::SMALLINT   => 'SMALLINT' . ($Column->unsigned ? self::UNSIGNED : ''),
            DataType::TINYINT    => 'TINYINT' . ($Column->unsigned ? self::UNSIGNED : ''),
            DataType::TEXT       => 'TEXT',
            DataType::MEDIUMTEXT => 'MEDIUMTEXT',
            DataType::LONGTEXT   => 'LONGTEXT',
            DataType::DATETIME   => 'DATETIME',
            DataType::DATE       => 'DATE',
            DataType::TIME       => 'TIME',
            DataType::TIMESTAMP  => 'TIMESTAMP',
            DataType::YEAR       => 'YEAR',
            DataType::DECIMAL    => 'DECIMAL(' . $Column->precision . ',' . $Column->scale . ')',
            DataType::FLOAT      => 'FLOAT' . ($Column->unsigned ? self::UNSIGNED : ''),
            DataType::DOUBLE     => 'DOUBLE' . ($Column->unsigned ? self::UNSIGNED : ''),
            DataType::BOOLEAN    => 'BOOLEAN',
            DataType::JSON       => 'JSON',
            DataType::ENUM       => 'ENUM(' . implode(', ', array_map(static fn(string $v): string => "'" . addslashes(string: $v) . "'", $Column->values ?? [])) . ')',
            DataType::SET        => 'SET(' . implode(', ', array_map(static fn(string $v): string => "'" . addslashes(string: $v) . "'", $Column->values ?? [])) . ')',
            DataType::BINARY     => 'BINARY(' . $Column->length . ')',
            DataType::VARBINARY  => 'VARBINARY(' . $Column->length . ')',
            DataType::BLOB       => 'BLOB',
            DataType::MEDIUMBLOB => 'MEDIUMBLOB',
            DataType::LONGBLOB   => 'LONGBLOB',
        };
    }

    /**
     * Reflects #[Column] attributes from a Table class's public properties.
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
     * @return string[]
     */
    public static function reflectPrimaryKey(ReflectionClass $ReflectionClass): array
    {
        $class_pks = $ReflectionClass->getAttributes(PrimaryKey::class);

        if ($class_pks !== [] && $class_pks[0]->newInstance()->columns !== []) {
            return $class_pks[0]->newInstance()->columns;
        }

        $columns = [];

        foreach ($ReflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->getAttributes(PrimaryKey::class) !== []) {
                $columns[] = $prop->getName();
            }
        }

        return $columns;
    }
}
