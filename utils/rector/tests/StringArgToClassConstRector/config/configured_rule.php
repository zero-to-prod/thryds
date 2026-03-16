<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\StringArgToClassConstRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(StringArgToClassConstRector::class, [
        [
            'class' => 'App\View',
            'methodName' => 'make',
            'paramName' => 'view',
        ],
    ]);
};
