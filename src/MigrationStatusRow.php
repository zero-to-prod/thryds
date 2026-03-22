<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;
use ZeroToProd\Thryds\Attributes\Infrastructure;

/**
 * Represents one row in the migration status report.
 *
 * Combines schema columns from Migration with the computed status field,
 * making the full result shape visible in the attribute graph.
 *
 * @method static self from(array{id: string, description: string, MigrationStatus: MigrationStatus|string, applied_at?: ?string, checksum: string} $data)
 */
#[Infrastructure]
readonly class MigrationStatusRow
{
    use DataModel;

    /** @see $id */
    public const string id = 'id';
    /** @see $description */
    public const string description = 'description';
    /** @see $MigrationStatus */
    public const string MigrationStatus = 'MigrationStatus';
    /** @see $applied_at */
    public const string applied_at = 'applied_at';
    /** @see $checksum */
    public const string checksum = 'checksum';

    public string $id;

    public string $description;

    public MigrationStatus $MigrationStatus;

    #[Describe([// TODO: [ForbidMagicStringArrayKeyRector] Constants name things — define a public const with value 'nullable' on the appropriate class.
        'nullable' => true])]
    public ?string $applied_at;

    public string $checksum;
}
