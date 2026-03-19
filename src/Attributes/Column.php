<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\DataType;

/**
 * Declares the SQL definition of a table enum case (column).
 *
 * The enum case name is the PHP identifier; the backed string value is the SQL column name.
 * All column-level constraints and metadata are expressed here via constructor parameters.
 *
 * $default behaviour:
 *   - null (omitted)           → no DEFAULT clause in DDL
 *   - Column::CURRENT_TIMESTAMP → DEFAULT CURRENT_TIMESTAMP
 *   - any other scalar         → DEFAULT '<value>' (quoted string or raw numeric)
 *
 * $values is required when $DataType is DataType::ENUM or DataType::SET.
 * $length is required for DataType::VARCHAR and DataType::CHAR.
 * $precision and $scale are required for DataType::DECIMAL.
 *
 * @example
 * #[Column(DataType: DataType::BIGINT, unsigned: true, auto_increment: true)]
 * #[PrimaryKey]
 * case id = 'id';
 *
 * #[Column(DataType: DataType::VARCHAR, length: 255)]
 * case email = 'email';
 *
 * #[Column(DataType: DataType::ENUM, values: ['active', 'suspended'], default: 'active')]
 * case status = 'status';
 *
 * #[Column(DataType: DataType::DATETIME, default: Column::CURRENT_TIMESTAMP)]
 * case created_at = 'created_at';
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Column
{
    /** Use as $default to generate DEFAULT CURRENT_TIMESTAMP in DDL. */
    public const string CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /**
     * @param DataType                    $DataType       SQL data type.
     * @param int|null                    $length         For VARCHAR, CHAR, BINARY, VARBINARY.
     * @param int|null                    $precision      For DECIMAL: total digits.
     * @param int|null                    $scale          For DECIMAL: digits after decimal point.
     * @param bool                        $unsigned       Applies to integer and numeric types.
     * @param bool                        $nullable       If true, column allows NULL.
     * @param bool                        $auto_increment If true, column is AUTO_INCREMENT.
     * @param string|int|float|bool|null  $default        Default value, Column::CURRENT_TIMESTAMP, or null (no default).
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
