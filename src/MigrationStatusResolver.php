<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Queries\SelectMigrationsQuery;
use ZeroToProd\Thryds\Tables\Migration;

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
     * Each row: {Migration::id, Migration::description, Migrator::col_status, Migration::applied_at, Migration::checksum}
     *
     * @return array<int, array<string, mixed>>
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
                $result[] = [
                    Migration::id          => $id,
                    Migration::description => $this->MigrationDiscovery->description($id),
                    Migrator::col_status         => $checksum === $applied[$id][Migration::checksum] ? MigrationStatus::applied : MigrationStatus::modified,
                    Migration::applied_at  => $applied[$id][Migration::applied_at],
                    Migration::checksum    => $checksum,
                ];
            } else {
                $result[] = [
                    Migration::id          => $id,
                    Migration::description => $this->MigrationDiscovery->description($id),
                    Migrator::col_status         => MigrationStatus::pending,
                    Migration::applied_at  => null,
                    Migration::checksum    => $checksum,
                ];
            }
        }

        return $result;
    }
}
