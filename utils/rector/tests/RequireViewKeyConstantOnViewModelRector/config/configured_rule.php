<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireViewKeyConstantOnViewModelRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireViewKeyConstantOnViewModelRector::class, [
        'viewModelAttribute' => 'ZeroToProd\\Thryds\\Attributes\\ViewModel',
        'mode' => 'warn',
        'message' => 'TODO: [RequireViewKeyConstantOnViewModelRector] %s is missing `public const string view_key`. Required for graph inventory.',
    ]);
};
