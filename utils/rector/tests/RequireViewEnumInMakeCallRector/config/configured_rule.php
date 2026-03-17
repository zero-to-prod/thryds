<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireViewEnumInMakeCallRector;

require_once __DIR__ . '/../Support/TestView.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireViewEnumInMakeCallRector::class, [
        'enumClass' => 'Utils\\Rector\\Tests\\RequireViewEnumInMakeCallRector\\TestView',
        'methodName' => 'make',
        'paramName' => 'view',
        'mode' => 'auto',
    ]);
};
