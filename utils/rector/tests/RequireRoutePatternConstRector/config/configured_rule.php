<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireRoutePatternConstRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireRoutePatternConstRector::class, [
        'classSuffix' => 'Route',
        'constName' => 'pattern',
        'excludedClasses' => ['WebRoutes'],
    ]);
};
