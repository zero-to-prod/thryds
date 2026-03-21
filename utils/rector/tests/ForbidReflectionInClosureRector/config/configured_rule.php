<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidReflectionInClosureRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidReflectionInClosureRector::class, [
        'mode' => 'warn',
    ]);
};
