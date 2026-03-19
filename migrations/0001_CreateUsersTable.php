<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Thryds\Attributes\Migration;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\MigrationInterface;

#[Migration(
    id: '0001',
    description: 'Create Users Table'
)]
final readonly class CreateUsersTable implements MigrationInterface
{
    public function up(Database $Database): void
    {
        $Database->execute(
            "CREATE TABLE IF NOT EXISTS `users` (
                id                CHAR(26)          NOT NULL COMMENT 'Primary key',
                name              VARCHAR(255)       NOT NULL COMMENT 'Display name',
                handle            VARCHAR(30)        NOT NULL COMMENT 'Unique public username',
                email             VARCHAR(255)       NULL     COMMENT 'Contact email address',
                email_verified_at TIMESTAMP          NULL     COMMENT 'Timestamp of email verification',
                password          VARCHAR(255)       NOT NULL COMMENT 'Hashed password',
                created_at        TIMESTAMP          NOT NULL DEFAULT CURRENT_TIMESTAMP                    COMMENT 'Record creation time',
                updated_at        TIMESTAMP          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update time',
                PRIMARY KEY (id)
            ) COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(Database $Database): void
    {
        $Database->execute('DROP TABLE IF EXISTS `users`');
    }
}