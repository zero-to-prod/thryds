<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Connection;
use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\HasTableName;
use ZeroToProd\Thryds\Attributes\SchemaSync;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\Schema\Charset;
use ZeroToProd\Thryds\Schema\Collation;
use ZeroToProd\Thryds\Schema\Engine;
use ZeroToProd\Thryds\Schema\SchemaSource;
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
