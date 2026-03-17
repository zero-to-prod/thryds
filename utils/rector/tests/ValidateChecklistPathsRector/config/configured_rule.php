<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ValidateChecklistPathsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ValidateChecklistPathsRector::class, [
        'attributes' => [
            ['attributeClass' => 'Utils\\Rector\\Tests\\ValidateChecklistPathsRector\\TestSourceOfTruth', 'paramName' => 'addCase'],
        ],
        'projectDir' => __DIR__ . '/..',
        'mode' => 'warn',
    ]);
};
