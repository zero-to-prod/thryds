<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RouteOperationRequiresRouteInfoRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RouteOperationRequiresRouteInfoRector::class, [
        'enumClass'              => 'TestRoute',
        'triggerAttributeClass'  => 'RouteOperation',
        'requiredAttributeClass' => 'RouteInfo',
        'mode'                   => 'warn',
        'message'                => "TODO: [RouteOperationRequiresRouteInfoRector] Route case '%s' declares #[RouteOperation] but is missing #[RouteInfo]. Both attributes are required together.",
    ]);
};
