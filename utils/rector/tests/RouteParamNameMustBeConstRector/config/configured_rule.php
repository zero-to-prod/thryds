<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RouteParamNameMustBeConstRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RouteParamNameMustBeConstRector::class, [
        'classSuffix' => 'Route',
        'constName' => 'pattern',
    ]);
};
