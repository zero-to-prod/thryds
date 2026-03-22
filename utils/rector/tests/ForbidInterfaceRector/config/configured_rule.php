<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidInterfaceRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidInterfaceRector::class, [
        'mode' => 'warn',
        'allowList' => [
            'Test\ForbidInterfaceRector\AllowedInterface',
        ],
    ]);
};
