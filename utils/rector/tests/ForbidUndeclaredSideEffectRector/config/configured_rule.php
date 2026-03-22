<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidUndeclaredSideEffectRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidUndeclaredSideEffectRector::class, [
        'mode' => 'warn',
    ]);
};
