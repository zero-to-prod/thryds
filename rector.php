<?php

declare(strict_types=1);

use ZeroToProd\Thryds\Log;
use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbiddenFuncCallRector;
use Utils\Rector\Rector\FrankenPhpLogToLogClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/public',
    ]);

    $rectorConfig->ruleWithConfiguration(ForbiddenFuncCallRector::class, [
        'error_log',
    ]);

    $rectorConfig->ruleWithConfiguration(FrankenPhpLogToLogClassRector::class, [
        'functions' => ['frankenphp_log'],
        'logClass' => Log::class,
    ]);
};
