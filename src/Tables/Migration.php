<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\HasTableName;
use ZeroToProd\Thryds\Attributes\SchemaSync;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Schema\Charset;
use ZeroToProd\Thryds\Schema\Collation;
use ZeroToProd\Thryds\Schema\Engine;
use ZeroToProd\Thryds\Schema\SchemaSource;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::database_table_columns,
    addCase: <<<TEXT
    1. Add enum case with #[Column] attribute.
    2. Write a migration to ALTER TABLE migrations ADD COLUMN ...
    TEXT
)]
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
 * Managed by {@see \ZeroToProd\Thryds\Migrator}. The table records which migrations
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
