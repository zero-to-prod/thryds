<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidHttpMethodBranchingInControllerRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidHttpMethodBranchingInControllerRector::class, [
        'mode' => 'warn',
    ]);
};
