<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\InlineSingleUseVariableRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(InlineSingleUseVariableRector::class, [
        'mode' => 'auto',
    ]);
};
