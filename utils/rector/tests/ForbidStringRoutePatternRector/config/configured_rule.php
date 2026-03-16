<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidStringRoutePatternRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidStringRoutePatternRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
    ]);
};
