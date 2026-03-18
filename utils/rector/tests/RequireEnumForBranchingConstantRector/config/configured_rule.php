<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireEnumForBranchingConstantRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireEnumForBranchingConstantRector::class, [
        'mode' => 'warn',
    ]);
};
