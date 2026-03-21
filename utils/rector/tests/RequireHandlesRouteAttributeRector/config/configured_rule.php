<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireHandlesRouteAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireHandlesRouteAttributeRector::class, [
        'mode' => 'warn',
    ]);
};
