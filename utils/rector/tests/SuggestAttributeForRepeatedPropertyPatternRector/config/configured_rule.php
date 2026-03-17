<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\SuggestAttributeForRepeatedPropertyPatternRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();
    $rectorConfig->ruleWithConfiguration(SuggestAttributeForRepeatedPropertyPatternRector::class, [
        'patterns' => [
            [
                'trait' => 'Utils\\Rector\\Tests\\SuggestAttributeForRepeatedPropertyPatternRector\\TestDataModel',
                'constant' => 'view_key',
                'attribute' => 'Utils\\Rector\\Tests\\SuggestAttributeForRepeatedPropertyPatternRector\\TestViewModel',
            ],
        ],
        'mode' => 'auto',
    ]);
};
