<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\UseLogContextConstRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
        'logClass' => 'Fixture\Log',
        'keys' => ['exception', 'file', 'line'],
    ]);
};
