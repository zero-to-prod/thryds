<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidStringArgForEnumParamRector;

require_once __DIR__ . '/../Support/TestAppEnv.php';
require_once __DIR__ . '/../Support/TestHTTPMethod.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidStringArgForEnumParamRector::class, [
        'enumClasses' => [
            'Utils\\Rector\\Tests\\ForbidStringArgForEnumParamRector\\TestAppEnv',
            'Utils\\Rector\\Tests\\ForbidStringArgForEnumParamRector\\TestHTTPMethod',
        ],
        'mode' => 'warn',
        'message' => "TODO: [ForbidStringArgForEnumParamRector] '%s' matches %s::%s — use %s::%s->value.",
    ]);
};
