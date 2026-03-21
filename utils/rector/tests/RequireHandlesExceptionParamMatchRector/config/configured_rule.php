<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireHandlesExceptionParamMatchRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireHandlesExceptionParamMatchRector::class, [
        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\HandlesException',
        'mode' => 'auto',
    ]);
};
