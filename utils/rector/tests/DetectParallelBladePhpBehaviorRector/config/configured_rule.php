<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\DetectParallelBladePhpBehaviorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(DetectParallelBladePhpBehaviorRector::class, [
        'mode' => 'warn',
    ]);
};
