<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\Charset;
use ZeroToProd\Thryds\Schema\Collation;
use ZeroToProd\Thryds\Schema\Engine;
use ZeroToProd\Thryds\Tables\TableName;

/**
 * Declares a backed enum as a database table definition.
 *
 * Each enum case represents one column. The case name is the PHP identifier;
 * the backed string value is the SQL column name used in queries and DDL.
 *
 * Place this on the enum class. Column-level details go on each case via #[Column].
 * Indexes go on the enum class via #[Index] (repeatable).
 * The primary key goes on the relevant case via #[PrimaryKey].
 *
 * @example
 * #[Table(name: 'users')]
 * #[Index(columns: ['email'], unique: true)]
 * enum UserTable: string
 * {
 *     #[Column(type: DataType::BigInt, unsigned: true, autoIncrement: true)]
 *     #[PrimaryKey]
 *     case id = 'id';
 *
 *     #[Column(type: DataType::VarChar, length: 255)]
 *     case email = 'email';
 * }
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Table
{
    public function __construct(
        public TableName $TableName,
        public Engine $Engine,
        public Charset $Charset,
        public Collation $Collation,
    ) {}
}
