<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Schema;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::database_drivers,
    addCase: <<<TEXT
    1. Add enum case.
    2. Handle every match() arm on the enum (DSN, quoting, type mapping, etc.).
    3. Add driver to test matrix.
    TEXT
)]
enum Driver: string
{
    case mysql  = 'mysql';
    case pgsql  = 'pgsql';
    case sqlite = 'sqlite';

    public function quote(string $identifier): string
    {
        return match ($this) {
            self::mysql  => '`' . $identifier . '`',
            self::pgsql, self::sqlite => '"' . $identifier . '"',
        };
    }

    public function dsn(string $host, int $port, string $database): string
    {
        return match ($this) {
            self::mysql  => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            self::pgsql  => sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database),
            self::sqlite => 'sqlite:' . $database,
        };
    }

    public function timezoneCommand(string $timezone): ?string
    {
        return match ($this) {
            self::mysql  => "SET time_zone = '$timezone'",
            self::pgsql  => "SET timezone TO '$timezone'",
            self::sqlite => null,
        };
    }

    /** @return list<string> */
    public function reconnectPatterns(): array
    {
        return match ($this) {
            self::mysql  => ['server has gone away', 'Lost connection'],
            self::pgsql  => ['terminating connection', 'server closed the connection unexpectedly', 'SSL connection has been closed unexpectedly'],
            self::sqlite => [],
        };
    }

    public function autoIncrementSql(): string
    {
        return match ($this) {
            self::mysql  => 'AUTO_INCREMENT',
            self::pgsql  => 'GENERATED ALWAYS AS IDENTITY',
            self::sqlite => 'AUTOINCREMENT',
        };
    }

    public function supportsUnsigned(): bool
    {
        return $this === self::mysql;
    }

    public function typeSql(Column $Column): string
    {
        return match ($this) {
            self::mysql  => self::mysqlTypeSql($Column),
            self::pgsql  => self::pgsqlTypeSql($Column),
            self::sqlite => self::sqliteTypeSql($Column),
        };
    }

    public function tableOptions(?Engine $Engine, ?Charset $Charset, ?Collation $Collation): string
    {
        return match ($this) {
            self::mysql => ($Engine !== null ? ' ENGINE=' . $Engine->value : '')
                . ($Charset !== null ? ' DEFAULT CHARSET=' . $Charset->value : '')
                . ($Collation !== null ? ' COLLATE=' . $Collation->value : ''),
            self::pgsql, self::sqlite => '',
        };
    }

    public function transactionalDdl(): bool
    {
        return $this !== self::mysql;
    }

    /** @param list<string> $values */
    public function enumConstraint(string $column, array $values): ?string
    {
        return match ($this) {
            self::pgsql => 'CHECK (' . $this->quote(identifier: $column) . ' IN ('
                . implode(', ', array_map(static fn(string $v): string => "'" . addslashes(string: $v) . "'", $values))
                . '))',
            self::mysql, self::sqlite => null,
        };
    }

    public function defaultPort(): int
    {
        return match ($this) {
            self::mysql  => 3306,
            self::pgsql  => 5432,
            self::sqlite => 0,
        };
    }

    private static function mysqlTypeSql(Column $Column): string
    {
        $unsigned = $Column->unsigned ? ' UNSIGNED' : '';

        return match ($Column->DataType) {
            DataType::VARCHAR    => 'VARCHAR(' . $Column->length . ')',
            DataType::CHAR       => 'CHAR(' . $Column->length . ')',
            DataType::BIGINT     => 'BIGINT' . $unsigned,
            DataType::INT        => 'INT' . $unsigned,
            DataType::SMALLINT   => 'SMALLINT' . $unsigned,
            DataType::TINYINT    => 'TINYINT' . $unsigned,
            DataType::TEXT       => 'TEXT',
            DataType::MEDIUMTEXT => 'MEDIUMTEXT',
            DataType::LONGTEXT   => 'LONGTEXT',
            DataType::DATETIME   => 'DATETIME',
            DataType::DATE       => 'DATE',
            DataType::TIME       => 'TIME',
            DataType::TIMESTAMP  => 'TIMESTAMP',
            DataType::YEAR       => 'YEAR',
            DataType::DECIMAL    => 'DECIMAL(' . $Column->precision . ',' . $Column->scale . ')',
            DataType::FLOAT      => 'FLOAT' . $unsigned,
            DataType::DOUBLE     => 'DOUBLE' . $unsigned,
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

    private static function pgsqlTypeSql(Column $Column): string
    {
        return match ($Column->DataType) {
            DataType::VARCHAR    => 'VARCHAR(' . $Column->length . ')',
            DataType::CHAR       => 'CHAR(' . $Column->length . ')',
            DataType::BIGINT     => $Column->auto_increment ? 'BIGSERIAL' : 'BIGINT',
            DataType::INT        => $Column->auto_increment ? 'SERIAL' : 'INTEGER',
            DataType::SMALLINT   => $Column->auto_increment ? 'SMALLSERIAL' : 'SMALLINT',
            DataType::TINYINT    => 'SMALLINT',
            DataType::TEXT, DataType::MEDIUMTEXT, DataType::LONGTEXT => 'TEXT',
            DataType::DATETIME   => 'TIMESTAMP',
            DataType::DATE       => 'DATE',
            DataType::TIME       => 'TIME',
            DataType::TIMESTAMP  => 'TIMESTAMP',
            DataType::YEAR       => 'SMALLINT',
            DataType::DECIMAL    => 'NUMERIC(' . $Column->precision . ',' . $Column->scale . ')',
            DataType::FLOAT      => 'REAL',
            DataType::DOUBLE     => 'DOUBLE PRECISION',
            DataType::BOOLEAN    => 'BOOLEAN',
            DataType::JSON       => 'JSONB',
            DataType::ENUM, DataType::SET => 'TEXT',
            DataType::BINARY, DataType::VARBINARY => 'BYTEA',
            DataType::BLOB, DataType::MEDIUMBLOB, DataType::LONGBLOB => 'BYTEA',
        };
    }

    private static function sqliteTypeSql(Column $Column): string
    {
        return match ($Column->DataType) {
            DataType::VARCHAR, DataType::CHAR, DataType::TEXT, DataType::MEDIUMTEXT, DataType::LONGTEXT => 'TEXT',
            DataType::BIGINT, DataType::INT, DataType::SMALLINT, DataType::TINYINT, DataType::YEAR => 'INTEGER',
            DataType::DATETIME, DataType::DATE, DataType::TIME, DataType::TIMESTAMP => 'TEXT',
            DataType::DECIMAL, DataType::FLOAT, DataType::DOUBLE => 'REAL',
            DataType::BOOLEAN => 'INTEGER',
            DataType::JSON => 'TEXT',
            DataType::ENUM, DataType::SET => 'TEXT',
            DataType::BINARY, DataType::VARBINARY, DataType::BLOB, DataType::MEDIUMBLOB, DataType::LONGBLOB => 'BLOB',
        };
    }
}
