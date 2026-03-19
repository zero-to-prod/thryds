<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\AddViewKeyConstantRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(AddViewKeyConstantRector::class, [
        'dataModelTraits' => ['ZeroToProd\\Thryds\\Attributes\\DataModel'],
        'viewModelAttribute' => 'ZeroToProd\\Thryds\\Attributes\\ViewModel',
        'mode' => 'auto',
    ]);
};
