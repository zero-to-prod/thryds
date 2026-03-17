<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireRouteTestRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireRouteTestRector::class, [
        'enumClass' => 'TestRoute',
        'testDir' => __DIR__ . '/../Source',
        'mode' => 'warn',
        'message' => "TODO: [RequireRouteTestRector] Route case '%s' has no corresponding test. Add a test that exercises this route.",
    ]);
};
