<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidDirectRouterInstantiationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidDirectRouterInstantiationRector::class, [
        'forbiddenClasses' => ['League\\Route\\Router'],
    ]);
};
