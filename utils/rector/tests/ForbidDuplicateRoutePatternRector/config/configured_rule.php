<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidDuplicateRoutePatternRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRoutePatternRector::class, [
        'classSuffix' => 'Route',
        'constNames' => ['pattern'],
    ]);
};
