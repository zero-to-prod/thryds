<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\MigrateAddCaseListToHeredocRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(MigrateAddCaseListToHeredocRector::class, [
        'mode' => 'auto',
    ]);
};
