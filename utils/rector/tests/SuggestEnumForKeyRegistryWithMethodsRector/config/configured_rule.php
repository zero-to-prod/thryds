<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\SuggestEnumForKeyRegistryWithMethodsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(SuggestEnumForKeyRegistryWithMethodsRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\SuggestEnumForKeyRegistryWithMethodsRector\\TestKeyRegistry',
        'mode' => 'warn',
    ]);
};
