<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\HasTableName;
use ZeroToProd\Thryds\Attributes\PrimaryKey;
use ZeroToProd\Thryds\Attributes\SchemaSync;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Schema\Charset;
use ZeroToProd\Thryds\Schema\Collation;
use ZeroToProd\Thryds\Schema\DataType;
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
class Migration
{
    use DataModel;
    use HasTableName;

    /** @see $id */
    public const string id = 'id';
    #[Column(
        DataType: DataType::VARCHAR,
        length: 20,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: null,
        values: null,
        comment: 'Migration id, matching the four-digit prefix of the migration filename (e.g. 0001).',
    )]
    #[PrimaryKey(columns: [])]
    public string $id;

    /** @see $description */
    public const string description = 'description';
    #[Column(
        DataType: DataType::VARCHAR,
        length: 255,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: null,
        values: null,
        comment: 'Human-readable description from the #[Migration] attribute on the migration class.',
    )]
    public string $description;

    /** @see $checksum */
    public const string checksum = 'checksum';
    #[Column(
        DataType: DataType::VARCHAR,
        length: 64,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: null,
        values: null,
        comment: 'SHA-256 hash of the migration file contents at the time it was applied.',
    )]
    public string $checksum;

    /** @see $applied_at */
    public const string applied_at = 'applied_at';
    #[Column(
        DataType: DataType::DATETIME,
        length: null,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: Column::CURRENT_TIMESTAMP,
        values: null,
        comment: 'Timestamp when the migration was applied.',
    )]
    public string $applied_at;
}
