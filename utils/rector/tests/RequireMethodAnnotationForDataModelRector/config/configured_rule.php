<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireMethodAnnotationForDataModelRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireMethodAnnotationForDataModelRector::class, [
        'dataModelTraits' => ['Zerotoprod\DataModel\DataModel'],
    ]);
};
