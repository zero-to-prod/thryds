<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidReflectionInInstanceMethodRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidReflectionInInstanceMethodRector::class, [
        'mode' => 'warn',
    ]);
};
