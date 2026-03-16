<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ReplaceShortClassNameWithViewKeyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ReplaceShortClassNameWithViewKeyRector::class, [
        'shortClassNameFunction' => 'ZeroToProd\\Thryds\\Helpers\\short_class_name',
        'mode' => 'auto',
    ]);
};
