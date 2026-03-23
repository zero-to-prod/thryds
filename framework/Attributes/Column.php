<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use ZeroToProd\Framework\Schema\DataType;

/**
 * Declares the SQL definition of a table enum case (column).
 *
 * The enum case name is the PHP identifier; the backed string value is the SQL column name.
 * All column-level constraints and metadata are expressed here via constructor parameters.
 *
 * $default behavior:
 *   - null (omitted) → no DEFAULT clause in DDL
 *   - The current-timestamp sentinel → DEFAULT CURRENT_TIMESTAMP
 *   - Any other scalar → DEFAULT '<value>' (quoted string or raw numeric)
 *
 * $values are required when $DataType is ENUM or SET.
 * $length is required for VARCHAR and CHAR.
 * $precision and $scale are required for DECIMAL.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[HopWeight(0)]
readonly class Column
{
    /** Use as $default to generate DEFAULT CURRENT_TIMESTAMP in DDL. */
    public const string CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /**
     * @param DataType                    $DataType       SQL data type.
     * @param int|null                    $length         For VARCHAR, CHAR, BINARY, VARBINARY.
     * @param int|null                    $precision      For DECIMAL: total digits.
     * @param int|null                    $scale          For DECIMAL: digits after a decimal point.
     * @param bool                        $unsigned       Applies to integer and numeric types.
     * @param bool                        $nullable       If true, column allows NULL.
     * @param bool                        $auto_increment If true, the column is AUTO_INCREMENT.
     * @param string|int|float|bool|null  $default        Default value, the current-timestamp sentinel, or null (no default).
     * @param string[]|null               $values         Required for ENUM and SET types: the allowed values.
     * @param string                      $comment        Stored as the MySQL column COMMENT and serves as the canonical human-readable description of the column. Replaces the need for a separate docblock on the enum case.
     */
    public function __construct(
        public DataType $DataType,
        public ?int $length,
        public ?int $precision,
        public ?int $scale,
        public bool $unsigned,
        public bool $nullable,
        public bool $auto_increment,
        public string|int|float|bool|null $default,
        public ?array $values,
        public string $comment,
    ) {}
}
