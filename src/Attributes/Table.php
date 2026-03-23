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
 * Place this on the enum class. Column-level details go on each case via the column definition attribute.
 * Indexes go on the enum class via the index attribute (repeatable).
 * The primary key goes on the relevant case via the primary key attribute.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class Table
{
    /**
     * Checklist for adding a new model — read by the inventory script to generate extension_guides.
     */
    public const string addCase
        = "1. Add entry to thryds.yaml tables section.\n"
        . "2. Run ./run sync:manifest.\n"
        . "3. Write a migration with #[CreateTable(TableClass::class)].\n"
        . '4. Run ./run fix:all.';

    public function __construct(
        public TableName $TableName,
        public ?Engine $Engine,
        public ?Charset $Charset,
        public ?Collation $Collation,
    ) {}
}
