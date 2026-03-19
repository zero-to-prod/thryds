<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireDownMigrationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireDownMigrationRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback. See: utils/rector/docs/RequireDownMigrationRector.md',
    ]);
};
