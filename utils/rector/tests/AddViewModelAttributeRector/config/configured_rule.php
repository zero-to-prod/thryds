<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\AddViewModelAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();
    $rectorConfig->ruleWithConfiguration(AddViewModelAttributeRector::class, [
        'namespace' => 'ZeroToProd\\Thryds\\ViewModels',
        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\ViewModel',
        'mode' => 'auto',
    ]);
};
