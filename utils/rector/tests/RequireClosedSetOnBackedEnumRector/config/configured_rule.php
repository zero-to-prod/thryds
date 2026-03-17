<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireClosedSetOnBackedEnumRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireClosedSetOnBackedEnumRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireClosedSetOnBackedEnumRector\\TestClosedSet',
        'mode' => 'warn',
    ]);
};
