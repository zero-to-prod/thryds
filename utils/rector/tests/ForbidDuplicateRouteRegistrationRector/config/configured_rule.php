<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidDuplicateRouteRegistrationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRouteRegistrationRector::class, [
        'methods' => ['map'],
        'methodArgPosition' => 0,
        'routeArgPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: '%s %s' was already registered above.",
    ]);
};
