<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireViewModelAttributeOnDataModelRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireViewModelAttributeOnDataModelRector::class, [
        'traitClasses' => [
            'Utils\\Rector\\Tests\\RequireViewModelAttributeOnDataModelRector\\TestDataModel',
        ],
        'constantName' => 'view_key',
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireViewModelAttributeOnDataModelRector\\TestViewModel',
        'mode' => 'auto',
    ]);
};
