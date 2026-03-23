<?php

declare(strict_types=1);

namespace ZeroToProd\Framework;

use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Queries\SelectMigrationsQuery;
use ZeroToProd\Framework\Tables\Migration;

/**
 * Computes per-migration status by comparing discovered files against the applied database state.
 *
 * Each row in the result contains the migration id, description, status (applied/pending/modified),
 * applied_at timestamp, and current file checksum.
 */
#[Infrastructure]
readonly class MigrationStatusResolver
{
    use RowAccess;

    public function __construct(
        private MigrationDiscovery $MigrationDiscovery,
        private Database $Database,
    ) {}

    /**
     * Returns one row per migration file, ordered by id.
     *
     * @return list<MigrationStatusRow>
     */
    public function status(): array
    {
        $applied = [];
        foreach (SelectMigrationsQuery::allRows($this->Database) as $row) {
            $applied[$this->rowStr($row, key: Migration::id)] = $row;
        }

        $result = [];
        foreach ($this->MigrationDiscovery->ids() as $id) {
            $checksum = $this->MigrationDiscovery->checksum($id);
            if (isset($applied[$id])) {
                $applied_row = $applied[$id];
                $result[] = MigrationStatusRow::from([
                    MigrationStatusRow::id              => $id,
                    MigrationStatusRow::description     => $this->MigrationDiscovery->description($id),
                    MigrationStatusRow::MigrationStatus => $checksum === $applied_row[Migration::checksum] ? MigrationStatus::applied : MigrationStatus::modified,
                    MigrationStatusRow::applied_at      => is_string($applied_row[Migration::applied_at]) ? $applied_row[Migration::applied_at] : null,
                    MigrationStatusRow::checksum        => $checksum,
                ]);
            } else {
                $result[] = MigrationStatusRow::from([
                    MigrationStatusRow::id              => $id,
                    MigrationStatusRow::description     => $this->MigrationDiscovery->description($id),
                    MigrationStatusRow::MigrationStatus => MigrationStatus::pending,
                    MigrationStatusRow::applied_at      => null,
                    MigrationStatusRow::checksum        => $checksum,
                ]);
            }
        }

        return $result;
    }
}
