<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\FrankenPhpLogToLogClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(FrankenPhpLogToLogClassRector::class, [
        'functions' => ['frankenphp_log', 'error_log'],
        'logClass' => 'ZeroToProd\\Thryds\\Log',
    ]);
};
