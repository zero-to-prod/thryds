<?php

declare(strict_types=1);

use ZeroToProd\Thryds\Log;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Utils\Rector\Rector\ForbiddenFuncCallRector;
use Utils\Rector\Rector\FrankenPhpLogToLogClassRector;
use Utils\Rector\Rector\AddNamedArgWhenVarMismatchesParamRector;
use Utils\Rector\Rector\RenameParamToMatchTypeNameRector;
use Utils\Rector\Rector\RenameVarToMatchReturnTypeRector;

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
    $rectorConfig->rule(RenameParamToMatchTypeNameRector::class);
    $rectorConfig->rule(RenameVarToMatchReturnTypeRector::class);
    $rectorConfig->rule(AddNamedArgWhenVarMismatchesParamRector::class);
    $rectorConfig->rule(RemoveUnusedPrivateMethodParameterRector::class);
    $rectorConfig->rule(RemoveUnusedPublicMethodParameterRector::class);
};
