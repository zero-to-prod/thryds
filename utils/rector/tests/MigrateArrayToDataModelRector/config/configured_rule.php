<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\MigrateArrayToDataModelRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(MigrateArrayToDataModelRector::class, [
        [
            'methodName' => 'make',
            'dataParam' => 'data',
            'viewParam' => 'view',
            'viewModelNamespace' => 'Fixture\\ViewModels',
            'viewModelDir' => __DIR__ . '/non_existent_dir',
            'templateDir' => __DIR__ . '/non_existent_dir',
            'dataModelTrait' => 'Zerotoprod\\DataModel\\DataModel',
        ],
    ]);
};
