<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidCallableTypeVariableNameRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidCallableTypeVariableNameRector::class, [
        'forbiddenNames' => [
            'Closure',
            'Callable',
            'Callback',
            'Function',
            'Func',
        ],
    ]);
};
