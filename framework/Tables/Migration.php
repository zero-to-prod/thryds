<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Tables;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\Connection;
use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\HasTableName;
use ZeroToProd\Framework\Attributes\SchemaSync;
use ZeroToProd\Framework\Attributes\Table;
use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\Schema\Charset;
use ZeroToProd\Framework\Schema\Collation;
use ZeroToProd\Framework\Schema\Engine;
use ZeroToProd\Framework\Schema\SchemaSource;
use ZeroToProd\Thryds\Tables\TableName;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::database_table_columns,
    addCase: <<<TEXT
    1. Add enum case with #[Column] attribute.
    2. Write a migration to ALTER TABLE migrations ADD COLUMN ...
    TEXT
)]
#[Connection(database: Database::class)]
#[SchemaSync(SchemaSource::attributes)]
#[Table(
    TableName: TableName::migrations,
    Engine: Engine::InnoDB,
    Charset: Charset::utf8mb4,
    Collation: Collation::utf8mb4_unicode_ci
)]
/**
 * Schema definition for the migrations tracking table.
 *
 * Managed by {@see \ZeroToProd\Framework\Migrator}. The table records which migrations
 * have been applied, when, and their checksums for tamper detection.
 *
 * Use the enum case values as column name references in queries:
 * e.g. Migration::id === 'id'
 */
readonly class Migration
{
    use DataModel;
    use HasTableName;
    use MigrationColumns;
}
