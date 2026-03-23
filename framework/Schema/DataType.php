<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Schema;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\RequiresLength;
use ZeroToProd\Framework\Attributes\RequiresPrecisionScale;
use ZeroToProd\Framework\Attributes\RequiresValues;
use ZeroToProd\Framework\Attributes\SupportsAutoIncrement;
use ZeroToProd\Framework\Attributes\SupportsUnsigned;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::sql_data_types,
    addCase: <<<TEXT
    1. Add enum case.
    2. Handle DDL generation in any schema-to-SQL tooling.
    TEXT
)]
/**
 * Closed set of supported SQL column data types.
 *
 * The backed string value is the SQL keyword used in DDL statements.
 */
enum DataType: string
{
    // Integer types
    #[SupportsUnsigned]
    #[SupportsAutoIncrement]
    case BIGINT   = 'BIGINT';
    #[SupportsUnsigned]
    #[SupportsAutoIncrement]
    case INT      = 'INT';
    #[SupportsUnsigned]
    #[SupportsAutoIncrement]
    case SMALLINT = 'SMALLINT';
    #[SupportsUnsigned]
    #[SupportsAutoIncrement]
    case TINYINT  = 'TINYINT';

    // String types
    #[RequiresLength]
    case VARCHAR    = 'VARCHAR';
    #[RequiresLength]
    case CHAR       = 'CHAR';
    case TEXT       = 'TEXT';
    case MEDIUMTEXT = 'MEDIUMTEXT';
    case LONGTEXT   = 'LONGTEXT';

    // Date/time types
    case DATETIME  = 'DATETIME';
    case DATE      = 'DATE';
    case TIME      = 'TIME';
    case TIMESTAMP = 'TIMESTAMP';
    case YEAR      = 'YEAR';

    // Numeric types
    #[SupportsUnsigned]
    #[RequiresPrecisionScale]
    case DECIMAL = 'DECIMAL';
    #[SupportsUnsigned]
    case FLOAT   = 'FLOAT';
    #[SupportsUnsigned]
    case DOUBLE  = 'DOUBLE';

    // Boolean (MySQL uses TINYINT(1))
    case BOOLEAN = 'BOOLEAN';

    // Document/structured
    case JSON = 'JSON';

    // Set-constrained string types
    #[RequiresValues]
    case ENUM = 'ENUM';
    #[RequiresValues]
    case SET  = 'SET';

    // Binary types
    #[RequiresLength]
    case BINARY    = 'BINARY';
    #[RequiresLength]
    case VARBINARY = 'VARBINARY';
    case BLOB      = 'BLOB';
    case MEDIUMBLOB = 'MEDIUMBLOB';
    case LONGBLOB  = 'LONGBLOB';
}
