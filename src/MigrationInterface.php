<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\Infrastructure;

/**
 * Contract for all migration classes in migrations/.
 *
 * Both up() and down() receive the Database wrapper directly.
 * DDL statements (CREATE TABLE, ALTER TABLE) cause MySQL to auto-commit,
 * so they cannot be rolled back if a migration fails mid-way. Write
 * down() defensively (e.g. DROP TABLE IF EXISTS) to handle partial states.
 */
#[Infrastructure]
interface MigrationInterface
{
    public function up(Database $Database): void;

    public function down(Database $Database): void;
}
