<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidMagicHttpStatusCodeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidMagicHttpStatusCodeRector::class, [
        'mode' => 'warn',
    ]);
};
