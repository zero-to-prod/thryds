<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidLongClosureRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidLongClosureRector::class, [
        'maxStatements' => 3,
        'skipArrowFunctions' => true,
    ]);
};
