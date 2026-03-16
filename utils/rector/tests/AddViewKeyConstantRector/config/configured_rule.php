<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\AddViewKeyConstantRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(AddViewKeyConstantRector::class, [
        'dataModelTraits' => ['ZeroToProd\\Thryds\\Helpers\\DataModel'],
        'viewModelAttribute' => 'ZeroToProd\\Thryds\\Helpers\\ViewModel',
        'mode' => 'auto',
    ]);
};
