<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\DetectStaleCodeReferencesRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(DetectStaleCodeReferencesRector::class, [
        'mode' => 'warn',
    ]);
};
