<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RouteInfoRequiredRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RouteInfoRequiredRector::class, [
        'enumClass' => 'TestRoute',
        'attributeClass' => 'RouteInfo',
        'mode' => 'warn',
        'message' => "TODO: [RouteInfoRequiredRector] Route case '%s' must declare #[RouteInfo] so the inventory graph can emit a description for this route.",
    ]);
};
