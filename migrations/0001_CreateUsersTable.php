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
            'CREATE TABLE IF NOT EXISTS `users` (
                id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                email      VARCHAR(255)     NOT NULL,
                created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function down(Database $Database): void
    {
        $Database->execute('DROP TABLE IF EXISTS `users`');
    }
}