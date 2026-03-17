<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireNamesKeysOnMixedConstantsClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireNamesKeysOnMixedConstantsClassRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireNamesKeysOnMixedConstantsClassRector\\TestNamesKeys',
        'minConstants' => 3,
        'excludedTraits' => [
            'Utils\\Rector\\Tests\\RequireNamesKeysOnMixedConstantsClassRector\\TestDataModel',
        ],
        'mode' => 'warn',
    ]);
};
