<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireAllRouteCasesRegisteredRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireAllRouteCasesRegisteredRector::class, [
        'enumClass' => 'TestRoute',
        'methods' => ['map'],
        'argPosition' => 1,
        'scanDir' => __DIR__ . '/../DynamicSource',
        'mode' => 'warn',
        'message' => "TODO: [RequireAllRouteCasesRegisteredRector] Route case '%s' is defined but never registered in any router map() call.",
    ]);
};
