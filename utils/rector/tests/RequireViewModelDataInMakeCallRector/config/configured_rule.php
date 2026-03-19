<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireViewModelDataInMakeCallRector;

require_once __DIR__ . '/../Support/TestView.php';
require_once __DIR__ . '/../Support/ViewModels/RegisterViewModel.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireViewModelDataInMakeCallRector::class, [
        'viewEnumClass'       => 'Utils\\Rector\\Tests\\RequireViewModelDataInMakeCallRector\\Support\\TestView',
        'viewModelsNamespace' => 'Utils\\Rector\\Tests\\RequireViewModelDataInMakeCallRector\\Support\\ViewModels',
        'methodName'          => 'make',
        'viewParamName'       => 'view',
        'dataParamName'       => 'data',
        'mode'                => 'warn',
        'message'             => "TODO: [RequireViewModelDataInMakeCallRector] make() renders '%s' which has a %s — add data: argument.",
    ]);
};
