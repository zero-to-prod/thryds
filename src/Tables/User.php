<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\HasTableName;
use ZeroToProd\Thryds\Attributes\PrimaryKey;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Schema\Charset;
use ZeroToProd\Thryds\Schema\Collation;
use ZeroToProd\Thryds\Schema\DataType;
use ZeroToProd\Thryds\Schema\Engine;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::database_table_columns,
    addCase: <<<TEXT
    1. Add enum case with #[Column] attribute.
    2. Write a migration to ALTER TABLE users ADD COLUMN ...
    TEXT
)]
#[Table(
    TableName: TableName::users,
    Engine: Engine::InnoDB,
    Charset: Charset::utf8mb4,
    Collation: Collation::utf8mb4_unicode_ci
)]
/**
 * Schema definition for the users table.
 *
 * Use the constant values as column name references in queries:
 * e.g. User::id === 'id'
 */
class User
{
    use DataModel;
    use HasTableName;

    /** @see $id */
    public const string id = 'id';
    #[Column(
        DataType: DataType::CHAR,
        length: 26,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: null,
        values: null,
        comment: 'Primary key',
    )]
    #[PrimaryKey(columns: [])]
    public string $id;

    /** @see $name */
    public const string name = 'name';
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
        comment: 'Display name',
    )]
    public string $name;

    /** @see $handle */
    public const string handle = 'handle';
    #[Column(
        DataType: DataType::VARCHAR,
        length: 30,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: null,
        values: null,
        comment: 'Unique public username',
    )]
    public string $handle;

    /** @see $email */
    public const string email = 'email';
    #[Column(
        DataType: DataType::VARCHAR,
        length: 255,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: true,
        auto_increment: false,
        default: null,
        values: null,
        comment: 'Contact email address',
    )]
    public ?string $email;

    /** @see $email_verified_at */
    public const string email_verified_at = 'email_verified_at';
    #[Column(
        DataType: DataType::TIMESTAMP,
        length: null,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: true,
        auto_increment: false,
        default: null,
        values: null,
        comment: 'Timestamp of email verification',
    )]
    public ?string $email_verified_at;

    /** @see $password */
    public const string password = 'password';
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
        comment: 'Hashed password',
    )]
    public string $password;

    /** @see $created_at */
    public const string created_at = 'created_at';
    #[Column(
        DataType: DataType::TIMESTAMP,
        length: null,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: Column::CURRENT_TIMESTAMP,
        values: null,
        comment: 'Record creation time',
    )]
    public string $created_at;

    /** @see $updated_at */
    public const string updated_at = 'updated_at';
    #[Column(
        DataType: DataType::TIMESTAMP,
        length: null,
        precision: null,
        scale: null,
        unsigned: false,
        nullable: false,
        auto_increment: false,
        default: Column::CURRENT_TIMESTAMP,
        values: null,
        comment: 'Record last update time',
    )]
    public string $updated_at;
}
