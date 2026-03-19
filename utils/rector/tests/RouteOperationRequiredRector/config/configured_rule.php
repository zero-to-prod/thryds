<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RouteOperationRequiredRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RouteOperationRequiredRector::class, [
        'enumClass'      => 'TestRoute',
        'attributeClass' => 'RouteOperation',
        'mode'           => 'warn',
        'message'        => "TODO: [RouteOperationRequiredRector] Route case '%s' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route.",
    ]);
};
