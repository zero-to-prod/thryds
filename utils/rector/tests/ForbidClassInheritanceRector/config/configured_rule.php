<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidClassInheritanceRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidClassInheritanceRector::class, [
        'mode' => 'warn',
        'allowList' => [
            'Test\ForbidClassInheritanceRector\AllowedBaseClass',
        ],
    ]);
};
