<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidHardcodedRouteStringRector;

require_once __DIR__ . '/../Support/TestRoute.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidHardcodedRouteStringRector::class, [
        'enumClass' => 'Utils\\Rector\\Tests\\ForbidHardcodedRouteStringRector\\TestRoute',
        'mode' => 'warn',
        'message' => "TODO: [ForbidHardcodedRouteStringRector] Use Route::%s->value instead of hardcoded '%s'.",
    ]);
};
