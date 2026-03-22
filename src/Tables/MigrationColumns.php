<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\Describe;
use ZeroToProd\Thryds\Attributes\PrimaryKey;
use ZeroToProd\Thryds\Schema\DataType;

trait MigrationColumns
{
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
    #[Describe(['nullable' => true])]
    public readonly ?string $id;

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
    #[Describe(['nullable' => true])]
    public readonly ?string $description;

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
    #[Describe(['nullable' => true])]
    public readonly ?string $checksum;

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
    #[Describe(['nullable' => true])]
    public readonly ?string $applied_at;
}
