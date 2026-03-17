<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireLimitsChoicesOnBackedEnumRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireLimitsChoicesOnBackedEnumRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\RequireLimitsChoicesOnBackedEnumRector\\TestLimitsChoices',
        'mode' => 'warn',
    ]);
};
