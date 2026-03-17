<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireNamesKeysOnConstantsClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireNamesKeysOnConstantsClassRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireNamesKeysOnConstantsClassRector\\TestNamesKeys',
        'excludedAttributes' => [
            'Utils\\Rector\\Tests\\RequireNamesKeysOnConstantsClassRector\\TestViewModel',
        ],
        'mode' => 'warn',
    ]);
};
