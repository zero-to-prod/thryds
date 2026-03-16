<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireNamedArgForBoolParamRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireNamedArgForBoolParamRector::class, [
        'skipBuiltinFunctions' => false,
        'skipWhenOnlyArg' => true,
    ]);
};
