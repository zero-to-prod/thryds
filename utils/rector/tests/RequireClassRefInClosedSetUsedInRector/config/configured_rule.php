<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireClassRefInClosedSetUsedInRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireClassRefInClosedSetUsedInRector::class, [
        'attributes' => [
            ['attributeClass' => 'Utils\\Rector\\Tests\\RequireClassRefInClosedSetUsedInRector\\TestClosedSet', 'paramName' => 'used_in'],
        ],
        'mode' => 'warn',
    ]);
};
