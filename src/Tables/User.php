<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

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
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::database_table_columns,
    addCase: <<<TEXT
    1. Add enum case with #[Column] attribute.
    2. Write a migration to ALTER TABLE users ADD COLUMN ...
    TEXT
)]
#[Connection(database: Database::class)]
#[SchemaSync(SchemaSource::attributes)]
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
readonly class User
{
    use DataModel;
    use HasTableName;
    use UserColumns;
}
