<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\Describe;
use ZeroToProd\Thryds\Attributes\PrimaryKey;
use ZeroToProd\Thryds\Attributes\StubValue;
use ZeroToProd\Thryds\Schema\DataType;

trait UserColumns
{
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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $id;

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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $name;

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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $handle;

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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $email;

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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $email_verified_at;

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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $password;

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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $created_at;

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
    #[Describe(['nullable' => true])]
    #[StubValue('')]
    public readonly ?string $updated_at;
}
