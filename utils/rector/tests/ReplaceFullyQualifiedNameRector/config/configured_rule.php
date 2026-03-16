<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ReplaceFullyQualifiedNameRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ReplaceFullyQualifiedNameRector::class, [
        'Zerotoprod\DataModel\DataModel' => 'ZeroToProd\Thryds\Helpers\DataModel',
        'Zerotoprod\DataModel\Describe' => 'ZeroToProd\Thryds\Helpers\Describe',
    ]);
};
