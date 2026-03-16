<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireLogEventRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireLogEventRector::class, [
        'logClass' => 'Fixture\Log',
        'eventKey' => 'event',
    ]);
};
