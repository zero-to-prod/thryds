<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidStringComparisonOnEnumPropertyRector;

require_once __DIR__ . '/../Support/TestAppEnv.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidStringComparisonOnEnumPropertyRector::class, [
        'enumClasses' => [
            'Utils\\Rector\\Tests\\ForbidStringComparisonOnEnumPropertyRector\\TestAppEnv',
        ],
        'mode' => 'auto',
        'message' => "TODO: [ForbidStringComparisonOnEnumPropertyRector] Compare against %s::%s instead of string '%s'.",
    ]);
};
